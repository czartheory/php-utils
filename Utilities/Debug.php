<?php
/*
 * Copyright 2011 Czar Theory, LLC
 * All rights reserved.
 */

namespace CzarTheory\Utilities;

/**
 * Description of Debug
 * @author Matthew Larson <matthew@czarTheory.com>
 */
class Debug
{

	/** @var string */
	protected static $_sapi = null;

	/** @var int The maximum depth for doctrine entities */
	protected static $_depth = 2;

	/**
	 * Get the current value of the debug output environment.
	 * This defaults to the value of PHP_SAPI.
	 *
	 * @return string;
	 */
	public static function getSapi()
	{
		if (self::$_sapi === null) {
			self::$_sapi = PHP_SAPI;
		}
		return self::$_sapi;
	}

	/**
	 * Set the debug ouput environment.
	 * Setting a value of null causes Zend_Debug to use PHP_SAPI.
	 *
	 * @param string $sapi
	 */
	public static function setSapi($sapi)
	{
		self::$_sapi = $sapi;
	}

	/**
	 * Sets the maximum recursion depth of output
	 * @param int $depth
	 */
	public static function setDepth($depth)
	{
		self::$_depth = $depth;
	}

	/**
	 * Debug helper function.  This is a wrapper for var_dump() that adds
	 * the <pre /> tags, cleans up newlines and indents, and runs
	 * htmlentities() before output.
	 *
	 * @param  mixed  $var   The variable to dump.
	 * @param  string $label OPTIONAL Label to prepend to output.
	 * @param  bool   $echo  OPTIONAL Echo output if true.
	 * @return string
	 */
	public static function dump($var, $label=null, $echo=true)
	{
		// format the label
		$label = ($label === null) ? '' : rtrim($label) . ' ';

		// var_dump the variable into a buffer and keep the output
		ob_start();
		\Doctrine\Common\Util\Debug::dump($var, self::$_depth);
		$output = ob_get_clean();

		// neaten the newlines and indents
		$output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
		if (self::getSapi() == 'cli') {
			$output = PHP_EOL . $label
				. PHP_EOL . $output
				. PHP_EOL;
		} else {
			if (!extension_loaded('xdebug')) {
				$output = htmlspecialchars($output, ENT_QUOTES);
			}

			$output = '<pre>'
				. $label
				. $output
				. '</pre>';
		}

		if ($echo) {
			echo($output);
		}
		return $output;
	}

}
