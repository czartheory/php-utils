<?php

namespace CzarTheory\Doctrine;

use CzarTheory\Utilities\IllegalStateException;
use CzarTheory\Utilities\InvalidOperationException;
use CzarTheory\Utilities\NotImplementedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
//
//use NetformApp\Entities\EntitySuperClass;
//use NetformApp\Interfaces\Representable;

/**
 * Manages entity repositories in an abstracted restful paradigm.
 *
 * @copyright Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author Matthew Larson <matthew@czarTheory.com>
 * @author Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
abstract class AbstractRestfulRepository extends EntityRepository
{
	protected $_localUpdateAttributes;
	protected $_localCreateAttributes;
	protected $_foreignUpdateAttributes;
	protected $_foreignCreateAttributes;
	protected $_repRepository;

	/**
	 * Overriding constructor, to add initialization check and further initialization
	 *
	 * @param EntityManager $em
	 * @param ClassMetadata $class
	 */
	public function __construct(EntityManager $em, ClassMetadata $class)
	{
		parent::__construct($em, $class);
		$this->_localUpdateAttributes = $this->_convertLocalAttributes(static::getLocalAttributes());
		$this->_localCreateAttributes = $this->_convertLocalAttributes(static::getLocalCreateAttributes());
		$this->_foreignUpdateAttributes = $this->_prepForeignAttributes(static::getForeignAttributes());
		$this->_foreignCreateAttributes = $this->_prepForeignAttributes(static::getForeignCreateAttributes());
		$this->_repRepository = null;
	}

	/**
	 * Creates an array of key=>array items, to be used in foreign-attribute updates.
	 * each value will be an array with the follwoing keys:
	 * 		'method' the set-method to use when setting the object
	 * 		'repository' the repository to use to get the foreign entity
	 *
	 * @param array $attributes the initial array to be used
	 * @return array the prepped array
	 */
	private function _prepForeignAttributes(array $attributes)
	{
		$mappings = array();
		foreach ($attributes as $index => $fieldName)
		{
			$functionName = ucFirst(str_replace('_', '', $fieldName));
			$mapping = array('setNew' => 'set' . $functionName);
			if (!isset($this->_class->associationMappings[$fieldName]))
				throw new IllegalStateException(end(explode('\\', $this->_class->name)) . 'Repository::getForeignAttributes contains an invalid entry: foreign attribute \'' . $fieldName . '\' does not exist for class ' . $this->_class->name);

			$associationMapping = $this->_class->associationMappings[$fieldName];
			$targetClass = $mapping['class'] = $associationMapping['targetEntity'];
			if (isset($associationMapping['inversedBy']))
			{
				$mapping['getOld'] = 'get' . $functionName;
				$targetFieldName = $associationMapping['inversedBy'];
				if ($associationMapping['type'] & (ClassMetadataInfo::MANY_TO_ONE | ClassMetadataInfo::MANY_TO_MANY))
				{
					$mapping['isMany'] = true;
					$mapping['removeOld'] = 'get' . ucFirst($targetFieldName);
					$targetMetadata = $this->_em->getClassMetadata($targetClass);
					if (null === $targetMetadata)
						throw new IllegalStateException('Invalid annotation for field \'' . $fieldName . '\' in ' . $this->_class->name . '; targetEntity \'' . $targetClass . '\' does not exist');
					$targetMapping = $targetMetadata->associationMappings[$targetFieldName];
					if (null === $targetMapping)
						throw new IllegalStateException('Invalid annotation for field \'' . $fieldName . '\' in ' . $this->_class->name . '; field \'' . $targetFieldName . '\' does not exist in targetEntity class \'' . $targetClass . '\'');
					$targetEntities = explode('\\', $targetMapping['targetEntity']);
					$targetFunctionName = end($targetEntities);
					$mapping['inverse'] = 'add' . $targetFunctionName;
				}
				else
				{
					$mapping['removeOld'] = $mapping['inverse'] = 'set' . ucFirst($targetFieldName);
				}
			}

			$mappings[$fieldName] = $mapping;
		}

		return $mappings;
	}

	/**
	 * Converts an indexed array to an associative array.
	 *
	 * The input values turn to keys on the output array. the
	 * values to those keys are in this form: set<attribute>, where
	 * <attribute> is the value of the original array. for example,
	 * ['name','age'] as an input will return
	 * {'name'=> 'setName','age' => 'setAge'} as the output.
	 *
	 * @param array $attributes an indexed array of strings, to be used as keys
	 * @return array the new associative array, with setter-method names as values
	 */
	private function _convertLocalAttributes(array $attributes)
	{
		$newArray = array();
		$size = count($attributes);
		for ($i = 0; $i < $size; $i++)
		{
			$key = $attributes[$i];
			$newArray[$key] = 'set' . ucfirst(str_replace('_', '', $key));
		}
		return $newArray;
	}

	/**
	 * Gets the number of items available based upon the criteria given
	 *
	 * @param array $criteria
	 * @return int
	 */
	public function count(array $criteria)
	{
		$qb = $this->_getBaseCountQueryBuilder();
		$this->_addCriteriaToBuilder($qb, 'e', $this->sanitizeQuery($criteria));
		$query = $qb->getQuery();
		$result = $query->getSingleScalarResult();
		return $result;
	}

	/**
	 * Gets a single entity from the database given the identifier and an optional set of criteria.
	 *
	 * @param string|int $id The identifier of the entity instance.
	 * @param array $criteria The criteria for restricting the result set.
	 * @return EntitySuperClass|null The requested Entity instance if found; otherwise null.
	 */
	public function get($identifier, array $criteria = null)
	{
		if($criteria === null) {
			return $this->find($identifier);
		}

		$qb = $this->_getBaseOneQueryBuilder()->setParameter('id', $identifier);
		$this->_addCriteriaToBuilder($qb, 'e', $this->sanitizeQuery($criteria));
		$query = $qb->getQuery();
		$result = $query->getOneOrNullResult();
		return $result;
	}

	/**
	 * Gets a single entity from the database which matches the given criteria
	 *
	 * @param array $criteria The critera for finding the Entity instance.
	 * @return EntitySuperClass|null The requested Entity instance if found; otherwise null.
	 */
	public function getOneBy(array $criteria)
	{
		$qb = $this->_getBaseAllQueryBuilder()->setMaxResults(1);
		$this->_addCriteriaToBuilder($qb, 'e', $criteria);
		$query = $qb->getQuery();
		$result = $query->getOneOrNullResult();
		return $result;
	}
	
	/**
	 * Gets a collection of entities matching the specified enhanced simple doctrine criteria.
	 *
	 * @param array $criteria The selection criteria.
	 * @param array|null $orderBy An array of column names to sort order ('ASC' or 'DESC') mappings.
	 * @param int|null The maximum number of records to return in the result set.
	 * @param int|null The starting offset at which records will be included in the result set.
	 * @return ArrayCollection The matching entities.
	 */
	public function getAll(array $criteria = array(), array $orderBy = array(), $limit = null, $offset = null)
	{
		$qb = $this->_getBaseAllQueryBuilder();

		$this->_addCriteriaToBuilder($qb, 'e', $this->sanitizeQuery($criteria));

		foreach ($orderBy as $sort => $order)
		{
			$qb->orderBy('e.' . $sort, $order);
		}

		if (null !== $offset)
		{
			$qb->setFirstResult($offset);
		}

		if (null !== $limit)
		{
			$qb->setMaxResults($limit);
		}

		return $this->_runQuery($qb);
	}

	/**
	 * @param array $criteria the unsanitized array
	 * @return array the sanitized array
	 */
	private function sanitizeQuery(array $criteria)
	{
		$sanitized = array();
		$mapping = $this->_getQueryMapping();
		foreach($criteria as $key => $value)
		{
			if(array_key_exists($key, $mapping))
			{
				$newKey = $mapping[$key];
				if(isset($newKey)) $sanitized[$newKey] = $value;
			} else {
				$sanitized[$key] = $value;
			}
		}
		return $sanitized;
	}

	/**
	 * @return type array the mapping metadata to sanitize queries
	 */
	protected function _getQueryMapping()
	{
		return array();
	}

	/**
	 * Generic method for creation & persisting of Entities
	 *
	 * This method has specific hooks baked in, so that much
	 * customization in subclasses can be performed, but sensible
	 * defaults are maintained. It merely creates a new Entity,
	 * runs any needed set-methods, then persists to the entitymanager
	 * use _preCreate and _postCreate for custom logic.
	 *
	 * @param array $values the values to be used in creation
	 * @return EntitySuperClass the Entity created and persisted
	 */
	public final function create(array $values)
	{
		$entity = $this->_newEntity();
		$this->_preCreate($entity, $values);
		self::_saveEntity($entity, $values, $this->_localCreateAttributes, $this->_foreignCreateAttributes);
		$this->_postCreate($entity, $values);
		$this->_em->persist($entity);
		return $entity;
	}

	/**
	 * Generic method for updating & persisting of Entities
	 *
	 * This method has specific hooks baked in, so that much
	 * customization in subclasses can be performed, but sensible
	 * defaults are maintained. It merely creates a new Entity,
	 * runs any needed set-methods, then persists to the entitymanager
	 * use _preUpdate and _postUpdate for custom logic.
	 *
	 * @param EntitySuperClass $entity The entity to update.
	 * @param array $values The values to be used in the update.
	 * @return EntitySuperClass The updated entity.
	 */
	public function update($entity, array $values)
	{
		$this->_preUpdate($entity, $values);
		self::_saveEntity($entity, $values, $this->_localUpdateAttributes, $this->_foreignUpdateAttributes);
		$this->_postUpdate($entity, $values);
		$this->_em->persist($entity);
		return $entity;
	}

	/**
	 * Method for deleting entities fom the database
	 *
	 * @param EntitySuperClass $entity the Entity to be removed from the database
	 */
	public function delete($entity)
	{
		$this->_preDelete($entity);
		$this->_em->remove($entity);
		$this->_postDelete($entity);
	}

	/**
	 * Helper method for modifying entities
	 *
	 * @param EntitySuperClass $entity the entity to be modified
	 * @param array $values the values to impose on the entity
	 * @param array $localKeys the local-attribute array
	 * @param array $foreignKeys the foreign-attribute array
	 */
	private function _saveEntity($entity, array $values, array &$localKeys, array &$foreignKeys)
	{
		foreach ($localKeys as $key => $method)
		{
			if (array_key_exists($key, $values))
				$entity->$method($values[$key]);
		}

		foreach ($foreignKeys as $attribute => &$array)
		{
			if (array_key_exists($attribute, $values))
			{
				$newForeign = $values[$attribute];
				if(empty($newForeign)) $newForeign = null;
				if (null !== $newForeign && !is_object($newForeign))
				{
					if (!isset($array['repository'])) $array['repository'] = $this->_em->getRepository($array['class']);
					$repository = $array['repository'];
					if (null !== $newForeign)
					{
						$foreign = $repository->find($newForeign);
						if (null === $foreign){
							throw new \InvalidArgumentException('Foreign-Key item for: ' . $attribute . ':'. $newForeign . ' not found. ');
						}
						$newForeign = $foreign;
					}
				}

				if (isset($array['inverse']))
				{
					$getOld = $array['getOld'];
					$oldForeign = $entity->$getOld();
					if (null !== $oldForeign)
					{
						$removeOld = $array['removeOld'];
						if (isset($array['isMany']))
							$oldForeign->$removeOld()->removeElement($entity);
						else
							$oldForeign->$removeOld(null);
					}
					if (null !== $newForeign)
					{
						$inverse = $array['inverse'];
						$newForeign->$inverse($entity);
					}
				}
				$setNew = $array['setNew'];
				$entity->$setNew($newForeign);
			}
		}
	}

	/**
	 * Constructs a new entity instance based on the doctrine class metadata.
	 * @return EntitySuperClass The new Entity instance
	 */
	protected function _newEntity()
	{
		$class = $this->getClassMetadata()->name;
		return new $class();
	}

	/**
	 * Customization hook for entity creation. to be overidden if needed.
	 *
	 * @param EntitySuperClass $entity the entity about to be created/persisted
	 * @param array $values the values used in creation
	 */
	protected function _preCreate($entity, array &$values)
	{

	}

	/**
	 * Customization hook for entity creation. to be overidden if needed.
	 *
	 * @param EntitySuperClass $entity the entity just created, but about to be persisted
	 * @param array $values the values used in creation
	 */
	protected function _postCreate($entity, array &$values)
	{

	}

	/**
	 * Customization hook for entity update. to be overidden if needed.
	 *
	 * @param EntitySuperClass $entity the entity about to be updated/persisted
	 * @param array $values the values used in the update
	 */
	protected function _preUpdate($entity, array &$values)
	{

	}

	/**
	 * Customization hook for entity update. to be overidden if needed.
	 *
	 * @param EntitySuperClass $entity the entity just updated, but about to be persisted
	 * @param array $values the values used in the update
	 */
	protected function _postUpdate($entity, array &$values)
	{

	}

	/**
	 * Customization hook for entity deletion. to be overidden if needed.
	 * @param EntitySuperClass $entity the entity about to be deleted
	 */
	protected function _preDelete($entity)
	{

	}

	/**
	 * Customization hook for entity update. to be overidden if needed.
	 * @param EntitySuperClass $entity the entity just deleted
	 */
	protected function _postDelete($entity)
	{

	}

	/**
	 * Overridable method for getting list of local update attributes
	 * @return array the Array of keys to use
	 */
	protected static function getLocalAttributes()
	{
		throw new NotImplementedException(get_called_class() . '::' . __FUNCTION__);
	}

	/**
	 * Overrideable method for local create attributes
	 * @return array the Array of keys to use
	 */
	protected static function getLocalCreateAttributes()
	{
		return static::getLocalAttributes();
	}

	/**
	 * Overridable method for getting foreign update attributes
	 *
	 * The output must be an array of entity property names
	 * which represent foreign key fields in the underlying database table.
	 *
	 * @return array the array of key=>entityName pairs to use
	 */
	protected static function getForeignAttributes()
	{
		return array();
	}

	/**
	 * Overrideable method for accessing foreign create keys.
	 * @return array the array of valid foreign field names.
	 */
	protected static function getForeignCreateAttributes()
	{
		return static::getForeignAttributes();
	}

	/**
	 * Overridable method for getting an associative array of criteria keys
	 * to foreign query expressions.
	 * 
	 * @return array The array of foreign criteria.
	 */
	protected static function getForeignCriteria()
	{
		return array();
	}
	
	/**
	 * Gets the name of the uniquely identifying column for the given repository's entities.
	 * May be overridden by child classes to return a column different from the default (id).
	 *
	 * @return string The id column name.
	 */
	protected function _getIdColumnName()
	{
		return 'id';
	}

	/**
	 * Sets the named parameters in the specified rep query builder
	 *
	 * @param QueryBuilder $qb The query builder to update
	 * @param string|null $identifier The identifier for single limit queries (e.g. findOne)
	 */
	protected function _setQueryBuilderParameters(QueryBuilder $qb, $identifier = null)
	{
		$parameters = $this->_getQueryParameters();

		if (null !== $identifier)
		{
			$parameters['id'] = $identifier;
		}

		$qb->setParameters($parameters);
	}

	/**
	 * Gets the array of named parameter assignments.
	 * May be overridden in a child classes to inject additional array elements.
	 *
	 * @return array The array of named parameter assignment tuples.
	 */
	protected function _getQueryParameters()
	{
		return array();
	}

	/**
	 * Gets the query builder for entity id based queries.
	 *
	 * @return QueryBuilder The initialized query builder.
	 */
	protected function _getBaseOneQueryBuilder()
	{
		$qb = $this->_em->createQueryBuilder()
				->select('DISTINCT e')
				->from($this->getClassMetadata()->name, 'e')
				->andWhere('e.' . $this->_getIdColumnName() . ' = :id')
				->setMaxResults(1);
		return $qb;
	}

	/**
	 * Gets the query builder for general entity queries.
	 *
	 * @return QueryBuilder The initialized query builder.
	 */
	protected function _getBaseAllQueryBuilder()
	{
		$qb = $this->_em->createQueryBuilder()
				->select('DISTINCT e')
				->from($this->getClassMetadata()->name, 'e');
		return $qb;
	}

	/**
	 * Gets the count query builder for general entity queries.
	 *
	 * @return QueryBuilder The initialized query builder.
	 */
	protected function _getBaseCountQueryBuilder()
	{
		$qb = $this->_em->createQueryBuilder()
				->select('COUNT(DISTINCT e.id)')
				->from($this->getClassMetadata()->name, 'e');
		return $qb;
	}

	/**
	 * Adds the appropriate parameterized arguments for an array operator.
	 *
	 * @param QueryBuilder $qb The query builder instance.
	 * @param ArrayCollection $expressions The array of where expressions.
	 * @param ArrayCollection $values The array of values for the where expressions.
	 * @param ArrayCollection $types The collection of parameter types.
	 * @param int $position The index in the expressions/values arrays.
	 * @param string $property The property on which to operate.
	 * @param string $operator The operator to use on the property.
	 * @param array $value The value used by the operator in comparison to the property.
	 * @return int The updates position counter.
	 */
	private function _addArrayOp(QueryBuilder $qb, ArrayCollection $expressions, ArrayCollection $values, ArrayCollection $types, $position,
							  $property, $operator, array $value = null)
	{
		if (!isset($value))
		{
			throw new \InvalidArgumentException('Missing \'value\' key in criteria array');
		}

		$expressions->add($qb->expr()->$operator($property, '?' . ++$position));
		$values->set($position, $this->_wrapValue($types, $position, $value));
		return $position;
	}

	/**
	 * Adds the appropriate parameterized arguments for a binary operator.
	 *
	 * @param QueryBuilder $qb The query builder instance.
	 * @param ArrayCollection $expressions The array of where expressions.
	 * @param ArrayCollection $values The array of values for the where expressions.
	 * @param ArrayCollection $types The collection of parameter types.
	 * @param int $position The index in the expressions/values arrays.
	 * @param string $property The property on which to operate.
	 * @param string $operator The operator to use on the property.
	 * @param int|string $value The value used by the operator in comparison to the property.
	 * @return int The updates position counter.
	 */
	private function _addBinaryOp(QueryBuilder $qb, ArrayCollection $expressions, ArrayCollection $values, ArrayCollection $types, $position,
							   $property, $operator, $value = null)
	{
		if (!isset($value))
		{
			throw new \InvalidArgumentException('Missing \'value\' key in criteria array');
		}

		$expressions->add($qb->expr()->$operator($property, '?' . ++$position));
		$values->set($position, $this->_wrapValue($types, $position, $value));
		return $position;
	}
	
	/**
	 * Sets the appropriate joins for foreign criteria.
	 * 
	 * @param QueryBuilder $qb The query builder instance.
	 * @param array $criteria The criteria tuples.
	 */
	private function _addForeignCriteriaToBuilder(QueryBuilder $qb, array &$criteria)
	{
		$foreignCriteria = static::getForeignCriteria();
		
		// iterate over criteria array
		foreach ($criteria as $key => $value)
		{
			if (!isset($foreignCriteria[$key]))
			{
				continue;
			}
			
			$criterion = $foreignCriteria[$key];
			// remove foreign criterion
			unset ($criteria[$key]);
			
			// replace with appropriate simple criteria association
			$criteria[$criterion['field']] = $value;
			
			// add selects, froms, and joins
			if (isset($criterion['select']))
			{
				foreach ($criterion['select'] as $select)
				{
					$qb->addSelect($select);
				}
			}

			if (isset($criterion['from']))
			{
				foreach ($criterion['from'] as $clause)
				{
					$qb->join($clause['join'], $clause['alias']);
				}
			}
		}
	}

	/**
	 * Adds the criteria tuples to the given query builder as where sub-clauses.
	 *
	 * @param QueryBuilder $qb The query builder to which the criteria should be added.
	 * @param string $alias The alias of the entity.
	 * @param array $criteria The criteria tuples.
	 *
	 * @return QueryBuilder The updated query builder.
	 */
	protected function _addCriteriaToBuilder(QueryBuilder $qb, $alias, array $criteria)
	{
		$this->_addForeignCriteriaToBuilder($qb, $criteria);
		$expr = new ArrayCollection();
		$vals = new ArrayCollection();
		$types = new ArrayCollection();
		$n = 0;
		foreach ($criteria as $property => $value)
		{
			$prop = (false === strpos($property, '.') ? $alias . '.' . $property : $property);
			$val = $value;
			if (is_array($value))
			{
				$val = isset($val['value']) ? $val['value'] : null;
				if (isset($value['op']))
				{
					$op = strtoupper($value['op']);
					switch ($op)
					{
						case 'LIKE':
							$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'like', $val);
							break;
						case 'NOT LIKE':
							$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'like', $val, true);
							break;
						case 'IN':
							$n = $this->_addArrayOp($qb, $expr, $vals, $types, $n, $prop, 'in', $val);
							break;
						case 'NOT IN':
							$n = $this->_addArrayOp($qb, $expr, $vals, $types, $n, $prop, 'notIn', $val);
							break;
						case 'IS NOT NULL':
							$expr[] = $qb->expr()->isNotNull($prop);
							break;
						case 'IS NULL':
							$expr[] = $qb->expr()->isNull($prop);
							break;
						case '==':
						case '=':
							$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'eq', $val);
							break;
						case '!=':
						case '<>':
							$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'neq', $val);
							break;
						case '>':
							$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'gt', $val);
							break;
						case '<':
							$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'lt', $val);
							break;
						case '>=':
							$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'gte', $val);
							break;
						case '<=':
							$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'lte', $val);
							break;
						default:
							throw new \InvalidArgumentException('Unsupported query criteria operator: ' . $op);
					}
				}
				else
				{
					$n = $this->_addArrayOp($qb, $expr, $vals, $types, $n, $prop, 'in', $val);
				}
			}
			elseif (null === $value)
			{
				$expr[] = $qb->expr()->isNull($prop);
			}
			else
			{
				$n = $this->_addBinaryOp($qb, $expr, $vals, $types, $n, $prop, 'eq', $val);
			}
		}

		if (!$expr->isEmpty())
		{
			call_user_func_array(array($qb, 'andWhere'), $expr->toArray());
			$qb->setParameters($vals->toArray(), $types->toArray());
		}
	}

	/**
	 * Wraps a value for use in a DQL query
	 *
	 * @param ArrayCollection $types The collection of parameter types.
	 * @param int $position The position in the collection.
	 * @param object|string|int|boolean|DateTime $value
	 * @return string|int|DateTime The wrapped value
	 */
	protected function _wrapValue(ArrayCollection $types, $position, $value)
	{
		if (is_object($value))
		{
			return $this->_wrapValue($types, $position, $value->getId());
		}
		elseif (is_string($value))
		{
//			$value = '\'' . addslashes($value) . '\'';
			$type = \PDO::PARAM_STR;
		}
		elseif (is_bool($value))
		{
			$value = $value ? 1 : 0;
			$type = Type::INTEGER;
		}
		elseif (is_int($value))
		{
			$type = Type::INTEGER;
		}
		elseif ($value instanceof \DateTime)
		{
			$type = Type::DATETIME;
		}
		elseif (is_array($value))
		{
			$type = is_integer($value[key($value)]) ? Connection::PARAM_INT_ARRAY : Connection::PARAM_STR_ARRAY;
		}

		$types->set($position, $type);
		return $value;
	}

	/**
	 * Runs the query defined by the specified QueryBuilder.
	 *
	 * @param QueryBuilder $qb The builder to convert to a query and run.
	 * @return array|null An array of results from the query or null if no results found.
	 */
	protected final function _runQuery(QueryBuilder $qb)
	{
		$query = $qb->getQuery();
		$result = $query->getResult();
		return $result;
	}
}
