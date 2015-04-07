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
namespace Leaps\Database;

use Closure;
use Leaps\Database;
use Leaps\Paginator;
use Leaps\Database\Query\Grammars\Postgres;
use Leaps\Database\Query\Grammars\SQLServer;

class Query
{

	/**
	 * The database connection.
	 *
	 * @var Connection
	 */
	public $connection;

	/**
	 * The query grammar instance.
	 *
	 * @var Query\Grammars\Grammar
	 */
	public $grammar;

	/**
	 * The SELECT clause.
	 *
	 * @var array
	 */
	public $selects;

	/**
	 * The aggregating column and function.
	 *
	 * @var array
	 */
	public $aggregate;

	/**
	 * Indicates if the query should return distinct results.
	 *
	 * @var bool
	 */
	public $distinct = false;

	/**
	 * The table name.
	 *
	 * @var string
	 */
	public $from;

	/**
	 * The table joins.
	 *
	 * @var array
	 */
	public $joins;

	/**
	 * The WHERE clauses.
	 *
	 * @var array
	 */
	public $wheres;

	/**
	 * The GROUP BY clauses.
	 *
	 * @var array
	 */
	public $groupings;

	/**
	 * The HAVING clauses.
	 *
	 * @var array
	 */
	public $havings;

	/**
	 * The ORDER BY clauses.
	 *
	 * @var array
	 */
	public $orderings;

	/**
	 * The LIMIT value.
	 *
	 * @var int
	 */
	public $limit;

	/**
	 * The OFFSET value.
	 *
	 * @var int
	 */
	public $offset;

	/**
	 * The query value bindings.
	 *
	 * @var array
	 */
	public $bindings = [ ];

	/**
	 * Create a new query instance.
	 *
	 * @param Connection $connection
	 * @param Grammar $grammar
	 * @param string $table
	 * @return void
	 */
	public function __construct(Connection $connection, Query\Grammars\Grammar $grammar, $table)
	{
		$this->from = $table;
		$this->grammar = $grammar;
		$this->connection = $connection;
	}

	/**
	 * Force the query to return distinct results.
	 *
	 * @return Query
	 */
	public function distinct()
	{
		$this->distinct = true;
		return $this;
	}

	/**
	 * Add an array of columns to the SELECT clause.
	 *
	 * @param array $columns
	 * @return Query
	 */
	public function select($columns = array('*'))
	{
		$this->selects = ( array ) $columns;
		return $this;
	}

	/**
	 * Add a join clause to the query.
	 *
	 * @param string $table
	 * @param string $column1
	 * @param string $operator
	 * @param string $column2
	 * @param string $type
	 * @return Query
	 */
	public function join($table, $column1, $operator = null, $column2 = null, $type = 'INNER')
	{
		if ($column1 instanceof Closure) {
			$this->joins [] = new Query\Join ( $type, $table );

			call_user_func ( $column1, end ( $this->joins ) );
		} else {
			$join = new Query\Join ( $type, $table );

			$join->on ( $column1, $operator, $column2 );

			$this->joins [] = $join;
		}

		return $this;
	}

	/**
	 * Add a left join to the query.
	 *
	 * @param string $table
	 * @param string $column1
	 * @param string $operator
	 * @param string $column2
	 * @return Query
	 */
	public function left_join($table, $column1, $operator = null, $column2 = null)
	{
		return $this->join ( $table, $column1, $operator, $column2, 'LEFT' );
	}

	/**
	 * Reset the where clause to its initial state.
	 *
	 * @return void
	 */
	public function reset_where()
	{
		list ( $this->wheres, $this->bindings ) = array (
				[ ],
				[ ]
		);
	}

	/**
	 * Add a raw where condition to the query.
	 *
	 * @param string $where
	 * @param array $bindings
	 * @param string $connector
	 * @return Query
	 */
	public function raw_where($where, $bindings = [], $connector = 'AND')
	{
		$this->wheres [] = [
				'type' => 'where_raw',
				'connector' => $connector,
				'sql' => $where
		];

		$this->bindings = array_merge ( $this->bindings, $bindings );

		return $this;
	}

	/**
	 * Add a raw or where condition to the query.
	 *
	 * @param string $where
	 * @param array $bindings
	 * @return Query
	 */
	public function raw_or_where($where, $bindings = [])
	{
		return $this->raw_where ( $where, $bindings, 'OR' );
	}

	/**
	 * Add a where condition to the query.
	 *
	 * @param string $column
	 * @param string $operator
	 * @param mixed $value
	 * @param string $connector
	 * @return Query
	 */
	public function where($column, $operator = null, $value = null, $connector = 'AND')
	{
		if ($column instanceof Closure) {
			return $this->where_nested ( $column, $connector );
		}
		$type = 'where';
		$this->wheres [] = compact ( 'type', 'column', 'operator', 'value', 'connector' );
		$this->bindings [] = $value;
		return $this;
	}

