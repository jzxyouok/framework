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
namespace Leaps\Database\Connectors;

use PDO;

class Postgres extends Connector
{

	/**
	 * The PDO connection options.
	 *
	 * @var array
	 */
	protected $options = [
			PDO::ATTR_CASE => PDO::CASE_LOWER,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
			PDO::ATTR_STRINGIFY_FETCHES => false
	];

	/**
	 * Establish a PDO database connection.
	 *
	 * @param array $config
	 * @return PDO
	 */
	public function connect($config)
	{
		$hostDsn = isset ( $host ) ? 'host=' . $config ['host'] . ';' : '';
		$dsn = "pgsql:{$hostDsn}dbname={$config['database']}";
		if (isset ( $config ['port'] )) {
			$dsn .= ";port={$config['port']}";
		}
		$connection = new PDO ( $dsn, $config ['username'], $config ['password'], $this->options ( $config ) );

		if (isset ( $config ['charset'] )) {
			$connection->prepare ( "SET NAMES '{$config['charset']}'" )->execute ();
		}
		if (isset ( $config ['schema'] )) {
			$connection->prepare ( "SET search_path TO {$config['schema']}" )->execute ();
		}
		return $connection;
	}
}