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

use Leaps\DiInterface;

/**
 * 服务提供者接口
 *
 * @since 1.0
 */
interface ServiceProviderInterface
{
	/**
	 * 注册一个服务到容器
	 * @param Container $container DI容器
	 * @return void
	 */
	public function register(DiInterface $di);
}