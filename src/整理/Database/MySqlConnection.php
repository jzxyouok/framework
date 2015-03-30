<?php
namespace Leaps\Database;

class MySqlConnection extends Connection {

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Database\Connection::getSchemaBuilder()
	 */
	public function getSchemaBuilder()
	{
		if (is_null($this->schemaGrammar)) { $this->useDefaultSchemaGrammar(); }

		return new Schema\MySqlBuilder($this);
	}

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Database\Connection::getDefaultQueryGrammar()
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new Query\Grammars\MySqlGrammar);
	}

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Database\Connection::getDefaultSchemaGrammar()
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new Schema\Grammars\MySqlGrammar);
	}

	/**
	 * Get the Doctrine DBAL Driver.
	 *
	 * @return \Doctrine\DBAL\Driver
	 */
	protected function getDoctrineDriver()
	{
		return new \Doctrine\DBAL\Driver\PDOMySql\Driver;
	}

}