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

use Leaps\Base;
use ArrayIterator;

class HeaderCollection extends Base implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * Header集合
	 * @var array
	 */
	private $_headers = [ ];

	/**
	 * 返回一个迭代器
	 *
	 * @return ArrayIterator an iterator for traversing the headers in the collection.
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
	 * 获取指定的Header
	 *
	 * @param string $name 要获取的Header名称
	 * @param mixed $default 如果不存在就返回默认值
	 * @param boolean $first 是否只返回指定名称的第一个header
	 * @return string|array the named header(s). If `$first` is true, a string will be returned;
	 *         If `$first` is false, an array will be returned.
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
	 * 添加一个新的 header.
	 *
	 * @param string $name the name of the header
	 * @param string $value the value of the header
	 * @return static the collection object itself
	 */
	public function set($name, $value = '')
	{
		$name = strtolower ( $name );
		$this->_headers [$name] = ( array ) $value;
		return $this;
	}

	/**
	 * 添加一个新的 header.
	 * 不会覆盖已有的
	 *
	 * @param string $name the name of the header
	 * @param string $value the value of the header
	 * @return static the collection object itself
	 */
	public function add($name, $value)
	{
		$name = strtolower ( $name );
		$this->_headers [$name] [] = $value;
		return $this;
	}

	/**
	 * 设置一个新的Header（不存在时有效）
	 *
	 * @param string $name the name of the header
	 * @param string $value the value of the header
	 * @return static the collection object itself
	 */
	public function setDefault($name, $value)
	{
		$name = strtolower ( $name );
		if (empty ( $this->_headers [$name] )) {
			$this->_headers [$name] [] = $value;
		}
		return $this;
	}

	/**
	 * 是否存在指定的Header
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
	 * 删除指定的Header
	 *
	 * @param string $name the name of the header to be removed.
	 * @return array the value of the removed header. Null is returned if the header does not exist.
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
	 * 删除所有的Header
	 */
	public function removeAll()
	{
		$this->_headers = [ ];
	}

	/**
	 * 返回Header集合
	 *
	 * @return array the array representation of the collection.
	 *         The array keys are header names, and the array values are the corresponding header values.
	 */
	public function toArray()
	{
		return $this->_headers;
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
}