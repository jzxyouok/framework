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

use Leaps\Di\Service;
use Leaps\Di\Exception;
use Leaps\Di\ServiceInterface;
use Leaps\Di\ServiceProviderInterface;

class Container implements \ArrayAccess, ContainerInterface
{
	protected $_services;
	protected $_sharedInstances;
	protected $_freshInstance = false;
	protected static $_default;

	/**
	 * 构造方法
	 */
	public function __construct()
	{
		if (! self::$_default) {
			self::$_default = $this;
		}
	}

	/**
	 * 注册一个服务到服务容器
	 *
	 * @param string name
	 * @param mixed definition
	 * @param boolean shared
	 * @return Leaps\Di\ServiceInterface
	 */
	public function set($name, $definition, $shared = false)
	{
		$service = new Service ( $name, $definition, $shared );
		$this->_services [$name] = $service;
		return $service;
	}

	/**
	 * Registers an "always shared" service in the services container
	 *
	 * @param string name
	 * @param mixed definition
	 * @return Leaps\Di\ServiceInterface
	 */
	public function setShared($name, $definition)
	{
		$service = new Service ( $name, $definition, true );
		$this->_services [$name] = $service;
		return $service;
	}

	/**
	 * 从容器中删除服务
	 *
	 * @param string name
	 */
	public function remove($name)
	{
		unset ( $this->_services [$name] );
	}

	/**
	 * Set a default dependency injection container to be obtained into static methods
	 *
	 * @param Leaps\Di\DiInterface dependencyInjector
	 */
	public static function setDefault(\Leaps\Di\ContainerInterface $dependencyInjector)
	{
		self::$_default = $dependencyInjector;
	}

	/**
	 * Attempts to register a service in the services container
	 * Only is successful if a service hasn"t been registered previously
	 * with the same name
	 *
	 * @param string name
	 * @param mixed definition
	 * @param boolean shared
	 * @return Leaps\Di\ServiceInterface|false
	 */
	public function attempt($name, $definition, $shared = false)
	{
		if (! isset ( $this->_services [$name] )) {
			$service = new Service ( $name, $definition, $shared );
			$this->_services [$name] = $service;
			return $service;
		}
		return false;
	}

	/**
	 * Sets a service using a raw Leaps\Di\Service definition
	 *
	 * @param string name
	 * @param Leaps\Di\ServiceInterface rawDefinition
	 * @return Leaps\Di\ServiceInterface
	 */
	public function setRaw($name, ServiceInterface $rawDefinition)
	{
		$this->_services [$name] = $rawDefinition;
		return $rawDefinition;
	}

	/**
	 * Returns a service definition without resolving
	 *
	 * @param string name
	 * @return mixed
	 */
	public function getRaw($name)
	{
		if (isset ( $this->_services [$name] )) {
			return $this->_services [$name]->getDefinition ();
		}
		throw new Exception ( "Service '" . name . "' wasn't found in the dependency injection container" );
	}

	/**
	 * 返回 Leaps\Di\Service 实例
	 *
	 * @param string name
	 * @return Leaps\Di\ServiceInterface
	 */
	public function getService($name)
	{
		if (isset ( $this->_services [$name] )) {
			return $this->_services [$name];
		}
		throw new Exception ( "Service '" . $name . "' wasn't found in the dependency injection container" );
	}

	/**
	 * 通过配置文件解析服务配置
	 *
	 * @param string name
	 * @param array parameters
	 * @return mixed
	 */
	public function get($name, $parameters = null)
	{
		if (isset ( $this->_services [$name] )) {

			/**
			 * 服务已经注册
			 */
			$instance = $this->_services [$name]->resolve ( $parameters, $this );
		} else {
			/**
			 * The DI also acts as builder for any class even if it isn't defined in the DI
			 */
			if (class_exists ( $name )) {
				if (is_array ( $parameters )) {
					if (count ( $parameters )) {
						if (version_compare ( PHP_VERSION, '5.6.0', '>=' )) {
							$reflection = new \ReflectionClass ( $name );
							$instance = $reflection->newInstanceArgs ( $parameters );
						} else {
							$reflection = new \ReflectionClass ( $name );
							$instance = $reflection->newInstanceArgs ( $parameters );
						}
					} else {
						if (version_compare ( PHP_VERSION, '5.6.0', '>=' )) {
							$reflection = new \ReflectionClass ( $name );
							$instance = $reflection->newInstance ();
						} else {
							$instance = new $name ();
						}
					}
				} else {
					if (version_compare ( PHP_VERSION, '5.6.0', '>=' )) {
						$reflection = new \ReflectionClass ( $name );
						$instance = $reflection->newInstance ();
					} else {
						$instance = new $name ();
					}
				}
			} else {
				throw new Exception ( "Service '" . $name . "' wasn't found in the dependency injection container" );
			}
		}

		/**
		 * Pass the DI itself if the instance implements \Leaps\Di\InjectionAwareInterface
		 */
		if (is_object ( $instance ) && method_exists ( $instance, "setDI" )) {
			$instance->setDI ( $this );
		}
		return $instance;
	}

