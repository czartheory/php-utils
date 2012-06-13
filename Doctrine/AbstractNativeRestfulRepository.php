<?php
namespace CzarTheory\Doctrine;

use CzarTheory\Utilities\NotImplementedException;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 *
 * Extends from the CUD, to add the 'R'
 * But this one is all squeaky with native SQL
 *
 * @todo Description of AbstractNativeRestfulRepository... done!
 *
 * @copyright   Copyright (c) 2012 by CzarTheory LLC.  All Rights Reserved.
 * @author      Andrew Wheelwright <wheelwright.tech@gmail.com>
 */
abstract class AbstractNativeRestfulRepository extends AbstractCudRepository
{
	/**
	 * Gets the number of items available based upon the criteria given
	 *
	 * @param array $criteria
	 * @return int
	 */
	public function count(array $criteria = array())
	{
		$parts = $this->_addNativeCriteriaToQuery($this->_getBaseCountQuery(), $criteria);
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('Count', 'Count');
		$query = $this->_buildNativeQuery($parts, $rsm);
		$result = $query->getSingleScalarResult();
		return $result;
	}

	/**
	 * Gets the minimum value of the specified field.
	 *
	 * @param string $field The field for which the minimum value is sought.
	 * @param array $criteria The selection criteria.
	 * @return mixed The minimum value.
	 */
	public function min($field, array $criteria = array())
	{
		$parts = $this->_addNativeCriteriaToQuery($this->_getBaseMinQuery($field), $criteria);
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('Min', 'Min');
		$query = $this->_buildNativeQuery($parts, $rsm);
		$result = $query->getSingleScalarResult();
		return $result;
	}

	/**
	 * Gets the maximum value of the specified field.
	 *
	 * @param string $field The field for which the maximum value is sought.
	 * @param array $criteria The selection criteria.
	 * @return mixed The maximum value.
	 */
	public function max($field, array $criteria = array())
	{
		$parts = $this->_addNativeCriteriaToQuery($this->_getBaseMaxQuery($field), $criteria);
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('Max', 'Max');
		$query = $this->_buildNativeQuery($parts, $rsm);
		$result = $query->getSingleScalarResult();
		return $result;
	}

	/**
	 * Gets a single entity from the database given the identifier and an optional set of criteria.
	 *
	 * @param string|int $id The identifier of the entity instance.
	 * @param array $criteria The criteria for restricting the result set.
	 * @return EntitySuperClass|null The requested Entity instance if found; otherwise null.
	 */
	public function get($identifier, array $criteria = null)
	{
		if (null === $criteria)
		{
			return $this->find($identifier);
		}

		$criteria['id'] = $identifier;
		$parts = $this->_addNativeCriteriaToQuery($this->_getBaseOneQuery(), $criteria);
		$parts['limit'] = 1;
		$query = $this->_buildNativeQuery($parts, $this->_getResultMapping());
		$result = $query->getOneOrNullResult();
		return $result;
	}

	/**
	 * Gets a single entity from the database which matches the given criteria
	 *
	 * @param array $criteria The critera for finding the Entity instance.
	 * @return EntitySuperClass|null The requested Entity instance if found; otherwise null.
	 */
	public function getOneBy(array $criteria)
	{
		$parts = $this->_addNativeCriteriaToQuery($this->_getBaseQuery(), $criteria);
		$parts['limit'] = 1;
		$query = $this->_buildNativeQuery($parts, $this->_getResultMapping());
		$result = $query->getOneOrNullResult();
		return $result;
	}

	/**
	 * Gets the set of unique field entries in the repository.
	 *
	 * @param string $field The name of the field.
	 * @param array $criteria The optional array of criteria for the query.
	 * @return ArrayCollection The collection of unique entries.
	 */
	public function getDistinct($field, array $criteria = array())
	{
		$parts = $this->_addNativeCriteriaToQuery($this->_getBaseDistinctQuery($field), $criteria);
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult($field, $field);
		$query = $this->_buildNativeQuery($parts, $rsm);
		$results = $query->getScalarResult();
		return $results;
	}

	/**
	 * Gets a collection of entities matching the specified enhanced simple doctrine criteria.
	 *
	 * @param array $criteria The selection criteria.
	 * @param array|null $orderBy An array of column names to sort order ('ASC' or 'DESC') mappings.
	 * @param int|null The maximum number of records to return in the result set.
	 * @param int|null The starting offset at which records will be included in the result set.
	 * @return ArrayCollection The matching entities.
	 */
	public function getAll(array $criteria = array(), array $orderBy = array(), $limit = null, $offset = null)
	{
		$parts = $this->_addNativeCriteriaToQuery($this->_getBaseQuery(), $criteria);

		if (!empty($orderBy))
		{
			$parts['sort'] = $orderBy;
		}

		if (isset($limit))
		{
			$parts['limit'] = $limit;
		}

		if (isset($offset))
		{
			$parts['offset'] = $offset;
		}

		$query = $this->_buildNativeQuery($parts, $this->_getResultMapping());
		$entities = $query->getResult();
		return $entities;
	}

	abstract protected function _getBaseQuery();

	protected function _getBaseCountQuery()
	{
		$query = $this->_getBaseQuery();
		$query['select'] = array('COUNT(*) AS Count');
		return $query;
	}

	protected function _getBaseDistinctQuery($field)
	{
		$query = $this->_getBaseQuery();
		$query['select'] = array('DISTINCT e.' . $field);
		return $query;
	}

	protected function _getBaseMaxQuery($field)
	{
		$query = $this->_getBaseQuery();
		$query['select'] = array('MAX(' . $field . ') AS Max');
		return $query;
	}

	protected function _getBaseMinQuery($field)
	{
		$query = $this->_getBaseQuery();
		$query['select'] = array('MIN(' . $field . ') AS Min');
		return $query;
	}

