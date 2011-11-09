<?php

namespace CzarTheory\Utilities;

use \RuntimeException;

/**
 * Exception thrown when the program encounters a situation that violates design
 * logic assumptions and must terminate before damage is done.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class IllegalStateException extends RuntimeException
{
	/**
	 * Gets the string representation of the exception.
	 * @return string The exception in string form.
	 */
	public function __toString()
	{
		return 'Illegal System State: ' . parent::__toString();
	}
}
