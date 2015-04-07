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

class SQLServer extends Connector
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
		$port = (isset ( $port )) ? ',' . $port : '';
		if (in_array ( 'dblib', PDO::getAvailableDrivers () )) {
			$dsn = "dblib:host={$config['host']}{$config['port']};dbname={$config['database']}";
		} else {
			$dsn = "sqlsrv:Server={$config['host']}{$config['port']};Database={$config['database']}";
		}
		return new PDO ( $dsn, $config ['username'], $config ['password'], $this->options ( $config ) );
	}
}