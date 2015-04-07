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

class SQLite extends Connector
{

	/**
	 * Establish a PDO database connection.
	 *
	 * @param array $config
	 * @return PDO
	 */
	public function connect($config)
	{
		$options = $this->options ( $config );
		if ($config ['database'] == ':memory:') {
			return new PDO ( 'sqlite::memory:', null, null, $options );
		}

		$path = path ( 'storage' ) . 'database' . DS . $config ['database'] . '.sqlite';

		return new PDO ( 'sqlite:' . $path, null, null, $options );
	}
}
