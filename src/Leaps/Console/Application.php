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
	/**
	 * The option name for specifying the application configuration file path.
	 */
	const OPTION_APPCONFIG = 'appconfig';

	/**
	 *
	 * @var string the default route of this application. Defaults to 'help',
	 *      meaning the `help` command.
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
	/**
	 * if ($result instanceof Response) {
	 * return $result;
	 * } else {
	 * $response = $this->getResponse();
	 * $response->exitStatus = $result;
	 * return $response;
	 * }
	 */
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Leaps\Application::coreServices()
	 */
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