	protected function _getResultMapping()
	{
		$map = new ResultSetMappingBuilder($this->_em);
		$map->addRootEntityFromClassMetadata($this->_class->name, 'e');
		return $map;
	}

	/**
	 * Overridable method for getting an associative array of criteria keys
	 * to foreign query expressions.
	 *
	 * @return array The array of foreign criteria.
	 */
	protected static function getForeignCriteria()
	{
		return array();
	}

	/**
	 * Builds a native query from the query object.
	 * @param array $query An associative array of query parts.
	 * @param ResultSetMapping|ResultSetMappingBuilder $map The doctrine result set mapping.
	 * @return NativeQuery The native query object.
	 */
	protected final function _buildNativeQuery(array $query, $map)
	{
		$parts = array('SELECT');
		$useParams = false;

		foreach ($query['select'] as $part)
		{
			$parts[] = $part;
		}

		if (isset($query['from']))
		{
			$parts[] = 'FROM';
			foreach ($query['from'] as $part)
			{
				if (isset($part['join']))
				{
					$parts[] = sprintf('%s %s %s ON %s', strtoupper($part['join']['type']), $part['table'], $part['alias'], $part['join']['clause']);
				}
				else
				{
					$parts[] = sprintf('%s %s', $part['table'], $part['alias']);
				}
			}
		}

		if (isset($query['where']))
		{
			$parts[] = 'WHERE';
			$parts[] = implode(' AND ', $query['where']['clauses']);
			$useParams = true;
		}

		if (isset($query['group']))
		{
			$parts[] = 'GROUP BY';
			$parts[] = implode(', ', $query['group']);
		}

		if (isset($query['sort']))
		{
			$parts[] = 'ORDER BY';
			foreach ($query['sort'] as $field => $direction)
			{
				$parts[] = sprintf('e.%s %s', $field, $direction);
			}
		}

		if (isset($query['limit']))
		{
			$parts[] = sprintf('LIMIT %d', $query['limit']);
		}

		if (isset($query['offset']))
		{
			$parts[] = sprintf('OFFSET %d', $query['offset']);
		}

		$sql = implode(' ', $parts);

		$native = $this->_em->createNativeQuery($sql, $map);
		if ($useParams)
		{
			$native->setParameters($query['where']['values']);
		}

		return $native;
	}

	/**
	 * Sets the appropriate joins for foreign criteria.
	 *
	 * @param QueryBuilder $qb The query builder instance.
	 * @param array $criteria The criteria tuples.
	 */
	private function _addForeignCriteriaToQuery(array &$query, array &$criteria)
	{
		$foreignCriteria = static::getForeignCriteria();

		// iterate over criteria array
		foreach ($criteria as $key => $value)
		{
			if (!isset($foreignCriteria[$key]))
			{
				continue;
			}

			$criterion = $foreignCriteria[$key];
			// remove foreign criterion
			unset ($criteria[$key]);

			// replace with appropriate simple criteria association
			$criteria[$criterion['field']] = $value;

			// add selects, froms, and joins
			if (isset($criterion['select']))
			{
				foreach ($criterion['select'] as $select)
				{
					$query['select'][] = $select;
				}
			}

			if (isset($criterion['from']))
			{
				foreach ($criterion['from'] as $from)
				{
					$query['from'][] = $from;
				}
			}
		}
	}

	/**
	 * Adds the specified criteria as native sql clauses.
	 * @param array $query The query object.
	 * @param string $alias The main table alias (should be 'e').
	 * @param array $criteria The criteria to add.
	 * @return array The update query object
	 * @throws \InvalidArgumentException If the criteria contains an unsupported operator.
	 */
	protected final function _addNativeCriteriaToQuery(array $query, array $criteria)
	{
		$this->_addForeignCriteriaToQuery($query, $criteria);
		$clauses = array();
		$values = array();
		$i = 0;
		foreach ($criteria as $field => $criterion)
		{
			if (is_array($criterion))
			{
				$value = isset($criterion['value']) ? $criterion['value'] : $criterion;
				if (isset($criterion['op']))
				{
					$op = strtoupper($criterion['op']);
					switch ($op)
					{
						case 'LIKE':
						case 'NOT LIKE':
							$clauses[] = sprintf('%1$s %2$s \'%%?%%\'', $field, $op);
							$values[++$i] = $value;
							break;
						case 'IN':
						case 'NOT IN':
							$clauses[] = sprintf('%1$s %2$s (?)', $field, $op);
							$values[++$i] = values;
							break;
						case 'IS NULL':
						case 'IS NOT NULL':
							$clauses[] = sprintf('%1$s %2$s', $field, $op);
							break;
						case '==':
						case '=':
							$clauses[] = sprintf('%1$s = ?', $field);
							$values[++$i] = $value;
							break;
						case '!=':
						case '<>':
							$clauses[] = sprintf('%1$s <> ?', $field);
							$values[++$i] = $value;
							break;
						case '>':
						case '<':
						case '>=':
						case '<=':
							$clauses[] = sprintf('%1$s %2$s ?', $field, $op);
							$values[++$i] = $value;
							break;
						default:
							throw new \InvalidArgumentException('Unsupported query criteria operator: ' . $op);
					}
				}
				else
				{
					$clauses[] = sprintf('%1$s IN (?)', $field);
					$values[++$i] = $value;
				}
			}
			elseif (null === $criterion)
			{
				$clauses[] = sprintf('%1$s IS NULL', $field);
			}
			else
			{
				$clauses[] = sprintf('%1$s = ?', $field);
				$values[++$i] = $criterion;
			}
		}

		if (!empty($clauses))
		{
			$query['where'] = array('clauses' => $clauses, 'values' => $values);
		}

		return $query;
	}
}