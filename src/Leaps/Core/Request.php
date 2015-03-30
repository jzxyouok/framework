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
use Leaps\Di\InjectionAwareInterface;

abstract class Request extends Base implements InjectionAwareInterface
{
	/**
	 * 依赖注入器
	 *
	 * @var Leaps\Di\DiInteface
	 */
	protected $_dependencyInjector;
	private $_scriptFile;
	private $_isConsoleRequest;

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
		if (! is_object ( $this->_dependencyInjector )) {
			$this->_dependencyInjector = \Leaps\Di::getDefault ();
		}
		return $this->_dependencyInjector;
	}

	/**
	 * 解析请求参数
	 *
	 * @return array
	 */
	abstract public function resolve();

	/**
	 * 是否是通过命令行的请求
	 *
	 * @return boolean
	 */
	public function IsCli()
	{
		return $this->_isConsoleRequest !== null ? $this->_isConsoleRequest : PHP_SAPI === 'cli';
	}

	/**
	 * 设置请求是通过命令行
	 *
	 * @param boolean $value
	 */
	public function setIsCli($value)
	{
		$this->_isConsoleRequest = $value;
	}

	/**
	 * 返回入口脚本路径
	 *
	 * @return string entry script file path (processed w/ realpath())
	 * @throws InvalidConfigException if the entry script file path cannot be determined automatically.
	 */
	public function getScriptFile()
	{
		if ($this->_scriptFile === null) {
			if (isset ( $_SERVER ['SCRIPT_FILENAME'] )) {
				$this->setScriptFile ( $_SERVER ['SCRIPT_FILENAME'] );
			} else {
				throw new InvalidConfigException ( 'Unable to determine the entry script file path.' );
			}
		}
		return $this->_scriptFile;
	}

	/**
	 * 设置入口脚本路径
	 *
	 * @param string $value the entry script file path. This can be either a file path or a path alias.
	 * @throws InvalidConfigException if the provided entry script file path is invalid.
	 */
	public function setScriptFile($value)
	{
		$scriptFile = realpath ( Kernel::getAlias ( $value ) );
		if ($scriptFile !== false && is_file ( $scriptFile )) {
			$this->_scriptFile = $scriptFile;
		} else {
			throw new InvalidConfigException ( 'Unable to determine the entry script file path.' );
		}
	}
}