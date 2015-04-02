<?php
namespace Leaps\Database;

interface ConnectionResolverInterface {

	/**
	 * 获取数据库连接实例
	 *
	 * @param  string  $name
	 * @return \Leaps\Database\Connection
	 */
	public function connection($name = null);

	/**
	 * 获取默认连接的名字。
	 *
	 * @return string
	 */
	public function getDefaultConnection();

	/**
	 * 设置默认连接的名字。
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultConnection($name);

}