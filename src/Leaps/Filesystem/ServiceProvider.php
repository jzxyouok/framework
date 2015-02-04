<?php
/*
 +------------------------------------------------------------------------+
 | Leaps Framework                                                        |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)           |
 +------------------------------------------------------------------------+
 | This source file is subject to the Apache License that is bundled      |
 | with this package in the file docs/LICENSE.txt.                        |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@tintsoft.com so we can send you a copy immediately.         |
 +------------------------------------------------------------------------+
 | Authors: XuTongle <xutongle@gmail.com>                                 |
 +------------------------------------------------------------------------+
 */

namespace Leaps\Filesystem;

use Leaps\Di\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface{

	/**
	 * 注册服务提供者
	 *
	 * @return void
	 */
	public function register($di)
	{
		$di->set('files',function() {
			return new Filesystem;
		});
	}
}