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
namespace Leaps\HttpClient;

use Illuminate\Support\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
{

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared ( 'httpclient', function ($app)
		{
			if (function_exists ( "curl_init" )) {
				return new \Leaps\HttpClient\Adapter\Curl();
			} else {
				return new \Leaps\HttpClient\Adapter\Fsock();
			}
		} );
	}
}