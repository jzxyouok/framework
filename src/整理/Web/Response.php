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
use Leaps\Web\Response\CookieCollection;
use Leaps\Web\Response\HeaderCollection;

class Response extends \Leaps\Response
{
	const FORMAT_RAW = 'raw';
	const FORMAT_HTML = 'html';
	const FORMAT_JSON = 'json';
	const FORMAT_JSONP = 'jsonp';
	const FORMAT_XML = 'xml';

	/**
	 * 响应类型
	 * @var sring
	 */
	public $format = self::FORMAT_HTML;

	/**
	 * MIME类型
	 * @var string
	 */
	public $acceptMimeType;

	/**
	 * 参数
	 * @var array
	 */
	public $acceptParams = [];

	/**
	 *
	 * @var array 响应内容的格式化程序用于将数据转换成指定的 [[format]].
	 * @see format
	 */
	public $formatters = [ ];

	/**
	 * 原始响应数据
	 *
	 * @var mixed
	 * @see content
	 */
	public $data;

	/**
	 * 格式化后的响应内容
	 * @var string
	 * @see data
	 */
	public $content;

	/**
	 * 响应流
	 * @var resource|array
	 */
	public $stream;

	/**
	 * 响应文本的字符集
	 *
	 * @var string
	 */
	public $charset;

