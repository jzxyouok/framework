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
	 * @var Object 服务实例
	 */
	protected $_sharedInstance;

	/**
	 * Leaps\Di\Service
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
	 * 设置或重置共享服务的相关实例
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
	public function resolve($parameters = null, \Leaps\DiInterface $dependencyInjector = null)
	{
		$shared = $this->_shared;
		/**
		 * 判断服务是否是共享的
		 */
		if ($shared) {
			$sharedInstance = $this->_sharedInstance;
			if ($sharedInstance !== null) {
				return $sharedInstance;
			}
		}
		$found = true;
		$instance = null;

		$definition = $this->_definition;
		if (gettype ( $definition ) == "string") {
			/**
			 * 定义是字符串
			 */
			if (class_exists ( $definition )) {
				if (gettype ( $parameters ) == "array") {
					if (count ( $parameters )) {
						if (version_compare ( PHP_VERSION, '5.6.0', '>=' )) {
							$reflection = new \ReflectionClass ( $definition );
							$instance = $reflection->newInstanceArgs ( $parameters );
						} else {
							$reflection = new \ReflectionClass ( $definition );
							$instance = $reflection->newInstanceArgs ( $parameters );
						}
					} else {
						if (version_compare ( PHP_VERSION, '5.6.0', '>=' )) {
							$reflection = new \ReflectionClass ( $definition );
							$instance = $reflection->newInstance ();
						} else {
							$instance = new $definition ();
						}
					}
				} else {
					if (version_compare ( PHP_VERSION, '5.6.0', '>=' )) {
						$reflection = new \ReflectionClass ( $definition );
						$instance = $reflection->newInstance ();
					} else {
						$instance = new $definition ();
					}
				}
			} else {
				$found = false;
			}
		} else {
			/**
			 * 对象定义
			 */
			if (gettype ( $definition ) == "object") {
				if ($definition instanceof \Closure) {
					if (gettype ( $parameters ) == "array") {
						$instance = call_user_func_array ( $definition, $parameters );
					} else {
						$instance = call_user_func ( $definition );
					}
				} else {
					$instance = $definition;
				}
			} else {
				/**
				 * 数组定义需要'className'参数
				 */
				if (gettype ( $definition ) == "array") {
					$instance = Kernel::createObject ( $definition );
				} else {
					$found = false;
				}
			}
		}

		/**
		 * 创建失败抛出异常
		 */
		if ($found === false) {
			throw new Exception ( "Service '" . $this->_name . "' cannot be resolved" );
		}

		/**
		 * 更新服务共享实例
		 */
		if ($shared) {
			$this->_sharedInstance = $instance;
		}

		$this->_resolved = true;

		return $instance;
	}

	/**
	 * 改变服务参数
	 *
	 * @param int position
	 * @param array parameter
	 * @return Leaps\Di\Service
	 */
	public function setParameter($position, $parameter)
	{
		$definition = $this->_definition;
		if (! is_array ( $definition )) {
			throw new Exception ( "Definition must be an array to update its parameters" );
		}

		/**
		 * 更新参数
		 */
		if (isset ( $definition ["arguments"] )) {
			$arguments = $definition ["arguments"];
			$arguments [$position] = $parameter;
		} else {
			$arguments = [ $position => $parameter ];
		}

		/**
		 * Re-update the arguments
		 */
		$definition ["arguments"] = $arguments;

		/**
		 * Re-update the definition
		 */
		$this->_definition = $definition;
		return $this;
	}

	/**
	 * 返回参数
	 *
	 * @param int position
	 * @return array
	 */
	public function getParameter($position)
	{
		$definition = $this->_definition;
		if (! is_array ( $definition )) {
			throw new Exception ( "Definition must be an array to obtain its parameters" );
		}
		if (isset ( $definition [$position] )) {
			return $definition [$position];
		}

		return null;
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