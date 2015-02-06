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

use Leaps\Kernel;
use Leaps\InvalidConfigException;
use Leaps\InvalidParamException;

class Response extends \Leaps\Response
{
	const FORMAT_RAW = 'raw';
	const FORMAT_HTML = 'html';
	const FORMAT_JSON = 'json';
	const FORMAT_JSONP = 'jsonp';
	const FORMAT_XML = 'xml';
	public $format = self::FORMAT_HTML;
	public $acceptMimeType;
	public $acceptParams = [ ];
	public $formatters = [ ];
	public $data;
	public $content;
	public $stream;
	public $charset;

	/**
	 *
	 * @var string HTTP状态描述
	 * @see httpStatuses
	 */
	public $statusText = 'OK';

	/**
	 *
	 * @var string the version of the HTTP protocol to use. If not set, it will be determined via `$_SERVER['SERVER_PROTOCOL']`,
	 *      or '1.1' if that is not available.
	 */
	public $version;

	/**
	 *
	 * @var boolean whether the response has been sent. If this is true, calling [[send()]] will do nothing.
	 */
	public $isSent = false;
	public static $httpStatuses = [ 100 => 'Continue',101 => 'Switching Protocols',102 => 'Processing',118 => 'Connection timed out',200 => 'OK',201 => 'Created',202 => 'Accepted',203 => 'Non-Authoritative',204 => 'No Content',205 => 'Reset Content',206 => 'Partial Content',
			207 => 'Multi-Status',208 => 'Already Reported',210 => 'Content Different',226 => 'IM Used',300 => 'Multiple Choices',301 => 'Moved Permanently',302 => 'Found',303 => 'See Other',304 => 'Not Modified',305 => 'Use Proxy',306 => 'Reserved',307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',310 => 'Too many Redirect',400 => 'Bad Request',401 => 'Unauthorized',402 => 'Payment Required',403 => 'Forbidden',404 => 'Not Found',405 => 'Method Not Allowed',406 => 'Not Acceptable',407 => 'Proxy Authentication Required',408 => 'Request Time-out',
			409 => 'Conflict',410 => 'Gone',411 => 'Length Required',412 => 'Precondition Failed',413 => 'Request Entity Too Large',414 => 'Request-URI Too Long',415 => 'Unsupported Media Type',416 => 'Requested range unsatisfiable',417 => 'Expectation failed',418 => 'I\'m a teapot',
			422 => 'Unprocessable entity',423 => 'Locked',424 => 'Method failure',425 => 'Unordered Collection',426 => 'Upgrade Required',428 => 'Precondition Required',429 => 'Too Many Requests',431 => 'Request Header Fields Too Large',449 => 'Retry With',
			450 => 'Blocked by Windows Parental Controls',500 => 'Internal Server Error',501 => 'Not Implemented',502 => 'Bad Gateway or Proxy Error',503 => 'Service Unavailable',504 => 'Gateway Time-out',505 => 'HTTP Version not supported',507 => 'Insufficient storage',508 => 'Loop Detected',
			509 => 'Bandwidth Limit Exceeded',510 => 'Not Extended',511 => 'Network Authentication Required' ];

	/**
	 *
	 * @var integer HTTP响应状态代码
	 */
	private $_statusCode = 200;

	/**
	 *
	 * @var HeaderCollection
	 */
	private $_headers;

	/**
	 * 获取HTTP响应状态代码
	 *
	 * @return integer HTTP响应状态代码
	 */
	public function getStatusCode()
	{
		return $this->_statusCode;
	}

	/**
	 * 设置响应代码
	 * This method will set the corresponding status text if `$text` is null.
	 *
	 * @param integer $value the status code
	 * @param string $text the status text. If not set, it will be set automatically based on the status code.
	 * @throws InvalidParamException if the status code is invalid.
	 */
	public function setStatusCode($value, $text = null)
	{
		if ($value === null) {
			$value = 200;
		}
		$this->_statusCode = ( int ) $value;
		if ($this->getIsInvalid ()) {
			throw new InvalidParamException ( "The HTTP status code is invalid: $value" );
		}
		if ($text === null) {
			$this->statusText = isset ( static::$httpStatuses [$this->_statusCode] ) ? static::$httpStatuses [$this->_statusCode] : '';
		} else {
			$this->statusText = $text;
		}
	}