	/**
	 * Http状态描述
	 *
	 * @var string
	 * @see httpStatuses
	 */
	public $statusText = 'OK';

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
	 * 发送响应的HTTP状态代码
	 *
	 * @return integer
	 */
	public function getStatusCode()
	{
		return $this->_statusCode;
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
			throw new InvalidParamException ( "The HTTP status code is invalid: $value" );
		}
		if ($text === null) {
			$this->statusText = isset ( static::$httpStatuses [$this->_statusCode] ) ? static::$httpStatuses [$this->_statusCode] : '';
		} else {
			$this->statusText = $text;
		}
	}

	/**
	 * 设置Header集合
	 *
	 * @param Leaps\Web\Response\CookieCollection headers
	 * @return Leaps\Web\Response\CookieCollection
	 */
	public function setHeaders(HeaderCollection $headers)
	{
		$this->_headers = $headers;
		return $this;
	}

	/**
	 * 返回Header集合
	 *
	 * @return Leaps\Web\Response\CookieCollection
	 */
	public function getHeaders()
	{
		if ($this->_headers === null) {
			$this->_headers = new HeaderCollection ();
		}
		return $this->_headers;
	}

	/**
	 * 设置一个响应头
	 *
	 * <code>
	 * $response->setHeader("Content-Type", "text/plain");
	 * </code>
	 *
	 * @param string name
	 * @param string value
	 * @return Phalcon\Http\ResponseInterface
	 */
	public function setHeader($name, $value)
	{
		$headers = $this->getHeaders ();
		$headers->set ( $name, $value );
		return $this;
	}

	/**
	 * 重置Header集合
	 *
	 * @return Phalcon\Http\ResponseInterface
	 */
	public function resetHeaders()
	{
		$headers = $this->getHeaders ();
		$headers->removeAll ();
		return $this;
	}
	private $_cookies;

	/**
	 * 设置Cookie集合
	 *
	 * @param Leaps\Web\Response\CookieCollection cookies
	 * @return Leaps\Web\Response\CookieCollection
	 */
	public function setCookies(CookieCollection $cookies)
	{
		$this->_cookies = $cookies;
		return $this;
	}

	/**
	 * 返回Cookie集合
	 *
	 * @return Leaps\Web\Response\CookieCollection
	 */
	public function getCookies()
	{
		if ($this->_cookies === null) {
			$this->_cookies = new CookieCollection ();
		}
		return $this->_cookies;
	}

	/**
	 * 设置Cookie
	 *
	 * @param array $config
	 */
	public function setCookie($config = [])
	{
		$cookies = $this->getCookies ();
		$cookies->add ( new \Leaps\Web\Cookie ( $config ) );
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
		//$this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepare();
        //$this->trigger(self::EVENT_AFTER_PREPARE);
        $this->sendHeaders();
        $this->sendContent();
        //$this->trigger(self::EVENT_AFTER_SEND);
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
		// $this->sendCookies ();
	}

	/**
	 * 发送Cookie到客户端
	 */
	protected function sendCookies()
	{

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
		set_time_limit(0); // Reset time limit for big files
		$chunkSize = 8 * 1024 * 1024; // 8MB per chunk

		if (is_array($this->stream)) {
			list ($handle, $begin, $end) = $this->stream;
			fseek($handle, $begin);
			while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
				if ($pos + $chunkSize > $end) {
					$chunkSize = $end - $pos + 1;
				}
				echo fread($handle, $chunkSize);
				flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
			}
			fclose($handle);
		} else {
			while (!feof($this->stream)) {
				echo fread($this->stream, $chunkSize);
				flush();
			}
			fclose($this->stream);
		}
	}

	/**
	 * 发送文件到浏览器
	 *
	 * Note that this method only prepares the response for file sending. The file is not sent
	 * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
	 *
	 * @param string $filePath the path of the file to be sent.
	 * @param string $attachmentName the file name shown to the user. If null, it will be determined from `$filePath`.
	 * @param array $options additional options for sending the file. The following options are supported:
	 *
	 *  - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
	 *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
	 *     meaning a download dialog will pop up.
	 *
	 * @return static the response object itself
	 */
	public function sendFile($filePath, $attachmentName = null, $options = [])
	{
		if (!isset($options['mimeType'])) {
			$options['mimeType'] = FileHelper::getMimeTypeByExtension($filePath);
		}
		if ($attachmentName === null) {
			$attachmentName = basename($filePath);
		}
		$handle = fopen($filePath, 'rb');
		$this->sendStreamAsFile($handle, $attachmentName, $options);

		return $this;
	}

	/**
	 * 是否是有效的HTTP状态码 [[statusCode]].
	 *
	 * @return boolean
	 */
	public function getIsInvalid()
	{
		return $this->getStatusCode () < 100 || $this->getStatusCode () >= 600;
	}

	/**
	 * 默认的格式器支持
	 *
	 * @return array the formatters that are supported by default
	 */
	protected function defaultFormatters()
	{
		return [
				self::FORMAT_HTML => 'Leaps\Web\Response\HtmlFormatter',
				self::FORMAT_XML => 'Leaps\Web\Response\XmlFormatter',
				self::FORMAT_JSON => 'Leaps\Web\Response\JsonFormatter',
				self::FORMAT_JSONP => [
						'className' => 'Leaps\Web\Response\JsonFormatter',
						'useJsonp' => true
				]
		];
	}

	/**
	 * Prepares for sending the response.
	 * The default implementation will convert [[data]] into [[content]] and set headers accordingly.
	 * @throws InvalidConfigException if the formatter for the specified format is invalid or [[format]] is not supported
	 */
	protected function prepare()
	{
		if ($this->stream !== null || $this->data === null) {
			return;
		}

		if (isset($this->formatters[$this->format])) {
			$formatter = $this->formatters[$this->format];
			if (!is_object($formatter)) {
				$this->formatters[$this->format] = $formatter = Kernel::createObject($formatter);
			}
			if ($formatter instanceof ResponseFormatterInterface) {
				$formatter->format($this);
			} else {
				throw new InvalidConfigException("The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
			}
		} elseif ($this->format === self::FORMAT_RAW) {
			$this->content = $this->data;
		} else {
			throw new InvalidConfigException("Unsupported response format: {$this->format}");
		}

		if (is_array($this->content)) {
			throw new InvalidParamException("Response content must not be an array.");
		} elseif (is_object($this->content)) {
			if (method_exists($this->content, '__toString')) {
				$this->content = $this->content->__toString();
			} else {
				throw new InvalidParamException("Response content must be a string or an object implementing __toString().");
			}
		}
	}
}