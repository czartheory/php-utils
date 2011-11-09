<?php

namespace CzarTheory\Interfaces;

/**
 * An identifiable instance is an instance which can return an id.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
interface Identifiable
{
	/**
	 * Gets the identifier of this instance.
	 *
	 * return string|int The identifier.
	 */
	public function getId();
}