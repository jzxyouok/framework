<?php
/*
 * +------------------------------------------------------------------------+
 * | Leaps Framework                                                        |
 * +------------------------------------------------------------------------+
 * | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)           |
 * +------------------------------------------------------------------------+
 * | This source file is subject to the Apache License that is bundled      |
 * | with this package in the file docs/LICENSE.txt.                        |
 * |                                                                        |
 * | If you did not receive a copy of the license and are unable to         |
 * | obtain it through the world-wide-web, please send an email             |
 * | to license@tintsoft.com so we can send you a copy immediately.         |
 * +------------------------------------------------------------------------+
 * | Authors: XuTongle <xutongle@gmail.com>                                 |
 * +------------------------------------------------------------------------+
 */
namespace Leaps\Di;

use Leaps\Di\ContainerInterface;

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
	public function register(ContainerInterface $di);
}