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
namespace Leaps\Di;

use Leaps\Kernel;

/**
 * Leaps\Di\Service
 *
 * 服务容器类
 *
 * <code>
 * $service = new \Leaps\Di\Service('request', 'Leaps\Http\Request');
 * $request = service->resolve();
 * <code>
 */
class Service implements ServiceInterface
{
	/**
	 *
	 * @var string 服务名称
	 */
	protected $_name;

	/**
	 *
	 * @var mixed 服务定义
	 */
	protected $_definition;

	/**
	 *
	 * @var boolean 是否共享
	 */
	protected $_shared = false;

	/**
	 *
	 * @var boolean 服务是否已经解析
	 */
	protected $_resolved = false;

	/**
	 *
	 * @var Object 服务共享实例
	 */
	protected $_sharedInstance;

	/**
	 * 构造方法
	 *
	 * @param string name 服务名称
	 * @param mixed definition 定义
	 * @param boolean shared 是否共享
	 */
	public final function __construct($name, $definition, $shared = false)
	{
		$this->_name = $name;
		$this->_definition = $definition;
		$this->_shared = $shared;
	}

	/**
	 * 返回服务名称
	 *
	 * @param string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * 设置服务是否共享
	 *
	 * @param boolean shared
	 */
	public function setShared($shared)
	{
		$this->_shared = $shared;
	}

	/**
	 * 检查服务是否共享
	 *
	 * @return boolean
	 */
	public function isShared()
	{
		return $this->_shared;
	}

	/**
	 * 设置或重置服务的共享实例
	 *
	 * @param mixed sharedInstance
	 */
	public function setSharedInstance($sharedInstance)
	{
		$this->_sharedInstance = $sharedInstance;
	}

	/**
	 * 设置服务定义
	 *
	 * @param mixed definition
	 */
	public function setDefinition($definition)
	{
		$this->_definition = $definition;
	}

	/**
	 * 返回服务定义
	 *
	 * @return mixed
	 */
	public function getDefinition()
	{
		return $this->_definition;
	}

	/**
	 * 解析服务
	 *
	 * @param array parameters
	 * @param Leaps\Di\DiInterface dependencyInjector
	 * @return mixed
	 */
	public function resolve($parameters = null)
	{
		/**
		 * 判断服务是否是共享的
		 */
		if ($this->_shared) {
			if ($this->_sharedInstance !== null) {
				return $this->_sharedInstance;
			}
		}
		$definition = $this->_definition;
		$instance = Kernel::createObject ( $definition, $parameters, false );
		/**
		 * 创建失败抛出异常
		 */
		if (! is_object ( $instance )) {
			throw new Exception ( "Service '" . $this->_name . "' cannot be resolved" );
		}
		/**
		 * 更新服务共享实例
		 */
		if ($this->_shared) {
			$this->_sharedInstance = $instance;
		}
		$this->_resolved = true;
		return $instance;
	}

	/**
	 * 服务是否已经解析
	 *
	 * @return bool
	 */
	public function isResolved()
	{
		return $this->_resolved;
	}

	/**
	 * 恢复服务内部状态
	 *
	 * @param array attributes
	 * @return Leaps\Di\Service
	 */
	public static function __set_state($attributes)
	{
		if (isset ( $attributes ["_name"] )) {
			throw new Exception ( "The attribute '_name' is required" );
		}

		if (isset ( $attributes ["_definition"] )) {
			throw new Exception ( "The attribute '_name' is required" );
		}

		if (isset ( $attributes ["_shared"] )) {
			throw new Exception ( "The attribute '_shared' is required" );
		}
		return new self ( $attributes ["_name"], $attributes ["_definition"], $attributes ["_shared"] );
	}
}