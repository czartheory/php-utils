<?php
/*
 * Copyright 2011 Czar Theory, LLC
 * All rights reserved.
 */

namespace CzarTheory\Services;

use CzarTheory\Utilities\AccessViolationException;

/**
 * Interface defining a collection-based service.
 *
 * Allows an actor to do CRUD on a collection/table of items.
 * Provides access-checking to see if actor has credentials
 * and other methods for getting info about a collection of items.
 *
 * @author Matthew Larson <matthew@czarTheory.com>
 */
interface RestfulService
{
	/**
	 * Configures the criteria for subsequent getAll calls.
	 * @param array $criteria The filtering criteria used in the where clause.
	 */
	public function setCriteria(array $criteria);

	/**
	 * Ammends the criteria for subsequent getAll calls.
	 * @param array $criteria The filtering criteria used in the where clause.
	 */
	public function addCriteria(array $criteria);

	/**
	 * Configures the ordering of subsequent getAll calls.
	 * @param array $orderBy The associative array of columns mapped to 'ASC' or 'DESC' values.
	 */
	public function setOrderBy(array $orderBy);

	/**
	 * Configures the limit and offset (pagination) of the getAll request.
	 *
	 * @param integer $limit The number of records to return.
	 * @param integer $offset The starting result of the records to return.
	 */
	public function setPagination($limit, $offset = 0);

	/**
	 * Gets a value indicating whether the criteria has been set (i.e. not null or an empty array()).
	 * @return boolean true if criteria was previously set, otherwise false.
	 */
	public function hasCriteria();

	/**
	 * Erases previously set criteria.
	 */
	public function resetCriteria();

	/**
	 * Retrieves an object from the collection, wrapped as a ProtectedObject
	 *
	 * @param string|int $identifier the unique identifier to get the entity
	 * @param array $criteria (optional) The collection of enhanced simple doctrine criteria tuples to apply to the query, if any
	 * @return ProtectedObject|null The wrapped entity or null if not found
	 */
	public function get($identifier, array $criteria = array());

	/**
	 * Retrieves all available objects, wrapped as ProtectedObject instances
	 * @return ArrayAccess Collection of ProtectedObject which the user is allowed to read (may be empty)
	 */
	public function getAll();

	/**
	 * Gets the count of Items available with a similar getAll() method
	 * @return int the number of items counted
	 */
	public function count();

	/**
	 * Gets a value indicating whether the actor is allowed to add an object to the collection
	 * @return boolean
	 */
	public function canCreate();

	/**
	 * Creates an entity based upon the given values
	 *
	 * @param array $values an associative array containing the needed values
	 * @return ProtectedObject The wrapped entity
	 * @throws AccessViolationException if access was denied
	 */
	public function create(array $values, $flush = true);

	/**
	 * Gets a value indicating whether the actor is allowed to update an object in the collection
	 *
	 * @param string $identifier (Optional) The id/identifier of the object to update.
	 * @return boolean
	 */
	public function canUpdate($identifier = null);

	/**
	 * Modifies an object based upon the given values
	 *
	 * @param string $identifier the unique identifier to get the object
	 * @param array $values an associative array containing the needed values
	 *
	 * @return ProtectedObject the wrapped object
	 * @throws InvalidOperationException if the object was not found
	 * @throws AccessViolationException if access was denied
	 */
	public function update($identifier, array $values, $flush = true);

	/**
	 * Gets a value indicating whether the actor is allowed to delete an object
	 *
	 * @param string $identifier (Optional) The id/identifier of the object to delete
	 * @return boolean
	 */
	public function canDelete($identifier = null);

	/**
	 * Deletes an object from the collection if it exists. For idempotence,
	 * this method silently fails if the object was not found.
	 *
	 * @param string $identifier the unique identifier to get the entity
	 * @throws AccessViolationException if access was denied
	 */
	public function delete($identifier, $flush = true);
}
