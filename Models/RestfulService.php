<?php
/*
 * Copyright 2011 Czar Theory, LLC
 * All rights reserved.
 */

namespace CzarTheory\Models;

/**
 * Description of RestfulService
 * @author Matthew Larson <matthew@czarTheory.com>
 */

interface RestfulService
{
	/**
	 * used to get a single item 
	 * 
	 * @param string|integer $id the unique identifier for the record
	 * @return Object the record 
	 */
	public function get($id);
	
	/**
	 * used to get all or a range of items. If no parameters are given,
	 * it is expected that all records will be returned
	 * 
	 * @param array $criteria (optional) a filter for limiting search
	 * @param int $begin (optional) the starting record number required
	 * @param int $offset (optional) the number of records needed
	 * @return array the records retreived
	 */
	public function getAll(array $criteria = null, $begin = null, $end = null);
	
	/**
	 * for record creation
	 * 
	 * @param array $values the key=>value pairs to create a record
	 * @return Object the created record
	 */
	public function create(array $values);
	
	/**
	 * for record modification
	 * 
	 * @param integer|string $id the unique identifier for the record
	 * @param array $values the key=>value pairs to modify the record
	 * @return Object the updated record
	 */
	public function update($id, array $values);
	
	/**
	 * for record deletion
	 * @param integer|string $id the unique identifier for the record
	 */
	public function delete($id);
}
