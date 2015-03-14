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
namespace Leaps;

/**
 * Leaps\DiInterface
 *
 * Interface for Leaps\Di
 */
interface DiInterface extends \ArrayAccess
{

	/**
	 * 注册一个服务到容器
	 *
	 * @param string name
	 * @param mixed definition
	 * @param boolean shared
	 * @return Leaps\Di\ServiceInterface
	 */
	public function set($name, $definition, $shared = false);

	/**
	 * 注册一个共享的服务到容器
	 *
	 * @param string name
	 * @param mixed definition
	 * @return Leaps\Di\ServiceInterface
	 */
	public function setShared($name, $definition);

	/**
	 * 从容器删除一个服务
	 *
	 * @param string name
	 */
	public function remove($name);

	/**
	 * Attempts to register a service in the services container
	 * Only is successful if a service hasn't been registered previously
	 * with the same name
	 *
	 * @param string name
	 * @param mixed definition
	 * @param boolean shared
	 * @return Leaps\Di\ServiceInterface
	 */
	public function attempt($name, $definition, $shared = false);

	/**
	 * 解析并返回服务实例
	 *
	 * @param string name
	 * @param array parameters
	 * @return mixed
	 */
	public function get($name, $parameters = null);

	/**
	 * 解析并返回共享的服务实例
	 *
	 * @param string name
	 * @param array parameters
	 * @return mixed
	 */
	public function getShared($name, $parameters = null);

	/**
	 * Sets a service using a raw Leaps\Di\Service definition
	 *
	 * @param string name
	 * @param Leaps\Di\ServiceInterface rawDefinition
	 * @return Leaps\Di\ServiceInterface
	 */
	public function setRaw($name, \Leaps\Di\ServiceInterface $rawDefinition);

	/**
	 * Returns a service definition without resolving
	 *
	 * @param string name
	 * @return mixed
	 */
	public function getRaw($name);

	/**
	 * 返回服务容器实例
	 *
	 * @param string name
	 * @return Leaps\Di\ServiceInterface
	 */
	public function getService($name);

	/**
	 * Check whether the DI contains a service by a name
	 *
	 * @param string name
	 * @return boolean
	 */
	public function has($name);

	/**
	 * Check whether the last service obtained via getShared produced a fresh instance or an existing one
	 *
	 * @return boolean
	 */
	public function wasFreshInstance();

	/**
	 * 返回容器中所有的服务
	 *
	 * @return array
	 */
	public function getServices();

	/**
	 * 通过静态方法设置一个默认的DI容器
	 *
	 * @param Leaps\DiInterface dependencyInjector
	 */
	public static function setDefault(\Leaps\DiInterface $dependencyInjector);

	/**
	 * 返回最后一个DI实例
	 *
	 * @return Leaps\DiInterface
	 */
	public static function getDefault();

	/**
	 * 重置DI容器
	 */
	public static function reset();
}