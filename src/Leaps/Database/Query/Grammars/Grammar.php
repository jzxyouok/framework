<?php
// +----------------------------------------------------------------------
// | Leaps Framework [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author XuTongle <xutongle@gmail.com>
// +----------------------------------------------------------------------
namespace Leaps\Database\Query\Grammars;

use Leaps\Database\Query;
use Leaps\Database\Expression;

class Grammar extends \Leaps\Database\Grammar
{

	/**
	 * The format for properly saving a DateTime.
	 *
	 * @var string
	 */
	public $datetime = 'Y-m-d H:i:s';

	/**
	 * All of the query components in the order they should be built.
	 *
	 * @var array
	 */
	protected $components = [
			'aggregate',
			'selects',
			'from',
			'joins',
			'wheres',
			'groupings',
			'havings',
			'orderings',
			'limit',
			'offset'
	];

	/**
	 * Compile a SQL SELECT statement from a Query instance.
	 *
	 * @param Query $query
	 * @return string
	 */
	public function select(Query $query)
	{
		return $this->concatenate ( $this->components ( $query ) );
	}

	/**
	 * Generate the SQL for every component of the query.
	 *
	 * @param Query $query
	 * @return array
	 */
	final protected function components($query)
	{
		foreach ( $this->components as $component ) {
			if (! is_null ( $query->$component )) {
				$sql [$component] = call_user_func ( array (
						$this,
						$component
				), $query );
			}
		}

		return ( array ) $sql;
	}

	/**
	 * Concatenate an array of SQL segments, removing those that are empty.
	 *
	 * @param array $components
	 * @return string
	 */
	final protected function concatenate($components)
	{
		return implode ( ' ', array_filter ( $components, function ($value)
		{
			return ( string ) $value !== '';
		} ) );
	}

	/**
	 * Compile the SELECT clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function selects(Query $query)
	{
		if (! is_null ( $query->aggregate ))
			return;

		$select = ($query->distinct) ? 'SELECT DISTINCT ' : 'SELECT ';

		return $select . $this->columnize ( $query->selects );
	}

	/**
	 * Compile an aggregating SELECT clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function aggregate(Query $query)
	{
		$column = $this->columnize ( $query->aggregate ['columns'] );
		if ($query->distinct and $column !== '*') {
			$column = 'DISTINCT ' . $column;
		}

		return 'SELECT ' . $query->aggregate ['aggregator'] . '(' . $column . ') AS ' . $this->wrap ( 'aggregate' );
	}

	/**
	 * Compile the FROM clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function from(Query $query)
	{
		return 'FROM ' . $this->wrap_table ( $query->from );
	}

	/**
	 * Compile the JOIN clauses for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function joins(Query $query)
	{
		foreach ( $query->joins as $join ) {
			$table = $this->wrap_table ( $join->table );

			$clauses = array ();

			foreach ( $join->clauses as $clause ) {
				extract ( $clause );

				$column1 = $this->wrap ( $column1 );

				$column2 = $this->wrap ( $column2 );

				$clauses [] = "{$connector} {$column1} {$operator} {$column2}";
			}

			$search = array (
					'AND ',
					'OR '
			);

			$clauses [0] = str_replace ( $search, '', $clauses [0] );

			$clauses = implode ( ' ', $clauses );

			$sql [] = "{$join->type} JOIN {$table} ON {$clauses}";
		}
		return implode ( ' ', $sql );
	}

	/**
	 * Compile the WHERE clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	final protected function wheres(Query $query)
	{
		if (is_null ( $query->wheres ))
			return '';

		foreach ( $query->wheres as $where ) {
			$sql [] = $where ['connector'] . ' ' . $this->{$where ['type']} ( $where );
		}

		if (isset ( $sql )) {

			return 'WHERE ' . preg_replace ( '/AND |OR /', '', implode ( ' ', $sql ), 1 );
		}
	}

	/**
	 * Compile a nested WHERE clause.
	 *
	 * @param array $where
	 * @return string
	 */
	protected function where_nested($where)
	{
		return '(' . substr ( $this->wheres ( $where ['query'] ), 6 ) . ')';
	}

	/**
	 * Compile a simple WHERE clause.
	 *
	 * @param array $where
	 * @return string
	 */
	protected function where($where)
	{
		$parameter = $this->parameter ( $where ['value'] );

		return $this->wrap ( $where ['column'] ) . ' ' . $where ['operator'] . ' ' . $parameter;
	}

	/**
	 * Compile a WHERE IN clause.
	 *
	 * @param array $where
	 * @return string
	 */
	protected function where_in($where)
	{
		$parameters = $this->parameterize ( $where ['values'] );

		return $this->wrap ( $where ['column'] ) . ' IN (' . $parameters . ')';
	}

	/**
	 * Compile a WHERE NOT IN clause.
	 *
	 * @param array $where
	 * @return string
	 */
	protected function where_not_in($where)
	{
		$parameters = $this->parameterize ( $where ['values'] );

		return $this->wrap ( $where ['column'] ) . ' NOT IN (' . $parameters . ')';
	}

	/**
	 * Compile a WHERE BETWEEN clause
	 *
	 * @param array $where
	 * @return string
	 */
	protected function where_between($where)
	{
		$min = $this->parameter ( $where ['min'] );
		$max = $this->parameter ( $where ['max'] );

		return $this->wrap ( $where ['column'] ) . ' BETWEEN ' . $min . ' AND ' . $max;
	}

