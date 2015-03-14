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
namespace Leaps\Console;

use Leaps\Arr;

class Application extends \Leaps\Application
{
	public function handleRequest($request)
	{
		print_r($request);
		exit;
		echo 999;
		exit ();
	}

	public function coreServices()
	{
		return Arr::mergeArray ( parent::coreServices (), [
				'router' => [
						'className' => 'Leaps\Web\Router'
				],
				'request' => [
						'className' => '\Leaps\Console\Request'
				]
		] );
	}
}