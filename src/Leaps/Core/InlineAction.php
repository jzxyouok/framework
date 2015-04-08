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
class InlineAction extends Action
{
	/**
	 * @var string the controller method that this inline action is associated with
	 */
	public $actionMethod;


	/**
	 * @param string $id the ID of this action
	 * @param Controller $controller the controller that owns this action
	 * @param string $actionMethod the controller method that this inline action is associated with
	 * @param array $config name-value pairs that will be used to initialize the object properties
	 */
	public function __construct($id, $controller, $actionMethod, $config = [])
	{
		$this->actionMethod = $actionMethod;
		parent::__construct($id, $controller, $config);
	}

	/**
	 * Runs this action with the specified parameters.
	 * This method is mainly invoked by the controller.
	 * @param array $params action parameters
	 * @return mixed the result of the action
	 */
	public function runWithParams($params)
	{
		$args = $this->controller->bindActionParams($this, $params);
		Kernel::trace('Running action: ' . get_class($this->controller) . '::' . $this->actionMethod . '()', __METHOD__);
		if (Kernel::getDi()->requestedParams === null) {
			Kernel::getDi()->requestedParams = $args;
		}
		return call_user_func_array([$this->controller, $this->actionMethod], $args);
	}
}