	/**
	 * Compile a WHERE NOT BETWEEN clause
	 *
	 * @param array $where
	 * @return string
	 */
	protected function where_not_between($where)
	{
		$min = $this->parameter ( $where ['min'] );
		$max = $this->parameter ( $where ['max'] );

		return $this->wrap ( $where ['column'] ) . ' NOT BETWEEN ' . $min . ' AND ' . $max;
	}

	/**
	 * Compile a WHERE NULL clause.
	 *
	 * @param array $where
	 * @return string
	 */
	protected function where_null($where)
	{
		return $this->wrap ( $where ['column'] ) . ' IS NULL';
	}

	/**
	 * Compile a WHERE NULL clause.
	 *
	 * @param array $where
	 * @return string
	 */
	protected function where_not_null($where)
	{
		return $this->wrap ( $where ['column'] ) . ' IS NOT NULL';
	}

	/**
	 * Compile a raw WHERE clause.
	 *
	 * @param array $where
	 * @return string
	 */
	final protected function where_raw($where)
	{
		return $where ['sql'];
	}

	/**
	 * Compile the GROUP BY clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function groupings(Query $query)
	{
		return 'GROUP BY ' . $this->columnize ( $query->groupings );
	}

	/**
	 * Compile the HAVING clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function havings(Query $query)
	{
		if (is_null ( $query->havings ))
			return '';

		foreach ( $query->havings as $having ) {
			$sql [] = 'AND ' . $this->wrap ( $having ['column'] ) . ' ' . $having ['operator'] . ' ' . $this->parameter ( $having ['value'] );
		}

		return 'HAVING ' . preg_replace ( '/AND /', '', implode ( ' ', $sql ), 1 );
	}

	/**
	 * Compile the ORDER BY clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function orderings(Query $query)
	{
		foreach ( $query->orderings as $ordering ) {
			$sql [] = $this->wrap ( $ordering ['column'] ) . ' ' . strtoupper ( $ordering ['direction'] );
		}

		return 'ORDER BY ' . implode ( ', ', $sql );
	}

	/**
	 * Compile the LIMIT clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function limit(Query $query)
	{
		return 'LIMIT ' . $query->limit;
	}

	/**
	 * Compile the OFFSET clause for a query.
	 *
	 * @param Query $query
	 * @return string
	 */
	protected function offset(Query $query)
	{
		return 'OFFSET ' . $query->offset;
	}

	/**
	 * Compile a SQL INSERT statement from a Query instance.
	 *
	 * This method handles the compilation of single row inserts and batch inserts.
	 *
	 * @param Query $query
	 * @param array $values
	 * @return string
	 */
	public function insert(Query $query, $values)
	{
		$table = $this->wrap_table ( $query->from );

		// Force every insert to be treated like a batch insert. This simply makes
		// creating the SQL syntax a little easier on us since we can always treat
		// the values as if it contains multiple inserts.
		if (! is_array ( reset ( $values ) ))
			$values = array (
					$values
			);

			// Since we only care about the column names, we can pass any of the insert
			// arrays into the "columnize" method. The columns should be the same for
			// every record inserted into the table.
		$columns = $this->columnize ( array_keys ( reset ( $values ) ) );

		// Build the list of parameter place-holders of values bound to the query.
		// Each insert should have the same number of bound parameters, so we can
		// just use the first array of values.
		$parameters = $this->parameterize ( reset ( $values ) );

		$parameters = implode ( ', ', array_fill ( 0, count ( $values ), "($parameters)" ) );

		return "INSERT INTO {$table} ({$columns}) VALUES {$parameters}";
	}

	/**
	 * Compile a SQL INSERT and get ID statement from a Query instance.
	 *
	 * @param Query $query
	 * @param array $values
	 * @param string $column
	 * @return string
	 */
	public function insert_get_id(Query $query, $values, $column)
	{
		return $this->insert ( $query, $values );
	}

	/**
	 * Compile a SQL UPDATE statement from a Query instance.
	 *
	 * @param Query $query
	 * @param array $values
	 * @return string
	 */
	public function update(Query $query, $values)
	{
		$table = $this->wrap_table ( $query->from );

		foreach ( $values as $column => $value ) {
			$columns [] = $this->wrap ( $column ) . ' = ' . $this->parameter ( $value );
		}

		$columns = implode ( ', ', $columns );
		return trim ( "UPDATE {$table} SET {$columns} " . $this->wheres ( $query ) );
	}

	/**
	 * Compile a SQL DELETE statement from a Query instance.
	 *
	 * @param Query $query
	 * @return string
	 */
	public function delete(Query $query)
	{
		$table = $this->wrap_table ( $query->from );

		return trim ( "DELETE FROM {$table} " . $this->wheres ( $query ) );
	}

	/**
	 * Transform an SQL short-cuts into real SQL for PDO.
	 *
	 * @param string $sql
	 * @param array $bindings
	 * @return string
	 */
	public function shortcut($sql, &$bindings)
	{
		if (strpos ( $sql, '(...)' ) !== false) {
			for($i = 0; $i < count ( $bindings ); $i ++) {
				if (is_array ( $bindings [$i] )) {
					$parameters = $this->parameterize ( $bindings [$i] );

					array_splice ( $bindings, $i, 1, $bindings [$i] );

					$sql = preg_replace ( '~\(\.\.\.\)~', "({$parameters})", $sql, 1 );
				}
			}
		}

		return trim ( $sql );
	}
}