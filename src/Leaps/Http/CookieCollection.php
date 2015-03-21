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

use Leaps\Base;
use \ArrayIterator;

class CookieCollection extends Base implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * Cookie默认路径
	 *
	 * @var string
	 */
	public $path = '/';

	/**
	 * Cookie默认作用域
	 *
	 * @var string
	 */
	public $domain = null;

	/**
	 * 默认过期时间
	 *
	 * @var integer the timestamp at which the cookie expires. This is the server timestamp.
	 *      Defaults to 0, meaning "until the browser is closed".
	 */
	public $expire = 0;

	/**
	 * 是否是安全连接
	 *
	 * @var boolean whether cookie should be sent via secure connection
	 */
	public $secure = false;

	/**
	 * Cookie是否只能通过HTTP协议读取
	 *
	 * @var boolean whether the cookie should be accessible only through the HTTP protocol.
	 *      By setting this property to true, the cookie will not be accessible by scripting languages,
	 *      such as JavaScript, which can effectively help to reduce identity theft through XSS attacks.
	 */
	public $httpOnly = true;

	/**
	 * 是否只读
	 * @var unknown
	 */
	public $readOnly = false;

	/**
	 *
	 * @var Cookie[] the cookies in this collection (indexed by the cookie names)
	 */
	private $_cookies = [ ];

	/**
	 * 创建一个新的Cookie实例
	 *
	 * @param string $name 名称
	 * @param string $value 值
	 * @param int $minutes 有效期（分钟）
	 * @param string $path 路径
	 * @param string $domain 域名
	 * @param bool $secure 是否HTTPS
	 * @param bool $httpOnly 是否只读
	 * @return \Leaps\Http\Cookie
	 */
	public function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true)
	{
		$path = $path ? $path : $this->path;
		$domain = $domain ? $domain : $this->domain;
		// 过期时间
		$time = ($minutes == 0) ? 0 : time () + ($minutes * 60);
		$secure = $secure ? $secure : $this->secure;
		$httpOnly = $httpOnly ? $httpOnly : $this->httpOnly;
		$cookie = new Cookie ( [
				'name' => $name,
				'value' => $value,
				'expire' => $time,
				'path' => $path,
				'domain' => $domain,
				'secure' => $secure,
				'httpOnly' => $httpOnly
		] );
		return $this->add ( $cookie );
	}

	/**
	 * 创建一个永不过期的Cookie
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httpOnly
	 * @return \Leaps\Http\Cookie
	 */
	public function forever($name, $value, $path = null, $domain = null, $secure = false, $httpOnly = true)
	{
		return $this->make ( $name, $value, 2628000, $path, $domain, $secure, $httpOnly );
	}

	/**
	 * 设置指定Cookie过期
	 *
	 * @param string $name
	 * @param string $path
	 * @param string $domain
	 * @return \Leaps\Http\Cookie
	 */
	public function forget($name, $path = null, $domain = null)
	{
		return $this->make ( $name, null, - 2628000, $path, $domain );
	}

	/**
	 * 添加一个Cookie到容器
	 * If there is already a cookie with the same name in the collection, it will be removed first.
	 *
	 * @param Cookie $cookie the cookie to be added
	 * @throws InvalidCallException if the cookie collection is read only
	 */
	public function add($cookie)
	{
		if ($this->readOnly) {
			throw new \Leaps\InvalidCallException ( 'The cookie collection is read only.' );
		}
		$this->_cookies [$cookie->name] = $cookie;
	}

	/**
	 * Returns an iterator for traversing the cookies in the collection.
	 * This method is required by the SPL interface `IteratorAggregate`.
	 * It will be implicitly called when you use `foreach` to traverse the collection.
	 *
	 * @return ArrayIterator an iterator for traversing the cookies in the collection.
	 */
	public function getIterator()
	{
		return new ArrayIterator ( $this->_cookies );
	}

	/**
	 * Returns the number of cookies in the collection.
	 * This method is required by the SPL `Countable` interface.
	 * It will be implicitly called when you use `count($collection)`.
	 *
	 * @return integer the number of cookies in the collection.
	 */
	public function count()
	{
		return $this->getCount ();
	}

	/**
	 * 返回Coookie总数
	 *
	 * @return integer the number of cookies in the collection.
	 */
	public function getCount()
	{
		return count ( $this->_cookies );
	}

	/**
	 * 获取指定Cookie实例
	 *
	 * @param string $name the cookie name
	 * @return Cookie the cookie with the specified name. Null if the named cookie does not exist.
	 * @see getValue()
	 */
	public function get($name)
	{
		return isset ( $this->_cookies [$name] ) ? $this->_cookies [$name] : null;
	}

	/**
	 * 返回指定的cookie的值。
	 *
	 * @param string $name the cookie name
	 * @param mixed $defaultValue the value that should be returned when the named cookie does not exist.
	 * @return mixed the value of the named cookie.
	 * @see get()
	 */
	public function getValue($name, $defaultValue = null)
	{
		return isset ( $this->_cookies [$name] ) ? $this->_cookies [$name]->getValue () : $defaultValue;
	}

	/**
	 * 返回指定Cookie是否存在
	 *
	 * @param string $name the cookie name
	 * @return boolean whether the named cookie exists
	 * @see remove()
	 */
	public function has($name)
	{
		return isset ( $this->_cookies [$name] ) && $this->_cookies [$name]->getValue () !== '' && ($this->_cookies [$name]->getExpiresTime () === null || $this->_cookies [$name]->getExpiresTime >= time ());
	}

	/**
	 * 删除指定Cookie
	 *
	 * @param Cookie|string $cookie the cookie object or the name of the cookie to be removed.
	 * @param boolean $removeFromBrowser whether to remove the cookie from browser
	 * @throws InvalidCallException if the cookie collection is read only
	 */
	public function remove($cookie, $removeFromBrowser = true)
	{
		if ($this->readOnly) {
			throw new \Leaps\InvalidCallException ( 'The cookie collection is read only.' );
		}
		if ($cookie instanceof Cookie) {
			$cookie->expire = 1;
			$cookie->value = '';
		} else {
			$cookie = new Cookie ( [
					'name' => $cookie,
					'expire' => 1
			] );
		}
		if ($removeFromBrowser) {
			$this->_cookies [$cookie->name] = $cookie;
		} else {
			unset ( $this->_cookies [$cookie->name] );
		}
	}

	/**
	 * 删除所有Cookie
	 *
	 * @throws InvalidCallException if the cookie collection is read only
	 */
	public function removeAll()
	{
		if ($this->readOnly) {
			throw new \Leaps\InvalidCallException ( 'The cookie collection is read only.' );
		}
		$this->_cookies = [ ];
	}

	/**
	 * 返回集合数组
	 *
	 * @return array the array representation of the collection.
	 *         The array keys are cookie names, and the array values are the corresponding cookie objects.
	 */
	public function toArray()
	{
		return $this->_cookies;
	}

	/**
	 * 从一个数组填充Cookie集合。
	 *
	 * @param array $array the cookies to populate from
	 * @since 2.0.3
	 */
	public function fromArray(array $array)
	{
		$this->_cookies = $array;
	}
	public function offsetExists($name)
	{
		return $this->has ( $name );
	}
	public function offsetGet($name)
	{
		return $this->get ( $name );
	}
	public function offsetSet($name, $cookie)
	{
		$this->add ( $cookie );
	}
	public function offsetUnset($name)
	{
		$this->remove ( $name );
	}
}