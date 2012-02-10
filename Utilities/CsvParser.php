<?php
/*
 * Copyright 2011 Czar Theory, LLC
 * All rights reserved.
 */

namespace CzarTheory\Utilities;

/*
 * THIS CODE IS BASED UPON THE FOLLOWING:
 * Class: parseCSV v0.3.2
 * http://code.google.com/p/parsecsv-for-php/
 */

class CsvParser
{
	/** @var string delimiter between fields */
	private $delimiter = ',';

	/** @var string enclosing character for fields */
	private $enclosure = '"';

	/** @var boolean */
	private $convertEncoding = false;

	/** @var string */
	private $inputEncoding = 'ISO-8859-1';

	/** @var string */
	private $outputEncoding = 'ISO-8859-1';

	/** @var string */
	private $fileName;

	/** @var file */
	private $rawData;

	/** @var integer */
	private $cursor = 0;

	/** @var integer */
	private $rowsLoaded = 0;

	/** @var array Array of values from header-row */
	private $schema = null;

	/** @var array two dimentional array of parsed data */
	private $parsedData = array();

	/**
	 * Constructor
	 * @param string $input can be either a file name or string of csv-text
	 */
	public function __construct($input)
	{
		if (is_readable($input)) $this->_loadFile($input);
		else $this->rawData = $input;
	}

	/**
	 * Load local file into memory, and convert encoding if needed
	 * @param string $fileName 
	 */
	private function _loadFile($fileName)
	{
		$this->fileName = $fileName;
		$data = file_get_contents($fileName);
		if ($data === false)
		{
			throw new \InvalidArgumentException("File $fileName was unable to be read");
		}

		if ($this->convertEncoding)
		{
			$data = iconv($this->inputEncoding, $this->outputEncoding, $data);
		}

		if (substr($data, -1) != "\n") 
		{
			$data .= "\n";
		}
		
		$this->rawData = $data;
	}

	/**
	 * Gets the header-row of the document
	 * If this method is called, all data-rows will be parsed as associative arrays instead of indexed arrays
	 *
	 * @return array The header row 
	 */
	public function getHeaderRow()
	{
		if ($this->schema === null)
		{
			if ($this->rowsLoaded > 0)
			{
				throw new \InvalidArgumentException("Cannot get header row after data has been parsed");
			}
			
			$row = null;
			do
			{
				$row = $this->_getRawRow();
				if ($row === null)
				{
					throw new \InvalidArgumentException("No data found in parse");
				}
				
				$row = $this->_parseRawRow($row, false);
			}
			while (empty($row));

			$this->schema = $row;
		}

		return $this->schema;
	}

	/**
	 * Gets the next row in the CSV data
	 * 
	 * If getHeaderRow() was called previously, the row will 
	 * be converted to an associative array. Blank rows are skipped
	 *
	 * @return array the row found or null if at the end of the file
	 */
	public function getNextRow()
	{
		$row = null;
		do
		{
			$raw = $this->_getRawRow();
			if ($raw === null) return null;

			$raw = $this->_parseRawRow($raw);
		}
		while (empty($raw));

		if ($this->schema !== null)
		{
			$rawRow = $raw;
			$row = array();
			$size = count($this->schema);
			for ($i = 0; $i < $size; ++$i)
			{
				$key = $this->schema[$i];
				$row[$key] = $rawRow[$i];
			}
		}

		$this->parsedData[] = $row;
		return $row;
	}

	/**
	 * Gets the next row of data
	 * @return string the unparsed row, including rows with enclosed linebreaks
	 */
	private function _getRawRow()
	{
		$lineEnd = function($char, $previousChar, $nextChar = null)
			{
				return (($char == "\r" && $nextChar != "\n" /* not windows */) || $char == "\n");
			};

		$result = $this->_getRawUntil($lineEnd, $this->rawData, $this->cursor);
		$this->cursor = $result[1];
		return $result[0];
	}

	/**
	 * Get raw data until a specified $checkFunction states that a delimiter has been found
	 *
	 * @param callback $checkFunction the function($char, $prev, $next) to decide if parsing is done.
	 * @param string $input
	 * @param integer $startCursor
	 * @return array (the raw string, the final cursor position) 
	 */
	private function _getRawUntil($checkFunction, $input, $startCursor)
	{
		$cursor = $startCursor;
		$length = strlen($input);
		$data = $input;

		$enclosure = $this->enclosure;
		if ($cursor >= $length) return null;

		$terminationFound = false;
		$enclosed = false;
		$wasEnclosed = false;

		$char = false;
		$charNext = $data{$cursor};
		$charPrev = false;
		$result = "";

		while (!$terminationFound && $cursor < $length)
		{
			$charPrev = $char;
			$char = $charNext;
			++$cursor;
			$charNext = ($cursor < $length) ? $data{$cursor} : false;
			$result .= $char;

			// open and closing quotes
			if ($char == $enclosure && ($charNext != $enclosure || !$enclosed))
			{
				$enclosed = !$enclosed;
				if ($enclosed) $wasEnclosed = true;

				// check for end of row
			}
			elseif (!$enclosed && $checkFunction($char, $charPrev, $charNext))
			{
				$terminationFound = true;
			}
		}

		return array(trim($result), $cursor, $wasEnclosed);
	}

	/**
	 *
	 * @param type $raw the raw-string unparsed row
	 * @return array the fields in sequence
	 */
	private function _parseRawRow($raw)
	{
		$row = array();
		$cursor = 0;
		$length = strlen($raw);
		if ($cursor == $length) return null;

		$delimiter = $this->delimiter;
		$checkFunction = function ($char, $prev = null, $next = null) use ($delimiter)
			{
				return ($char == $delimiter);
			};

		while ($cursor < $length)
		{
			$ret = $this->_getRawUntil($checkFunction, $raw, $cursor);
			if ($ret === null) break;

			$field = $ret[0];
			$cursor = $ret[1];
			$wasEnclosed = $ret[2];

			if ($wasEnclosed) $field = substr($field, 1, strlen($field) - 2);

			$row[] = $field;
		}

		return $row;
	}
}

