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
namespace Leaps\Filesystem;

use Leaps\Di\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{

	/**
	 * 注册服务提供者
	 *
	 * @return void
	 */
	public function register($di)
	{
		$di->set ( 'files', function ()
		{
			return new Filesystem ();
		} );
	}
}