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
 * Fully conforms to the specifications lined out on wikipedia:
 *  - http://en.wikipedia.org/wiki/Comma-separated_values
 * 
 * Based on the concept of Ming Hong Ng's CsvFileParser class:
 *  - http://minghong.blogspot.com/2006/07/csv-parser-for-php.html
 *
 *  Copyright (c) 2007 Jim Myhrberg (jim@zydev.info).
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

class CsvParser
{
	/**
	 * delimiter between fields
	 * @var string
	 */
	private $delimiter = ',';

	/**
	 * enclosing character for fields
	 * @var string
	 */
	private $enclosure = '"';

	/**
	 * basic SQL-like conditions for row matching
	 * @var string
	 */
	private $conditions = null;

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
	private $length;

	/** @var integer */
	private $cursor = 0;

	/** @var integer */
	private $rowsLoaded = 0;

	/**
	 * Array of values from header-row
	 * @var array
	 */
	private $schema = null;

	/**
	 * two dimentional array of parsed data
	 * @var array
	 */
	private $parsedData = array();

	/**
	 * Constructor
	 *
	 * @param string $input can be either a file name or string of csv-text
	 * @param int $limit the number of total rows to parse. null means all rows. optional
	 * @param string $conditions conditions to be used???. optional
	 */
	public function __construct($input, $conditions = null)
	{
		if(is_readable($input)) $this->_loadFile($input);
		else $this->rawData = $input;

		$this->length = strlen($this->rawData);

		$conditions = trim($conditions);
		if(!empty($conditions)) $this->conditions = $conditions;
	}

	/**
	 * Load local file into memory, and convert encoding if needed
	 * @param string $fileName 
	 */
	private function _loadFile($fileName)
	{
		$this->fileName = $fileName;
		$data = file_get_contents($fileName);
		if($data === false) throw new \InvalidArgumentException("File $fileName was unable to be read");

		if($this->convertEncoding) $data = iconv($this->inputEncoding, $this->outputEncoding, $data);
		if(substr($data, -1) != "\n") $data .= "\n";
		$this->rawData = $data;
	}

	/**
	 * Gets the header-row of the document
	 * 
	 * If this method is called, 
	 *
	 * @return array The header row 
	 */
	public function getHeaderRow()
	{
		if($this->schema === null) {
			if($this->rowsLoaded > 0) throw new \InvalidArgumentException("Cannot get header row after data has been parsed");
			$row = null;
			do {
				$row = $this->_getNextRow();
				if($row === null) throw new \InvalidArgumentException("No data found in parse");
			} while($this->hasData($row));

			$this->schema = $row;
		}
		return $this->schema;
	}

