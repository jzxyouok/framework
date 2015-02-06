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

abstract class Application extends Di
{
	/**
	 * 当前app名称
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * 应用配置
	 * @var array
	 */
	protected $config = [];

	protected $_defaultModule;

	/**
	 * 构造方法
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		parent::__construct();
		Kernel::$app = $this;
		$this->config = $config;
		$this->preInit ();
		$this->init ();
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
			//$this->get('event')->trigger(self::EVENT_BEFORE_REQUEST);
			$response = $this->handleRequest($this->getRequest());
			//$this->event->trigger(self::EVENT_AFTER_REQUEST);
			$response->send();
			return $response->exitStatus;
		} catch ( ExitException $e ) {
			//$this->end($e->statusCode, isset($response) ? $response : null);
			return $e->statusCode;
		}
	}

	/**
	 * 初始化
	 */
	public function init(){

	}

	/**
	 * 初始化配置信息
	 */
	public function preInit()
	{
		if (isset($this->config['isclosed']) && $this->config['isclosed']) {
			throw new Exception('Sorry, Site has been closed!');
		}
		if (isset($this->config['id'])) {
			$this->id = $this->config['id'];
			unset($this->config['id']);
		} else {
			throw new InvalidConfigException('The "id" configuration for the Application is required.');
		}

		if (isset($this->config['basePath'])) {
			$this->setBasePath($this->config['basePath']);
			unset($this->config['basePath']);
		} else {
			throw new InvalidConfigException('The "basePath" configuration for the Application is required.');
		}

		if (isset($this->config['vendorPath'])) {
			$this->setVendorPath($this->config['vendorPath']);
			unset($this->config['vendorPath']);
		} else {
			// set "@vendor"
			$this->getVendorPath();
		}

		if (isset($this->config['runtimePath'])) {
			$this->setRuntimePath($this->config['runtimePath']);
			unset($this->config['runtimePath']);
		} else {
			// set "@runtime"
			$this->getRuntimePath();
		}

		if (isset($this->config['timeZone'])) {
			$this->setTimeZone($this->config['timeZone']);
			unset($this->config['timeZone']);
		} elseif (!ini_get('date.timezone')) {
			$this->setTimeZone('UTC');
		}
		$this->config ['services'] = Arr::mergeArray($this->coreServices(),$this->config ['services']);
		foreach ( $this->config ['services'] as $id => $service ) {
			$this->set ( $id, $service );
		}
	}

	/**
	 * Register an array of modules present in the application
	 *
	 *<code>
	 *	$this->registerModules(array(
	 *		'frontend' => array(
	 *			'className' => 'Multiple\Frontend\Module',
	 *			'path' => '../apps/frontend/Module.php'
	 *		),
	 *		'backend' => array(
	 *			'className' => 'Multiple\Backend\Module',
	 *			'path' => '../apps/backend/Module.php'
	 *		)
	 *	));
	 *</code>
	 *
	 * @param array modules
	 * @param boolean merge
	 * @param Leaps\Application
	 */
	public function registerModules($modules, $merge = false)
	{
		if ($merge === false) {
			$this->_modules = $modules;
		} else {
			if (is_array($this->_modules)) {
				$this->_modules = array_merge($this->_modules, $modules);
			} else {
				$this->_modules = $modules;
			}
		}
		return $this;
	}

	/**
	 * Return the modules registered in the application
	 *
	 * @return array
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * Gets the module definition registered in the application via module name
	 *
	 * @param string name
	 * @return array|object
	 */
	public function getModule($name)
	{
		if (isset($this->_modules[$name])) {
			throw new Exception("Module '" . $name . "' isn't registered in the application container");
		}
		return $this->_modules[$name];
	}


	private $_basePath;

	/**
	 * Returns the root directory of the module.
	 * It defaults to the directory containing the module class file.
	 * @return string the root directory of the module.
	 */
	public function getBasePath()
	{
		if ($this->_basePath === null) {
			$class = new \ReflectionClass($this);
			$this->_basePath = dirname($class->getFileName());
		}

		return $this->_basePath;
	}

	/**
	 * 设置应用程序的根目录和@App别名。
	 *
	 * @param string $path the root directory of the application.
	 * @property string the root directory of the application.
	 * @throws InvalidParamException if the directory does not exist.
	 */
	public function setBasePath($path)
	{
		$path = Kernel::getAlias($path);
		$p = realpath($path);
		if ($p !== false && is_dir($p)) {
			$this->_basePath = $p;
		} else {
			throw new InvalidParamException("The directory does not exist: $path");
		}
		Kernel::setAlias('@app', $this->getBasePath());
	}

	/**
	 *
	 * @var string 运行时文件目录
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
	 * @return string the directory that stores vendor files.
	 * Defaults to "vendor" directory under [[basePath]].
	 */
	public function getVendorPath()
	{
		if ($this->_vendorPath === null) {
			$this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'Vendor');
		}

		return $this->_vendorPath;
	}

	/**
	 * Sets the directory that stores vendor files.
	 * @param string $path the directory that stores vendor files.
	 */
	public function setVendorPath($path)
	{
		$this->_vendorPath = Kernel::getAlias($path);
		Kernel::setAlias('@vendor', $this->_vendorPath);
		Kernel::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
		Kernel::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
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
	 * @return multitype:multitype:string
	 */
	public function coreServices()
	{
		return [
				'file'=>['className'=>'Leaps\Filesystem\Filesystem'],
				'crypt'=>['className'=>'Leaps\Crypt\Crypt'],
				'event' => [ 'className' => 'Leaps\Event\Dispatcher'],
				'registry' => ['className' => 'Leaps\Registry'],
				'log'=>['className'=>'Leaps\Log\Logger'],
				'cache'=>['className'=>'Leaps\Cache\DummyCache'],
		];
	}
}