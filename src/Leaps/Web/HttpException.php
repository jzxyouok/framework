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
namespace Leaps\Web;

use Leaps\Core\UserException;

class HttpException extends UserException
{
	/**
	 *
	 * @var integer HTTP status code, such as 403, 404, 500, etc.
	 */
	public $statusCode;

	/**
	 * Constructor.
	 *
	 * @param integer $status HTTP status code, such as 404, 500, etc.
	 * @param string $message error message
	 * @param integer $code error code
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	public function __construct($status, $message = null, $code = 0, \Exception $previous = null)
	{
		$this->statusCode = $status;
		parent::__construct ( $message, $code, $previous );
	}

	/**
	 *
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		if (isset ( Response::$httpStatuses [$this->statusCode] )) {
			return Response::$httpStatuses [$this->statusCode];
		} else {
			return 'Error';
		}
	}
}
