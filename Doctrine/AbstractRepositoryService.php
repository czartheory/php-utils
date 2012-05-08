<?php

namespace CpgLink\Services;

use Doctrine\Common\Collections\ArrayCollection;

use CzarTheory\Services\RestfulService;
use CzarTheory\Utilities\InvalidOperationException;
use CzarTheory\Utilities\NotImplementedException;

use CpgLink\Repositories\AbstractRestfulRepository;
use CpgLink\Services\AbstractUserService;
use CpgLink\Entities\Person;

/**
 * CollectionServiceAbstract
 * Base class for all collection services (e.g. plural entity services).
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 */
abstract class AbstractRepositoryService extends AbstractUserService implements RestfulService
{
	/**
	 * The underlying repository instance.
	 * @var AbstractRestfulRepository
	 */
	protected $_repository;

	/**
	 * The owning entity for dependent repository services.
	 * @var Entity
	 */
	protected $_owningEntity;

	/**
	 * The base criteria used for finding items
	 * @var array
	 */
	protected $_baseCriteria;

	/**
	 * An array of key=>[entity,person] tuples which have been previously retreived.
	 * @var array
	 */
	protected $_entityRegistry;

	/**
	 * The criteria to be used when performing getAll() and count()
	 * @var array
	 */
	protected $_criteria = array();

	/**
	 * a list of columns to sort by when performing a getAll()
	 * @var array
	 */
	protected $_order = array();

	/**
	 * The maximum number of records to retrieve
	 * @var integer|string
	 */
	protected $_limit = null;

	/**
	 * The starting record to retreive
	 * @var integer|string
	 */
	protected $_offset = null;

	/**
	 * Builds a collection service based on the user's person ID (or rep instance) and
	 * (optionally), the owning entity (for dependent services), and the specified entity manager.
	 *
	 * @param string|Person $personOrId The rep instance or user id used to establish the role of the current user.
	 * @param Entity|null $owningEntity <b>(Optional)</b> The owning entity for dependent services.
	 * @param EntityManager|string|null $entityManagerOrName <b>(Optional)</b> An existing entity manager,
	 * the name of the desired entity manager, or null.
	 */
	public function __construct($personOrId = null, $owningEntity = null, $owner = null, $entityManagerOrName = null)
	{
		parent::__construct($personOrId, $owner, $entityManagerOrName);
		$this->_owningEntity = $owningEntity;
		$this->_repository = $this->_getRepository();
		$this->_baseCriteria = $this->getBaseCriteria();
	}

	private function getBaseCriteria()
	{
		$criteria = $this->_getBaseCriteria();
		if(isset($this->_owningEntity)){
			$propertyName = $this->_getOwningEntityPropertyName();
			$criteria[$propertyName] = $this->_owningEntity;
		}
		return $criteria;
	}

	/**
	 * Gets the array of property names which may be set when creating an entity.
	 * @return array|null The array of property names (default: null).
	 */
	protected function _getCreateKeys()
	{
		return null;
	}

	/**
	 * Gets the array of property names which may be set when updating an entity.
	 * @return array|null The array of property names (default: null).
	 */
	protected function _getUpdateKeys()
	{
		return $this->_getCreateKeys();
	}

	/**
	 * Gets the array of property names which need to be renamed before filtering property values.
	 * @return array|null The renamed array of property names (default: null).
	 */
	protected function _getRenamedKeys()
	{
		return null;
	}

	/**
	 * Method for any criteria that needs to be added to get/getall methods
	 * @return array any essential added criteria for searching
	 */
	protected function _getBaseCriteria()
	{
		return array();
	}

	/**
	 * Used if the service is dependent (i.e., has an _owningEntity)
	 * @return string the property-name of the owning entity
	 */
	protected function _getOwningEntityPropertyName()
	{
		throw new NotImplementedException($this->_getClass() . '::' . __FUNCTION__);
	}

	/**
	 * Renames keys according to the specified translation array.  If no array is specified,
	 * the original <var>$values</var> array is returned.
	 *
	 * @param array $values The array of values with keys which need to be renamed.
	 * @param array $renamedKeys (Optional) The associative array of old to new key name mappings.
	 * @return array The renamed values.
	 */
	private function _renameKeys(array $values, array $renamedKeys = null)
	{
		if(null !== $renamedKeys) {
			foreach($renamedKeys as $key => $value) {
				if(isset($values[$key])) {
					$values[$value] = $values[$key];
					unset($values[$key]);
				}
			}
		}
		return $values;
	}

