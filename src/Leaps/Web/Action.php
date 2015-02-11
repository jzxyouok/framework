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
use Leaps\Kernel;
use Leaps\Di\Injectable;
use Leaps\InvalidConfigException;
class Action extends Injectable {

	/**
	 * @var string ID of the action
	 */
	public $id;

	/**
	 * @var Controller|\Leaps\Web\Controller the controller that owns this action
	 */
	public $controller;

	/**
	 * @var string the controller method that this inline action is associated with
	 */
	public $actionMethod;

	/**
	 * Constructor.
	 *
	 * @param string $id the ID of this action
	 * @param Controller $controller the controller that owns this action
	 * @param array $config name-value pairs that will be used to initialize the object properties
	 */
	public function __construct($id, $controller, $actionMethod)
	{
		$this->id = $id;
		$this->controller = $controller;
		$this->actionMethod = $actionMethod;
	}

	/**
	 * Returns the unique ID of this action among the whole application.
	 *
	 * @return string the unique ID of this action among the whole application.
	 */
	public function getUniqueId()
	{
		return $this->controller->getUniqueId() . '/' . $this->id;
	}

	/**
	 * 从指定的参数执行该方法
	 *
	 * @param array $params the parameters to be bound to the action's run() method.
	 * @return mixed the result of the action
	 * @throws InvalidConfigException if the action class does not have a run() method
	 */
	public function runWithParams($params)
	{
		$args = $this->controller->bindActionParams($this, $params);
        //Kernel::trace('Running action: ' . get_class($this->controller) . '::' . $this->actionMethod . '()', __METHOD__);
        return call_user_func_array([$this->controller, $this->actionMethod], $args);
	}
}
