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
namespace Leaps\Core;

use Leaps\Kernel;

class Controller extends Base implements ViewContextInterface
{
	/**
	 *
	 * @var string the ID of this controller.
	 */
	public $id;
	/**
	 *
	 * @var Module $module the module that this controller belongs to.
	 */
	public $module;
	/**
	 *
	 * @var string the ID of the action that is used when the action ID is not specified
	 *      in the request. Defaults to 'index'.
	 */
	public $defaultAction = 'index';

	/**
	 * 布局
	 * @var string|boolean the name of the layout to be applied to this controller's views.
	 *      This property mainly affects the behavior of [[render()]].
	 *      Defaults to null, meaning the actual layout value should inherit that from [[module]]'s layout value.
	 *      If false, no layout will be applied.
	 */
	public $layout;

	/**
	 *
	 * @var Action the action that is currently being executed. This property will be set
	 *      by [[run()]] when it is called by [[Application]] to run an action.
	 */
	public $action;

	/**
	 *
	 * @var View the view object that can be used to render views or view files.
	 */
	private $_view;

	/**
	 * 构造方法
	 *
	 * @param string $id the ID of this controller.
	 * @param Module $module the module that this controller belongs to.
	 * @param array $config name-value pairs that will be used to initialize the object properties.
	 */
	public function __construct($id, $module, $config = [])
	{
		$this->id = $id;
		$this->module = $module;
		parent::__construct ( $config );
	}

	/**
	 * Runs an action within this controller with the specified action ID and parameters.
	 * If the action ID is empty, the method will use [[defaultAction]].
	 *
	 * @param string $id the ID of the action to be executed.
	 * @param array $params the parameters (name-value pairs) to be passed to the action.
	 * @return mixed the result of the action.
	 * @throws InvalidRouteException if the requested action ID cannot be resolved into an action successfully.
	 * @see createAction()
	 */
	public function runActionInstance($id, $params = [])
	{
		$action = $this->createActionInstance ( $id );

		if ($action === null) {
			throw new InvalidRouteException ( 'Unable to resolve the request: ' . $this->getUniqueId () . '/' . $id );
		}
		Kernel::trace ( "Route to run: " . $action->getUniqueId (), __METHOD__ );

		if (Kernel::getDi ()->requestedAction === null) {
			Kernel::getDi ()->requestedAction = $action;
		}

		$oldAction = $this->action;
		$this->action = $action;

		$modules = [ ];
		$runAction = true;

		// call beforeAction on modules
		foreach ( $this->getModules () as $module ) {
			if ($module->beforeAction ( $action )) {
				array_unshift ( $modules, $module );
			} else {
				$runAction = false;
				break;
			}
		}

		$result = null;

		if ($runAction && $this->beforeAction ( $action )) {
			// run the action
			$result = $action->runWithParams ( $params );

			$result = $this->afterAction ( $action, $result );

			// call afterAction on modules
			foreach ( $modules as $module ) {
				/* @var $module Module */
				$result = $module->afterAction ( $action, $result );
			}
		}

		$this->action = $oldAction;

		return $result;
	}

	/**
	 * 根据指定的路由执行请求
	 * @param string $route the route to be handled, e.g., 'view', 'comment/view', '/admin/comment/view'.
	 * @param array $params the parameters to be passed to the action.
	 * @return mixed the result of the action.
	 * @see runAction()
	 */
	public function run($route, $params = [])
	{
		$pos = strpos ( $route, '/' );
		if ($pos === false) {
			return $this->runAction ( $route, $params );
		} elseif ($pos > 0) {
			return $this->module->runAction ( $route, $params );
		} else {
			return Kernel::getDi()->runAction ( ltrim ( $route, '/' ), $params );
		}
	}

	/**
	 * Binds the parameters to the action.
	 * This method is invoked by [[Action]] when it begins to run with the given parameters.
	 *
	 * @param Action $action the action to be bound with parameters.
	 * @param array $params the parameters to be bound to the action.
	 * @return array the valid parameters that the action can run with.
	 */
	public function bindActionParams($action, $params)
	{
		return [ ];
	}

