<?php
namespace Leaps\Database\Query\Processors;

use Leaps\Database\Query\Builder;

class PostgresProcessor extends Processor {

	/**
	 * Process an "insert get ID" query.
	 *
	 * @param  \Leaps\Database\Query\Builder  $query
	 * @param  string  $sql
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return int
	 */
	public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
	{
		$results = $query->getConnection()->select($sql, $values);
		$sequence = $sequence ?: 'id';
		$result = (array) $results[0];
		return (int) $result[$sequence];
	}

}