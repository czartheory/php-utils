<?php

namespace CzarTheory\Utilities;

/**
 * Configures PHP Assertion functionality and provides a callback mechanism for failed assertions.
 * 
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class Assertion
{
	/**
	 * Throws an exception with an informative message derived from a failing assertion.
	 *
	 * @param string $file The file containing <var>$assertion</var>
	 * @param int $line The line number in <var>$file</var> of <var>$assertion</var>.
	 * @param string $assertion The failing assertion.
	 * @throws IllegalStateException based on the information in the assert
	 */
	public static function bailCallback($file, $line, $assertion)
	{
		throw new IllegalStateException("Failed assertion '$assertion' on line $line of $file");
	}

	public static function warnCallback($file, $line, $assertion)
	{
		$e = new Exception();
		echo "<p style=\"color:red\"><strong><em>Failed assertion</em> '$assertion' on line $line of $file</strong></p>",
			$e->getTraceAsString(), '<br/>';
	}
}
