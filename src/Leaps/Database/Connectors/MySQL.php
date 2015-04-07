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

class MySQL extends Connector
{

	/**
	 * Establish a PDO database connection.
	 *
	 * @param array $config
	 * @return PDO
	 */
	public function connect($config)
	{
		$dsn = "mysql:host={$config['host']};dbname={$config['database']}";
		if (isset ( $config ['port'] )) {
			$dsn .= ";port={$config['port']}";
		}
		if (isset ( $config ['unix_socket'] )) {
			$dsn .= ";unix_socket={$config['unix_socket']}";
		}
		$connection = new PDO ( $dsn, $config ['username'], $config ['password'], $this->options ( $config ) );
		if (isset ( $config ['charset'] )) {
			$connection->prepare ( "SET NAMES '{$config['charset']}'" )->execute ();
		}
		return $connection;
	}
}