	/**
	 * Gets the next row of data
	 * @return array the data pulled from the row or null if the cursor reaches the end of the file
	 */
	private function _getNextRow()
	{
		$cursor = $this->cursor;
		$length = $this->length;
		if($cursor >= $length) return null;

		$data = $this->rawData;
		$lineEndFound = false;
		$enclosed = false;
		$wasEnclosed = false;

		$char = false;
		$nextChar = $data{$cursor};
		$prevChar = false;
		$line = "";

		while(!$lineEndFound && $cursor < $this->length) {
			$prevChar = $char;
			$char = $nextChar;
			$cursor++;
			$nextChar = ($cursor < $length) ? $data{$cursor} : false;
			$line.= $char;

			if($char == $this->enclosure) && (!$enclosed || $nextChar != $this->enclosure)) {
				// open and closing quotes
				$enclosed = !$enclosed;
				if($enclosed) $wasEnclosed = true;

			} elseif(($char == $this->delimiter || ($char == "\n" && $prevChar != "\r") || $char == "\r") &&!$enclosed) {
				// end of field/row
				if(!$wasEnclosed) $line = trim($line);
				$key = (!empty($head[$col]) ) ? $head[$col] : $col;
				$row[$key] = $line;
				$line = '';
				$col++;

				// end of row
				if($char == "\n" || $char == "\r") {
					if($this->_validateRowOffest($rowCount) && $this->_validateRowConditions($row, $this->conditions)) {
						if($this->heading && empty($head)) {
							$head = $row;
						} elseif(empty($this->fields) || (!empty($this->fields) && (($this->heading && $rowCount > 0) || !$this->heading))) {
							if(!empty($this->sortBy) && !empty($row[$this->sortBy])) {
								if(isset($rows[$row[$this->sortBy]])) {
									$rows[$row[$this->sortBy] . '_0'] = &$rows[$row[$this->sortBy]];
									unset($rows[$row[$this->sortBy]]);
									for($sn = 1; isset($rows[$row[$this->sortBy] . '_' . $sn]); $sn++) {
										
									}
									$rows[$row[$this->sortBy] . '_' . $sn] = $row;
								} else $rows[$row[$this->sortBy]] = $row;
							} else $rows[] = $row;
						}
					}
					$row = array();
					$col = 0;
					$rowCount++;
					if($this->sortBy === null && $this->limit !== null && count($rows) == $this->limit) {
						$cursor = $dataLength;
					}
				}

				// append character to current field
			} else {
				$line .= $char;
			}
		}
	}

	/**
	 * Parse CSV string into a 2D array
	 *
	 * @param string $data CSV-formatted string
	 * @return type 
	 */
	private function _parseString($data)
	{

		$rows = array();
		$row = array();
		$rowCount = 0;
		$current = '';
		$head = (!empty($this->fields)) ? $this->fields : array();
		$col = 0;
		$enclosed = false;
		$wasEnclosed = false;
		$dataLength = strlen($data);

		// walk through each character
		for($i = 0; $i < $dataLength; $i++) {
			$char = $data{$i};
			$nextChar = ( isset($data{$i + 1}) ) ? $data{$i + 1} : false;
			$prevChar = ( isset($data{$i - 1}) ) ? $data{$i - 1} : false;

			// open and closing quotes
			if($char == $this->enclosure && (!$enclosed || $nextChar != $this->enclosure)) {
				$enclosed = ($enclosed) ? false : true;
				if($enclosed) $wasEnclosed = true;

				// inline quotes	
			} elseif($char == $this->enclosure && $enclosed) {
				$current .= $char;
				$i++;

				// end of field/row
			} elseif(($char == $this->delimiter || ($char == "\n" && $prevChar != "\r") || $char == "\r") && !$enclosed) {
				if(!$wasEnclosed) $current = trim($current);
				$key = (!empty($head[$col]) ) ? $head[$col] : $col;
				$row[$key] = $current;
				$current = '';
				$col++;

				// end of row
				if($char == "\n" || $char == "\r") {
					if($this->_validateRowOffest($rowCount) && $this->_validateRowConditions($row, $this->conditions)) {
						if($this->heading && empty($head)) {
							$head = $row;
						} elseif(empty($this->fields) || (!empty($this->fields) && (($this->heading && $rowCount > 0) || !$this->heading))) {
							if(!empty($this->sortBy) && !empty($row[$this->sortBy])) {
								if(isset($rows[$row[$this->sortBy]])) {
									$rows[$row[$this->sortBy] . '_0'] = &$rows[$row[$this->sortBy]];
									unset($rows[$row[$this->sortBy]]);
									for($sn = 1; isset($rows[$row[$this->sortBy] . '_' . $sn]); $sn++) {
										
									}
									$rows[$row[$this->sortBy] . '_' . $sn] = $row;
								} else $rows[$row[$this->sortBy]] = $row;
							} else $rows[] = $row;
						}
					}
					$row = array();
					$col = 0;
					$rowCount++;
					if($this->sortBy === null && $this->limit !== null && count($rows) == $this->limit) {
						$i = $dataLength;
					}
				}

				// append character to current field
			} else {
				$current .= $char;
			}
		}
		$this->schema = $head;
		if(!empty($this->sortBy)) {
			( $this->sortReverse ) ? krsort($rows) : ksort($rows);
			if($this->offset !== null || $this->limit !== null) {
				$rows = array_slice($rows, ($this->offset === null ? 0 : $this->offset), $this->limit, true);
			}
		}
		return $rows;
	}

	/**
	 * Validate a row against specified conditions
	 * @param   row          array with values from a row
	 * @param   conditions   specified conditions that the row must match 
	 * @return  true of false
	 */
	private function _validateRowConditions($row = array(), $conditions = null)
	{
		if(!empty($row)) {
			if(!empty($conditions)) {
				$conditions = (strpos($conditions, ' OR ') !== false) ? explode(' OR ', $conditions) : array($conditions);
				$or = '';
				foreach($conditions as $key => $value) {
					if(strpos($value, ' AND ') !== false) {
						$value = explode(' AND ', $value);
						$and = '';
						foreach($value as $k => $v) {
							$and .= $this->_validateRowCondition($row, $v);
						}
						$or .= (strpos($and, '0') !== false) ? '0' : '1';
					} else {
						$or .= $this->_validateRowCondition($row, $value);
					}
				}
				return (strpos($or, '1') !== false) ? true : false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Validate a row against a single condition
	 * @param   row          array with values from a row
	 * @param   condition   specified condition that the row must match 
	 * @return  true of false
	 */
	private function _validateRowCondition($row, $condition)
	{
		$operators = array(
			'=', 'equals', 'is',
			'!=', 'is not',
			'<', 'is less than',
			'>', 'is greater than',
			'<=', 'is less than or equals',
			'>=', 'is greater than or equals',
			'contains',
			'does not contain',
		);
		$operators_regex = array();
		foreach($operators as $value) {
			$operators_regex[] = preg_quote($value, '/');
		}
		$operators_regex = implode('|', $operators_regex);
		if(preg_match('/^(.+) (' . $operators_regex . ') (.+)$/i', trim($condition), $capture)) {
			$field = $capture[1];
			$op = $capture[2];
			$value = $capture[3];
			if(preg_match('/^([\'\"]{1})(.*)([\'\"]{1})$/i', $value, $capture)) {
				if($capture[1] == $capture[3]) {
					$value = $capture[2];
					$value = str_replace("\\n", "\n", $value);
					$value = str_replace("\\r", "\r", $value);
					$value = str_replace("\\t", "\t", $value);
					$value = stripslashes($value);
				}
			}
			if(array_key_exists($field, $row)) {
				if(($op == '=' || $op == 'equals' || $op == 'is') && $row[$field] == $value) {
					return '1';
				} elseif(($op == '!=' || $op == 'is not') && $row[$field] != $value) {
					return '1';
				} elseif(($op == '<' || $op == 'is less than' ) && $row[$field] < $value) {
					return '1';
				} elseif(($op == '>' || $op == 'is greater than') && $row[$field] > $value) {
					return '1';
				} elseif(($op == '<=' || $op == 'is less than or equals' ) && $row[$field] <= $value) {
					return '1';
				} elseif(($op == '>=' || $op == 'is greater than or equals') && $row[$field] >= $value) {
					return '1';
				} elseif($op == 'contains' && preg_match('/' . preg_quote($value, '/') . '/i', $row[$field])) {
					return '1';
				} elseif($op == 'does not contain' && !preg_match('/' . preg_quote($value, '/') . '/i', $row[$field])) {
					return '1';
				} else {
					return '0';
				}
			}
		}
		return '1';
	}

	/**
	 * Validates if the row is within the offset or not if sorting is disabled
	 * @param   current_row   the current row number being processed
	 * @return  true of false
	 */
	private function _validateRowOffest($current_row)
	{
		if($this->sortBy === null && $this->offset !== null && $current_row < $this->offset) return false;
		return true;
	}
}

