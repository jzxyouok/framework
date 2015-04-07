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

use PDO, PDOStatement, Laravel\Config, Laravel\Event;

class Connection
{

	/**
	 * The raw PDO connection instance.
	 *
	 * @var PDO
	 */
	public $pdo;

	/**
	 * The connection configuration array.
	 *
	 * @var array
	 */
	public $config;

	/**
	 * The query grammar instance for the connection.
	 *
	 * @var Query\Grammars\Grammar
	 */
	protected $grammar;

	/**
	 * All of the queries that have been executed on all connections.
	 *
	 * @var array
	 */
	public static $queries = array ();

	/**
	 * Create a new database connection instance.
	 *
	 * @param PDO $pdo
	 * @param array $config
	 * @return void
	 */
	public function __construct(PDO $pdo, $config)
	{
		$this->pdo = $pdo;
		$this->config = $config;
	}

	/**
	 * Begin a fluent query against a table.
	 *
	 * <code>
	 * // Start a fluent query against the "users" table
	 * $query = DB::connection()->table('users');
	 *
	 * // Start a fluent query against the "users" table and get all the users
	 * $users = DB::connection()->table('users')->get();
	 * </code>
	 *
	 * @param string $table
	 * @return Query
	 */
	public function table($table)
	{
		return new Query ( $this, $this->grammar (), $table );
	}

	/**
	 * Create a new query grammar for the connection.
	 *
	 * @return Query\Grammars\Grammar
	 */
	protected function grammar()
	{
		if (isset ( $this->grammar ))
			return $this->grammar;

		if (isset ( \Laravel\Database::$registrar [$this->driver ()] )) {
			return $this->grammar = \Laravel\Database::$registrar [$this->driver ()] ['query'] ();
		}

		switch ($this->driver ()) {
			case 'mysql' :
				return $this->grammar = new Query\Grammars\MySQL ( $this );

			case 'sqlite' :
				return $this->grammar = new Query\Grammars\SQLite ( $this );

			case 'sqlsrv' :
				return $this->grammar = new Query\Grammars\SQLServer ( $this );

			case 'pgsql' :
				return $this->grammar = new Query\Grammars\Postgres ( $this );

			default :
				return $this->grammar = new Query\Grammars\Grammar ( $this );
		}
	}

	/**
	 * 执行数据库事务
	 *
	 * @param callback $callback
	 * @return bool
	 */
	public function transaction($callback)
	{
		$this->pdo->beginTransaction ();
		try {
			call_user_func ( $callback );
		} catch ( \Exception $e ) {
			$this->pdo->rollBack ();

			throw $e;
		}

		return $this->pdo->commit ();
	}

	/**
	 * Execute a SQL query against the connection and return a single column result.
	 *
	 * <code>
	 * // Get the total number of rows on a table
	 * $count = DB::connection()->only('select count(*) from users');
	 *
	 * // Get the sum of payment amounts from a table
	 * $sum = DB::connection()->only('select sum(amount) from payments')
	 * </code>
	 *
	 * @param string $sql
	 * @param array $bindings
	 * @return mixed
	 */
	public function only($sql, array $bindings = [])
	{
		$results = ( array ) $this->first ( $sql, $bindings );
		return reset ( $results );
	}

	/**
	 * Execute a SQL query against the connection and return the first result.
	 *
	 * <code>
	 * // Execute a query against the database connection
	 * $user = DB::connection()->first('select * from users');
	 *
	 * // Execute a query with bound parameters
	 * $user = DB::connection()->first('select * from users where id = ?', array($id));
	 * </code>
	 *
	 * @param string $sql
	 * @param array $bindings
	 * @return object
	 */
	public function first($sql, array $bindings = [])
	{
		if (count ( $results = $this->query ( $sql, $bindings ) ) > 0) {
			return $results [0];
		}
	}

	/**
	 * Execute a SQL query and return an array of StdClass objects.
	 *
	 * @param string $sql
	 * @param array $bindings
	 * @return array
	 */
	public function query($sql, $bindings = array())
	{
		$sql = trim ( $sql );
		list ( $statement, $result ) = $this->execute ( $sql, $bindings );
		if (stripos ( $sql, 'select' ) === 0 || stripos ( $sql, 'show' ) === 0) {
			return $this->fetch ( $statement, Config::get ( 'database.fetch' ) );
		} elseif (stripos ( $sql, 'update' ) === 0 or stripos ( $sql, 'delete' ) === 0) {
			return $statement->rowCount ();
		} elseif (stripos ( $sql, 'insert' ) === 0 and stripos ( $sql, 'returning' ) !== false) {
			return $this->fetch ( $statement, Config::get ( 'database.fetch' ) );
		} else {
			return $result;
		}
	}

	/**
	 * 执行Mysql查询
	 *
	 * The PDO statement and boolean result will be returned in an array.
	 *
	 * @param string $sql
	 * @param array $bindings
	 * @return array
	 */
	protected function execute($sql, $bindings = array())
	{
		$bindings = ( array ) $bindings;
		$bindings = array_filter ( $bindings, function ($binding)
		{
			return ! $binding instanceof Expression;
		} );
		$bindings = array_values ( $bindings );
		$sql = $this->grammar ()->shortcut ( $sql, $bindings );
		$datetime = $this->grammar ()->datetime;
		for($i = 0; $i < count ( $bindings ); $i ++) {
			if ($bindings [$i] instanceof \DateTime) {
				$bindings [$i] = $bindings [$i]->format ( $datetime );
			}
		}
		try {
			$statement = $this->pdo->prepare ( $sql );

			$start = microtime ( true );

			$result = $statement->execute ( $bindings );
		} catch ( \Exception $exception ) {
			$exception = new Exception ( $sql, $bindings, $exception );

			throw $exception;
		}

		if (Config::get ( 'database.profile' )) {
			$this->log ( $sql, $bindings, $start );
		}

		return array (
				$statement,
				$result
		);
	}

	/**
	 * Fetch all of the rows for a given statement.
	 *
	 * @param PDOStatement $statement
	 * @param int $style
	 * @return array
	 */
	protected function fetch($statement, $style)
	{
		if ($style === PDO::FETCH_CLASS) {
			return $statement->fetchAll ( PDO::FETCH_CLASS, 'stdClass' );
		} else {
			return $statement->fetchAll ( $style );
		}
	}

	/**
	 * Log the query and fire the core query event.
	 *
	 * @param string $sql
	 * @param array $bindings
	 * @param int $start
	 * @return void
	 */
	protected function log($sql, $bindings, $start)
	{
		$time = number_format ( (microtime ( true ) - $start) * 1000, 2 );
		Event::fire ( 'leaps.query', array (
				$sql,
				$bindings,
				$time
		) );
		static::$queries [] = compact ( 'sql', 'bindings', 'time' );
	}

	/**
	 * Get the driver name for the database connection.
	 *
	 * @return string
	 */
	public function driver()
	{
		return $this->config ['driver'];
	}

	/**
	 * Magic Method for dynamically beginning queries on database tables.
	 */
	public function __call($method, $parameters)
	{
		return $this->table ( $method );
	}
}
