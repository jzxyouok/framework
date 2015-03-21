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
namespace Leaps\Http;

use Leaps\Base;
use Leaps\Kernel;
use Leaps\Http\ResponseInterface;
use Leaps\Http\Response\Exception;
use Leaps\Di\InjectionAwareInterface;

/**
 * Leaps\Http\Response
 *
 * Part of the HTTP cycle is return responses to the clients.
 * Leaps\HTTP\Response is the Leaps component responsible to achieve this task.
 * HTTP responses are usually composed by headers and body.
 *
 * <code>
 * $response = new \Leaps\Http\Response();
 * $response->setStatusCode(200, "OK");
 * $response->setContent("<html><body>Hello</body></html>");
 * $response->send();
 * </code>
 */
class Response extends Base implements ResponseInterface, InjectionAwareInterface
{
	const FORMAT_RAW = 'raw';
	const FORMAT_HTML = 'html';
	const FORMAT_JSON = 'json';
	const FORMAT_JSONP = 'jsonp';
	const FORMAT_XML = 'xml';

	/**
	 *
	 * @var array 响应内容的格式化程序用于将数据转换成指定的 [[format]].
	 * @see format
	 */
	public $formatters = [ ];

	/**
	 * 响应文本的字符集
	 *
	 * @var string
	 */
	public $charset;

	/**
	 * 使用HTTP协议的版本
	 *
	 * @var string
	 */
	public $version;

	/**
	 * 是否已经发出响应
	 *
	 * @var boolean
	 */
	public $isSent = false;

	/**
	 * 原始响应数据
	 *
	 * @var mixed
	 * @see content
	 */
	public $data;

	/**
	 * 格式化后的响应内容
	 *
	 * @var string
	 * @see data
	 */
	public $content;

	/**
	 * 响应流
	 *
	 * @var resource|array
	 */
	public $stream;

	/**
	 * Http状态描述
	 *
	 * @var string
	 * @see httpStatuses
	 */
	public $statusText = 'OK';

	/**
	 * 响应类型
	 *
	 * @var sring
	 */
	public $format = self::FORMAT_HTML;

	/**
	 * MIME类型
	 *
	 * @var string
	 */
	public $acceptMimeType;

	/**
	 * 参数
	 *
	 * @var array
	 */
	public $acceptParams = [ ];

	/**
	 * HTTP状态代码列表和相应的文本
	 *
	 * @var array
	 */
	public static $httpStatuses = [
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			118 => 'Connection timed out',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			208 => 'Already Reported',
			210 => 'Content Different',
			226 => 'IM Used',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',
			310 => 'Too many Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Time-out',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested range unsatisfiable',
			417 => 'Expectation failed',
			418 => 'I\'m a teapot',
			422 => 'Unprocessable entity',
			423 => 'Locked',
			424 => 'Method failure',
			425 => 'Unordered Collection',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',
			449 => 'Retry With',
			450 => 'Blocked by Windows Parental Controls',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway or Proxy Error',
			503 => 'Service Unavailable',
			504 => 'Gateway Time-out',
			505 => 'HTTP Version not supported',
			507 => 'Insufficient storage',
			508 => 'Loop Detected',
			509 => 'Bandwidth Limit Exceeded',
			510 => 'Not Extended',
			511 => 'Network Authentication Required'
	];

	/**
	 * http状态码
	 *
	 * @var int
	 */
	protected $_statusCode = 200;
	protected $_dependencyInjector;

	/**
	 * Header集合
	 *
	 * @var HeaderCollection
	 */
	private $_headers;

	/**
	 * 初始化组件
	 */
	public function init()
	{
		if ($this->version === null) {
			if (isset ( $_SERVER ['SERVER_PROTOCOL'] ) && $_SERVER ['SERVER_PROTOCOL'] === 'HTTP/1.0') {
				$this->version = '1.0';
			} else {
				$this->version = '1.1';
			}
		}
		if ($this->charset === null) {
			$this->charset = Kernel::$app->charset;
		}
		$formatters = $this->defaultFormatters ();
		$this->formatters = empty ( $this->formatters ) ? $formatters : array_merge ( $formatters, $this->formatters );
	}

