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
namespace Leaps;

use Leaps\Di\Injectable;
//use Leaps\ViewContextInterface;
use Leaps\Web\Router\Exception as RouteException;

abstract class Controller extends Injectable// implements ViewContextInterface
{

	/**
	 * 当前模块名称
	 *
	 * @var string
	 */
	public $id;

	/**
	 * 父模块
	 *
	 * @var Module
	 */
	public $module;

	/**
	 * 默认的Action
	 *
	 * @var string
	 */
	public $defaultAction = 'index';

	/**
	 * Action实例
	 *
	 * @var
	 *
	 */
	public $action;

	/**
	 * 视图对象实例
	 *
	 * @var View
	 */
	//public $_view;

	/**
	 * 构造方法
	 *
	 * @param string $id the Controller ID
	 * @param Module $module 该控制器所属的模块
	 * @param array $config 对象属性初始化配置
	 */
	public function __construct($id, $module)
	{
		$this->id = $id;
		$this->module = $module;
	}

	/**
	 * 返回控制器前缀ID
	 * @return string
	 */
	public function getUniqueId()
	{
		return $this->module instanceof \Leaps\Application ? $this->id : $this->module->getUniqueId () . '/' . $this->id;
	}

	/**
	 * 执行Action实例
	 * @param unknown $id
	 * @param unknown $params
	 * @throws RouteException
	 * @return unknown
	 */
	public function runActionInstance($id, $params = [])
	{
		$action = $this->createActionInstance ( $id );
		if ($action === null) {
			throw new RouteException ( 'Unable to resolve the request: ' . $this->getUniqueId () . '/' . $id );
		}
		//Kernel::trace ( "Route to run: " . $action->getUniqueId (), __METHOD__ );
		if (Kernel::$app->requestedAction === null) {
			Kernel::$app->requestedAction = $action;
		}
		$oldAction = $this->action;
		$this->action = $action;
		// run the action
		$result = $action->runWithParams ( $params );
		$this->action = $oldAction;
		return $result;
	}

	/**
	 * 绑定参数到action
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
	 * 创建Action实例
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
			$methodName = str_replace ( ' ', '', strtolower ( implode ( ' ', explode ( '-', $id ) ) ) ).'Action';
			if (method_exists ( $this, $methodName )) {
				$method = new \ReflectionMethod ( $this, $methodName );
				if ($method->isPublic () && $method->getName () === $methodName) {
					return new \Leaps\Web\Action ( $id, $this, $methodName );
				}
			}
		}
		return null;
	}

	/**
	 * 返回所有模块
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
	 * 返回当前请求的路由
	 *
	 * @return string the route (module ID, controller ID and action ID) of the current request.
	 */
	public function getRoute()
	{
		return $this->action !== null ? $this->action->getUniqueId () : $this->getUniqueId ();
	}

	/**
	 * 获取控制器视图路径
	 *
	 * @return string the directory containing the view files for this controller.
	 */
	public function getViewPath()
	{
		return $this->module->getViewPath () . DIRECTORY_SEPARATOR . ucwords ( $this->id );
	}

	/**
	 * Returns the view object that can be used to render views or view files.
	 * The [[render()]], [[renderPartial()]] and [[renderFile()]] methods will use
	 * this view object to implement the actual view rendering.
	 * If not set, it will default to the "view" application component.
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
}