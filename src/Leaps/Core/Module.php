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

use Leaps\Di;
use Leaps\Kernel;

class Module extends Di
{
	public $id;
	public $module;
	public $controllerNamespace;
	public $defaultRoute = 'home';
	protected $_modules = [ ];
	private $_basePath;
	private $_layoutPath;
	public $_viewPath;

	/**
	 * 构造方法
	 *
	 * @param string $id 模块ID
	 * @param Module $parent 父模块实例
	 * @param array $config 模块配置
	 */
	public function __construct($id, $parent = null, $config = [])
	{
		$this->id = $id;
		$this->module = $parent;
		parent::__construct ( $config );
	}

	/**
	 * 初始化模块
	 */
	public function init()
	{
		if ($this->controllerNamespace === null) {
			$class = get_class ( $this );
			if (($pos = strrpos ( $class, '\\' )) !== false) {
				$this->controllerNamespace = substr ( $class, 0, $pos ) . '\\Controller';
			}
		}
	}

	/**
	 * 返回唯一的应用程序标识
	 *
	 * @return string the unique ID of the module.
	 */
	public function getUniqueId()
	{
		return $this->module ? ltrim ( $this->module->getUniqueId () . '/' . $this->id, '/' ) : $this->id;
	}

	/**
	 * 返回模块跟文件夹
	 *
	 * @return string the root directory of the module.
	 */
	public function getBasePath()
	{
		if ($this->_basePath === null) {
			$class = new \ReflectionClass ( $this );
			$this->_basePath = dirname ( $class->getFileName () );
		}
		return $this->_basePath;
	}

	/**
	 * 设置模块文件夹
	 * This method can only be invoked at the beginning of the constructor.
	 *
	 * @param string $path the root directory of the module. This can be either a directory name or a path alias.
	 * @throws InvalidParamException if the directory does not exist.
	 */
	public function setBasePath($path)
	{
		$path = Kernel::getAlias ( $path );
		$p = realpath ( $path );
		if ($p !== false && is_dir ( $p )) {
			$this->_basePath = $p;
		} else {
			throw new InvalidParamException ( "The directory does not exist: $path" );
		}
	}

	/**
	 * 获取控制器路径
	 * an alias for the root namespace of [[controllerNamespace]].
	 *
	 * @return string the directory that contains the controller classes.
	 * @throws InvalidParamException if there is no alias defined for the root namespace of [[controllerNamespace]].
	 */
	public function getControllerPath()
	{
		return Kernel::getAlias ( '@' . str_replace ( '\\', '/', $this->controllerNamespace ) );
	}

	/**
	 * 返回模块视图文件夹
	 *
	 * @return string the root directory of view files. Defaults to "[[basePath]]/views".
	 */
	public function getViewPath()
	{
		if ($this->_viewPath !== null) {
			return $this->_viewPath;
		} else {
			return $this->_viewPath = $this->getBasePath () . DIRECTORY_SEPARATOR . 'View';
		}
	}

	/**
	 * 设置模块视图文件夹
	 *
	 * @param string $path the root directory of view files.
	 * @throws InvalidParamException if the directory is invalid
	 */
	public function setViewPath($path)
	{
		$this->_viewPath = Kernel::getAlias ( $path );
	}

	/**
	 * 获取布局文件路径
	 *
	 * @return string the root directory of layout files. Defaults to "[[viewPath]]/layouts".
	 */
	public function getLayoutPath()
	{
		if ($this->_layoutPath !== null) {
			return $this->_layoutPath;
		} else {
			return $this->_layoutPath = $this->getViewPath () . DIRECTORY_SEPARATOR . 'Layout';
		}
	}

	/**
	 * 设置布局文件
	 *
	 * @param string $path the root directory or path alias of layout files.
	 * @throws InvalidParamException if the directory is invalid
	 */
	public function setLayoutPath($path)
	{
		$this->_layoutPath = Kernel::getAlias ( $path );
	}