	/**
	 * Add an or where condition to the query.
	 *
	 * @param string $column
	 * @param string $operator
	 * @param mixed $value
	 * @return Query
	 */
	public function or_where($column, $operator = null, $value = null)
	{
		return $this->where ( $column, $operator, $value, 'OR' );
	}

	/**
	 * Add an or where condition for the primary key to the query.
	 *
	 * @param mixed $value
	 * @return Query
	 */
	public function or_where_id($value)
	{
		return $this->or_where ( 'id', '=', $value );
	}

	/**
	 * Add a where in condition to the query.
	 *
	 * @param string $column
	 * @param array $values
	 * @param string $connector
	 * @param bool $not
	 * @return Query
	 */
	public function where_in($column, $values, $connector = 'AND', $not = false)
	{
		$type = ($not) ? 'where_not_in' : 'where_in';
		$this->wheres [] = compact ( 'type', 'column', 'values', 'connector' );
		$this->bindings = array_merge ( $this->bindings, $values );
		return $this;
	}

	/**
	 * Add an or where in condition to the query.
	 *
	 * @param string $column
	 * @param array $values
	 * @return Query
	 */
	public function or_where_in($column, $values)
	{
		return $this->where_in ( $column, $values, 'OR' );
	}

	/**
	 * Add a where not in condition to the query.
	 *
	 * @param string $column
	 * @param array $values
	 * @param string $connector
	 * @return Query
	 */
	public function where_not_in($column, $values, $connector = 'AND')
	{
		return $this->where_in ( $column, $values, $connector, true );
	}

	/**
	 * Add an or where not in condition to the query.
	 *
	 * @param string $column
	 * @param array $values
	 * @return Query
	 */
	public function or_where_not_in($column, $values)
	{
		return $this->where_not_in ( $column, $values, 'OR' );
	}

	/**
	 * Add a BETWEEN condition to the query
	 *
	 * @param string $column
	 * @param mixed $min
	 * @param mixed $max
	 * @param string $connector
	 * @param boolean $not
	 * @return Query
	 */
	public function where_between($column, $min, $max, $connector = 'AND', $not = false)
	{
		$type = ($not) ? 'where_not_between' : 'where_between';
		$this->wheres [] = compact ( 'type', 'column', 'min', 'max', 'connector' );
		$this->bindings [] = $min;
		$this->bindings [] = $max;
		return $this;
	}

	/**
	 * Add a OR BETWEEN condition to the query
	 *
	 * @param string $column
	 * @param mixed $min
	 * @param mixed $max
	 * @return Query
	 */
	public function or_where_between($column, $min, $max)
	{
		return $this->where_between ( $column, $min, $max, 'OR' );
	}

	/**
	 * Add a NOT BETWEEN condition to the query
	 *
	 * @param string $column
	 * @param mixed $min
	 * @param mixed $max
	 * @return Query
	 */
	public function where_not_between($column, $min, $max, $connector = 'AND')
	{
		return $this->where_between ( $column, $min, $max, $connector, true );
	}

	/**
	 * Add a OR NOT BETWEEN condition to the query
	 *
	 * @param string $column
	 * @param mixed $min
	 * @param mixed $max
	 * @return Query
	 */
	public function or_where_not_between($column, $min, $max)
	{
		return $this->where_not_between ( $column, $min, $max, 'OR' );
	}

	/**
	 * Add a where null condition to the query.
	 *
	 * @param string $column
	 * @param string $connector
	 * @param bool $not
	 * @return Query
	 */
	public function where_null($column, $connector = 'AND', $not = false)
	{
		$type = ($not) ? 'where_not_null' : 'where_null';

		$this->wheres [] = compact ( 'type', 'column', 'connector' );

		return $this;
	}

	/**
	 * Add an or where null condition to the query.
	 *
	 * @param string $column
	 * @return Query
	 */
	public function or_where_null($column)
	{
		return $this->where_null ( $column, 'OR' );
	}

	/**
	 * Add a where not null condition to the query.
	 *
	 * @param string $column
	 * @param string $connector
	 * @return Query
	 */
	public function where_not_null($column, $connector = 'AND')
	{
		return $this->where_null ( $column, $connector, true );
	}

	/**
	 * Add an or where not null condition to the query.
	 *
	 * @param string $column
	 * @return Query
	 */
	public function or_where_not_null($column)
	{
		return $this->where_not_null ( $column, 'OR' );
	}

