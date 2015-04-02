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
namespace Leaps\Core;

/**
 * Leaps\Registry
 *
 * A registry is a container for storing objects and values in the application space.
 * By storing the value in a registry, the same object is always available throughout
 * your application.
 *
 * <code>
 * $registry = new \Leaps\Registry();
 *
 * // Set value
 * $registry->something = 'something';
 * // or
 * $registry['something'] = 'something';
 *
 * // Get value
 * $value = $registry->something;
 * // or
 * $value = $registry['something'];
 *
 * // Check if the key exists
 * $exists = isset($registry->something);
 * // or
 * $exists = isset($registry['something']);
 *
 * // Unset
 * unset($registry->something);
 * // or
 * unset($registry['something']);
 * </code>
 *
 * In addition to ArrayAccess, Leaps\Registry also implements Countable
 * (count($registry) will return the number of elements in the registry),
 * Serializable and Iterator (you can iterate over the registry
 * using a foreach loop) interfaces. For PHP 5.4 and higher, JsonSerializable
 * interface is implemented.
 *
 * Leaps\Registry is very fast (it is typically faster than any userspace
 * implementation of the registry); however, this comes at a price:
 * Leaps\Registry is a final class and cannot be inherited from.
 *
 * Though Leaps\Registry exposes methods like __get(), offsetGet(), count() etc,
 * it is not recommended to invoke them manually (these methods exist mainly to
 * match the interfaces the registry implements): $registry->__get('property')
 * is several times slower than $registry->property.
 *
 * Internally all the magic methods (and interfaces except JsonSerializable)
 * are implemented using object handlers or similar techniques: this allows
 * to bypass relatively slow method calls.
 */
final class Registry implements \ArrayAccess, \Countable, \Iterator
{
	protected $_data;

	/**
	 * Registry constructor
	 */
	public final function __construct()
	{
		$this->_data = [ ];
	}

	/**
	 * Checks if the element is present in the registry
	 *
	 * @param string offset
	 */
	public final function offsetExists($offset)
	{
		return isset ( $this->_data [$offset] );
	}

	/**
	 * Returns an index in the registry
	 *
	 * @param string offset
	 */
	public final function offsetGet($offset)
	{
		return $this->_data [$offset];
	}

	/**
	 * Sets an element in the registry
	 *
	 * @param string offset
	 * @param mixed value
	 */
	public final function offsetSet($offset, $value)
	{
		$this->_data [$offset] = $value;
	}

	/**
	 * Unsets an element in the registry
	 *
	 * @param string offset
	 */
	public final function offsetUnset($offset)
	{
		unset ( $this->_data [$offset] );
	}

	/**
	 * Sets an element in the registry
	 *
	 * @param string offset
	 * @param mixed value
	 */
	public final function __set($offset, $value)
	{
		$this->_data [$offset] = $value;
	}

	/**
	 * Returns an index in the registry
	 *
	 * @param string offset
	 */
	public final function __get($offset)
	{
		return $this->_data [$offset];
	}

	/**
	 * Checks how many elements are in the register
	 *
	 * @return int
	 */
	public final function count()
	{
		return count ( $this->_data );
	}

	/**
	 * Moves cursor to next row in the registry
	 */
	public final function next()
	{
		next ( $this->_data );
	}

	/**
	 * Gets pointer number of active row in the registry
	 *
	 * @return int
	 */
	public final function key()
	{
		return key ( $this->_data );
	}

	/**
	 * Rewinds the registry cursor to its beginning
	 */
	public final function rewind()
	{
		reset ( $this->_data );
	}

	/**
	 * Checks if the iterator is valid
	 */
	public function valid()
	{
		return key ( $this->_data ) !== null;
	}

	/**
	 */
	public function current()
	{
		return current ( $this->_data );
	}
}