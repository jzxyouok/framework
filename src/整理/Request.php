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

use Leaps\Di\Injectable;

abstract class Request extends Injectable
{
	private $_scriptFile;
	private $_isConsoleRequest;

	/**
	 * Resolves the current request into a route and the associated parameters.
	 *
	 * @return array the first element is the route, and the second is the associated parameters.
	 */
	abstract public function resolve();

	/**
	 * 是否是通过命令行的请求
	 *
	 * @return boolean the value indicating whether the current request is made via console
	 */
	public function IsCli()
	{
		return $this->_isConsoleRequest !== null ? $this->_isConsoleRequest : PHP_SAPI === 'cli';
	}

	/**
	 * 设置请求是通过命令行
	 *
	 * @param boolean $value the value indicating whether the current request is made via command line
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
	 * The entry script file path can normally be determined based on the `SCRIPT_FILENAME` SERVER variable.
	 * However, for some server configurations, this may not be correct or feasible.
	 * This setter is provided so that the entry script file path can be manually specified.
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