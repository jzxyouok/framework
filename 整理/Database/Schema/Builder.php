<?php
namespace Leaps\Database\Schema;

use Closure;
use Leaps\Database\Connection;


class Builder {

	/**
	 * 数据库连接实例
	 *
	 * @var \Leaps\Database\Connection
	 */
	protected $connection;

	/**
	 * 语法的模式实例
	 *
	 * @var \Leaps\Database\Schema\Grammars\Grammar
	 */
	protected $grammar;

	/**
	 * Create a new database Schema manager.
	 *
	 * @param  \Leaps\Database\Connection  $connection
	 * @return void
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->grammar = $connection->getSchemaGrammar();
	}

	/**
	 * Determine if the given table exists.
	 *
	 * @param  string  $table
	 * @return bool
	 */
	public function hasTable($table)
	{
		$sql = $this->grammar->compileTableExists();
		$table = $this->connection->getTablePrefix().$table;
		return count($this->connection->select($sql, array($table))) > 0;
	}

	/**
	 * Determine if the given table has a given column.
	 *
	 * @param  string  $table
	 * @param  string  $column
	 * @return bool
	 */
	public function hasColumn($table, $column)
	{
		$schema = $this->connection->getDoctrineSchemaManager();
		return in_array($column, array_keys($schema->listTableColumns($table)));
	}

	/**
	 * Modify a table on the schema.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return \Leaps\Database\Schema\Blueprint
	 */
	public function table($table, Closure $callback)
	{
		$this->build($this->createBlueprint($table, $callback));
	}

	/**
	 * Create a new table on the schema.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return \Leaps\Database\Schema\Blueprint
	 */
	public function create($table, Closure $callback)
	{
		$blueprint = $this->createBlueprint($table);
		$blueprint->create();
		$callback($blueprint);
		$this->build($blueprint);
	}

	/**
	 * Drop a table from the schema.
	 *
	 * @param  string  $table
	 * @return \Leaps\Database\Schema\Blueprint
	 */
	public function drop($table)
	{
		$blueprint = $this->createBlueprint($table);
		$blueprint->drop();
		$this->build($blueprint);
	}

	/**
	 * Drop a table from the schema if it exists.
	 *
	 * @param  string  $table
	 * @return \Leaps\Database\Schema\Blueprint
	 */
	public function dropIfExists($table)
	{
		$blueprint = $this->createBlueprint($table);
		$blueprint->dropIfExists();
		$this->build($blueprint);
	}

	/**
	 * Rename a table on the schema.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return \Leaps\Database\Schema\Blueprint
	 */
	public function rename($from, $to)
	{
		$blueprint = $this->createBlueprint($from);
		$blueprint->rename($to);
		$this->build($blueprint);
	}

	/**
	 * Execute the blueprint to build / modify the table.
	 *
	 * @param  \Leaps\Database\Schema\Blueprint  $blueprint
	 * @return void
	 */
	protected function build(Blueprint $blueprint)
	{
		$blueprint->build($this->connection, $this->grammar);
	}

	/**
	 * Create a new command set with a Closure.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return \Leaps\Database\Schema\Blueprint
	 */
	protected function createBlueprint($table, Closure $callback = null)
	{
		return new Blueprint($table, $callback);
	}

	/**
	 * Get the database connection instance.
	 *
	 * @return \Leaps\Database\Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Set the database connection instance.
	 *
	 * @param  \Leaps\Database\Connection
	 * @return \Leaps\Database\Schema\Builder
	 */
	public function setConnection(Connection $connection)
	{
		$this->connection = $connection;
		return $this;
	}

}