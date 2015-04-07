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
namespace Leaps\Di;

use Leaps\Core\Base;
use Leaps\Core\InvalidCallException;

/**
 * Leaps\Di\Injectable
 *
 * This class allows to access services in the services container by just only accessing a public property
 * with the same name of a registered service
 */
abstract class Injectable extends Base implements InjectionAwareInterface
{

	/**
	 * 依赖注入器
	 *
	 * @var Leaps\Di\DiInteface
	 */
	protected $_dependencyInjector;

	/**
	 * Events Manager
	 *
	 * @var Phalcon\Events\ManagerInterface
	 */
	protected $_eventsManager;

	/**
	 * 设置依赖注入器
	 *
	 * @param \Leaps\Di\ContainerInterface dependencyInjector
	 */
	public function setDI(ContainerInterface $dependencyInjector)
	{
		if (! is_object ( $dependencyInjector )) {
			throw new \Leaps\Di\Exception ( "Dependency Injector is invalid" );
		}
		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * 返回依赖注入器实例
	 *
	 * @return \Leaps\Di\ContainerInterface
	 */
	public function getDI()
	{
		$dependencyInjector = $this->_dependencyInjector;
		if (! is_object ( $dependencyInjector )) {
			$dependencyInjector = Container::getDefault ();
		}
		return $dependencyInjector;
	}

	/**
	 * Sets the event manager
	 *
	 * @param Leaps\Events\ManagerInterface eventsManager
	 */
	public function setEventsManager(ManagerInterface $eventsManager)
	{
		$this->_eventsManager = $eventsManager;
	}

	/**
	 * Returns the internal event manager
	 *
	 * @return Leaps\Events\ManagerInterface
	 */
	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	/**
	 * 魔术方法 __get
	 *
	 * @param string propertyName
	 */
	public function __get($propertyName)
	{
		$dependencyInjector = $this->_dependencyInjector;
		if (! is_object ( $dependencyInjector )) {
			$dependencyInjector = Container::getDefault ();
			if (! is_object ( $dependencyInjector )) {
				throw new \Leaps\Di\Exception ( "A dependency injection object is required to access the application services" );
			}
		}
		/**
		 * Fallback to the PHP userland if the cache is not available
		 */
		if ($dependencyInjector->has ( $propertyName )) {
			$service = $dependencyInjector->getShared ( $propertyName );
			$this->$propertyName = $service;
			return $service;
		}
		if ($propertyName == "di") {
			$this->$propertyName = $dependencyInjector;
			return $dependencyInjector;
		}
		/**
		 * A notice is shown if the property is not defined and isn't a valid service
		 */
		trigger_error ( "Access to undefined property " . $propertyName );
		return null;
	}

	/**
	 * 魔术方法__set
	 *
	 * @param string $name
	 * @param unknown $value
	 * @throws InvalidCallException
	 */
	public function __set($name, $value)
	{
		$setter = 'set' . $name;
		if (method_exists ( $this, $setter )) {
			$this->$setter ( $value );
		} else {
			$this->$name = $value;
		}
	}
}
