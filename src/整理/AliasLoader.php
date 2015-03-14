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

/**
 * 类别名加载器 方便用别名的方式加载静态类
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class AliasLoader
{

	/**
	 * 已经注册的别名数组
	 *
	 * @var array
	 */
	protected $aliases = [ ];

	/**
	 * 是否注册到系统自动装载
	 *
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * 单例模式装载机
	 *
	 * @var \Leaps\Base\AliasLoader
	 */
	protected static $instance;

	/**
	 * 创建一个新的别名类加载器实例。
	 *
	 * @param array $aliases
	 * @return void
	 */
	public function __construct(array $aliases = [])
	{
		$this->aliases = $aliases;
	}

	/**
	 * 获取或创建单例别名装载器实例。
	 *
	 * @param array $aliases
	 * @return \Leaps\Base\AliasLoader
	 */
	public static function getInstance(array $aliases = [])
	{
		if (is_null ( static::$instance ))
			static::$instance = new static ( $aliases );
		$aliases = array_merge ( static::$instance->getAliases (), $aliases );
		static::$instance->setAliases ( $aliases );
		return static::$instance;
	}

	/**
	 * 加载一个已注册的类别名
	 *
	 * @param string $alias 别名
	 * @return void
	 */
	public function load($alias)
	{
		if (isset ( $this->aliases [$alias] )) {
			return class_alias ( $this->aliases [$alias], $alias );
		}
	}

	/**
	 * 添加类别名
	 *
	 * @param string $class 类名
	 * @param string $alias 别名
	 * @return void
	 */
	public function alias($class, $alias)
	{
		$this->aliases [$class] = $alias;
	}

	/**
	 * 在系统装载器堆栈注册装载器
	 *
	 * @return void
	 */
	public function register()
	{
		if (! $this->registered) {
			$this->prependToLoaderStack ();
			$this->registered = true;
		}
	}

	/**
	 * 注册到系统autoload
	 *
	 * @return void
	 */
	protected function prependToLoaderStack()
	{
		spl_autoload_register ( [ $this,'load' ], true, true );
	}

	/**
	 * 获得注册所有已经注册的类别名数组
	 *
	 * @return array
	 */
	public function getAliases()
	{
		return $this->aliases;
	}

	/**
	 * 设置已注册的别名
	 *
	 * @param array $aliases 别名数组
	 * @return void
	 */
	public function setAliases(array $aliases)
	{
		$this->aliases = $aliases;
	}

	/**
	 * 装载程序是否已经注册到系统
	 *
	 * @return bool
	 */
	public function isRegistered()
	{
		return $this->registered;
	}

	/**
	 * 设置装载机状态
	 *
	 * @param bool $value
	 * @return void
	 */
	public function setRegistered($value)
	{
		$this->registered = $value;
	}

	/**
	 * 设置别名装载机实例
	 *
	 * @param \Leaps\Base\AliasLoader $loader
	 * @return void
	 */
	public static function setInstance($loader)
	{
		static::$instance = $loader;
	}
}