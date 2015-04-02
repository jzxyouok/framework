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
namespace Leaps\Log;

use Leaps\Logger;
use Leaps\Di\Injectable;
use Leaps\Core\InvalidConfigException;

abstract class Target extends Injectable
{
	/**
	 *
	 * @var boolean whether to enable this log target. Defaults to true.
	 */
	public $enabled = true;

	/**
	 *
	 * @var array list of the PHP predefined variables that should be logged in a message.
	 *      Note that a variable must be accessible via `$GLOBALS`. Otherwise it won't be logged.
	 *      Defaults to `['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_SERVER']`.
	 */
	public $logVars = [ "_GET","_POST","_FILES","_COOKIE","_SESSION","_SERVER" ];

	/**
	 *
	 * @var callable a PHP callable that returns a string to be prefixed to every exported message.
	 *
	 *      If not set, [[getMessagePrefix()]] will be used, which prefixes the message with context information
	 *      such as user IP, user ID and session ID.
	 *
	 *      The signature of the callable should be `function ($message)`.
	 */
	public $prefix;
	/**
	 *
	 * @var integer how many messages should be accumulated before they are exported.
	 *      Defaults to 1000. Note that messages will always be exported when the application terminates.
	 *      Set this property to be 0 if you don't want to export messages until the application terminates.
	 */
	public $exportInterval = 1000;

	/**
	 *
	 * @var array the messages that are retrieved from the logger so far by this log target.
	 *      Please refer to [[Logger::messages]] for the details about the message structure.
	 */
	public $messages = [ ];
	private $_levels = 0;

	/**
	 * Exports log [[messages]] to a specific destination.
	 * Child classes must implement this method.
	 */
	abstract public function export();

	/**
	 * 处理日志消息
	 *
	 * @param array $messages log messages to be processed. See [[Logger::messages]] for the
	 *        structure of each message.
	 * @param boolean $final whether this method is called at the end of the current application
	 */
	public function collect(array $messages, $isFinal)
	{
		$this->messages = array_merge ( $this->messages, $this->filterMessages ( $messages, $this->getLevels () ) );
		$count = count ( $this->messages );
		if ($count > 0 && ($isFinal || $this->exportInterval > 0 && $count >= $this->exportInterval)) {
			//if (($context = $this->getContextMessage ()) !== '') {
			//	$this->messages [] = [ $context,Logger::LEVEL_INFO,"application",START_TIME ];
			//}
			$this->export ();
			$this->messages = [ ];
		}
	}

	/**
	 * 生成记录的上下文信息。
	 *
	 * @return string the context information. If an empty string, it means no context information.
	 */
	protected function getContextMessage()
	{
		$context = [ ];
		foreach ( $this->logVars as $name ) {
			if (! empty ( $GLOBALS [$name] )) {
				$context [] = "\${$name} = " . var_export ( $GLOBALS [$name], true );
			}
		}
		return implode ( "\n\n", $context );
	}

	/**
	 * 获取消息级别
	 *
	 * @return integer the message levels that this target is interested in. This is a bitmap of
	 *         level values. Defaults to 0, meaning all available levels.
	 */
	public function getLevels()
	{
		return $this->_levels;
	}

	/**
	 * Sets the message levels that this target is interested in.
	 *
	 * The parameter can be either an array of interested level names or an integer representing
	 * the bitmap of the interested level values. Valid level names include: 'error',
	 * 'warning', 'info', 'trace' and 'profile'; valid level values include:
	 * [[Logger::LEVEL_ERROR]], [[Logger::LEVEL_WARNING]], [[Logger::LEVEL_INFO]],
	 * [[Logger::LEVEL_TRACE]] and [[Logger::LEVEL_PROFILE]].
	 *
	 * For example,
	 *
	 * ~~~
	 * ['error', 'warning']
	 * // which is equivalent to:
	 * Logger::LEVEL_ERROR | Logger::LEVEL_WARNING
	 * ~~~
	 *
	 * @param array|integer $levels message levels that this target is interested in.
	 * @throws InvalidConfigException if an unknown level name is given
	 */
	public function setLevels($levels)
	{
		static $levelMap = [ "error" => Logger::LEVEL_ERROR,"warning" => Logger::LEVEL_WARNING,"info" => Logger::LEVEL_INFO,"trace" => Logger::LEVEL_TRACE,"profile" => Logger::LEVEL_PROFILE ];
		if (is_array ( $levels )) {
			$this->_levels = 0;
			foreach ( $levels as $level ) {
				if (isset ( $levelMap [$level] )) {
					$this->_levels |= $levelMap [$level];
				} else {
					throw new InvalidConfigException ( "Unrecognized level: $level" );
				}
			}
		} else {
			$this->_levels = $levels;
		}
	}

	/**
	 * 根据级别过滤消息
	 *
	 * @param array $messages 消息的结构
	 * @param integer $levels 消息级别的过滤器。这是一个位图的水平值,值为0意味着允许各级。
	 * @return array 过滤后的消息
	 */
	public static function filterMessages(array $messages, $levels = 0)
	{
		foreach ( $messages as $i => $message ) {
			if ($levels && ! ($levels & $message [1])) {
				unset ( $messages [i] );
			}
		}
		return $messages;
	}

	/**
	 * 格式化消息
	 *
	 * @param array $message the log message to be formatted.
	 * @return string the formatted message
	 */
	public function formatMessage($message)
	{
		list ( $text, $level, $category, $timestamp ) = $message;
		$level = Logger::getLevelName ( $level );
		if (! is_string ( $text )) {
			$text = var_export ( $text, true );
		}
		$ip = isset ( $_SERVER ['REMOTE_ADDR'] ) ? $_SERVER ['REMOTE_ADDR'] : '127.0.0.1';
		return date ( "Y/m/d H:i:s", $timestamp ) . " [" . $ip . "] [" . $level . "] [" . $category . "] " . $text;
	}
}