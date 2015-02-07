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
namespace Leaps\Web;

use Leaps\Arr;
use Leaps\Kernel;
use Leaps\Web\Router\Exception as RouteException;

class Application extends \Leaps\Application
{
	/**
	 * 默认路由
	 *
	 * @var unknown
	 */
	public $defaultRoute = 'home';

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Leaps\Application::handleRequest()
	 */
	public function handleRequest($request)
	{
		Kernel::setAlias ( '@webroot', dirname ( $request->getScriptFile () ) );
		Kernel::setAlias ( '@web', $request->getBaseUrl () );
		list ( $route, $params ) = $request->resolve ();
		try {
			kernel::trace ( "Route requested: '$route'", __METHOD__ );
			$this->requestedRoute = $route;
			$result = $this->runAction ( $route, $params );
			if ($result instanceof Response) {
				return $result;
			} else {
				$response = $this->getResponse ();
				if ($result !== null) {
					$response->data = $result;
				}

				return $response;
			}
		} catch ( RouteException $e ) {
			throw new NotFoundHttpException ( 'Page not found.', $e->getCode (), $e );
		}
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
						'className' => 'Leaps\Web\Request'
				],
				'response' => [
						'className' => 'Leaps\Web\Response'
				],
				'session' => [
						'className' => 'Leaps\Web\Session'
				]
		] );
	}
}