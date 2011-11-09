<?php

namespace CzarTheory\Utilities;

use \RuntimeException;

/**
 * Exception thrown when the user authentication fails or the user does not
 * have sufficient authorization to access an object or method.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class AccessViolationException extends RuntimeException
{
    /**
     * Gets the string representation of the exception.
     * @return string The exception in string form.
     */
    public function __toString()
    {
        return 'Access Denied: ' . parent::__toString();
    }
}
