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
 * Leaps\Di\InjectionAwareInterface
 */
interface InjectionAwareInterface
{

	/**
	 * 设置依赖注入器
	 *
	 * @param \Leaps\DiInterface dependencyInjector
	 */
	public function setDI(\Leaps\DiInterface $dependencyInjector);

	/**
	 * 获取依赖注入器
	 *
	 * @return Leaps\DiInterface
	 */
	public function getDI();
}
