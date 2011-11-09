<?php

namespace CzarTheory\Interfaces;

/**
 * Provides the toArray function contract for implementing classes.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
interface Arrayable
{
	/**
	 * Returns the data of the implementing instance as an associative collection.
	 *
	 * @return array The associative array of property => value tuples.
	 */
	public function toArray();
}