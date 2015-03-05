<?php
/*
 * +------------------------------------------------------------------------+
 * | Leaps Framework                                                        |
 * +------------------------------------------------------------------------+
 * | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)           |
 * +------------------------------------------------------------------------+
 * | This source file is subject to the Apache License that is bundled      |
 * | with this package in the file docs/LICENSE.txt.                        |
 * |                                                                        |
 * | If you did not receive a copy of the license and are unable to         |
 * | obtain it through the world-wide-web, please send an email             |
 * | to license@tintsoft.com so we can send you a copy immediately.         |
 * +------------------------------------------------------------------------+
 * | Authors: XuTongle <xutongle@gmail.com>                                 |
 * +------------------------------------------------------------------------+
 */

namespace Leaps\Event;

use Leaps\Di\Injectable;

class Dispatcher  extends Injectable
{

	/**
	 * 所有的注册事件。
	 *
	 * @var array
	 */
	public $events = [ ];

	/**
	 * 排队的事件等待清理。
	 *
	 * @var array
	 */
	public $queued = [ ];

	/**
	 * 所有队列事件处理器回调
	 *
	 * @var array
	 */
	public $flushers = [ ];

	/**
	 * 判断事件是否有监听器
	 *
	 * @param string $event
	 * @return bool
	 */
	public function listeners($event)
	{
		return isset ( $this->events [$event] );
	}

	/**
	 * 注册一个事件处理器
	 *
	 * @param string $event 事件名称
	 * @param mixed $callback 回调
	 * @return void
	 */
	public function listen($event, $callback)
	{
		$this->events [$event] [] = $callback;
	}

	/**
	 * 使用指定的回调覆盖事件所有的回调
	 *
	 * @param string $event
	 * @param mixed $callback
	 * @return void
	 */
	public function override($event, $callback)
	{
		$this->clear ( $event );
		$this->listen ( $event, $callback );
	}

	/**
	 * 向事件队列注册一个事件
	 *
	 * @param string $queue
	 * @param string $key
	 * @param mixed $data
	 * @return void
	 */
	public function queue($queue, $key, $data = [])
	{
		$this->queued [$queue] [$key] = $data;
	}

	/**
	 * 注册一个事件处理器
	 *
	 * @param string $queue
	 * @param mixed $callback
	 * @return void
	 */
	public function flusher($queue, $callback)
	{
		$this->flushers [$queue] [] = $callback;
	}

	/**
	 * 清除指定事件的监听器
	 *
	 * @param string $event
	 * @return void
	 */
	public function clear($event)
	{
		unset ( $this->events [$event] );
	}

	/**
	 * 触发一个事件并获取第一条返回值
	 *
	 * @param string $event
	 * @param array $parameters
	 * @return mixed
	 */
	public function first($event, $parameters = [])
	{
		return reset ( $this->trigger ( $event, $parameters ) );
	}

	/**
	 * 触发一个事件，并直到有一个事件监听器返回非null数据为止
	 *
	 * @param string $event 事件名称
	 * @param array $parameters 参数
	 * @return mixed
	 */
	public function until($event, $parameters = [])
	{
		return $this->trigger ( $event, $parameters, true );
	}

	/**
	 * 除掉队列中的所有事件
	 *
	 * @param string $queue
	 * @return void
	 */
	public function flush($queue)
	{
		foreach ( $this->flushers [$queue] as $flusher ) {
			if (! isset ( $this->queued [$queue] )) {
				continue;
			}
			foreach ( $this->queued [$queue] as $key => $payload ) {
				array_unshift ( $payload, $key );
				call_user_func_array ( $flusher, $payload );
			}
		}
	}

	/**
	 * 触发事件
	 *
	 * @param string|array $event
	 * @param array $parameters
	 * @param bool $halt
	 * @return array
	 */
	public function trigger($event, array $parameters = [], $halt = false)
	{
		$responses = [ ];
		if (is_string ( $event )) {
			$events = [ $event ];
		} else {
			$events = $event;
		}
		foreach ( $events as $event ) {
			if ($this->listeners ( $event )) {
				foreach ( $this->events [$event] as $callback ) {
					$response = call_user_func_array ( $callback, $parameters );
					if ($halt && ! is_null ( $response )) {
						return $response;
					}
					$responses [] = $response;
				}
			}
		}
		return $halt ? null : $responses;
	}
}