	/**
	 * 设置别名路径
	 * For example,
	 *
	 * ~~~
	 * [
	 * '@models' => '@app/models', // an existing alias
	 * '@backend' => __DIR__ . '/../backend', // a directory
	 * ]
	 * ~~~
	 */
	public function setAliases($aliases)
	{
		foreach ( $aliases as $name => $alias ) {
			Kernel::setAlias ( $name, $alias );
		}
	}

	/**
	 * 检查是否存在指定的子模块
	 *
	 * @param string $id module ID. For grand child modules, use ID path relative to this module (e.g. `admin/content`).
	 * @return boolean whether the named module exists. Both loaded and unloaded modules
	 *         are considered.
	 */
	public function hasModule($id)
	{
		if (($pos = strpos ( $id, '/' )) !== false) {
			// sub-module
			$module = $this->getModule ( substr ( $id, 0, $pos ) );

			return $module === null ? false : $module->hasModule ( substr ( $id, $pos + 1 ) );
		} else {
			return isset ( $this->_modules [$id] );
		}
	}

	/**
	 * Retrieves the child module of the specified ID.
	 * This method supports retrieving both child modules and grand child modules.
	 *
	 * @param string $id module ID (case-sensitive). To retrieve grand child modules,
	 *        use ID path relative to this module (e.g. `admin/content`).
	 * @param boolean $load whether to load the module if it is not yet loaded.
	 * @return Module|null the module instance, null if the module does not exist.
	 * @see hasModule()
	 */
	public function getModule($id, $load = true)
	{
		if (($pos = strpos ( $id, '/' )) !== false) {
			// sub-module
			$module = $this->getModule ( substr ( $id, 0, $pos ) );

			return $module === null ? null : $module->getModule ( substr ( $id, $pos + 1 ), $load );
		}

		if (isset ( $this->_modules [$id] )) {
			if ($this->_modules [$id] instanceof Module) {
				return $this->_modules [$id];
			} elseif ($load) {
				Kernel::trace ( "Loading module: $id", __METHOD__ );
				/* @var $module Module */
				$module = Kernel::createObject ( $this->_modules [$id], [
						$id,
						$this
				] );
				$module->setInstance ( $module );
				return $this->_modules [$id] = $module;
			}
		}

		return null;
	}

	/**
	 * 添加子模块到当前模块
	 *
	 * @param string $id module ID
	 * @param Module|array|null $module the sub-module to be added to this module. This can
	 *        be one of the followings:
	 *
	 *        - a [[Module]] object
	 *        - a configuration array: when [[getModule()]] is called initially, the array
	 *        will be used to instantiate the sub-module
	 *        - null: the named sub-module will be removed from this module
	 */
	public function setModule($id, $module)
	{
		if ($module === null) {
			unset ( $this->_modules [$id] );
		} else {
			$this->_modules [$id] = $module;
		}
	}

	/**
	 * 返回当前模块的子模块
	 *
	 * @param boolean $loadedOnly whether to return the loaded sub-modules only. If this is set false,
	 *        then all sub-modules registered in this module will be returned, whether they are loaded or not.
	 *        Loaded modules will be returned as objects, while unloaded modules as configuration arrays.
	 * @return array the modules (indexed by their IDs)
	 */
	public function getModules($loadedOnly = false)
	{
		if ($loadedOnly) {
			$modules = [ ];
			foreach ( $this->_modules as $module ) {
				if ($module instanceof Module) {
					$modules [] = $module;
				}
			}

			return $modules;
		} else {
			return $this->_modules;
		}
	}

	/**
	 * 注册子模块到当前模块
	 *
	 * The following is an example for registering two sub-modules:
	 *
	 * ~~~
	 * [
	 * 'comment' => [
	 * 'className' => 'app\modules\comment\CommentModule',
	 * 'db' => 'db',
	 * ],
	 * 'booking' => ['className' => 'app\modules\booking\BookingModule'],
	 * ]
	 * ~~~
	 *
	 * @param array $modules modules (id => module configuration or instances)
	 */
	public function setModules(array $modules)
	{
		foreach ( $modules as $id => $module ) {
			$this->_modules [$id] = $module;
		}
	}

