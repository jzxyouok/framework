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
use Leaps\Core\InvalidRouteException;

class Application extends \Leaps\Core\Application
{
	/**
	 * 指定应用程序配置文件名称
	 */
	const OPTION_APPCONFIG = 'appconfig';

	/**
	 * 默认的路由
	 *
	 * @var string
	 */
	public $defaultRoute = 'help';

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Leaps\Application::handleRequest()
	 */
	public function handleRequest($request)
	{
		list ( $route, $params ) = $request->resolve ();
		$this->requestedRoute = $route;
		$result = $this->runAction ( $route, $params );
		if ($result instanceof Response) {
			return $result;
		} else {
			$response = $this->getResponse ();
			$response->exitStatus = $result;
			return $response;
		}
	}
	public function runAction($route, $params = [])
	{
		try {
			return ( int ) parent::runAction ( $route, $params );
		} catch ( InvalidRouteException $e ) {
			throw new Exception ( "Unknown command \"$route\".", 0, $e );
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Leaps\Core\Application::coreServices()
	 */
	public function coreServices()
	{
		return Arr::mergeArray ( parent::coreServices (), [
				'request' => [
						'className' => '\Leaps\Console\Request'
				],
				'response' => [
						'className' => 'Leaps\Console\Response'
				],
				'errorHandler' => [
						'className' => 'Leaps\Console\ErrorHandler'
				]
		] );
	}
}