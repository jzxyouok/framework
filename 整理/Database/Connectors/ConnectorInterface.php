<?php
namespace Leaps\Database\Connectors;

interface ConnectorInterface {

	/**
	 * 建立一个数据库连接。
	 *
	 * @param  array  $config
	 * @return PDO
	 */
	public function connect(array $config);

}