	/**
	 * Creates an action based on the given action ID.
	 * The method first checks if the action ID has been declared in [[actions()]]. If so,
	 * it will use the configuration declared there to create the action object.
	 * If not, it will look for a controller method whose name is in the format of `actionXyz`
	 * where `Xyz` stands for the action ID. If found, an [[InlineAction]] representing that
	 * method will be created and returned.
	 *
	 * @param string $id the action ID.
	 * @return Action the newly created action instance. Null if the ID doesn't resolve into any action.
	 */
	public function createActionInstance($id)
	{
		if ($id === '') {
			$id = $this->defaultAction;
		}
		if (preg_match ( '/^[a-z0-9\\-_]+$/', $id ) && strpos ( $id, '--' ) === false && trim ( $id, '-' ) === $id) {
			$methodName = str_replace ( ' ', '', strtolower ( implode ( ' ', explode ( '-', $id ) ) ) ) . 'Action';
			if (method_exists ( $this, $methodName )) {
				$method = new \ReflectionMethod ( $this, $methodName );
				if ($method->isPublic () && $method->getName () === $methodName) {
					return new InlineAction ( $id, $this, $methodName );
				}
			}
		}
		return null;
	}

	/**
	 * 返回控制器所有的父模块
	 *
	 * @return Module[] all ancestor modules that this controller is located within.
	 */
	public function getModules()
	{
		$modules = [
				$this->module
		];
		$module = $this->module;
		while ( $module->module !== null ) {
			array_unshift ( $modules, $module->module );
			$module = $module->module;
		}
		return $modules;
	}

	/**
	 *
	 * @return string the controller ID that is prefixed with the module ID (if any).
	 */
	public function getUniqueId()
	{
		return $this->module instanceof Application ? $this->id : $this->module->getUniqueId () . '/' . $this->id;
	}

	/**
	 * 返回当前请求的路由。
	 *
	 * @return string the route (module ID, controller ID and action ID) of the current request.
	 */
	public function getRoute()
	{
		return $this->action !== null ? $this->action->getUniqueId () : $this->getUniqueId ();
	}

	/**
	 * Renders a view and applies layout if available.
	 *
	 * The view to be rendered can be specified in one of the following formats:
	 *
	 * - path alias (e.g. "@app/views/site/index");
	 * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
	 * The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
	 * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
	 * The actual view file will be looked for under the [[Module::viewPath|view path]] of [[module]].
	 * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
	 *
	 * To determine which layout should be applied, the following two steps are conducted:
	 *
	 * 1. In the first step, it determines the layout name and the context module:
	 *
	 * - If [[layout]] is specified as a string, use it as the layout name and [[module]] as the context module;
	 * - If [[layout]] is null, search through all ancestor modules of this controller and find the first
	 * module whose [[Module::layout|layout]] is not null. The layout and the corresponding module
	 * are used as the layout name and the context module, respectively. If such a module is not found
	 * or the corresponding layout is not a string, it will return false, meaning no applicable layout.
	 *
	 * 2. In the second step, it determines the actual layout file according to the previously found layout name
	 * and context module. The layout name can be:
	 *
	 * - a path alias (e.g. "@app/views/layouts/main");
	 * - an absolute path (e.g. "/main"): the layout name starts with a slash. The actual layout file will be
	 * looked for under the [[Application::layoutPath|layout path]] of the application;
	 * - a relative path (e.g. "main"): the actual layout file will be looked for under the
	 * [[Module::layoutPath|layout path]] of the context module.
	 *
	 * If the layout name does not contain a file extension, it will use the default one `.php`.
	 *
	 * @param string $view the view name.
	 * @param array $params the parameters (name-value pairs) that should be made available in the view.
	 *        These parameters will not be available in the layout.
	 * @return string the rendering result.
	 * @throws InvalidParamException if the view file or the layout file does not exist.
	 */
	public function render($view, $params = [])
	{
		$content = $this->getView ()->render ( $view, $params, $this );
		return $this->renderContent ( $content );
	}

