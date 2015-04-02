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
namespace Leaps\Web\Response;

use ArrayIterator;
use Leaps\Web\Cookie;
use Leaps\Di\Injectable;
use Leaps\InvalidCallException;

class CookieCollection extends Injectable implements \IteratorAggregate, \ArrayAccess, \Countable
{
	protected $_registered = false;

	protected $_useEncryption = true;

	/**
	 * Cookie集合数组
	 *
	 * @var Cookie[]
	 */
	private $_cookies = [ ];

	/**
	 * 构造方法
	 *
	 * @param array $cookies the cookies that this collection initially contains. This should be
	 *        an array of name-value pairs.
	 * @param array $config name-value pairs that will be used to initialize the object properties
	 */
	public function __construct($cookies = [], $config = [])
	{
		$this->_cookies = $cookies;
		parent::__construct ( $config );
	}

	/**
	 * 判断指定Cookie是否存在
	 *
	 * @param string $name the cookie name
	 * @return boolean whether the named cookie exists
	 * @see remove()
	 */
	public function has($name)
	{
		/**
		 * Check the internal bag
		 */
		if (isset($this->_cookies[$name])) {
			return true;
		}
		/**
		 * Check the superglobal
		 */
		if (isset ($_COOKIE[$name])) {
			return true;
		}
		return false;
	}

	/**
	 * 设置一个Cookie
	 *
	 * @param string name
	 * @param mixed value
	 * @param int expire
	 * @param string path
	 * @param boolean secure
	 * @param string domain
	 * @param boolean httpOnly
	 * @return Phalcon\Http\Response\Cookies
	 */
	public function set($name, $value=null, $expire=0, $path="/", $secure=null, $domain=null, $httpOnly=null)
	{
		$encryption = $this->_useEncryption;
		if (!isset($this->_cookies[$name])) {
			$cookie = new Cookie($name, $value, $expire, $path, $secure, $domain, $httpOnly);
			$cookie->setDi($this->_dependencyInjector);
			if ($encryption) {
				$cookie->useEncryption($encryption);
			}
			$this->_cookies[$name] = $cookie;
		} else {
			$this->_cookies[$name]->setValue($value);
			$this->_cookies[$name]->setExpiration($expire);
			$this->_cookies[$name]->setPath($path);
			$this->_cookies[$name]->setSecure($secure);
			$this->_cookies[$name]->setDomain($domain);
			$this->_cookies[$name]->setHttpOnly($httpOnly);
		}

		/**
		 * Register the cookies bag in the response
		 */
		if ($this->_registered === false) {

			$dependencyInjector = $this->_dependencyInjector;
			if (!is_object($dependencyInjector)) {
				throw new Exception("A dependency injection object is required to access the 'response' service");
			}
			$response = $dependencyInjector->getShared("response");

			/**
			 * Pass the cookies bag to the response so it can send the headers at the of the request
			*/
			$response->setCookies($this);
		}

		return $this;
	}

	/**
	 * Gets a cookie from the bag
	 *
	 * @param string name
	 * @return Phalcon\Http\Cookie
	 */
	public function get($name)
	{
		if (isset($this->_cookies[$name])) {
			return $this->_cookies[$name];
		}
		/**
		 * Create the cookie if the it does not exist
		 */
		$cookie = new \Leaps\Web\Cookie($name);
		$dependencyInjector = $this->_dependencyInjector;

		if (is_object($dependencyInjector)){

			/**
			 * Pass the DI to created cookies
			 */
			$cookie->setDi($dependencyInjector);

			$encryption = $this->_useEncryption;

			/**
			 * Enable encryption in the cookie
			 */
			if ($encryption) {
				$cookie->useEncryption($encryption);
			}
		}
		$this->_cookies[$name] = $cookie;
		return $cookie;
	}

	/**
	 * 删除指定Cookie
	 *
	 * @param Cookie|string $cookie the cookie object or the name of the cookie to be removed.
	 * @throws InvalidCallException if the cookie collection is read only
	 */
	public function delete($name)
	{
		if ($this->readOnly) {
			throw new InvalidCallException ( 'The cookie collection is read only.' );
		}
		if(isset($this->_cookies [$name])){
			$this->_cookies [$name]->delete();
			return true;
		}
		return false;
	}

	/**
	 * 发送Cookie到客户端
	 *
	 * @return boolean
	 */
	public function send()
	{
		if (!headers_sent() ){
			foreach ($this->_cookies as $cookie) {
				$cookie->send();
			}
			return true;
		}
		return false;
	}

	/**
	 * 重置Cookie
	 * @return Leaps\Web\Response\CookieCollection
	 * @throws InvalidCallException if the cookie collection is read only
	 */
	public function reset()
	{
		if ($this->readOnly) {
			throw new InvalidCallException ( 'The cookie collection is read only.' );
		}
		$this->_cookies = [ ];
		return $this;
	}

	public function toArray()
	{
		return $this->_cookies;
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
	public function getIterator()
	{
		return new ArrayIterator ( $this->_cookies );
	}
	public function count()
	{
		return $this->getCount ();
	}
	public function getCount()
	{
		return count ( $this->_cookies );
	}
}