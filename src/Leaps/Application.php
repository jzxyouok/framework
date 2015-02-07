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

abstract class Application extends Module
{
	/**
	 * 应用程序启动事件
	 * @event Event
	 */
	const EVENT_BEFORE_REQUEST = 'beforeRequest';

	/**
	 * 应用程序退出事件
	 * @event Event
	 */
	const EVENT_AFTER_REQUEST = 'afterRequest';

	/**
	 * 当前应用程序编码
	 *
	 * @var string
	 */
	public $charset = 'UTF-8';

	/**
	 * 最终用户语言
	 * @var string
	 * @see sourceLanguage
	 */
	public $language = 'en-US';

	/**
	 * 当前活跃的控制器实例
	 * @var Controller
	 */
	public $controller;

	/**
	 * 应用程序布局，false为禁用
	 * @var string|boolean
	 */
	public $layout = 'main';

	/**
	 * 请求的操作
	 * @var Action
	 */
	public $requestedAction;

	/**
	 * 请求的路由
	 *
	 * @var string
	 */
	public $requestedRoute;

	/**
	 * 请求操作参数
	 * @var array
	 */
	public $requestedParams;

	/**
	 *
	 * @var array 已经加载的模块列表
	 */
	public $loadedModules = [ ];

	/**
	 * 构造方法
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		Kernel::$app = $this;
		$this->setInstance($this);
		$this->preInit ( $config );
		$this->init ();
		Di::__construct();
	}

	/**
	 * 初始化配置信息
	 */
	public function preInit($config)
	{
		if (isset ( $config ['id'] )) {
			$this->id = $config ['id'];
			unset ( $config ['id'] );
		} else {
			throw new InvalidConfigException ( 'The "id" configuration for the Application is required.' );
		}

		if (isset ( $config ['charset'] )) {
			$this->id = $config ['charset'];
			unset ( $config ['charset'] );
		}

		if (isset ( $config ['language'] )) {
			$this->language = $config ['language'];
			unset ( $config ['language'] );
		}

		if (isset ( $config ['layout'] )) {
			$this->layout = $config ['layout'];
			unset ( $config ['layout'] );
		}

		if (isset ( $config ['basePath'] )) {
			$this->setBasePath ( $config ['basePath'] );
			unset ( $config ['basePath'] );
		} else {
			throw new InvalidConfigException ( 'The "basePath" configuration for the Application is required.' );
		}

		if (isset ( $config ['vendorPath'] )) {
			$this->setVendorPath ( $config ['vendorPath'] );
			unset ( $config ['vendorPath'] );
		} else {
			// set "@vendor"
			$this->getVendorPath ();
		}

		if (isset ( $config ['runtimePath'] )) {
			$this->setRuntimePath ( $config ['runtimePath'] );
			unset ( $config ['runtimePath'] );
		} else {
			// set "@runtime"
			$this->getRuntimePath ();
		}

		if (isset ( $config ['timeZone'] )) {
			$this->setTimeZone ( $config ['timeZone'] );
			unset ( $config ['timeZone'] );
		} elseif (! ini_get ( 'date.timezone' )) {
			$this->setTimeZone ( 'UTC' );
		}

		// 注册Module
		if (isset ( $config ['modules'] )) {
			$this->setModules ( $config ['modules'] );
			unset ( $config ['modules'] );
		}

		$config ['services'] = Arr::mergeArray ( $this->coreServices (), $config ['services'] );
		foreach ( $config ['services'] as $id => $service ) {
			$this->set ( $id, $service );
		}
		unset ( $config ['services'] );
	}

	/**
	 * 初始化模块信息
	 */
	public function init()
	{
	}

	/**
	 * 处理指定的请求
	 *
	 * 此方法返回 [[Response]] 实例或其子类来表示处理请求的结果。
	 *
	 * @param Request $request the request to be handled
	 * @return Response the resulting response
	 */
	abstract public function handleRequest($request);

