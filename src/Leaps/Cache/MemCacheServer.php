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
namespace Leaps\Cache;

class MemCacheServer extends \Leaps\Core\Base
{
	/**
	 * memcache服务器域名或IP地址
	 * @var string
	 */
	public $host;

	/**
	 * memcache服务器端口
	 * @var integer
	 */
	public $port = 11211;

	/**
	 * 服务器权重（越大使用概率越高 ）
	 * @var integer
	 */
	public $weight = 1;

	/**
	 * 是否使用持久连接。
	 * @var boolean
	 */
	public $persistent = true;

	/**
	 * 连接超时时间（毫秒）
	 * @var integer
	 */
	public $timeout = 1000;

	/**
	 * 失败重试时间（秒）
	 * @var integer
	 */
	public $retryInterval = 15;

	/**
	 * 标记服务器状态
	 * @var boolean
	 */
	public $status = true;

	/**
	 * 失败时的回调
	 * @var \Closure
	 */
	public $failureCallback;
}