	/**
	 * Sets the dependency injector
	 *
	 * @param Leaps\DiInterface dependencyInjector
	 */
	public function setDI(\Leaps\DiInterface $dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * Returns the internal dependency injector
	 *
	 * @return Leaps\DiInterface
	 */
	public function getDI()
	{
		if (! is_object ( $this->_dependencyInjector )) {
			$this->_dependencyInjector = \Leaps\Di::getDefault ();
			if (! is_object ( $this->_dependencyInjector )) {
				throw new Exception ( "A dependency injection object is required to access the 'url' service" );
			}
		}
		return $this->_dependencyInjector;
	}

	/**
	 * 发送响应的HTTP状态代码
	 *
	 * @return integer
	 */
	public function getStatusCode()
	{
		return $this->_statusCode;
	}

	/**
	 * Returns the header collection.
	 * The header collection contains the currently registered HTTP headers.
	 *
	 * @return HeaderCollection the header collection
	 */
	public function getHeaders()
	{
		if ($this->_headers === null) {
			$this->_headers = new HeaderCollection ();
		}
		return $this->_headers;
	}

	/**
	 * 设置响应状态码
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
			throw new \Leaps\InvalidParamException ( "The HTTP status code is invalid: $value" );
		}
		if ($text === null) {
			$this->statusText = isset ( static::$httpStatuses [$this->_statusCode] ) ? static::$httpStatuses [$this->_statusCode] : '';
		} else {
			$this->statusText = $text;
		}
	}

	/**
	 * Sends a Not-Modified response
	 *
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setNotModified()
	{
		$this->setStatusCode ( 304, "Not modified" );
		return $this;
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
		return in_array ( $this->getStatusCode (), [
				201,
				204,
				304
		] );
	}

	/**
	 * Gets the HTTP response body
	 *
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * 设置Http响应内容
	 *
	 * <code>
	 * response->setContent("<h1>Hello!</h1>");
	 * </code>
	 *
	 * @param string content
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setContent($content)
	{
		$this->content = $content;
		return $this;
	}

	/**
	 * 检查是否已经发送响应
	 *
	 * @return boolean
	 */
	public function isSent()
	{
		return $this->isSent;
	}

	/**
	 * 发送文件到浏览器
	 *
	 * @param string $filePath the path of the file to be sent.
	 * @param string $attachmentName the file name shown to the user. If null, it will be determined from `$filePath`.
	 * @param array $options additional options for sending the file. The following options are supported:
	 *
	 *        - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
	 *        - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
	 *        meaning a download dialog will pop up.
	 *
	 * @return static the response object itself
	 */
	public function sendFile($filePath, $attachmentName = null, $options = [])
	{
		if (! isset ( $options ['mimeType'] )) {
			$options ['mimeType'] = \Leaps\Filesystem\MimeType::getMimeType ( $filePath );
		}
		if ($attachmentName === null) {
			$attachmentName = basename ( $filePath );
		}
		$handle = fopen ( $filePath, 'rb' );
		$this->sendStreamAsFile ( $handle, $attachmentName, $options );
		return $this;
	}

	/**
	 * 将指定的内容作为一个文件发送到浏览器。
	 *
	 * @param string $content the content to be sent. The existing [[content]] will be discarded.
	 * @param string $attachmentName the file name shown to the user.
	 * @param array $options additional options for sending the file. The following options are supported:
	 *
	 *        - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
	 *        - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
	 *        meaning a download dialog will pop up.
	 *
	 * @return static the response object itself
	 * @throws HttpException if the requested range is not satisfiable
	 */
	public function sendContentAsFile($content, $attachmentName, $options = [])
	{
		$headers = $this->getHeaders ();
		$contentLength = \Leaps\Str::byteLength ( $content );
		$range = $this->getHttpRange ( $contentLength );
		if ($range === false) {
			$headers->set ( 'Content-Range', "bytes */$contentLength" );
			throw new \Leaps\Web\HttpException ( 416, 'Requested range not satisfiable' );
		}
		list ( $begin, $end ) = $range;
		if ($begin != 0 || $end != $contentLength - 1) {
			$this->setStatusCode ( 206 );
			$headers->set ( 'Content-Range', "bytes $begin-$end/$contentLength" );
			$this->content = \Leaps\Str::byteSubstr ( $content, $begin, $end - $begin + 1 );
		} else {
			$this->setStatusCode ( 200 );
			$this->content = $content;
		}
		$mimeType = isset ( $options ['mimeType'] ) ? $options ['mimeType'] : 'application/octet-stream';
		$this->setDownloadHeaders ( $attachmentName, $mimeType, ! empty ( $options ['inline'] ), $end - $begin + 1 );
		$this->format = self::FORMAT_RAW;
		return $this;
	}

	/**
	 * Sets a default set of HTTP headers for file downloading purpose.
	 *
	 * @param string $attachmentName the attachment file name
	 * @param string $mimeType the MIME type for the response. If null, `Content-Type` header will NOT be set.
	 * @param boolean $inline whether the browser should open the file within the browser window. Defaults to false,
	 *        meaning a download dialog will pop up.
	 * @param integer $contentLength the byte length of the file being downloaded. If null, `Content-Length` header will NOT be set.
	 * @return static the response object itself
	 */
	public function setDownloadHeaders($attachmentName, $mimeType = null, $inline = false, $contentLength = null)
	{
		$headers = $this->getHeaders ();
		$disposition = $inline ? 'inline' : 'attachment';
		$headers->setDefault ( 'Pragma', 'public' )->setDefault ( 'Accept-Ranges', 'bytes' )->setDefault ( 'Expires', '0' )->setDefault ( 'Cache-Control', 'must-revalidate, post-check=0, pre-check=0' )->setDefault ( 'Content-Disposition', "$disposition; filename=\"$attachmentName\"" );
		if ($mimeType !== null) {
			$headers->setDefault ( 'Content-Type', $mimeType );
		}
		if ($contentLength !== null) {
			$headers->setDefault ( 'Content-Length', $contentLength );
		}
		return $this;
	}

	/**
	 * Sends the specified stream as a file to the browser.
	 *
	 * Note that this method only prepares the response for file sending. The file is not sent
	 * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
	 *
	 * @param resource $handle the handle of the stream to be sent.
	 * @param string $attachmentName the file name shown to the user.
	 * @param array $options additional options for sending the file. The following options are supported:
	 *
	 *        - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
	 *        - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
	 *        meaning a download dialog will pop up.
	 *
	 * @return static the response object itself
	 * @throws HttpException if the requested range cannot be satisfied.
	 */
	public function sendStreamAsFile($handle, $attachmentName, $options = [])
	{
		$headers = $this->getHeaders ();
		fseek ( $handle, 0, SEEK_END );
		$fileSize = ftell ( $handle );
		$range = $this->getHttpRange ( $fileSize );
		if ($range === false) {
			$headers->set ( 'Content-Range', "bytes */$fileSize" );
			throw new \Leaps\Web\HttpException ( 416, 'Requested range not satisfiable' );
		}
		list ( $begin, $end ) = $range;
		if ($begin != 0 || $end != $fileSize - 1) {
			$this->setStatusCode ( 206 );
			$headers->set ( 'Content-Range', "bytes $begin-$end/$fileSize" );
		} else {
			$this->setStatusCode ( 200 );
		}
		$mimeType = isset ( $options ['mimeType'] ) ? $options ['mimeType'] : 'application/octet-stream';
		$this->setDownloadHeaders ( $attachmentName, $mimeType, ! empty ( $options ['inline'] ), $end - $begin + 1 );
		$this->format = self::FORMAT_RAW;
		$this->stream = [
				$handle,
				$begin,
				$end
		];
		return $this;
	}

	/**
	 * 发送响应到客户端
	 *
	 * @return Phalcon\Http\ResponseInterface
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
	 * 默认的格式器支持
	 *
	 * @return array the formatters that are supported by default
	 */
	protected function defaultFormatters()
	{
		return [
				self::FORMAT_HTML => 'Leaps\Http\Response\HtmlFormatter',
				self::FORMAT_XML => 'Leaps\Http\Response\XmlFormatter',
				self::FORMAT_JSON => 'Leaps\Http\Response\JsonFormatter',
				self::FORMAT_JSONP => [
						'className' => 'Leaps\Http\Response\JsonFormatter',
						'useJsonp' => true
				]
		];
	}

	/**
	 * Prepares for sending the response.
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
				throw new \Leaps\InvalidConfigException ( "The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface." );
			}
		} elseif ($this->format === self::FORMAT_RAW) {
			$this->content = $this->data;
		} else {
			throw new \Leaps\InvalidConfigException ( "Unsupported response format: {$this->format}" );
		}

		if (is_array ( $this->content )) {
			throw new \Leaps\InvalidParamException ( "Response content must not be an array." );
		} elseif (is_object ( $this->content )) {
			if (method_exists ( $this->content, '__toString' )) {
				$this->content = $this->content->__toString ();
			} else {
				throw new \Leaps\InvalidParamException ( "Response content must be a string or an object implementing __toString()." );
			}
		}
	}

	/**
	 * 发送响应头到客户端
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
		$request = Yii::$app->getRequest();
		if ($request->enableCookieValidation) {
			if ($request->cookieValidationKey == '') {
				throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
			}
			$validationKey = $request->cookieValidationKey;
		}
		foreach ($this->getCookies() as $cookie) {
			$value = $cookie->value;
			if ($cookie->expire != 1  && isset($validationKey)) {
				$value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
			}
			setcookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
		}
	}

	/**
	 * 发送响应内容到客户端
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

	/**
	 * Determines the HTTP range given in the request.
	 *
	 * @param integer $fileSize the size of the file that will be used to validate the requested HTTP range.
	 * @return array|boolean the range (begin, end), or false if the range request is invalid.
	 */
	protected function getHttpRange($fileSize)
	{
		if (! isset ( $_SERVER ['HTTP_RANGE'] ) || $_SERVER ['HTTP_RANGE'] === '-') {
			return [
					0,
					$fileSize - 1
			];
		}
		if (! preg_match ( '/^bytes=(\d*)-(\d*)$/', $_SERVER ['HTTP_RANGE'], $matches )) {
			return false;
		}
		if ($matches [1] === '') {
			$start = $fileSize - $matches [2];
			$end = $fileSize - 1;
		} elseif ($matches [2] !== '') {
			$start = $matches [1];
			$end = $matches [2];
			if ($end >= $fileSize) {
				$end = $fileSize - 1;
			}
		} else {
			$start = $matches [1];
			$end = $fileSize - 1;
		}
		if ($start < 0 || $start > $end) {
			return false;
		} else {
			return [
					$start,
					$end
			];
		}
	}
}