	/**
	 * Resolves a service, the resolved service is stored in the DI, subsequent requests for this service will return the same instance
	 *
	 * @param string name
	 * @param array parameters
	 * @return mixed
	 */
	public function getShared($name, $parameters = null)
	{
		/**
		 * This method provides a first level to shared instances allowing to use non-shared services as shared
		 */
		if (isset ( $this->_sharedInstances [$name] )) {
			$instance = $this->_sharedInstances [$name];
			$this->_freshInstance = false;
		} else {
			/**
			 * Resolve the instance normally
			 */
			$instance = $this->get ( $name, $parameters );
			/**
			 * Save the instance in the first level shared
			 */
			$this->_sharedInstances [$name] = $instance;
			$this->_freshInstance = true;
		}
		return $instance;
	}

	/**
	 * Check whether the DI contains a service by a name
	 *
	 * @param string name
	 * @return boolean
	 */
	public function has($name)
	{
		return isset ( $this->_services [$name] );
	}

	/**
	 * Check whether the last service obtained via getShared produced a fresh instance or an existing one
	 *
	 * @return boolean
	 */
	public function wasFreshInstance()
	{
		return $this->_freshInstance;
	}

	/**
	 * 返回服务列表
	 *
	 * @return Leaps\Di\Service[]
	 */
	public function getServices()
	{
		return $this->_services;
	}

	/**
	 * Check if a service is registered using the array syntax
	 *
	 * @param string name
	 * @return boolean
	 */
	public function offsetExists($name)
	{
		return $this->has ( $name );
	}

	/**
	 * Allows to register a shared service using the array syntax
	 *
	 * <code>
	 * $di["request"] = new \Leaps\Http\Request();
	 * </code>
	 *
	 * @param string name
	 * @param mixed definition
	 * @return boolean
	 */
	public function offsetSet($name, $definition)
	{
		$this->setShared ( $name, $definition );
		return true;
	}

	/**
	 * Allows to obtain a shared service using the array syntax
	 *
	 * <code>
	 * var_dump($di["request"]);
	 * </code>
	 *
	 * @param string name
	 * @return mixed
	 */
	public function offsetGet($name)
	{
		return $this->getShared ( $name );
	}

	/**
	 * Removes a service from the services container using the array syntax
	 *
	 * @param string name
	 */
	public function offsetUnset($name)
	{
		return false;
	}

	/**
	 * Magic method to get or set services using setters/getters
	 *
	 * @param string method
	 * @param array arguments
	 * @return mixed
	 */
	public function __call($method, $arguments = null)
	{
		/**
		 * If the magic method starts with "get" we try to get a service with that name
		 */
		if (substr ( $method, 0, 3 ) == "get") {
			// if (starts_with ( $method, "get" )) {
			$possibleService = lcfirst ( substr ( $method, 3 ) );
			if (isset ( $this->_services [$possibleService] )) {
				if (count ( $arguments )) {
					$instance = $this->get ( $possibleService, $arguments );
				} else {
					$instance = $this->get ( $possibleService );
				}
				return $instance;
			}
		}

		/**
		 * If the magic method starts with "set" we try to set a service using that name
		 */
		if (substr ( $method, 0, 3 ) == "set") {
			// if (starts_with ( $method, "set" )) {
			if (isset ( $arguments [0] )) {
				$this->set ( lcfirst ( substr ( $method, 3 ) ), $arguments [0] );
				return null;
			}
		}

		/**
		 * The method doesn't start with set/get throw an exception
		 */
		throw new Exception ( "Call to undefined method or service '" . $method . "'" );
	}

	/**
	 * 批量注册服务到容器
	 *
	 * The following is an example for registering two component definitions:
	 *
	 * ```php
	 * [
	 * 'db' => [
	 * 'className' => 'Leaps\Db\Connection',
	 * 'dsn' => 'sqlite:path/to/file.db',
	 * ],
	 * 'cache' => [
	 * 'className' => 'Leaps\Cache\DbCache',
	 * 'db' => 'db',
	 * ],
	 * ]
	 * ```
	 *
	 * @param array $services service definitions or instances
	 */
	public function setServices($services)
	{
		foreach ( $services as $id => $service ) {
			$this->set ( $id, $service );
		}
	}

	/**
	 * 注册服务提供者到容器
	 *
	 * @param ServiceProviderInterface $provider The service provider to register.w
	 *
	 * @return Container This object for chaining.
	 *
	 * @since 2.0
	 */
	public function registerServiceProvider(ServiceProviderInterface $provider)
	{
		$provider->register ( $this );
		return $this;
	}

	/**
	 * Return the lastest DI created
	 *
	 * @return Phalcon\DiInterface
	 */
	public static function getDefault()
	{
		if (! self::$_default) {
			self::$_default = new static ();
		}
		return self::$_default;
	}

	/**
	 * 重置DI
	 */
	public static function reset()
	{
		self::$_default = null;
	}
}