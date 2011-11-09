<?php

namespace CzarTheory\Utilities;

/**
 * Provides static functions for handling encryption.
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class Encryption
{
    /**
     * Generates a pseudo-random word for password salting.
     * @return string */
    final static public function _generateSalt()
    {
        $min = 0;
        $max = 2147483647;
        $randomSalt = substr(
                str_replace('+', '.', base64_encode(pack(
                        'N4',
                        mt_rand($min, $max),
                        mt_rand($min, $max),
                        mt_rand($min, $max),
                        mt_rand($min, $max)))),
                0,
                22);

        return '$6$rounds=500' . $randomSalt . '$';
    }

    /**
     * Generates a hash of the specified value and salt.
     * @param string $value The value to hash.
     * @param string $salt The value salt.
     * @return string The hashed value.
     */
    final static public function _generateHash($value, $salt)
    {
        $hash = $value . $salt;
        for($i = 0; $i < 20; ++$i)
        {
            $hash = md5($hash);
        }

        return $hash;
    }
	 
 
	 /**
	  * Genearates a string of random characters
	  *
	  * @param integer $chars the number of characters needed
	  * @return string the random string of characters
	  */
	 final static public function _generateRandomChars($chars)
	 {
		 return substr(md5(uniqid(mt_rand(),true)), 0, $chars);
	 }

    /**
     * Generates a random n-digit code
     * @param int $digits The number of digits in the code.
     * @return string
     */
    final static public function _generateRandomCode($digits)
    {
        if (!is_numeric($digits) || $digits < 1)
        {
            throw new \InvalidArgumentException('$digits must be an integer > 0');
        }
		  
        return str_pad((int) mt_rand(0, pow(10, $digits) - 1), $digits, '0', STR_PAD_LEFT);
    }
}
