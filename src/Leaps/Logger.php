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
use Leaps\Core\InvalidConfigException;

class Logger extends Injectable
{
	/**
	 * Error message level.
	 * An error message is one that indicates the abnormal termination of the
	 * application and may require developer's handling.
	 */
	const LEVEL_ERROR = 0x01;

	/**
	 * Warning message level.
	 * A warning message is one that indicates some abnormal happens but the
	 * application is able to continue to run. Developers should pay attention to this message.
	 */
	const LEVEL_WARNING = 0x02;

	/**
	 * Informational message level.
	 * An informational message is one that includes certain
	 * information for developers to review.
	 */
	const LEVEL_INFO = 0x04;

	/**
	 * Tracing message level.
	 * An tracing message is one that reveals the code execution flow.
	 */
	const LEVEL_TRACE = 0x08;

	/**
	 * Profiling message level.
	 * This indicates the message is for profiling purpose.
	 */
	const LEVEL_PROFILE = 0x40;

	/**
	 * Profiling message level.
	 * This indicates the message is for profiling purpose. It marks the
	 * beginning of a profiling block.
	 */
	const LEVEL_PROFILE_BEGIN = 0x50;

	/**
	 * Profiling message level.
	 * This indicates the message is for profiling purpose. It marks the
	 * end of a profiling block.
	 */
	const LEVEL_PROFILE_END = 0x60;
	public $messages;

	/**
	 *
	 * @var array debug data. This property stores various types of debug data reported at different
	 *      instrument places.
	 */
	public $data;

	/**
	 *
	 * @var array Target[] log targets. Each array element represents a single [[Target|log target]]
	 *      instance or the configuration for creating the log target instance.
	 */
	public $targets;

	/**
	 *
	 * @var integer how many messages should be logged before they are flushed from memory and sent
	 *      to targets. Defaults to 1000, meaning the [[flush]] method will be invoked once every
	 *      1000 messages logged. Set this property to be 0 if you don't want to flush messages
	 *      until the application terminates. This property mainly affects how much memory will be
	 *      taken by the logged messages. A smaller value means less memory, but will increase the
	 *      execution time due to the overhead of [[flush()]].
	 */
	public $flushInterval = 1000;

	/**
	 *
	 * @var integer how much call stack information (file name and line number) should be logged for
	 *      each message. If it is greater than 0, at most that number of call stacks will be
	 *      logged. Note that only application call stacks are counted. If not set, it will default
	 *      to 3 when `LEAPS_ENV` is set as "dev", and 0 otherwise.
	 */
	public $traceLevel;

	/**
	 * Initializes the logger by registering [[flush()]] as a shutdown function.
	 */
	public function init()
	{
		if ($this->traceLevel === null) {
			$this->traceLevel = Kernel::$env == Kernel::DEVELOPMENT ? 3 : 0;
		}
		if (is_array ( $this->targets )) {
			foreach ( $this->targets as $name => $target ) {
				if (! is_object ( $target )) {
					$this->targets [$name] = Kernel::createObject ( $target );
				}
			}
		}
		$this->messages = [ ];
		register_shutdown_function ( [ $this,"flush" ], true );
	}

	/**
	 * Logs a message with the given type and category.
	 * If [[traceLevel]] is greater than 0, additional call stack information about
	 * the application code will be logged as well.
	 *
	 * @param string $message the message to be logged.
	 * @param integer $level the level of the message. This must be one of the following:
	 *        `Logger::LEVEL_ERROR`, `Logger::LEVEL_WARNING`, `Logger::LEVEL_INFO`, `Logger::LEVEL_TRACE`,
	 *        `Logger::LEVEL_PROFILE_BEGIN`, `Logger::LEVEL_PROFILE_END`.
	 * @param string $category the category of the message.
	 */
	public function log($message, $level, $category = "application")
	{
		$time = microtime ( true );
		if ($this->traceLevel > 0) {
			$count = 0;
			$ts = debug_backtrace ( DEBUG_BACKTRACE_IGNORE_ARGS );
			array_pop ( $ts ); // remove the last trace since it would be the entry script, not very useful
			foreach ( $ts as $trace ) {
				if (isset ( $trace ["file"] ) && isset ( $trace ["line"] )) {
					unset ( $trace ["object"], $trace ["args"] );
					$traces [] = $trace;
					$count ++;
					if ($count >= $this->traceLevel) {
						break;
					}
				}
			}
		}
		$this->messages [] = [ $message,$level,$category,$time,$traces ];
		$messagescount = count ( $this->messages );
		if ($this->flushInterval > 0 && $messagescount >= $this->flushInterval) {
			$this->flush ();
		}
	}

	/**
	 * Returns the total elapsed time since the start of the current request.
	 * This method calculates the difference between now and the timestamp
	 * defined by constant `YII_BEGIN_TIME` which is evaluated at the beginning
	 * of [[\yii\BaseYii]] class file.
	 *
	 * @return float the total elapsed time in seconds for current request.
	 */
	public function getElapsedTime()
	{
		return microtime ( true ) - constant ( "START_TIME" );
	}

	/**
	 * Flushes log messages from memory to targets.
	 *
	 * @param boolean $final whether this is a final call during a request.
	 */
	public function flush($isFinal = false)
	{
		if (is_array ( $this->targets )) {
			foreach ( $this->targets as $target ) {
				if ($target->enabled) {
					$target->collect ( $this->messages, $isFinal );
				}
			}
		}
		$this->messages = [ ];
	}

	/**
	 * Flushes log messages from memory to targets.
	 *
	 * @param boolean $final whether this is a final call during a request.
	 */
	public function shutdown_flush()
	{
		$this->flush ( true );
	}
	private function calculateTimings()
	{
		$timings = [ ];
		$stack = [ ];
		foreach ( $this->messages as $log ) {
			list ( $token, $level, $category, $timestamp ) = $log;
			if ($level == self::LEVEL_PROFILE_BEGIN) {
				$stack [] = $log;
			} elseif ($level == self::LEVEL_PROFILE_END) {
				$last = array_pop ( $stack );
				if ($last !== null && $last [0] === $token) {
					$timings [] = [ $token,$category,$timestamp - $last [3] ];
				} else {
					throw new InvalidConfigException ( "Unmatched profiling block: $token" );
				}
			}
		}

		$now = microtime ( true );
		while ( ($last = array_pop ( $stack )) !== null ) {
			$delta = $now - $last [3];
			$timings [] = [ $last [0],$last [2],$delta ];
		}
		return $timings;
	}

	/**
	 * Returns the text display of the specified level.
	 *
	 * @param integer $level the message level, e.g. [[LEVEL_ERROR]], [[LEVEL_WARNING]].
	 * @return string the text display of the level
	 */
	public static function getLevelName($level)
	{
		static $levels = [ self::LEVEL_ERROR => "error",self::LEVEL_WARNING => "warning",self::LEVEL_INFO => "info",self::LEVEL_TRACE => "trace",self::LEVEL_PROFILE_BEGIN => "profile begin",self::LEVEL_PROFILE_END => "profile end" ];
		return isset ( $levels [$level] ) ? $levels [$level] : "unknown";
	}
}