	/**
	 * 返回Header集合
	 * The header collection contains the currently registered HTTP headers.
	 * @return HeaderCollection the header collection
	 */
	public function getHeaders()
	{
		if ($this->_headers === null) {
			$this->_headers = new HeaderCollection;
		}
		return $this->_headers;
	}

	/**
	 * 发送响应给客户端
	 *
	 * @see \Leaps\Response::send()
	 */
	public function send()
	{
		if ($this->isSent) {
			return;
		}
		// $this->trigger(self::EVENT_BEFORE_SEND);
		$this->prepare ();
		// $this->trigger(self::EVENT_AFTER_PREPARE);
		$this->sendHeaders ();
		$this->sendContent ();
		// $this->trigger(self::EVENT_AFTER_SEND);
		$this->isSent = true;
	}

	/**
	 * 清理响应
	 */
	public function clear()
	{
		$this->_headers = null;
		$this->_cookies = null;
		$this->_statusCode = 200;
		$this->statusText = 'OK';
		$this->data = null;
		$this->stream = null;
		$this->content = null;
		$this->isSent = false;
	}

	/**
	 * 发送Header到客户端
	 */
	protected function sendHeaders()
	{
		if (headers_sent ()) {
			return;
		}
		$statusCode = $this->getStatusCode ();
		header ( "HTTP/{$this->version} $statusCode {$this->statusText}" );
		if ($this->_headers) {
			$headers = $this->getHeaders ();
			foreach ( $headers as $name => $values ) {
				$name = str_replace ( ' ', '-', ucwords ( str_replace ( '-', ' ', $name ) ) );
				// set replace for first occurrence of header but false afterwards to allow multiple
				$replace = true;
				foreach ( $values as $value ) {
					header ( "$name: $value", $replace );
					$replace = false;
				}
			}
		}
		$this->sendCookies ();
	}

	/**
	 * 发送Cookie到客户端
	 */
	protected function sendCookies()
	{
		if ($this->_cookies === null) {
			return;
		}
		$request = Yii::$app->getRequest ();
		if ($request->enableCookieValidation) {
			if ($request->cookieValidationKey == '') {
				throw new InvalidConfigException ( get_class ( $request ) . '::cookieValidationKey must be configured with a secret key.' );
			}
			$validationKey = $request->cookieValidationKey;
		}
		foreach ( $this->getCookies () as $cookie ) {
			$value = $cookie->value;
			if ($cookie->expire != 1 && isset ( $validationKey )) {
				$value = Yii::$app->getSecurity ()->hashData ( serialize ( [ $cookie->name,$value ] ), $validationKey );
			}
			setcookie ( $cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly );
		}
		$this->getCookies ()->removeAll ();
	}

	/**
	 * 发送内容到客户端
	 */
	protected function sendContent()
	{
		if ($this->stream === null) {
			echo $this->content;
			return;
		}
		set_time_limit ( 0 ); // Reset time limit for big files
		$chunkSize = 8 * 1024 * 1024; // 8MB per chunk
		if (is_array ( $this->stream )) {
			list ( $handle, $begin, $end ) = $this->stream;
			fseek ( $handle, $begin );
			while ( ! feof ( $handle ) && ($pos = ftell ( $handle )) <= $end ) {
				if ($pos + $chunkSize > $end) {
					$chunkSize = $end - $pos + 1;
				}
				echo fread ( $handle, $chunkSize );
				flush (); // Free up memory. Otherwise large files will trigger PHP's memory limit.
			}
			fclose ( $handle );
		} else {
			while ( ! feof ( $this->stream ) ) {
				echo fread ( $this->stream, $chunkSize );
				flush ();
			}
			fclose ( $this->stream );
		}
	}
	private $_cookies;
	/**
	 * Returns the cookie collection.
	 * Through the returned cookie collection, you add or remove cookies as follows,
	 *
	 * ~~~
	 * // add a cookie
	 * $response->cookies->add(new Cookie([
	 * 'name' => $name,
	 * 'value' => $value,
	 * ]);
	 *
	 * // remove a cookie
	 * $response->cookies->remove('name');
	 * // alternatively
	 * unset($response->cookies['name']);
	 * ~~~
	 *
	 * @return CookieCollection the cookie collection.
	 */
	public function getCookies()
	{
		if ($this->_cookies === null) {
			$this->_cookies = new CookieCollection ();
		}
		return $this->_cookies;
	}

