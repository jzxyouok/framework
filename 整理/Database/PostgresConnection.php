<?php
namespace Leaps\Database;

class PostgresConnection extends Connection {

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Database\Connection::getDefaultQueryGrammar()
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new Query\Grammars\PostgresGrammar);
	}

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Database\Connection::getDefaultSchemaGrammar()
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new Schema\Grammars\PostgresGrammar);
	}

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Database\Connection::getDefaultPostProcessor()
	 */
	protected function getDefaultPostProcessor()
	{
		return new Query\Processors\PostgresProcessor;
	}

	/**
	 *
	 * @return \Doctrine\DBAL\Driver\PDOPgSql\Driver
	 */
	protected function getDoctrineDriver()
	{
		return new \Doctrine\DBAL\Driver\PDOPgSql\Driver;
	}

}