	/**
	 * Renders a static string by applying a layout.
	 *
	 * @param string $content the static string being rendered
	 * @return string the rendering result of the layout with the given static string as the `$content` variable.
	 *         If the layout is disabled, the string will be returned back.
	 * @since 2.0.1
	 */
	public function renderContent($content)
	{
		$layoutFile = $this->findLayoutFile ( $this->getView () );
		if ($layoutFile !== false) {
			return $this->getView ()->renderFile ( $layoutFile, [
					'content' => $content
			], $this );
		} else {
			return $content;
		}
	}

	/**
	 * Renders a view without applying layout.
	 * This method differs from [[render()]] in that it does not apply any layout.
	 *
	 * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
	 * @param array $params the parameters (name-value pairs) that should be made available in the view.
	 * @return string the rendering result.
	 * @throws InvalidParamException if the view file does not exist.
	 */
	public function renderPartial($view, $params = [])
	{
		return $this->getView ()->render ( $view, $params, $this );
	}

	/**
	 * 渲染视图文件
	 *
	 * @param string $file the view file to be rendered. This can be either a file path or a path alias.
	 * @param array $params the parameters (name-value pairs) that should be made available in the view.
	 * @return string the rendering result.
	 * @throws InvalidParamException if the view file does not exist.
	 */
	public function renderFile($file, $params = [])
	{
		return $this->getView ()->renderFile ( $file, $params, $this );
	}

	/**
	 * 获取控制器视图对象
	 *
	 * @return View|\yii\web\View the view object that can be used to render views or view files.
	 */
	public function getView()
	{
		if ($this->_view === null) {
			$this->_view = Kernel::$app->getView ();
		}
		return $this->_view;
	}

	/**
	 * 设置控制器视图对象
	 *
	 * @param View|\yii\web\View $view the view object that can be used to render views or view files.
	 */
	public function setView($view)
	{
		$this->_view = $view;
	}

	/**
	 * 返回控制器视图文件夹
	 *
	 * @return string the directory containing the view files for this controller.
	 */
	public function getViewPath()
	{
		return $this->module->getViewPath () . DIRECTORY_SEPARATOR . $this->id;
	}

	/**
	 * 查找应用布局文件
	 *
	 * @param View $view the view object to render the layout file.
	 * @return string|boolean the layout file path, or false if layout is not needed.
	 *         Please refer to [[render()]] on how to specify this parameter.
	 * @throws InvalidParamException if an invalid path alias is used to specify the layout.
	 */
	public function findLayoutFile($view)
	{
		$module = $this->module;
		if (is_string ( $this->layout )) {
			$layout = $this->layout;
		} elseif ($this->layout === null) {
			while ( $module !== null && $module->layout === null ) {
				$module = $module->module;
			}
			if ($module !== null && is_string ( $module->layout )) {
				$layout = $module->layout;
			}
		}

		if (! isset ( $layout )) {
			return false;
		}

		if (strncmp ( $layout, '@', 1 ) === 0) {
			$file = Kernel::getAlias ( $layout );
		} elseif (strncmp ( $layout, '/', 1 ) === 0) {
			$file = Kernel::$app->getLayoutPath () . DIRECTORY_SEPARATOR . substr ( $layout, 1 );
		} else {
			$file = $module->getLayoutPath () . DIRECTORY_SEPARATOR . $layout;
		}

		if (pathinfo ( $file, PATHINFO_EXTENSION ) !== '') {
			return $file;
		}
		$path = $file . '.' . $view->defaultExtension;
		if ($view->defaultExtension !== 'php' && ! is_file ( $path )) {
			$path = $file . '.php';
		}

		return $path;
	}
}