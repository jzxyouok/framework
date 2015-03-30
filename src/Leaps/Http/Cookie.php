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
namespace Leaps\Http;

class Cookie extends \Leaps\Core\Base
{
	/**
	 * Cookie名称
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Cookie值
	 *
	 * @var string
	 */
	public $value = '';

	/**
	 * Cookie 作用域
	 *
	 * @var string
	 */
	public $domain = '';

	/**
	 * Cookie过期时间戳 0为浏览器进程
	 *
	 * @var integer
	 */
	public $expire = 0;

	/**
	 * Cookie 作用路径
	 *
	 * @var string
	 */
	public $path = '/';

	/**
	 * 是否应通过安全连接发送cookie
	 *
	 * @var boolean
	 */
	public $secure = false;

	/**
	 * Cookie是否只能通过HTTP协议读取
	 *
	 * @var boolean
	 */
	public $httpOnly = true;

	/**
	 * 发送Cookie到客户端
	 */
	public function send()
	{
		setcookie ( $this->name, $this->value, $this->expire, $this->path, $this->domain, $this->secure, $this->httpOnly );
	}


	/**
	 * Magic method to turn a cookie object into a string without having to explicitly access [[value]].
	 *
	 * ~~~
	 * if (isset($request->cookies['name'])) {
	 * $value = (string) $request->cookies['name'];
	 * }
	 * ~~~
	 *
	 * @return string The value of the cookie. If the value property is null, an empty string will be returned.
	 */
	public function __toString()
	{
		return ( string ) $this->value;
	}
}