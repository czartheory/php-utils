<?php

namespace CzarTheory\Utilities;

/**
 * Provides static functions for handling encryption.
 *
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class Cryptography
{
	/**
	 * Encrypts the password using the algorithm specified by the salt string.
	 *
	 * @see crypt
	 * @param string $password
	 * @param string $salt
	 * @return string
	 */
	final static public function encryptPassword($password, $salt)
	{
		return crypt($password, $salt);
	}

    /**
     * Generates a pseudo-random word for password salting.
	 *
     * @return string */
    final static public function generateSalt()
    {
		$cryptographySettings = \Zend_Registry::get('cryptography');
		$randomSalt = substr(bin2hex(openssl_random_pseudo_bytes(22, $strong)), 0, 22);
		if (!($randomSalt && $strong))
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
		}

        return '$' . $cryptographySettings['hash'] . '$rounds=' . $cryptographySettings['rounds'] . '$' . $randomSalt . '$';
    }

    /**
     * Generates a hash of the specified value and salt.
	 *
     * @param string $value The value to hash.
     * @param string $salt The value salt.
     * @return string The hashed value.
     */
    final static public function generateHash($value, $salt)
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
	  * @param integer $length the number of characters needed
	  * @return string the random string of characters
	  */
	 final static public function generateRandomChars($length)
	 {
		 $chars = substr(bin2hex(openssl_random_pseudo_bytes($length, $strong)), 0, $length);
		 if (!($chars && $strong))
		 {
			 $chars = substr(md5(uniqid(mt_rand(), mt_rand())), 0, $length);
		 }

		 return $chars;
	 }

    /**
     * Generates a random n-digit code
	 *
     * @param int $digits The number of digits in the code.
     * @return string
     */
    final static public function generateRandomCode($digits)
    {
        if (!is_numeric($digits) || $digits < 1)
        {
            throw new \InvalidArgumentException('$digits must be an integer > 0');
        }

		$code = str_pad((int) mt_rand(0, pow(10, $digits) - 1), $digits, '0', STR_PAD_LEFT);
        return $code;
    }
}