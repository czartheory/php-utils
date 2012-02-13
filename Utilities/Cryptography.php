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
			$randomSalt = substr(str_replace('+', '.',
									base64_encode(pack(
							'N4', mt_rand($min, $max), mt_rand($min, $max), mt_rand($min, $max), mt_rand($min, $max)))), 0, 22);
		}

		return '$' . $cryptographySettings['hash'] . '$rounds=' .
			$cryptographySettings['rounds'] . '$' . $randomSalt . '$';
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
		for ($i = 0; $i < 20; ++$i)
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
		if (!is_numeric($length) || $length < 1)
		{
			throw new \InvalidArgumentException('$chars must be an integer > 0');
		}

		$chars = substr(bin2hex(openssl_random_pseudo_bytes($length, $strong)), 0, $length);
		if (!($chars && $strong))
		{
			$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			$chars = '';
			$min = 0;
			$max = strlen($charset) - 1;
			for ($i = 0; $i < $length; ++$i)
			{
				$chars += $charset[mt_rand($min, $max)];
			}
		}

		return $chars;
	}

	/**
	 * Generates a 55 character unique identifier
	 * 
	 * @return string The randomly generated unique identifier. 
	 */
	final static public function _generateUniqueId()
	{
		return uniqid(md5(mt_rand()), true);
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
			throw new \InvalidArgumentException('$digits must be an integer greater than 0');
		}
		
		for ($code = ''; 0 < $digits; --$digits)
		{
			$code .= (int)mt_rand(0, 9);
		}

		return $code;
	}
}
