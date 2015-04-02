<?php
namespace Leaps\Database;
use Closure;

interface ConnectionInterface {

	/**
	 * 运行一个select语句返回一个结果。
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return mixed
	 */
	public function selectOne($query, $bindings = array());

	/**
	 * 运行一个select。
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return array
	 */
	public function select($query, $bindings = array());

	/**
	 * 运行一个insert语句
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return bool
	 */
	public function insert($query, $bindings = array());

	/**
	 * 运行一个更新语句
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function update($query, $bindings = array());

	/**
	 * 运行一个delete语句
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function delete($query, $bindings = array());

	/**
	 * 执行一条SQL语句,返回布尔结果。
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return bool
	 */
	public function statement($query, $bindings = array());

	/**
	 * 在一个事务中执行一个闭包。
	 *
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function transaction(Closure $callback);

}