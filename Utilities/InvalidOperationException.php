<?php

namespace CzarTheory\Utilities;

use \RuntimeException;

/**
 * Exception thrown when the program enters an invalid state.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class InvalidOperationException extends RuntimeException
{
    /**
     * Gets the string representation of the exception.
     * @return string The exception in string form.
     */
    public function __toString()
    {
        return 'Invalid Operation: ' . parent::__toString();
    }
}