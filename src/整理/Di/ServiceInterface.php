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

/**
 * Leaps\Di\ServiceInterface
 *
 * 服务容器接口
 */
interface ServiceInterface
{

	/**
	 * Leaps\Di\ServiceInterface
	 *
	 * @param string name
	 * @param mixed definition
	 * @param boolean shared
	 */
	public function __construct($name, $definition, $shared = false);

	/**
	 * 返回服务名称
	 *
	 * @param string
	 */
	public function getName();

	/**
	 * 设置服务是共享的
	 *
	 * @param boolean shared
	 */
	public function setShared($shared);

	/**
	 * 判断服务是否是共享的
	 *
	 * @return boolean
	 */
	public function isShared();

	/**
	 * 设置服务定义
	 *
	 * @param mixed definition
	 */
	public function setDefinition($definition);

	/**
	 * 获取服务定义
	 *
	 * @return mixed
	 */
	public function getDefinition();

	/**
	 * 解析服务
	 *
	 * @param array parameters
	 * @param Leaps\Di\ContainerInterface dependencyInjector
	 * @return mixed
	 */
	public function resolve($parameters = null);

	/**
	 * 恢复服务
	 *
	 * @param array attributes
	 * @return Leaps\Di\ServiceInterface
	 */
	public static function __set_state($attributes);
}