	/**
	 * Filters all non-allowed keys from an array.
	 * if no allowed keys are provided, returns the original array
	 *
	 * @param array $values the input
	 * @param array $allowedKeys the input
	 * @return array the filtered input
	 */
	private function _filterValues(array $values, array $allowedKeys = null)
	{
		$values = $this->_renameKeys($values, $this->_getRenamedKeys());
		if(null !== $allowedKeys) {
			$allowedKeys = array_fill_keys($allowedKeys, true);
			$values = array_intersect_key($values, $allowedKeys);
		}
		return $values;
	}

	/**
	 * Retrieves an entity, wrapped as a service or view-proxy
	 *
	 * @param string|int $identifier the unique identifier to get the entity
	 * @param array $criteria (optional) The collection of enhanced simple doctrine criteria tuples to apply to the query, if any
	 * @return AbstractEntityService|AbstractEntityProxy|null The wrapped entity or null if not found or access is denied
	 */
	public final function get($identifier, array $criteria = null)
	{
		if($criteria == null) $criteria = $this->_baseCriteria;
		$criteria = array_merge($criteria, $this->_baseCriteria);
		$entity = $this->_getOne($identifier, $criteria);
		return null === $entity ? null : $this->_wrapEntity($entity);
	}

	/**
	 * Gets the requested entity for the given Representable instance.
	 * May be overridden in a child class to call a different repository method.
	 *
	 * @param string|int $identifier The record identifier
	 * @param array $criteria The collection of enhanced simple doctrine criteria tuples
	 *
	 * @return array|null An array containing two elements, the first being the entity
	 * sought and the second being the associated rep object if the entity is found; otherwise null.
	 */
	protected function _getOne($identifier, array $criteria)
	{
		return $this->_repository->get($identifier, $criteria);
	}

	/**
	 * Configures the criteria for subsequent getAll calls.
	 * @param array $criteria The filtering criteria used in the where clause.
	 */
	public function setCriteria(array $criteria)
	{
		$this->_criteria = $criteria;
	}

	/**
	 * Erases previously set criteria.
	 */
	public function resetCriteria()
	{
		$this->_criteria = array();
	}

	/**
	 * Configures the ordering of subsequent getAll calls.
	 * @param array $orderBy The associative array of columns mapped to 'ASC' or 'DESC' values.
	 */
	public function setOrderBy(array $orderBy)
	{
		$this->_order = $orderBy;
	}

	/**
	 * Configures the limit and offset (pagination) of the getAll request.
	 *
	 * @param integer $limit The number of records to return.
	 * @param integer $offset The starting result of the records to return.
	 */
	public function setPagination($limit, $offset = 0)
	{
		$this->_limit = $limit;
		$this->_offset = $offset;
	}

	public function getOffset()
	{
		return $this->_offset;
	}

	public function getLimit()
	{
		return $this->_limit;
	}

	/**
	 * Gets a value indicating whether the criteria has been set (i.e. not null or an empty array()).
	 * @return boolean true if criteria was previously set, otherwise false.
	 */
	public function hasCriteria()
	{
		return !empty($this->_criteria);
	}

	/**
	 * Retrieves all entities, wrapped in services or view-proxies
	 *
	 * @return ArrayCollection Collection of wrapped entities which the user is allowed to read (may be empty)
	 */
	public final function getAll()
	{
		$criteria = array_merge($this->_criteria, $this->_baseCriteria);
		$entities = $this->_getAll($criteria, $this->_order, $this->_limit, $this->_offset);
		$output = new ArrayCollection();
		foreach($entities as $entity) $output->add($this->_wrapEntity($entity));
		return $output;
	}

	/**
	 * Gets the entities from the repository based on the specified criteria.
	 * May be overridden in a child class to call a different repository method.
	 *
	 * @param array $criteria The collection of key => value tuples used to select the desired entities
	 * @param array|null $orderBy An array of column names to sort order ('ASC' or 'DESC') mappings.
	 * @param int|null The maximum number of records to return in the result set.
	 * @param int|null The starting offset at which records will be included in the result set.
	 * @return array A collection of entity/rep tuples matching the specified criteria
	 */
	protected function _getAll(array $criteria, $orderBy, $limit, $offset)
	{
		return $this->_repository->getAll($criteria, $orderBy, $limit, $offset);
	}


	/**
	 * Gets the count of Items available with a similar getAll() method
	 *
	 * @param array $criteria
	 * @return int
	 */
	public final function count(array $criteria = array())
	{
		$criteria = array_merge($this->_criteria, $this->_baseCriteria);
		return $this->_repository->count($criteria);
	}

	/**
	 * Creates an entity based upon the given values
	 *
	 * @param array $values an associative array containing the needed values
	 * @return AbstractEntityService|AbstractEntityProxy The wrapped entity
	 */
	public final function create(array $values, $flush = true)
	{
		if(!$this->canCreate()) self::throwAccessError($this->_getClass(), 'create', $this->_getRole());
		$values = $this->_filterValues($values, $this->_getCreateKeys());
		if(isset($this->_owningEntity)){
			$propertyName = $this->_getOwningEntityPropertyName();
			$values[$propertyName] = $this->_owningEntity;
		}

		$entity = $this->_create($values);
		if($flush) $this->_em->flush();
		return $this->_wrapEntity($entity);
	}

