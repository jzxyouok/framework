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

use Leaps\Kernel;

class Base implements Arrayable
{
	/**
	 * 返回这个类的完全限定名称。
	 *
	 * @return string
	 */
	public static function className()
	{
		return get_called_class ();
	}

	/**
	 * 构造方法 默认的实现做了两件事:
	 * - 使用给定的初始化对象配置。 - Call [[init()]].
	 * 如果在子类重写此方法,建议调用父实现的构造函数。
	 *
	 * @param array $config 名称-值对将用于初始化对象属性
	 */
	public function __construct($config = [])
	{
		if (! empty ( $config )) {
			Kernel::configure ( $this, $config );
		}
		$this->init ();
	}

	/**
	 * 初始化对象。 特定的配置。
	 */
	public function init()
	{
	}

	/**
	 * 返回一个对象属性的值。
	 *
	 * @param string $name 属性名
	 * @return mixed 属性值
	 * @throws UnknownPropertyException 如果属性没有定义
	 * @throws InvalidCallException 如果只写属性
	 * @see __set()
	 */
	public function __get($name)
	{
		$getter = 'get' . $name;
		if (method_exists ( $this, $getter )) {
			return $this->$getter ();
		} elseif (method_exists ( $this, 'set' . $name )) {
			throw new InvalidCallException ( 'Getting write-only property: ' . get_class ( $this ) . '::' . $name );
		} else {
			throw new UnknownPropertyException ( 'Getting unknown property: ' . get_class ( $this ) . '::' . $name );
		}
	}

	/**
	 * 设置对象的属性值。
	 *
	 * @param string $name 属性名或事件的名称
	 * @param mixed $value 属性值
	 * @throws UnknownPropertyException 如果属性没有定义
	 * @throws InvalidCallException 如果属性是只读的
	 * @see __get()
	 */
	public function __set($name, $value)
	{
		$setter = 'set' . $name;
		if (method_exists ( $this, $setter )) {
			$this->$setter ( $value );
		} elseif (method_exists ( $this, 'get' . $name )) {
			throw new InvalidCallException ( 'Setting read-only property: ' . get_class ( $this ) . '::' . $name );
		} else {
			throw new UnknownPropertyException ( 'Setting unknown property: ' . get_class ( $this ) . '::' . $name );
		}
	}

	/**
	 * 检查指定的属性设置(not null)。 注意,如果没有定义的属性,将返回false。
	 *
	 * @param string $name 属性名或事件的名称
	 * @return boolean 指定的属性是否已设置(not null)。
	 */
	public function __isset($name)
	{
		$getter = 'get' . $name;
		if (method_exists ( $this, $getter )) {
			return $this->$getter () !== null;
		} else {
			return false;
		}
	}

	/**
	 * 一个对象属性设置为null。 注意,如果没有定义的属性,该方法将什么也不做。 如果属性是只读的,它会抛出一个异常。
	 *
	 * @param string $name 属性名
	 * @throws InvalidCallException 如果属性是只读的。
	 */
	public function __unset($name)
	{
		$setter = 'set' . $name;
		if (method_exists ( $this, $setter )) {
			$this->$setter ( null );
		} elseif (method_exists ( $this, 'get' . $name )) {
			throw new InvalidCallException ( 'Unsetting read-only property: ' . get_class ( $this ) . '::' . $name );
		}
	}

	/**
	 * 调用指定的方法。
	 *
	 *
	 * @param string $name 方法名
	 * @param array $params 方法参数
	 * @throws UnknownMethodException 当调用未知的方法
	 * @return mixed 该方法返回值
	 */
	public function __call($name, $params)
	{
		throw new UnknownMethodException ( 'Unknown method: ' . get_class ( $this ) . "::$name()" );
	}

	/**
	 * 返回一个值,该值指示属性是否被定义。
	 *
	 * @param string $name 属性名
	 * @param boolean $checkVars 成员变量是否为属性
	 * @return boolean 是否定义的属性
	 * @see canGetProperty()
	 * @see canSetProperty()
	 */
	public function hasProperty($name, $checkVars = true)
	{
		return $this->canGetProperty ( $name, $checkVars ) || $this->canSetProperty ( $name, false );
	}

	/**
	 * 返回一个值,该值指示属性是否可读。
	 *
	 * @param string $name 属性名
	 * @param boolean $checkVars 成员变量是否为属性
	 * @return boolean 该属性是否可读
	 * @see canSetProperty()
	 */
	public function canGetProperty($name, $checkVars = true)
	{
		return method_exists ( $this, 'get' . $name ) || $checkVars && property_exists ( $this, $name );
	}

	/**
	 * 返回一个值指示是否可以设置一个属性。
	 *
	 * @param string $name 属性名
	 * @param boolean $checkVars 将成员变量是否为属性
	 * @return boolean 该属性是否可以写
	 * @see canGetProperty()
	 */
	public function canSetProperty($name, $checkVars = true)
	{
		return method_exists ( $this, 'set' . $name ) || $checkVars && property_exists ( $this, $name );
	}

	/**
	 * 返回一个值指示是否定义了一个方法。
	 *
	 * @param string $name 属性名
	 * @return boolean 是否定义的属性
	 */
	public function hasMethod($name)
	{
		return method_exists ( $this, $name );
	}

	/**
	 * 将对象转换为一个数组。 默认实现将返回所有公共属性值为数组。
	 *
	 * @return array 对象的数组表示
	 */
	public function toArray()
	{
		return Kernel::getObjectVars ( $this );
	}
}