	/**
	 * 从路由执行控制器操作
	 *
	 * @param string $route the route that specifies the action.
	 * @param array $params the parameters to be passed to the action
	 * @return mixed the result of the action.
	 * @throws InvalidRouteException if the requested route cannot be resolved into an action successfully
	 */
	public function runAction($route, $params = [])
	{
		$parts = $this->createController ( $route );

		if (is_array ( $parts )) {
			/* @var $controller Controller */
			list ( $controller, $actionID ) = $parts;
			$oldController = Kernel::$app->controller;
			Kernel::$app->controller = $controller;
			$result = $controller->runActionInstance ( $actionID, $params );
			Kernel::$app->controller = $oldController;
			return $result;
		} else {
			throw new \Leaps\Web\Router\Exception ( 'Unable to resolve the request "' . $route . '".' );
		}
	}

	/**
	 * 根据控制器ID创建控制器
	 *
	 * The controller ID is relative to this module. The controller class
	 * should be namespaced under [[controllerNamespace]].
	 *
	 * Note that this method does not check [[modules]] or [[controllerMap]].
	 *
	 * @param string $id the controller ID
	 * @return Controller the newly created controller instance, or null if the controller ID is invalid.
	 * @throws InvalidConfigException if the controller class and its file name do not match.
	 *         This exception is only thrown when in debug mode.
	 */
	public function createController($route)
	{
		if ($route === '') {
			$route = $this->defaultRoute;
		}

		// double slashes or leading/ending slashes may cause substr problem
		$route = trim ( $route, '/' );
		if (strpos ( $route, '//' ) !== false) {
			return false;
		}

		if (strpos ( $route, '/' ) !== false) {
			list ( $id, $route ) = explode ( '/', $route, 2 );
		} else {
			$id = $route;
			$route = '';
		}

		$module = $this->getModule ( $id );

		if ($module !== null) {
			return $module->createController ( $route );
		}

		if (($pos = strrpos ( $route, '/' )) !== false) {
			$id .= '/' . substr ( $route, 0, $pos );
			$route = substr ( $route, $pos + 1 );
		}
		$controller = $this->createControllerByID ( $id );
		if ($controller === null && $route !== '') {
			$controller = $this->createControllerByID ( $id . '/' . $route );
			$route = '';
		}

		return $controller === null ? false : [
				$controller,
				$route
		];
	}

	/**
	 * 根据控制器ID创建控制器
	 *
	 * The controller ID is relative to this module. The controller class
	 * should be namespaced under [[controllerNamespace]].
	 *
	 * Note that this method does not check [[modules]] or [[controllerMap]].
	 *
	 * @param string $id the controller ID
	 * @return Controller the newly created controller instance, or null if the controller ID is invalid.
	 * @throws InvalidConfigException if the controller class and its file name do not match.
	 *         This exception is only thrown when in debug mode.
	 */
	public function createControllerByID($id)
	{
		$pos = strrpos ( $id, '/' );
		if ($pos === false) {
			$prefix = '';
			$className = $id;
		} else {
			$prefix = substr ( $id, 0, $pos + 1 );
			$className = substr ( $id, $pos + 1 );
		}

		if (! preg_match ( '%^[a-z][a-z0-9\\-_]*$%', $className )) {
			return null;
		}
		if ($prefix !== '' && ! preg_match ( '%^[a-z0-9_/]+$%i', $prefix )) {
			return null;
		}

		$className = str_replace ( ' ', '', ucwords ( str_replace ( '-', ' ', $className ) ) ) . 'Controller';
		$className = ltrim ( $this->controllerNamespace . '\\' . str_replace ( '/', '\\', $prefix ) . $className, '\\' );

		if (strpos ( $className, '-' ) !== false || ! class_exists ( $className )) {
			return null;
		}

		if (is_subclass_of ( $className, 'Leaps\Controller' )) {
			return Kernel::createObject ( $className, [
					$id,
					$this
			] );
		} elseif (Kernel::$env == Kernel::DEVELOPMENT) {
			throw new InvalidConfigException ( "Controller class must extend from \\Leaps\\Controller." );
		} else {
			return null;
		}
	}
}