<?php

namespace CzarTheory\Utilities;

/**
 * Method to create a json-string, completely unescaped.
 * 
 * @copyright   Copyright (c) 2011 by CzarTheory, LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class JsonExactBypass
{
	protected $_json;
	
	public function __construct($json)
	{
		$this->_json = $json;
	}
	
	public function toJson()
	{
		return $this->_json;
	}
}