	/**
	 * Add a nested where condition to the query.
	 *
	 * @param Closure $callback
	 * @param string $connector
	 * @return Query
	 */
	public function where_nested($callback, $connector = 'AND')
	{
		$type = 'where_nested';
		$query = new Query ( $this->connection, $this->grammar, $this->from );
		call_user_func ( $callback, $query );
		if ($query->wheres !== null) {
			$this->wheres [] = compact ( 'type', 'query', 'connector' );
		}
		$this->bindings = array_merge ( $this->bindings, $query->bindings );
		return $this;
	}

	/**
	 * Add dynamic where conditions to the query.
	 *
	 * @param string $method
	 * @param array $parameters
	 * @return Query
	 */
	private function dynamic_where($method, $parameters)
	{
		$finder = substr ( $method, 6 );
		$flags = PREG_SPLIT_DELIM_CAPTURE;
		$segments = preg_split ( '/(_and_|_or_)/i', $finder, - 1, $flags );
		$connector = 'AND';
		$index = 0;
		foreach ( $segments as $segment ) {
			if ($segment != '_and_' and $segment != '_or_') {
				$this->where ( $segment, '=', $parameters [$index], $connector );

				$index ++;
			} else {
				$connector = trim ( strtoupper ( $segment ), '_' );
			}
		}

		return $this;
	}

	/**
	 * Add a grouping to the query.
	 *
	 * @param string $column
	 * @return Query
	 */
	public function group_by($column)
	{
		$this->groupings [] = $column;
		return $this;
	}

	/**
	 * Add a having to the query.
	 *
	 * @param string $column
	 * @param string $operator
	 * @param mixed $value
	 */
	public function having($column, $operator, $value)
	{
		$this->havings [] = compact ( 'column', 'operator', 'value' );
		$this->bindings [] = $value;
		return $this;
	}

	/**
	 * Add an ordering to the query.
	 *
	 * @param string $column
	 * @param string $direction
	 * @return Query
	 */
	public function order_by($column, $direction = 'asc')
	{
		$this->orderings [] = compact ( 'column', 'direction' );
		return $this;
	}

	/**
	 * Set the query offset.
	 *
	 * @param int $value
	 * @return Query
	 */
	public function skip($value)
	{
		$this->offset = $value;
		return $this;
	}

	/**
	 * Set the query limit.
	 *
	 * @param int $value
	 * @return Query
	 */
	public function take($value)
	{
		$this->limit = $value;
		return $this;
	}

	/**
	 * Set the query limit and offset for a given page.
	 *
	 * @param int $page
	 * @param int $per_page
	 * @return Query
	 */
	public function for_page($page, $per_page)
	{
		return $this->skip ( ($page - 1) * $per_page )->take ( $per_page );
	}

	/**
	 * Find a record by the primary key.
	 *
	 * @param int $id
	 * @param array $columns
	 * @return object
	 */
	public function find($id, $columns = array('*'))
	{
		return $this->where ( 'id', '=', $id )->first ( $columns );
	}

	/**
	 * Execute the query as a SELECT statement and return a single column.
	 *
	 * @param string $column
	 * @return mixed
	 */
	public function only($column)
	{
		$sql = $this->grammar->select ( $this->select ( array (
				$column
		) ) );
		return $this->connection->only ( $sql, $this->bindings );
	}

	/**
	 * Execute the query as a SELECT statement and return the first result.
	 *
	 * @param array $columns
	 * @return mixed
	 */
	public function first($columns = array('*'))
	{
		$columns = ( array ) $columns;
		$results = $this->take ( 1 )->get ( $columns );
		return (count ( $results ) > 0) ? $results [0] : null;
	}

	/**
	 * Get an array with the values of a given column.
	 *
	 * @param string $column
	 * @param string $key
	 * @return array
	 */
	public function lists($column, $key = null)
	{
		$columns = (is_null ( $key )) ? [
				$column
		] : [
				$column,
				$key
		];

		$results = $this->get ( $columns );
		$values = array_map ( function ($row) use($column)
		{
			return $row->$column;
		}, $results );
		if (! is_null ( $key ) && count ( $results )) {
			return array_combine ( array_map ( function ($row) use($key)
			{
				return $row->$key;
			}, $results ), $values );
		}

		return $values;
	}

