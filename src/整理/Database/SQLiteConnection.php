<?php
namespace Leaps\Database;

class SQLiteConnection extends Connection {

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Database\Connection::getDefaultQueryGrammar()
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new Query\Grammars\SQLiteGrammar);
	}

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Database\Connection::getDefaultSchemaGrammar()
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new Schema\Grammars\SQLiteGrammar);
	}

	/**
	 *
	 * @return \Doctrine\DBAL\Driver\PDOSqlite\Driver
	 */
	protected function getDoctrineDriver()
	{
		return new \Doctrine\DBAL\Driver\PDOSqlite\Driver;
	}

}