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

class Application extends \Leaps\Application
{
	/**
	 * 默认路由
	 * @var unknown
	 */
	public $defaultRoute = 'home/index/index';

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
			kernel::trace("Route requested: '$route'", __METHOD__);
			//$this->requestedRoute = $route;
			print_r($this);
			$result = $this->runAction($route, $params);
			if ($result instanceof Response) {
				return $result;
			} else {
				$response = $this->getResponse();
				if ($result !== null) {
					$response->data = $result;
				}

				return $response;
			}
		} catch (InvalidRouteException $e) {
			throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'), $e->getCode(), $e);
		}

	}

	/**
	 * Runs a controller action specified by a route.
	 * This method parses the specified route and creates the corresponding child module(s), controller and action
	 * instances. It then calls [[Controller::runAction()]] to run the action with the given parameters.
	 * If the route is empty, the method will use [[defaultRoute]].
	 * @param string $route the route that specifies the action.
	 * @param array $params the parameters to be passed to the action
	 * @return mixed the result of the action.
	 * @throws InvalidRouteException if the requested route cannot be resolved into an action successfully
	 */
	public function runAction($route, $params = [])
	{
		$parts = $this->createController($route);
		if (is_array($parts)) {
			/* @var $controller Controller */
			list($controller, $actionID) = $parts;
			$oldController = Yii::$app->controller;
			Yii::$app->controller = $controller;
			$result = $controller->runAction($actionID, $params);
			Yii::$app->controller = $oldController;

			return $result;
		} else {
			$id = $this->getUniqueId();
			throw new InvalidRouteException('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
		}
	}

	/**
	 * 从路由创建控制器
	 *
	 * If any of the above steps resolves into a controller, it is returned together with the rest
	 * part of the route which will be treated as the action ID. Otherwise, false will be returned.
	 *
	 * @param string $route the route consisting of module, controller and action IDs.
	 * @return array|boolean If the controller is created successfully, it will be returned together
	 * with the requested action ID. Otherwise false will be returned.
	 * @throws InvalidConfigException if the controller class and its file do not match.
	 */
	public function createController($route)
	{
		if ($route === '') {
			$route = $this->defaultRoute;
		}
		// double slashes or leading/ending slashes may cause substr problem
		$route = trim($route, '/');
		if (strpos($route, '//') !== false) {
			return false;
		}
		if (strpos($route, '/') !== false) {
			list ($id, $route) = explode ( '/', $route,2 );
		}





		if (strpos($route, '/') !== false) {
			list ($id, $route) = explode('/', $route, 2);
		} else {
			$id = $route;
			$route = '';
		}
		echo $id;
		exit;

		// module and controller map take precedence
		if (isset($this->controllerMap[$id])) {
			$controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
			return [$controller, $route];
		}
		$module = $this->getModule($id);
		if ($module !== null) {
			return $module->createController($route);
		}

		if (($pos = strrpos($route, '/')) !== false) {
			$id .= '/' . substr($route, 0, $pos);
			$route = substr($route, $pos + 1);
		}

		$controller = $this->createControllerByID($id);
		if ($controller === null && $route !== '') {
			$controller = $this->createControllerByID($id . '/' . $route);
			$route = '';
		}

		return $controller === null ? false : [$controller, $route];
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Leaps\Application::coreServices()
	 */
	public function coreServices()
	{
		return Arr::mergeArray ( parent::coreServices (), [ 'router' => [ 'className' => 'Leaps\Router\Router' ],'request' => [ 'className' => 'Leaps\Web\Request' ],'response' => [ 'className' => 'Leaps\Web\Response' ],'session' => [ 'className' => 'Leaps\Web\Session' ] ] );
	}
}