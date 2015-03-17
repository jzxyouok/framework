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

class ExitException extends Exception
{
	/**
	 *
	 * @var integer the exit status code
	 */
	public $statusCode;

	/**
	 * Constructor.
	 *
	 * @param integer $status the exit status code
	 * @param string $message error message
	 * @param integer $code error code
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	public function __construct($status = 0, $message = null, $code = 0, \Exception $previous = null)
	{
		$this->statusCode = $status;
		parent::__construct ( $message, $code, $previous );
	}
}