<?php

namespace CzarTheory\Utilities;

use \RuntimeException;

/**
 * Exception thrown when the program receives an invalid or malformed response to a
 * query or request (e.g. expected data fields are missing).
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class UnexpectedResponseException extends RuntimeException
{
    /**
     * Gets the string representation of the exception.
     * @return string The exception in string form.
     */
    public function __toString()
    {
        return 'Unexpected Response: ' . parent::__toString();
    }
}