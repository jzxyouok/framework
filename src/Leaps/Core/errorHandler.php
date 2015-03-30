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
use Leaps\Debug\Dump;

abstract class errorHandler extends Base implements \Leaps\Di\InjectionAwareInterface
{

	/**
	 * 依赖注入器
	 *
	 * @var Leaps\Di\DiInteface
	 */
	protected $_dependencyInjector;

	/**
	 *
	 * @var boolean whether to discard any existing page output before error display. Defaults to true.
	 */
	public $discardExistingOutput = true;

	/**
	 *
	 * @var integer the size of the reserved memory. A portion of memory is pre-allocated so that
	 *      when an out-of-memory issue occurs, the error handler is able to handle the error with
	 *      the help of this reserved memory. If you set this value to be 0, no memory will be reserved.
	 *      Defaults to 256KB.
	 */
	public $memoryReserveSize = 262144;

	/**
	 *
	 * @var \Exception the exception that is being handled currently.
	 */
	public $exception;

	/**
	 *
	 * @var string Used to reserve memory for fatal error handler.
	 */
	private $_memoryReserve;

	/**
	 * 设置依赖注入器
	 *
	 * @param Leaps\DiInterface dependencyInjector
	 */
	public function setDI(\Leaps\DiInterface $dependencyInjector)
	{
		if (! is_object ( $dependencyInjector )) {
			throw new \Leaps\Di\Exception ( "Dependency Injector is invalid" );
		}
		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * 返回依赖注入器实例
	 *
	 * @return Leaps\Di\DiInterface
	 */
	public function getDI()
	{
		$dependencyInjector = $this->_dependencyInjector;
		if (! is_object ( $dependencyInjector )) {
			$dependencyInjector = \Leaps\Di::getDefault ();
		}
		return $dependencyInjector;
	}

	/**
	 * 监听异常
	 *
	 * @return Leaps\Debug
	 */
	public function register()
	{
		//ini_set ( 'display_errors', false );
		set_exception_handler ( [
				$this,
				'handleException'
		] );
		set_error_handler ( [
				$this,
				'handleError'
		] );
		if ($this->memoryReserveSize > 0) {
			$this->_memoryReserve = str_repeat ( 'x', $this->memoryReserveSize );
		}
		register_shutdown_function ( [
				$this,
				'handleFatalError'
		] );
	}

	/**
	 * 恢复PHP异常监听
	 */
	public function unregister()
	{
		restore_error_handler ();
		restore_exception_handler ();
	}

	/**
	 * 渲染异常
	 *
	 * @param \Exception $exception the exception to be rendered.
	 */
	abstract protected function renderException($exception);

	/**
	 * PHP异常处理
	 *
	 * This method is implemented as a PHP exception handler.
	 *
	 * @param \Exception $exception the exception that is not caught
	 */
	public function handleException($exception)
	{
		if ($exception instanceof ExitException) {
			return;
		}

		$this->exception = $exception;

		// 处理异常时禁用错误捕获避免递归误差
		$this->unregister ();

		try {
			$this->logException ( $exception );
			if ($this->discardExistingOutput) {
				$this->clearOutput ();
			}
			$this->renderException ( $exception );
			if (! Kernel::$env != Kernel::TEST) {
				exit ( 1 );
			}
		} catch ( \Exception $e ) {
			// an other exception could be thrown while displaying the exception
			$msg = ( string ) $e;
			$msg .= "\nPrevious exception:\n";
			$msg .= ( string ) $exception;
			if (Kernel::$env == Kernel::DEVELOPMENT) {
				if (PHP_SAPI === 'cli') {
					echo $msg . "\n";
				} else {
					echo '<pre>' . htmlspecialchars ( $msg, ENT_QUOTES, Kernel::$app->charset ) . '</pre>';
				}
			}
			$msg .= "\n\$_SERVER = " . Dump::export ( $_SERVER );
			error_log ( $msg );
			exit ( 1 );
		}
		$this->exception = null;
	}

	/**
	 * 处理错误
	 *
	 * @param unknown $code
	 * @param unknown $message
	 * @param unknown $file
	 * @param unknown $line
	 * @throws \Leaps\ErrorException
	 * @return boolean
	 */
	public function handleError($code, $message, $file, $line)
	{
		if (error_reporting () & $code) {
			// load ErrorException manually here because autoloading them will not work
			// when error occurs while autoloading a class
			if (! class_exists ( 'Leaps\\ErrorException', false )) {
				require_once (__DIR__ . '/ErrorException.php');
			}
			$exception = new ErrorException ( $message, $code, $code, $file, $line );
			// in case error appeared in __toString method we can't throw any exception
			$trace = debug_backtrace ( DEBUG_BACKTRACE_IGNORE_ARGS );
			array_shift ( $trace );
			foreach ( $trace as $frame ) {
				if ($frame ['function'] == '__toString') {
					$this->handleException ( $exception );
					exit ( 1 );
				}
			}
			throw $exception;
		}
		return false;
	}

	/**
	 * 处理致命PHP错误
	 */
	public function handleFatalError()
	{
		unset ( $this->_memoryReserve );
		// load ErrorException manually here because autoloading them will not work
		// when error occurs while autoloading a class
		if (! class_exists ( 'Leaps\\ErrorException', false )) {
			require_once (__DIR__ . '/ErrorException.php');
		}
		$error = error_get_last ();
		if (ErrorException::isFatalError ( $error )) {
			$exception = new ErrorException ( $error ['message'], $error ['type'], $error ['type'], $error ['file'], $error ['line'] );
			$this->exception = $exception;
			$this->logException ( $exception );
			if ($this->discardExistingOutput) {
				$this->clearOutput ();
			}
			$this->renderException ( $exception );
			// need to explicitly flush logs because exit() next will terminate the app immediately
			// Yii::getLogger()->flush(true);
			exit ( 1 );
		}
	}

	/**
	 * Generates a link to the current version documentation
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return "<div class=\"version\">Leaps Framework <a target=\"_new\" href=\"http://leaps.tintsoft.com/" . $this->getMajorVersion () . "/\">" . \Leaps\Version::get () . "</a></div>";
	}

	/**
	 * 返回框架大版本
	 *
	 * @return string
	 */
	public function getMajorVersion()
	{
		$parts = explode ( " ", \Leaps\Version::get () );
		return $parts [0];
	}

	/**
	 * 记录异常到日志
	 *
	 * @param \Exception $exception the exception to be logged
	 */
	protected function logException($exception)
	{
		$category = get_class ( $exception );
		if ($exception instanceof \Leaps\Web\HttpException) {
			$category = 'Leaps\\Web\\HttpException:' . $exception->statusCode;
		} elseif ($exception instanceof \ErrorException) {
			$category .= ':' . $exception->getSeverity ();
		}
		Kernel::error ( ( string ) $exception, $category );
	}

	/**
	 * 删除缓冲区数据
	 */
	public function clearOutput()
	{
		for($level = ob_get_level (); $level > 0; -- $level) {
			if (! @ob_end_clean ()) {
				ob_clean ();
			}
		}
	}
}