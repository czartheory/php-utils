<?php

namespace CzarTheory\Doctrine;

use CzarTheory\Utilities\IllegalStateException;
use CzarTheory\Utilities\NotImplementedException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Base Class for handling all CRUD, except for 'R'
 * Hence, 'cud' repository, get it?
 *
 * @copyright Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author Matthew Larson <matthew@czarTheory.com>
 * @author Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
abstract class AbstractCudRepository extends EntityRepository
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
		foreach ($attributes as $fieldName)
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
		for ($i = 0; $i < $size; ++$i)
		{
			$key = $attributes[$i];
			$newArray[$key] = 'set' . ucfirst(str_replace('_', '', $key));
		}
		return $newArray;
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
		$this->_saveEntity($entity, $values, $this->_localCreateAttributes, $this->_foreignCreateAttributes);
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
		$this->_saveEntity($entity, $values, $this->_localUpdateAttributes, $this->_foreignUpdateAttributes);
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
	protected final function _saveEntity($entity, array $values, array &$localKeys, array &$foreignKeys)
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
}
