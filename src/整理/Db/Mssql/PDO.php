<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2013 TintSoft LLC
 * @license http://www.tintsoft.com/license/
 */
namespace Leaps\Db\Mssql;

/**
 * This is an extension of the default PDO class of MSSQL and DBLIB drivers.
 * It provides workarounds for improperly implemented functionalities of the MSSQL and DBLIB drivers.
 *
 * @author Timur Ruziev <resurtm@gmail.com>
 * @since 2.0
 */
class PDO extends \PDO
{
	/**
	 * Returns value of the last inserted ID.
	 * @param string|null $sequence the sequence name. Defaults to null.
	 * @return integer last inserted ID value.
	 */
	public function lastInsertId($sequence = null)
	{
		return $this->query('SELECT CAST(COALESCE(SCOPE_IDENTITY(), @@IDENTITY) AS bigint)')->fetchColumn();
	}

	/**
	 * Starts a transaction. It is necessary to override PDO's method as MSSQL PDO driver does not
	 * natively support transactions.
	 * @return boolean the result of a transaction start.
	 */
	public function beginTransaction()
	{
		$this->exec('BEGIN TRANSACTION');
		return true;
	}

	/**
	 * Commits a transaction. It is necessary to override PDO's method as MSSQL PDO driver does not
	 * natively support transactions.
	 * @return boolean the result of a transaction commit.
	 */
	public function commit()
	{
		$this->exec('COMMIT TRANSACTION');
		return true;
	}

	/**
	 * Rollbacks a transaction. It is necessary to override PDO's method as MSSQL PDO driver does not
	 * natively support transactions.
	 * @return boolean the result of a transaction rollback.
	 */
	public function rollBack()
	{
		$this->exec('ROLLBACK TRANSACTION');
		return true;
	}
}
