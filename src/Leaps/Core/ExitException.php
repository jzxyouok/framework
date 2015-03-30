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

class ExitException extends Exception
{
	/**
	 * 退出状态代码
	 *
	 * @var integer
	 */
	public $statusCode;

	/**
	 * 构造方法
	 *
	 * @param integer $status 退出状态代码
	 * @param string $message 错误消息
	 * @param integer $code 错误代码
	 * @param \Exception $previous
	 */
	public function __construct($status = 0, $message = null, $code = 0, \Exception $previous = null)
	{
		$this->statusCode = $status;
		parent::__construct ( $message, $code, $previous );
	}
}