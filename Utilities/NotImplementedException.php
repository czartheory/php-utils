<?php

namespace CzarTheory\Utilities;

use \RuntimeException;

/**
 * Exception thrown when the user accesses functionality which is not currently implemented.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class NotImplementedException extends RuntimeException
{
	/**
	 * Creates an instance of a NotImplementedException for the specified method.
	 *
	 * @param string $method The name of the unimplemented method.
	 * @param mixed $code (optional) The error code of the exception.
	 * @param Exception $previous (optional) The parent exception in the exception stack.
	 */
	public function __construct($method, $code = null, Exception $previous = null)
	{
		parent::__construct('The requested method '.$method.' has not been implemented', $code, $previous);
	}
}
