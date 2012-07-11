<?php

namespace CzarTheory\Doctrine\DBAL\Schema;

use CzarTheory\Doctrine\DBAL\Schema\EnhancedSchemaDiff;
use CzarTheory\Doctrine\DBAL\Schema\EnhancedTableDiff;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * Compare two Schemas and return an instance of SchemaDiff with
 * the enhancement of included foreign key update logic when
 * foreign key columns are modified.
 *
 * @copyright Copyright (C) 2011 CzarThoery LLC. All rights reserved.
 * @version 1.1
 * @author  Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
class EnhancedComparator extends Comparator
{
	/** @var array|null The foreign keys referencing the current table. */
	protected $foreignKeysToTable = null;

	/**
	 * Returns a SchemaDiff object containing the differences between the schemas $fromSchema and $toSchema.
	 *
	 * The returned diferences are returned in such a way that they contain the
	 * operations to change the schema stored in $fromSchema to the schema that is
	 * stored in $toSchema.
	 *
	 * @param Schema $fromSchema
	 * @param Schema $toSchema
	 *
	 * @return SchemaDiff
	 */
	public function compare(Schema $fromSchema, Schema $toSchema)
	{
		$diff = new EnhancedSchemaDiff();

		$this->foreignKeysToTable = array();

		/* Check for removed tables and build the foreign key map */
		foreach ($fromSchema->getTables() AS $tableName => $table)
		{
			if (!$toSchema->hasTable($tableName))
			{
				$diff->removedTables[$tableName] = $table;
			}

			// also remember all foreign keys that point to a specific table
			foreach ($table->getForeignKeys() AS $foreignKey)
			{
				$foreignTable = strtolower($foreignKey->getForeignTableName());
				if (!isset($this->foreignKeysToTable[$foreignTable]))
				{
					$this->foreignKeysToTable[$foreignTable] = array();
				}

				$this->foreignKeysToTable[$foreignTable][] = $foreignKey;
			}
		}

		/* Check for new or changed tables */
		foreach ($toSchema->getTables() AS $tableName => $table)
		{
			if (!$fromSchema->hasTable($tableName))
			{
				$diff->newTables[$tableName] = $table;
			}
			else
			{
				$tableDifferences = $this->diffTable($fromSchema->getTable($tableName), $table);
				if ($tableDifferences !== false)
				{
					$diff->changedTables[$tableName] = $tableDifferences;
				}
			}
		}

		foreach ($diff->removedTables AS $tableName => $table)
		{
			if (isset($this->foreignKeysToTable[$tableName]))
			{
				$diff->orphanedForeignKeys = array_merge($diff->orphanedForeignKeys, $this->foreignKeysToTable[$tableName]);
			}
		}

		foreach ($toSchema->getSequences() AS $sequenceName => $sequence)
		{
			if (!$fromSchema->hasSequence($sequenceName))
			{
				$diff->newSequences[] = $sequence;
			}
			else
			{
				if ($this->diffSequence($sequence, $fromSchema->getSequence($sequenceName)))
				{
					$diff->changedSequences[] = $toSchema->getSequence($sequenceName);
				}
			}
		}

		foreach ($fromSchema->getSequences() AS $sequenceName => $sequence)
		{
			if (!$toSchema->hasSequence($sequenceName))
			{
				$diff->removedSequences[] = $sequence;
			}
		}

		return $diff;
	}