	/**
	 * Execute the query as a SELECT statement.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function get($columns = ['*'])
	{
		if (is_null ( $this->selects ))
			$this->select ( $columns );
		$sql = $this->grammar->select ( $this );
		$results = $this->connection->query ( $sql, $this->bindings );
		if ($this->offset > 0 and $this->grammar instanceof SQLServer) {
			array_walk ( $results, function ($result)
			{
				unset ( $result->rownum );
			} );
		}
		$this->selects = null;
		return $results;
	}

	/**
	 * Get an aggregate value.
	 *
	 * @param string $aggregator
	 * @param array $columns
	 * @return mixed
	 */
	public function aggregate($aggregator, $columns)
	{
		$this->aggregate = compact ( 'aggregator', 'columns' );
		$sql = $this->grammar->select ( $this );
		$result = $this->connection->only ( $sql, $this->bindings );
		$this->aggregate = null;
		return $result;
	}

	/**
	 * Get the paginated query results as a Paginator instance.
	 *
	 * @param int $per_page
	 * @param array $columns
	 * @return Paginator
	 */
	public function paginate($per_page = 20, $columns = array('*'))
	{
		list ( $orderings, $this->orderings ) = array (
				$this->orderings,
				null
		);
		$total = $this->count ( reset ( $columns ) );
		$page = Paginator::page ( $total, $per_page );
		$this->orderings = $orderings;
		$results = $this->for_page ( $page, $per_page )->get ( $columns );
		return Paginator::make ( $results, $total, $per_page );
	}

	/**
	 * Insert an array of values into the database table.
	 *
	 * @param array $values
	 * @return bool
	 */
	public function insert($values)
	{
		if (! is_array ( reset ( $values ) ))
			$values = [
					$values
			];

		$bindings = [ ];
		foreach ( $values as $value ) {
			$bindings = array_merge ( $bindings, array_values ( $value ) );
		}

		$sql = $this->grammar->insert ( $this, $values );

		return $this->connection->query ( $sql, $bindings );
	}

	/**
	 * Insert an array of values into the database table and return the key.
	 *
	 * @param array $values
	 * @param string $column
	 * @return mixed
	 */
	public function insert_get_id($values, $column = 'id')
	{
		$sql = $this->grammar->insert_get_id ( $this, $values, $column );
		$result = $this->connection->query ( $sql, array_values ( $values ) );
		if (isset ( $values [$column] )) {
			return $values [$column];
		} else if ($this->grammar instanceof Postgres) {
			$row = ( array ) $result [0];

			return ( int ) $row [$column];
		} else {
			return ( int ) $this->connection->pdo->lastInsertId ();
		}
	}

	/**
	 * Increment the value of a column by a given amount.
	 *
	 * @param string $column
	 * @param int $amount
	 * @return int
	 */
	public function increment($column, $amount = 1)
	{
		return $this->adjust ( $column, $amount, ' + ' );
	}

	/**
	 * Decrement the value of a column by a given amount.
	 *
	 * @param string $column
	 * @param int $amount
	 * @return int
	 */
	public function decrement($column, $amount = 1)
	{
		return $this->adjust ( $column, $amount, ' - ' );
	}

	/**
	 * Adjust the value of a column up or down by a given amount.
	 *
	 * @param string $column
	 * @param int $amount
	 * @param string $operator
	 * @return int
	 */
	protected function adjust($column, $amount, $operator)
	{
		$wrapped = $this->grammar->wrap ( $column );
		$value = Database::raw ( $wrapped . $operator . $amount );
		return $this->update ( array (
				$column => $value
		) );
	}

	/**
	 * Update an array of values in the database table.
	 *
	 * @param array $values
	 * @return int
	 */
	public function update($values)
	{
		$bindings = array_merge ( array_values ( $values ), $this->bindings );
		$sql = $this->grammar->update ( $this, $values );
		return $this->connection->query ( $sql, $bindings );
	}

	/**
	 * Execute the query as a DELETE statement.
	 *
	 * Optionally, an ID may be passed to the method do delete a specific row.
	 *
	 * @param int $id
	 * @return int
	 */
	public function delete($id = null)
	{
		if (! is_null ( $id )) {
			$this->where ( 'id', '=', $id );
		}
		$sql = $this->grammar->delete ( $this );
		return $this->connection->query ( $sql, $this->bindings );
	}

	/**
	 * Magic Method for handling dynamic functions.
	 *
	 * This method handles calls to aggregates as well as dynamic where clauses.
	 */
	public function __call($method, $parameters)
	{
		if (strpos ( $method, 'where_' ) === 0) {
			return $this->dynamic_where ( $method, $parameters, $this );
		}
		if (in_array ( $method, [
				'count',
				'min',
				'max',
				'avg',
				'sum'
		] )) {
			if (count ( $parameters ) == 0)
				$parameters [0] = '*';

			return $this->aggregate ( strtoupper ( $method ), ( array ) $parameters [0] );
		}

		throw new \Exception ( "Method [$method] is not defined on the Query class." );
	}
}
