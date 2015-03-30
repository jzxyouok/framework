<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2013 TintSoft LLC
 * @license http://www.tintsoft.com/license/
 */
namespace Leaps\Db;
use Leaps;
/**
 * 异常是由一些DB-related操作引起的。
 *
 * @author Tongle Xu <xutongle@gmail.com>
 * @since 5.0
 */
class Exception extends \Leaps\Base\Exception
{
	/**
	 * PDO异常提供的错误信息。
	 * @var array
	 * by [PDO::errorInfo](http://www.php.net/manual/en/pdo.errorinfo.php).
	 */
	public $errorInfo = [];

	/**
	 * 构造方法
	 * @param string $message PDO error message
	 * @param array $errorInfo PDO error info
	 * @param integer $code PDO error code
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	public function __construct($message, $errorInfo = [], $code = 0, \Exception $previous = null)
	{
		$this->errorInfo = $errorInfo;
		parent::__construct($message, $code, $previous);
	}

	/**
	 * 返回用户友好的错误名称
	 * @return string
	 */
	public function getName()
	{
		return Leaps::t('leaps', 'Database Exception');
	}
}