	/**
	 * Returns the difference between the tables $table1 and $table2.
	 *
	 * If there are no differences this method returns the boolean false.
	 *
	 * @param Table $table1
	 * @param Table $table2
	 *
	 * @return bool|EnhancedTableDiff
	 */
	public function diffTable(Table $table1, Table $table2)
	{
		$changes		  = 0;
		$tableDifferences = new EnhancedTableDiff($table1->getName());

		$table1Columns = $table1->getColumns();
		$table2Columns = $table2->getColumns();

		/* See if all the fields in table 1 exist in table 2 */
		foreach ($table2Columns as $columnName => $column)
		{
			if (!$table1->hasColumn($columnName))
			{
				$tableDifferences->addedColumns[$columnName] = $column;
				++$changes;
			}
		}
		/* See if there are any removed fields in table 2 */
		foreach ($table1Columns as $columnName => $column)
		{
			if (!$table2->hasColumn($columnName))
			{
				$tableDifferences->removedColumns[$columnName] = $column;
				++$changes;
			}
		}

		foreach ($table1Columns as $columnName => $column)
		{
			if ($table2->hasColumn($columnName))
			{
				$changedProperties = $this->diffColumn($column, $table2->getColumn($columnName));
				if (count($changedProperties))
				{
					$columnDiff = new ColumnDiff($column->getName(), $table2->getColumn($columnName), $changedProperties);
					$tableDifferences->changedColumns[$column->getName()] = $columnDiff;
					// BUG: if the changed column is referenced by a foreign key we need to mark all referring keys as changed keys
					$tableName  = strtolower($table1->getName());
					if (isset($this->foreignKeysToTable[$tableName]))
					{
						foreach ($this->foreignKeysToTable[$tableName] as $constraint)
						{
							foreach ($constraint->getForeignColumns() as $constraintColumnName)
							{
								if ($constraintColumnName == $columnName)
								{
									$tableDifferences->impactedForeignKeys[$constraint->getLocalTableName()] = $constraint;
								}
							}
						}
					}
					++$changes;
				}
			}
		}

		$this->enhancedDetectColumnRenamings($tableDifferences);

		$table1Indexes = $table1->getIndexes();
		$table2Indexes = $table2->getIndexes();

		foreach ($table2Indexes AS $index2Name => $index2Definition)
		{
			foreach ($table1Indexes AS $index1Name => $index1Definition)
			{
				if ($this->diffIndex($index1Definition, $index2Definition) === false)
				{
					unset($table1Indexes[$index1Name]);
					unset($table2Indexes[$index2Name]);
				}
				elseif ($index1Name == $index2Name)
				{
					$tableDifferences->changedIndexes[$index2Name] = $table2Indexes[$index2Name];
					unset($table1Indexes[$index1Name]);
					unset($table2Indexes[$index2Name]);
					++$changes;
				}
			}
		}

		foreach ($table1Indexes AS $index1Name => $index1Definition)
		{
			$tableDifferences->removedIndexes[$index1Name] = $index1Definition;
			++$changes;
		}

		foreach ($table2Indexes AS $index2Name => $index2Definition)
		{
			$tableDifferences->addedIndexes[$index2Name] = $index2Definition;
			++$changes;
		}

		$fromFkeys = $table1->getForeignKeys();
		$toFkeys   = $table2->getForeignKeys();

		foreach ($fromFkeys AS $key1 => $constraint1)
		{
			foreach ($toFkeys AS $key2 => $constraint2)
			{
				if ($this->diffForeignKey($constraint1, $constraint2) === false)
				{
					unset($fromFkeys[$key1]);
					unset($toFkeys[$key2]);
				}
				elseif (strtolower($constraint1->getName()) == strtolower($constraint2->getName()))
				{
					$tableDifferences->changedForeignKeys[] = $constraint2;
					++$changes;
					unset($fromFkeys[$key1]);
					unset($toFkeys[$key2]);
				}
			}
		}

		foreach ($fromFkeys AS $key1 => $constraint1)
		{
			$tableDifferences->removedForeignKeys[] = $constraint1;
			++$changes;
		}

		foreach ($toFkeys AS $key2 => $constraint2)
		{
			$tableDifferences->addedForeignKeys[] = $constraint2;
			++$changes;
		}

		return $changes ? $tableDifferences : false;
	}

	/**
	 * Try to find columns that only changed their name, rename operations maybe cheaper than add/drop
	 * however ambiguouties between different possibilites should not lead to renaming at all.
	 *
	 * @param EnhancedTableDiff $tableDifferences
	 */
	private function enhancedDetectColumnRenamings(EnhancedTableDiff $tableDifferences)
	{
		$renameCandidates = array();
		foreach ($tableDifferences->addedColumns AS $addedColumnName => $addedColumn)
		{
			foreach ($tableDifferences->removedColumns AS $removedColumnName => $removedColumn)
			{
				if (count($this->diffColumn($addedColumn, $removedColumn)) == 0)
				{
					$renameCandidates[$addedColumn->getName()][] = array($removedColumn, $addedColumn, $addedColumnName);
				}
			}
		}

		foreach ($renameCandidates AS $candidateColumns)
		{
			if (count($candidateColumns) == 1)
			{
				list($removedColumn, $addedColumn) = $candidateColumns[0];
				$removedColumnName = strtolower($removedColumn->getName());
				$addedColumnName   = strtolower($addedColumn->getName());

				$tableDifferences->renamedColumns[$removedColumnName] = $addedColumn;
				unset($tableDifferences->addedColumns[$addedColumnName]);
				unset($tableDifferences->removedColumns[$removedColumnName]);
			}
		}
	}
}