	/**
	 *
	 * @return boolean whether this response has a valid [[statusCode]].
	 */
	public function getIsInvalid()
	{
		return $this->getStatusCode () < 100 || $this->getStatusCode () >= 600;
	}

	/**
	 *
	 * @return boolean whether this response is informational
	 */
	public function getIsInformational()
	{
		return $this->getStatusCode () >= 100 && $this->getStatusCode () < 200;
	}

	/**
	 *
	 * @return boolean whether this response is successful
	 */
	public function getIsSuccessful()
	{
		return $this->getStatusCode () >= 200 && $this->getStatusCode () < 300;
	}

	/**
	 *
	 * @return boolean whether this response is a redirection
	 */
	public function getIsRedirection()
	{
		return $this->getStatusCode () >= 300 && $this->getStatusCode () < 400;
	}

	/**
	 *
	 * @return boolean whether this response indicates a client error
	 */
	public function getIsClientError()
	{
		return $this->getStatusCode () >= 400 && $this->getStatusCode () < 500;
	}

	/**
	 *
	 * @return boolean whether this response indicates a server error
	 */
	public function getIsServerError()
	{
		return $this->getStatusCode () >= 500 && $this->getStatusCode () < 600;
	}

	/**
	 *
	 * @return boolean whether this response is OK
	 */
	public function getIsOk()
	{
		return $this->getStatusCode () == 200;
	}

	/**
	 *
	 * @return boolean whether this response indicates the current request is forbidden
	 */
	public function getIsForbidden()
	{
		return $this->getStatusCode () == 403;
	}

	/**
	 *
	 * @return boolean whether this response indicates the currently requested resource is not found
	 */
	public function getIsNotFound()
	{
		return $this->getStatusCode () == 404;
	}

	/**
	 *
	 * @return boolean whether this response is empty
	 */
	public function getIsEmpty()
	{
		return in_array ( $this->getStatusCode (), [ 201,204,304 ] );
	}

	/**
	 *
	 * @return array the formatters that are supported by default
	 */
	protected function defaultFormatters()
	{
		return [ self::FORMAT_HTML => 'Leaps\Web\HtmlResponseFormatter',self::FORMAT_XML => 'Leaps\Web\XmlResponseFormatter',self::FORMAT_JSON => 'Leaps\Web\JsonResponseFormatter',self::FORMAT_JSONP => [ 'class' => 'Leaps\Web\JsonResponseFormatter','useJsonp' => true ] ];
	}

	/**
	 * 准备发送响应
	 * The default implementation will convert [[data]] into [[content]] and set headers accordingly.
	 *
	 * @throws InvalidConfigException if the formatter for the specified format is invalid or [[format]] is not supported
	 */
	protected function prepare()
	{
		if ($this->stream !== null || $this->data === null) {
			return;
		}
		if (isset ( $this->formatters [$this->format] )) {
			$formatter = $this->formatters [$this->format];
			if (! is_object ( $formatter )) {
				$this->formatters [$this->format] = $formatter = Kernel::createObject ( $formatter );
			}
			if ($formatter instanceof ResponseFormatterInterface) {
				$formatter->format ( $this );
			} else {
				throw new InvalidConfigException ( "The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface." );
			}
		} elseif ($this->format === self::FORMAT_RAW) {
			$this->content = $this->data;
		} else {
			throw new InvalidConfigException ( "Unsupported response format: {$this->format}" );
		}
		if (is_array ( $this->content )) {
			throw new InvalidParamException ( "Response content must not be an array." );
		} elseif (is_object ( $this->content )) {
			if (method_exists ( $this->content, '__toString' )) {
				$this->content = $this->content->__toString ();
			} else {
				throw new InvalidParamException ( "Response content must be a string or an object implementing __toString()." );
			}
		}
	}
}