	/**
	 * Helper method to create a requested entity.
	 * Can be overridden as needed
	 *
	 * @param array $values
	 * @return Entity the created entity
	 */
	protected function _create(array $values)
	{
		return $this->_repository->create($values);
	}

	/**
	 * Gets a value indicating whether the current user is allowed to create an entity
	 * @return boolean
	 */
	public function canCreate()
	{
		if($this->_owner !== null) return true;
		return self::isAuthorized($this->_getClass(), 'create');
	}

	/**
	 * Modifies an entity based upon the given values
	 *
	 * @param string $identifier the unique identifier to get the entity
	 * @param array $values an associative array containing the needed values
	 *
	 * @return AbstractEntityService|AbstractEntityProxy the wrapped entity
	 * @throws InvalidOperationException if the Entity was not found
	 */
	public final function update($identifier, array $values, $flush = true)
	{
		$entity = $this->_getOne($identifier, $this->_baseCriteria);
		if(null === $entity) throw new InvalidOperationException('No Item found in database with given identifier:' . $identifier);

		if(!$this->_canUpdate($entity)) self::throwAccessError($this->_getClass(), 'update', $this->_getUpdateRole($entity));

		$values = $this->_filterValues($values, $this->_getUpdateKeys());
		if(isset($this->_owningEntity)){
			$propertyName = $this->_getOwningEntityPropertyName();
			$values[$propertyName] = $this->_owningEntity;
		}

		$this->_update($entity, $values);
		if($flush) $this->_em->flush();
		return $this->_wrapEntity($entity);
	}

	/**
	 * Helper method to update a given entity
	 * Can be overridden as needed
	 *
	 * @param Entity $entity
	 * @param array $values
	 */
	protected function _update($entity, array $values)
	{
		$this->_repository->update($entity, $values);
	}

	/**
	 * Public Facing version of _canUpdate
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public final function canUpdate($identifier = null)
	{
		$entity = null;
		if($identifier !== null){
			$entity = $this->_getOne($identifier, $this->_baseCriteria);
			if($entity === null) return false;
		}
		return $this->_canUpdate($entity);
	}

	/**
	 * Gets a value indicating whether the current user is allowed to update an entity
	 *
	 * @param Entity $entity (Optional) The entity to update.
	 * @return boolean
	 */
	protected function _canUpdate($entity)
	{
		if($this->_owner !== null) return true;
		return self::isAuthorized($this->_getClass(), 'update');
	}

	/**
	 * Gets a user's admin role if there is one
	 * @return AdminRole
	 */
	protected function _getRole(){
		return $this->_user !== null ? $this->_user->getRole() : null;
	}

	/**
	 * Deletes an entity
	 * @param string $identifier the unique identifier to get the entity
	 */
	public final function delete($identifier, $flush = true)
	{
		$entity = $this->_getOne($identifier, $this->_baseCriteria);
		if(null === $entity) return;

		if(!$this->_canDelete($entity)) self::throwAccessError($this->_getClass(), 'delete', $this->_getDeleteRole($entity));

		$this->_delete($entity);
		if($flush) $this->_em->flush();
	}

	/**
	 * Helper Method to delete a given entity
	 * Can be overridden as needed
	 *
	 * @param Entity $entity
	 */
	protected function _delete($entity)
	{
		$this->_repository->delete($entity);
	}

	/**
	 * Public Facing version of _canDelete
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public final function canDelete($identifier = null)
	{
		$entity = null;
		if($identifier !== null){
			$entity = $this->_getOne($identifier, $this->_baseCriteria);
		}
		return $this->_canDelete($entity);
	}

	/**
	 * Gets a value indicating whether the current user is allowed to delete an entity
	 *
	 * @param Entity $entity The entity to delete.
	 * @return boolean
	 */
	protected function _canDelete($entity)
	{
		if($this->_owner !== null) return true;
		return self::isAuthorized($this->_getClass(), 'delete');
	}

	/**
	 * Overridden by the child class to return the appropriate repository
	 * @return EntityRepository The repository.
	 */
	abstract protected function _getRepository();

	/**
	 * Gets the name of the child class
	 * @return string The name of the child class
	 */
	abstract protected function _getClass();

	/**
	 * Wraps an Entity in either a Service or ViewProxy
	 *
	 * @param Entity $entity the entity to be wrapped into a service/proxy
	 * @return AbstractEntityService|AbstractEntityProxy The wrapped entity
	 */
	abstract protected function _wrapEntity($entity);
}
