<?php
namespace Leaps\Database\Schema;
class MySqlBuilder extends Builder {

	/**
	 * 判断表是否存在
	 *
	 * @param  string  $table
	 * @return bool
	 */
	public function hasTable($table)
	{
		$sql = $this->grammar->compileTableExists();
		$database = $this->connection->getDatabaseName();
		return count($this->connection->select($sql, array($database, $table))) > 0;
	}

}