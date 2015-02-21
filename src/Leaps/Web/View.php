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

class View extends \Leaps\View
{
	protected $_viewParams;

	/**
	 * 模板主题
	 *
	 * @var theme
	 * @access protected
	 */
	protected $theme = '';

	public function getC(){
		return Kernel::$app->controller->getViewPath();
	}

	/**
	 * 模板变量赋值
	 *
	 * @access public
	 * @param mixed $name
	 * @param mixed $value
	 */
	public function aset($name, $value = '')
	{
		if (is_array ( $name )) {
			$this->tVar = array_merge ( $this->tVar, $name );
		} else {
			$this->tVar [$name] = $value;
		}
		return $this;
	}

	/**
	 * 取得模板变量的值
	 *
	 * @access public
	 * @param string $name
	 * @return mixed
	 */
	public function aget($name = '')
	{
		if ('' === $name) {
			return $this->tVar;
		}
		return isset ( $this->tVar [$name] ) ? $this->tVar [$name] : false;
	}

	/**
	 * Gets the name of the controller rendered
	 *
	 * @return string
	 */
	public function getControllerName()
	{
		return $this->_controllerName;
	}

	/**
	 * Gets the name of the action rendered
	 *
	 * @return string
	 */
	public function getActionName()
	{
		return $this->_actionName;
	}

	/**
	 * Gets extra parameters of the action rendered
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->_params;
	}

	/**
	 * Magic method to pass variables to the views
	 *
	 *<code>
	 *	$this->view->products = $products;
	 *</code>
	 *
	 * @param string key
	 * @param mixed value
	 */
	public function __set($key, $value)
	{
		$this->_viewParams[$key] = $value;
	}

	/**
	 * Magic method to retrieve a variable passed to the view
	 *
	 * <code>
	 * echo $this->view->products;
	 * </code>
	 *
	 * @param string key
	 * @return mixed
	 */
	public function __get($key)
	{
		if (isset ( $this->_viewParams [$key] )) {
			return $this->_viewParams [$key];
		}
		return null;
	}
}