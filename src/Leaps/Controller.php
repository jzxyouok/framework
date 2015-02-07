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
use Leaps\Web\Router\Exception as RouteException;

class Controller extends Injectable
{

	/**
	 *
	 * @var string an ID that uniquely identifies this module among other modules which have the same [[module|parent]].
	 */
	public $id;

	/**
	 *
	 * @var Module the parent module of this module. Null if this module does not have a parent.
	 */
	public $module;

	/**
	 *
	 * @var string the ID of the action that is used when the action ID is not specified
	 *      in the request. Defaults to 'index'.
	 */
	public $defaultAction = 'index';
	/**
	 * @var Action the action that is currently being executed. This property will be set
	 * by [[run()]] when it is called by [[Application]] to run an action.
	 */
	public $action;

	/**
	 *
	 * @param string $id the ID of this controller.
	 * @param Module $module the module that this controller belongs to.
	 * @param array $config name-value pairs that will be used to initialize the object properties.
	 */
	public function __construct($id, $module)
	{
		$this->id = $id;
		$this->module = $module;
	}

	/**
	 * @return string the controller ID that is prefixed with the module ID (if any).
	 */
	public function getUniqueId()
	{
		return $this->module instanceof Application ? $this->id : $this->module->getUniqueId() . '/' . $this->id;
	}

	public function runActionInstance($id, $params = [])
	{
		$action = $this->createActionInstance ( $id );
		if ($action === null) {
			throw new RouteException ( 'Unable to resolve the request: ' . $this->getUniqueId () . '/' . $id );
		}
		Kernel::trace("Route to run: " . $action->getUniqueId(), __METHOD__);

        // run the action
        $result = $action->runWithParams($params);

        return $result;
	}

	/**
	 * Binds the parameters to the action.
	 * This method is invoked by [[Action]] when it begins to run with the given parameters.
	 * @param Action $action the action to be bound with parameters.
	 * @param array $params the parameters to be bound to the action.
	 * @return array the valid parameters that the action can run with.
	 */
	public function bindActionParams($action, $params)
	{
		return [];
	}

	/**
	 * Creates an action based on the given action ID.
	 * The method first checks if the action ID has been declared in [[actions()]]. If so,
	 * it will use the configuration declared there to create the action object.
	 * If not, it will look for a controller method whose name is in the format of `actionXyz`
	 * where `Xyz` stands for the action ID. If found, an [[InlineAction]] representing that
	 * method will be created and returned.
	 * @param string $id the action ID.
	 * @return Action the newly created action instance. Null if the ID doesn't resolve into any action.
	 */
	public function createActionInstance($id)
	{
		if ($id === '') {
			$id = $this->defaultAction;
		}
		$actionMap = $this->actions();
		if (isset($actionMap[$id])) {
			return Kernel::createObject($actionMap[$id], [$id, $this]);
		} elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
			$methodName = 'Action'.str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
			if (method_exists($this, $methodName)) {
				$method = new \ReflectionMethod($this, $methodName);
				if ($method->isPublic() && $method->getName() === $methodName) {
					return new \Leaps\Web\Action($id, $this, $methodName);
				}
			}
		}
		return null;
	}

	/**
	 * Returns all ancestor modules of this controller.
	 * The first module in the array is the outermost one (i.e., the application instance),
	 * while the last is the innermost one.
	 * @return Module[] all ancestor modules that this controller is located within.
	 */
	public function getModules()
	{
		$modules = [$this->module];
		$module = $this->module;
		while ($module->module !== null) {
			array_unshift($modules, $module->module);
			$module = $module->module;
		}
		return $modules;
	}

	/**
	 * Returns the route of the current request.
	 * @return string the route (module ID, controller ID and action ID) of the current request.
	 */
	public function getRoute()
	{
		return $this->action !== null ? $this->action->getUniqueId() : $this->getUniqueId();
	}

	/**
	 * Returns the directory containing view files for this controller.
	 * The default implementation returns the directory named as controller [[id]] under the [[module]]'s
	 * [[viewPath]] directory.
	 * @return string the directory containing the view files for this controller.
	 */
	public function getViewPath()
	{
		return $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
	}

	public function actions(){
		return [];
	}
}