	/**
	 * 执行应用程序
	 * 这是应用程序入口
	 *
	 * @return integer 退出状态 (0 means normal, non-zero values mean abnormal)
	 */
	public function run()
	{
		try {
			$this->get ( 'event' )->trigger ( self::EVENT_BEFORE_REQUEST );
			$response = $this->handleRequest ( $this->getRequest () );
			$this->event->trigger ( self::EVENT_AFTER_REQUEST );
			$response->send ();
			return $response->exitStatus;
		} catch ( ExitException $e ) {
			return $e->statusCode;
		}
	}

	/**
	 * 返回应用程序的唯一ID
	 *
	 * @return string the unique ID of the module.
	 */
	public function getUniqueId()
	{
		return '';
	}

	/**
	 * 设置应用程序的根目录和@App别名。
	 *
	 * @param string $path 应用程序跟目录
	 * @property string the root directory of the application.
	 * @throws InvalidParamException 如果文件夹不存在抛出异常
	 */
	public function setBasePath($path)
	{
		parent::setBasePath ( $path );
		Kernel::setAlias ( '@app', $this->getBasePath () );
		Kernel::setAlias ( '@Module', $this->getBasePath ().'/Module' );
	}

	/**
	 * 运行时文件目录
	 *
	 * @var string
	 */
	private $_runtimePath;

	/**
	 * 返回存储运行时文件的目录。
	 *
	 * @return
	 *
	 */
	public function getRuntimePath()
	{
		if ($this->_runtimePath === null) {
			$this->setRuntimePath ( $this->getBasePath () . DIRECTORY_SEPARATOR . 'Runtime' );
		}
		return $this->_runtimePath;
	}

	/**
	 * 设置存储运行时文件的目录。
	 *
	 * @param string $path
	 */
	public function setRuntimePath($path)
	{
		$this->_runtimePath = Kernel::getAlias ( $path );
		Kernel::setAlias ( '@Runtime', $this->_runtimePath );
	}
	private $_vendorPath;

	/**
	 * Returns the directory that stores vendor files.
	 *
	 * @return string the directory that stores vendor files.
	 *         Defaults to "vendor" directory under [[basePath]].
	 */
	public function getVendorPath()
	{
		if ($this->_vendorPath === null) {
			$this->setVendorPath ( $this->getBasePath () . DIRECTORY_SEPARATOR . 'Vendor' );
		}

		return $this->_vendorPath;
	}

	/**
	 * Sets the directory that stores vendor files.
	 *
	 * @param string $path the directory that stores vendor files.
	 */
	public function setVendorPath($path)
	{
		$this->_vendorPath = Kernel::getAlias ( $path );
		Kernel::setAlias ( '@vendor', $this->_vendorPath );
		Kernel::setAlias ( '@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower' );
		Kernel::setAlias ( '@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm' );
	}

	/**
	 * 返回应用程序时区
	 *
	 * @return string the time zone used by this application.
	 * @see http://php.net/manual/en/function.date-default-timezone-get.php
	 */
	public function getTimeZone()
	{
		return date_default_timezone_get ();
	}

	/**
	 * 设置当前应用所属时区
	 *
	 * @param string $value 应用程序使用的时区
	 * @see http://php.net/manual/en/function.date-default-timezone-set.php
	 */
	public function setTimeZone($value)
	{
		date_default_timezone_set ( $value );
	}

	/**
	 * 核心服务
	 *
	 * @return multitype:multitype:string
	 */
	public function coreServices()
	{
		return [
				'file' => [
						'className' => 'Leaps\Filesystem\Filesystem'
				],
				'crypt' => [
						'className' => 'Leaps\Crypt\Crypt'
				],
				'event' => [
						'className' => 'Leaps\Event\Dispatcher'
				],
				'registry' => [
						'className' => 'Leaps\Registry'
				],
				'log' => [
						'className' => 'Leaps\Log\Logger'
				],
				'cache' => [
						'className' => 'Leaps\Cache\FileCache'
				]
		];
	}
}