<?php

namespace CzarTheory\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

/**
 * Extends from the CUD, to add the 'R'
 * Keeps to entities, no fancy SQL stuff..
 *
 * @copyright Copyright (c) 2012 by CzarTheory, LLC.  All Rights Reserved.
 * @author Matthew Larson <matthew@czarTheory.com>
 * @author Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
abstract class AbstractRestfulRepository extends AbstractCudRepository
{
	/**
	 * Gets the number of items available based upon the criteria given
	 *
	 * @param array $criteria
	 * @return int
	 */
	public function count(array $criteria = array())
	{
		$qb = $this->_getBaseCountQueryBuilder();
		$this->_addCriteriaToBuilder($qb, 'e', $this->sanitizeQuery($criteria));
		$query = $qb->getQuery();
		$result = $query->getSingleScalarResult();
		return $result;
	}


	/**
	 * Gets the number of items available based upon the criteria given
	 *
	 * @param string $field The name of the field for which the minimum is sought.
	 * @param array $criteria The criteria for restricting the result set.
	 * @return mixed The minimum value.
	 */
	public function min($field, array $criteria = array())
	{
		$qb = $this->_getBaseMinQueryBuilder($field);
		$this->_addCriteriaToBuilder($qb, 'e', $this->sanitizeQuery($criteria));
		$query = $qb->getQuery();
		$result = $query->getSingleScalarResult();
		return $result;
	}


	/**
	 * Gets the number of items available based upon the criteria given
	 *
	 * @param string $field The name of the field for which the maximum is sought.
	 * @param array $criteria The criteria for restricting the result set.
	 * @return mixed The maximum value.
	 */
	public function max($field, array $criteria = array())
	{
		$qb = $this->_getBaseMaxQueryBuilder($field);
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
		if(empty($criteria))
		{
			if ($this->_getIdColumnName() === 'id') {
				return $this->find($identifier);
			} else {
				$criteria = array();
			}
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
	 * Gets the set of unique field entries in the repository.
	 *
	 * @param string $field The name of the field.
	 * @param array $criteria The optional array of criteria for the query.
	 * @return ???
	 */
	public function getDistinct($field, array $criteria = array())
	{
		$qb = $this->_getBaseDistinctQueryBuilder($field);
		$this->_addCriteriaToBuilder($qb, 'e', $this->sanitizeQuery($criteria));
		$query = $qb->getQuery();
		$result = $query->getScalarResult();
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
	public function getAll(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
	{
		if (empty($criteria))
		{
			return $this->findBy($criteria, $orderBy, $limit, $offset);
		}

		$qb = $this->_getBaseAllQueryBuilder();
		$this->_addCriteriaToBuilder($qb, 'e', $this->sanitizeQuery($criteria));

		if(null !== $orderBy){
			foreach ($orderBy as $sort => $order) {$qb->orderBy('e.' . $sort, $order);}
		}

		if (null !== $offset) {
			$qb->setFirstResult($offset);
		}

		if (null !== $limit) {
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
	protected final function _getBaseOneQueryBuilder()
	{
		$qb = $this->_em->createQueryBuilder()
				->select('DISTINCT e')
				->from($this->getClassMetadata()->name, 'e')
				->andWhere('e.' . $this->_getIdColumnName() . ' = :id')
				->setMaxResults(1);
		return $qb;
	}

	/**
	 * Gets the query builder for querying unique column values.
	 *
	 * @param string $field The name of the column for which distinct values are sought.
	 * @return QueryBuilder The initialized query builder.
	 */
	protected final function _getBaseDistinctQueryBuilder($field)
	{
		$qb = $this->_em->createQueryBuilder()
				->select('DISTINCT e.' . $field)
				->from($this->getClassMetadata()->name, 'e');
		return $qb;
	}

	/**
	 * Gets the query builder for general entity queries.
	 *
	 * @return QueryBuilder The initialized query builder.
	 */
	protected final function _getBaseAllQueryBuilder()
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
	protected final function _getBaseCountQueryBuilder()
	{
		$qb = $this->_em->createQueryBuilder()
				->select('COUNT(DISTINCT e.id)')
				->from($this->getClassMetadata()->name, 'e');
		return $qb;
	}

	/**
	 * Gets the minimum value query builder.
	 *
	 * @param string $field The name of the field for which the minimum is sought.
	 * @return QueryBuilder The initialized query builder.
	 */
	protected final function _getBaseMinQueryBuilder($field)
	{
		$qb = $this->_em->createQueryBuilder()
				->select('MIN(e.' . $field . ')')
				->from($this->getClassMetadata()->name, 'e');
		return $qb;
	}

	/**
	 * Gets the minimum value query builder.
	 *
	 * @param string $field The name of the field for which the maximum is sought.
	 * @return QueryBuilder The initialized query builder.
	 */
	protected final function _getBaseMaxQueryBuilder($field)
	{
		$qb = $this->_em->createQueryBuilder()
				->select('MAX(e.' . $field . ')')
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
	protected final function _addCriteriaToBuilder(QueryBuilder $qb, $alias, array $criteria)
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
				$val = isset($val['value']) ? $val['value'] : $value;
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
	protected final function _wrapValue(ArrayCollection $types, $position, $value)
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
