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
	 * @var Leaps\Di\ContainerInterface
	 */
	protected $_dependencyInjector;

	/**
	 * 设置依赖注入器
	 *
	 * @param \Leaps\Di\ContainerInterface dependencyInjector
	 */
	public function setDI(ContainerInterface $dependencyInjector)
	{
		if (! is_object ( $dependencyInjector )) {
			throw new Exception ( "Dependency Injector is invalid" );
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
		if (! is_object ( $this->_dependencyInjector )) {
			$this->_dependencyInjector = \Leaps\Kernel::getDi ();
		}
		return $this->_dependencyInjector;
	}

	/**
	 * 魔术方法 __get
	 *
	 * @param string propertyName
	 */
	public function __get($propertyName)
	{
		if (! is_object ( $this->_dependencyInjector )) {
			$this->_dependencyInjector = \Leaps\Kernel::getDi ();
			if (! is_object ( $this->_dependencyInjector )) {
				throw new \Leaps\Di\Exception ( "A dependency injection object is required to access the application services" );
			}
		}
		/**
		 * Fallback to the PHP userland if the cache is not available
		 */
		if ($this->_dependencyInjector->has ( $propertyName )) {
			$service = $this->_dependencyInjector->getShared ( $propertyName );
			$this->$propertyName = $service;
			return $service;
		}
		if ($propertyName == "di") {
			$this->$propertyName = $this->_dependencyInjector;
			return $this->_dependencyInjector;
		}
		/**
		 * A notice is shown if the property is not defined and isn't a valid service
		 */
		trigger_error ( "Access to undefined property " . $propertyName );
		return null;
	}
}