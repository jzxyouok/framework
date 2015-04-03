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

use ArrayIterator;

/**
 * Leaps\Http\Response\Headers
 *
 * This class is a bag to manage the response headers
 */
class Headers implements \Leaps\Http\HeadersInterface, \IteratorAggregate, \ArrayAccess, \Countable
{
	protected $_headers;

	/**
	 * 返回头集合中的一个遍历迭代器
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator ( $this->_headers );
	}

	/**
	 * 返回Header数量
	 *
	 * @return integer the number of headers in the collection.
	 */
	public function count()
	{
		return $this->getCount ();
	}

	/**
	 * 返回Header数量
	 *
	 * @return integer the number of headers in the collection.
	 */
	public function getCount()
	{
		return count ( $this->_headers );
	}

	/**
	 * 返回指定的头是否存在
	 *
	 * @param string $name the name of the header
	 * @return boolean whether the named header exists
	 */
	public function has($name)
	{
		$name = strtolower ( $name );
		return isset ( $this->_headers [$name] );
	}

	/**
	 * Adds a new header.
	 * If there is already a header with the same name, the new one will
	 * be appended to it instead of replacing it.
	 *
	 * @param string $name the name of the header
	 * @param string $value the value of the header
	 * @return static the collection object itself
	 */
	public function add($name, $value)
	{
		$name = strtolower ( $name );
		if (! $this->has ( $name )) {
			$this->set ( $name, $value );
		}
		return $this;
	}

	/**
	 * 设置头
	 *
	 * @param string name
	 * @param string value
	 */
	public function set($name, $value)
	{
		$this->_headers [$name] = $value;
	}

	/**
	 * 获取头
	 *
	 * @param string name
	 * @return string
	 */
	public function get($name, $default = null, $first = true)
	{
		$name = strtolower ( $name );
		if (isset ( $this->_headers [$name] )) {
			return $first ? reset ( $this->_headers [$name] ) : $this->_headers [$name];
		} else {
			return $default;
		}
	}

	/**
	 * 设置原始头
	 *
	 * @param string header
	 */
	public function setRaw($header)
	{
		$this->_headers [$header] = null;
	}

	/**
	 * 删除指定的头
	 *
	 * @param string header Header name
	 */
	public function remove($name)
	{
		$name = strtolower ( $name );
		if (isset ( $this->_headers [$name] )) {
			$value = $this->_headers [$name];
			unset ( $this->_headers [$name] );
			return $value;
		} else {
			return null;
		}
	}

	/**
	 * 发送头到浏览器
	 *
	 * @return boolean
	 */
	public function send()
	{
		if (! headers_sent ()) {
			foreach ( $this->_headers as $name => $value ) {
				if (! empty ( $value )) {
					$name = str_replace ( ' ', '-', ucwords ( str_replace ( '-', ' ', $name ) ) );
					header ( $name . ": " . $value, true );
				} else {
					header ( $name, true );
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * 删除所有头
	 */
	public function reset()
	{
		$this->_headers = [ ];
	}

	/**
	 * Populates the header collection from an array.
	 *
	 * @param array $array the headers to populate from
	 * @since 2.0.3
	 */
	public function fromArray(array $array)
	{
		$this->_headers = $array;
	}
	/**
	 * Returns whether there is a header with the specified name.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `isset($collection[$name])`.
	 *
	 * @param string $name the header name
	 * @return boolean whether the named header exists
	 */
	public function offsetExists($name)
	{
		return $this->has ( $name );
	}

	/**
	 * Returns the current headers as an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->_headers;
	}

	/**
	 * Returns the header with the specified name.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `$header = $collection[$name];`.
	 * This is equivalent to [[get()]].
	 *
	 * @param string $name the header name
	 * @return string the header value with the specified name, null if the named header does not exist.
	 */
	public function offsetGet($name)
	{
		return $this->get ( $name );
	}

	/**
	 * Adds the header to the collection.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `$collection[$name] = $header;`.
	 * This is equivalent to [[add()]].
	 *
	 * @param string $name the header name
	 * @param string $value the header value to be added
	 */
	public function offsetSet($name, $value)
	{
		$this->set ( $name, $value );
	}
	/**
	 * Removes the named header.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `unset($collection[$name])`.
	 * This is equivalent to [[remove()]].
	 *
	 * @param string $name the header name
	 */
	public function offsetUnset($name)
	{
		$this->remove ( $name );
	}

	/**
	 * Restore a Leaps\Http\Response\Headers object
	 *
	 * @param array data
	 * @return Leaps\Http\Response\Headers
	 */
	public static function __set_state($data)
	{
		$headers = new self ();
		if ($data ["_headers"]) {
			foreach ( $data ["_headers"] as $key => $value ) {
				$headers->set ( $key, $value );
			}
		}
		return $headers;
	}
}