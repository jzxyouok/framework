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

use Leaps\InvalidConfigException;
use Leaps\Web\Response\HeaderCollection;

class Request extends \Leaps\Request
{

	/**
	 *
	 * @var string the name of the POST parameter that is used to indicate if a request is a PUT, PATCH or DELETE
	 *      request tunneled through POST. Defaults to '_method'.
	 * @see getMethod()
	 * @see getBodyParams()
	 */
	public $methodParam = '_method';

	/**
	 *
	 * @var array the headers in this collection (indexed by the header names)
	 */
	private $_headers;

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Leaps\Request::resolve()
	 */
	public function resolve()
	{
		$result = \Leaps\Kernel::$app->getRouter()->parseRequest($this);

		//$result = $this->router->parseRequest ( $this );
		if ($result !== false) {
			list ( $route, $params ) = $result;
			$_GET = array_merge ( $_GET, $params );
			return [
					$route,
					$_GET
			];
		} else {
			throw new \Exception ( 'Page not found.' );
		}
	}

	/**
	 * 返回Header集合
	 * The header collection contains incoming HTTP headers.
	 *
	 * @return HeaderCollection the header collection
	 */
	public function getHeaders()
	{
		if ($this->_headers === null) {
			$this->_headers = new HeaderCollection ();
			if (function_exists ( 'getallheaders' )) {
				$headers = getallheaders ();
			} elseif (function_exists ( 'http_get_request_headers' )) {
				$headers = http_get_request_headers ();
			} else {
				foreach ( $_SERVER as $name => $value ) {
					if (strncmp ( $name, 'HTTP_', 5 ) === 0) {
						$name = str_replace ( ' ', '-', ucwords ( strtolower ( str_replace ( '_', ' ', substr ( $name, 5 ) ) ) ) );
						$this->_headers->add ( $name, $value );
					}
				}

				return $this->_headers;
			}
			foreach ( $headers as $name => $value ) {
				$this->_headers->add ( $name, $value );
			}
		}

		return $this->_headers;
	}

	/**
	 * 返回当前请求的方法 (比如 GET, POST, HEAD, PUT, PATCH, DELETE)。
	 *
	 * @return string 请求方法,比如 GET, POST, HEAD, PUT, PATCH, DELETE。
	 */
	public function getMethod()
	{
		if (isset ( $_POST [$this->methodParam] )) {
			return strtoupper ( $_POST [$this->methodParam] );
		} elseif (isset ( $_SERVER ['HTTP_X_HTTP_METHOD_OVERRIDE'] )) {
			return strtoupper ( $_SERVER ['HTTP_X_HTTP_METHOD_OVERRIDE'] );
		} else {
			return isset ( $_SERVER ['REQUEST_METHOD'] ) ? strtoupper ( $_SERVER ['REQUEST_METHOD'] ) : 'GET';
		}
	}

	/**
	 * 返回是否是GET请求
	 *
	 * @return boolean whether this is a GET request.
	 */
	public function getIsGet()
	{
		return $this->getMethod () === 'GET';
	}

	/**
	 * 返回是否是 OPTIONS 请求
	 *
	 * @return boolean whether this is a OPTIONS request.
	 */
	public function getIsOptions()
	{
		return $this->getMethod () === 'OPTIONS';
	}

	/**
	 * 返回是否是 HEAD 请求
	 *
	 * @return boolean whether this is a HEAD request.
	 */
	public function getIsHead()
	{
		return $this->getMethod () === 'HEAD';
	}

	/**
	 * 返回是否是 POST 请求
	 *
	 * @return boolean whether this is a POST request.
	 */
	public function getIsPost()
	{
		return $this->getMethod () === 'POST';
	}

	/**
	 * 返回是否是 DELETE 请求
	 *
	 * @return boolean whether this is a DELETE request.
	 */
	public function getIsDelete()
	{
		return $this->getMethod () === 'DELETE';
	}

	/**
	 * 返回是否是 PUT 请求
	 *
	 * @return boolean whether this is a PUT request.
	 */
	public function getIsPut()
	{
		return $this->getMethod () === 'PUT';
	}

	/**
	 * 返回是否是 PATCH 请求
	 *
	 * @return boolean whether this is a PATCH request.
	 */
	public function getIsPatch()
	{
		return $this->getMethod () === 'PATCH';
	}

	/**
	 * 返回是否是 AJAX (XMLHttpRequest) 请求
	 *
	 * @return boolean whether this is an AJAX (XMLHttpRequest) request.
	 */
	public function getIsAjax()
	{
		return isset ( $_SERVER ['HTTP_X_REQUESTED_WITH'] ) && $_SERVER ['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}

	/**
	 * 返回是否是 PJAX 请求
	 *
	 * @return boolean whether this is a PJAX request
	 */
	public function getIsPjax()
	{
		return $this->getIsAjax () && ! empty ( $_SERVER ['HTTP_X_PJAX'] );
	}

	/**
	 * 返回是否是 Adobe Flash 或 Flex 请求
	 *
	 * @return boolean whether this is an Adobe Flash or Adobe Flex request.
	 */
	public function getIsFlash()
	{
		return isset ( $_SERVER ['HTTP_USER_AGENT'] ) && (stripos ( $_SERVER ['HTTP_USER_AGENT'], 'Shockwave' ) !== false || stripos ( $_SERVER ['HTTP_USER_AGENT'], 'Flash' ) !== false);
	}
	private $_rawBody;

	/**
	 * 返回原始HTTP请求体。
	 *
	 * @return string the request body
	 */
	public function getRawBody()
	{
		if ($this->_rawBody === null) {
			$this->_rawBody = file_get_contents ( 'php://input' );
		}

		return $this->_rawBody;
	}

	/**
	 * 设置原始HTTP请求体, 该方法主要用来测试
	 *
	 * @param $rawBody
	 */
	public function setRawBody($rawBody)
	{
		$this->_rawBody = $rawBody;
	}

	/**
	 * 返回POST参数
	 *
	 * @param string $name the parameter name
	 * @param mixed $defaultValue the default parameter value if the parameter does not exist.
	 * @return array|mixed
	 */
	public function post($name = null, $defaultValue = null)
	{
		if ($name === null) {
			return $this->getBodyParams ();
		} else {
			return $this->getBodyParam ( $name, $defaultValue );
		}
	}
	private $_queryParams;

	/**
	 * 返回 [[queryString]]参数
	 *
	 * This method will return the contents of `$_GET` if params where not explicitly set.
	 *
	 * @return array the request GET parameter values.
	 * @see setQueryParams()
	 */
	public function getQueryParams()
	{
		if ($this->_queryParams === null) {
			return $_GET;
		}

		return $this->_queryParams;
	}

	/**
	 * 设置 [[queryString]] 参数
	 *
	 * @param array $values the request query parameters (name-value pairs)
	 * @see getQueryParam()
	 * @see getQueryParams()
	 */
	public function setQueryParams($values)
	{
		$this->_queryParams = $values;
	}

	/**
	 * 返回GET参数
	 *
	 * @param string $name the parameter name
	 * @param mixed $defaultValue the default parameter value if the parameter does not exist.
	 * @return array|mixed
	 */
	public function get($name = null, $defaultValue = null)
	{
		if ($name === null) {
			return $this->getQueryParams ();
		} else {
			return $this->getQueryParam ( $name, $defaultValue );
		}
	}

	/**
	 * 返回GET参数值
	 *
	 * @param string $name the GET parameter name.
	 * @param mixed $defaultValue the default parameter value if the GET parameter does not exist.
	 * @return mixed the GET parameter value
	 * @see getBodyParam()
	 */
	public function getQueryParam($name, $defaultValue = null)
	{
		$params = $this->getQueryParams ();

		return isset ( $params [$name] ) ? $params [$name] : $defaultValue;
	}

	/**
	 * 返回Server的值
	 *
	 * 如果$name为空则返回所有Server的值
	 *
	 * @param string $name 获取的变量名,如果该值为null则返回$_SERVER数组,默认为null
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
	 * @return mixed
	 */
	public function getServer($name = null, $defaultValue = null)
	{
		if ($name === null) {
			return $_SERVER;
		}
		return (isset ( $_SERVER [$name] )) ? $_SERVER [$name] : $defaultValue;
	}
	private $_hostInfo;

	/**
	 * 返回当前请求的模式和主机部分URL。
	 * The returned URL does not have an ending slash.
	 * By default this is determined based on the user request information.
	 * You may explicitly specify it by setting the [[setHostInfo()|hostInfo]] property.
	 *
	 * @return string schema and hostname part (with port number if needed) of the request URL (e.g. `http://www.yiiframework.com`)
	 * @see setHostInfo()
	 */
	public function getHostInfo()
	{
		if ($this->_hostInfo === null) {
			$secure = $this->getIsSecureConnection ();
			$http = $secure ? 'https' : 'http';
			if (isset ( $_SERVER ['HTTP_HOST'] )) {
				$this->_hostInfo = $http . '://' . $_SERVER ['HTTP_HOST'];
			} else {
				$this->_hostInfo = $http . '://' . $_SERVER ['SERVER_NAME'];
				$port = $secure ? $this->getSecurePort () : $this->getPort ();
				if (($port !== 80 && ! $secure) || ($port !== 443 && $secure)) {
					$this->_hostInfo .= ':' . $port;
				}
			}
		}

		return $this->_hostInfo;
	}

	/**
	 * 设置当前请求的模式和主机部分URL
	 * This setter is provided in case the schema and hostname cannot be determined
	 * on certain Web servers.
	 *
	 * @param string $value the schema and host part of the application URL. The trailing slashes will be removed.
	 */
	public function setHostInfo($value)
	{
		$this->_hostInfo = rtrim ( $value, '/' );
	}
	private $_baseUrl;

	/**
	 * 返回应用程序的相对URL。
	 *
	 * @return string the relative URL for the application
	 * @see setScriptUrl()
	 */
	public function getBaseUrl()
	{
		if ($this->_baseUrl === null) {
			$this->_baseUrl = rtrim ( dirname ( $this->getScriptUrl () ), '\\/' );
		}

		return $this->_baseUrl;
	}

	/**
	 * 设置应用程序的相对URL
	 *
	 * @param string $value the relative URL for the application
	 */
	public function setBaseUrl($value)
	{
		$this->_baseUrl = $value;
	}
	private $_scriptUrl;

	/**
	 * 返回输入脚本的相对URL
	 *
	 * @return string the relative URL of the entry script.
	 * @throws InvalidConfigException if unable to determine the entry script URL
	 */
	public function getScriptUrl()
	{
		if ($this->_scriptUrl === null) {
			$scriptFile = $this->getScriptFile ();
			$scriptName = basename ( $scriptFile );
			if (basename ( $_SERVER ['SCRIPT_NAME'] ) === $scriptName) {
				$this->_scriptUrl = $_SERVER ['SCRIPT_NAME'];
			} elseif (basename ( $_SERVER ['PHP_SELF'] ) === $scriptName) {
				$this->_scriptUrl = $_SERVER ['PHP_SELF'];
			} elseif (isset ( $_SERVER ['ORIG_SCRIPT_NAME'] ) && basename ( $_SERVER ['ORIG_SCRIPT_NAME'] ) === $scriptName) {
				$this->_scriptUrl = $_SERVER ['ORIG_SCRIPT_NAME'];
			} elseif (($pos = strpos ( $_SERVER ['PHP_SELF'], '/' . $scriptName )) !== false) {
				$this->_scriptUrl = substr ( $_SERVER ['SCRIPT_NAME'], 0, $pos ) . '/' . $scriptName;
			} elseif (! empty ( $_SERVER ['DOCUMENT_ROOT'] ) && strpos ( $scriptFile, $_SERVER ['DOCUMENT_ROOT'] ) === 0) {
				$this->_scriptUrl = str_replace ( "\\", "/", str_replace ( $_SERVER ['DOCUMENT_ROOT'], '', $scriptFile ) );
			} else {
				throw new InvalidConfigException ( 'Unable to determine the entry script URL.' );
			}
		}

		return $this->_scriptUrl;
	}

	/**
	 * 设置输入脚本的相对URL
	 *
	 * @param string $value the relative URL for the application entry script.
	 */
	public function setScriptUrl($value)
	{
		$this->_scriptUrl = '/' . trim ( $value, '/' );
	}


	/**
	 * 设置输入脚本的物理路径
	 *
	 * @param string $value the entry script file path.
	 */
	public function setScriptFile($value)
	{
		$this->_scriptFile = $value;
	}
	private $_pathInfo;

	/**
	 * 返回当前请求的URL的路径信息。
	 *
	 * @return string part of the request URL that is after the entry script and before the question mark.
	 *         Note, the returned path info is already URL-decoded.
	 * @throws InvalidConfigException if the path info cannot be determined due to unexpected server configuration
	 */
	public function getPathInfo()
	{
		if ($this->_pathInfo === null) {
			$this->_pathInfo = $this->resolvePathInfo ();
		}
		return $this->_pathInfo;
	}

	/**
	 * 设置当前请求的URL的路径信息。
	 *
	 * @param string $value the path info of the current request
	 */
	public function setPathInfo($value)
	{
		$this->_pathInfo = ltrim ( $value, '/' );
	}

	/**
	 * 解析当前URL的路径信息
	 *
	 * @return string part of the request URL that is after the entry script and before the question mark.
	 *         Note, the returned path info is decoded.
	 * @throws InvalidConfigException if the path info cannot be determined due to unexpected server configuration
	 */
	protected function resolvePathInfo()
	{
		$pathInfo = $this->getUrl ();

		if (($pos = strpos ( $pathInfo, '?' )) !== false) {
			$pathInfo = substr ( $pathInfo, 0, $pos );
		}

		$pathInfo = urldecode ( $pathInfo );

		// try to encode in UTF8 if not so
		// http://w3.org/International/questions/qa-forms-utf-8.html
		if (! preg_match ( '%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs', $pathInfo )) {
			$pathInfo = utf8_encode ( $pathInfo );
		}

		$scriptUrl = $this->getScriptUrl ();
		$baseUrl = $this->getBaseUrl ();
		if (strpos ( $pathInfo, $scriptUrl ) === 0) {
			$pathInfo = substr ( $pathInfo, strlen ( $scriptUrl ) );
		} elseif ($baseUrl === '' || strpos ( $pathInfo, $baseUrl ) === 0) {
			$pathInfo = substr ( $pathInfo, strlen ( $baseUrl ) );
		} elseif (isset ( $_SERVER ['PHP_SELF'] ) && strpos ( $_SERVER ['PHP_SELF'], $scriptUrl ) === 0) {
			$pathInfo = substr ( $_SERVER ['PHP_SELF'], strlen ( $scriptUrl ) );
		} else {
			throw new InvalidConfigException ( 'Unable to determine the path info of the current request.' );
		}

		if ($pathInfo [0] === '/') {
			$pathInfo = substr ( $pathInfo, 1 );
		}

		return ( string ) $pathInfo;
	}

	/**
	 * 返回当前请求的绝对URL
	 * 这是一个快捷连接 [[hostInfo]] 和 [[url]].
	 *
	 * @return string the currently requested absolute URL.
	 */
	public function getAbsoluteUrl()
	{
		return $this->getHostInfo () . $this->getUrl ();
	}
	private $_url;

	/**
	 * 返回当前请求的相对URL。
	 *
	 * @return string the currently requested relative URL. Note that the URI returned is URL-encoded.
	 * @throws InvalidConfigException if the URL cannot be determined due to unusual server configuration
	 */
	public function getUrl()
	{
		if ($this->_url === null) {
			$this->_url = $this->resolveRequestUri ();
		}

		return $this->_url;
	}

	/**
	 * 设置当前请求的相对URL。
	 *
	 * @param string $value the request URI to be set
	 */
	public function setUrl($value)
	{
		$this->_url = $value;
	}

	/**
	 * 解析当前请求URL的 URI 部分。
	 *
	 * @return string|boolean the request URI portion for the currently requested URL.
	 *         Note that the URI returned is URL-encoded.
	 * @throws InvalidConfigException if the request URI cannot be determined due to unusual server configuration
	 */
	protected function resolveRequestUri()
	{
		if (isset ( $_SERVER ['HTTP_X_REWRITE_URL'] )) { // IIS
			$requestUri = $_SERVER ['HTTP_X_REWRITE_URL'];
		} elseif (isset ( $_SERVER ['REQUEST_URI'] )) {
			$requestUri = $_SERVER ['REQUEST_URI'];
			if ($requestUri !== '' && $requestUri [0] !== '/') {
				$requestUri = preg_replace ( "/^(http|https):\/\/[^\/]+/i", '', $requestUri );
			}
		} elseif (isset ( $_SERVER ['ORIG_PATH_INFO'] )) { // IIS 5.0 CGI
			$requestUri = $_SERVER ['ORIG_PATH_INFO'];
			if (! empty ( $_SERVER ['QUERY_STRING'] )) {
				$requestUri .= '?' . $_SERVER ['QUERY_STRING'];
			}
		} else {
			throw new InvalidConfigException ( 'Unable to determine the request URI.' );
		}

		return $requestUri;
	}

	/**
	 * 后返回的请求URL的问号部分。
	 *
	 * @return string part of the request URL that is after the question mark
	 */
	public function getQueryString()
	{
		return isset ( $_SERVER ['QUERY_STRING'] ) ? $_SERVER ['QUERY_STRING'] : '';
	}

	/**
	 * 是否是通过安全通道
	 *
	 * @return boolean if the request is sent via secure channel (https)
	 */
	public function getIsSecureConnection()
	{
		return isset ( $_SERVER ['HTTPS'] ) && (strcasecmp ( $_SERVER ['HTTPS'], 'on' ) === 0 || $_SERVER ['HTTPS'] == 1) || isset ( $_SERVER ['HTTP_X_FORWARDED_PROTO'] ) && strcasecmp ( $_SERVER ['HTTP_X_FORWARDED_PROTO'], 'https' ) === 0;
	}

	/**
	 * 返回服务器名称
	 *
	 * @return string server name
	 */
	public function getServerName()
	{
		return $_SERVER ['SERVER_NAME'];
	}

	/**
	 * 返回服务器端口
	 *
	 * @return integer server port number
	 */
	public function getServerPort()
	{
		return ( int ) $_SERVER ['SERVER_PORT'];
	}

	/**
	 * 返回URL来路
	 *
	 * @return string URL referrer, null if not present
	 */
	public function getReferrer()
	{
		return isset ( $_SERVER ['HTTP_REFERER'] ) ? $_SERVER ['HTTP_REFERER'] : null;
	}

	/**
	 * 返回 user agent。
	 *
	 * @return string user agent, null if not present
	 */
	public function getUserAgent()
	{
		return isset ( $_SERVER ['HTTP_USER_AGENT'] ) ? $_SERVER ['HTTP_USER_AGENT'] : null;
	}

	/**
	 * 客户端IP
	 *
	 * @var string
	 */
	protected $_clientIp = null;

	/**
	 * 返回用户的IP地址
	 *
	 * @return string user IP address. Null is returned if the user IP address cannot be detected.
	 */
	public function getUserIP()
	{
		if ($this->_clientIp == null) {
			$this->_getClientIp ();
		}
		return $this->_clientIp;
	}

	/**
	 * 返回访问的IP地址
	 *
	 * <pre>Example:
	 * 返回：127.0.0.1
	 * </pre>
	 *
	 * @return string
	 */
	private function _getClientIp()
	{
		if (($ip = $this->getServer ( 'HTTP_CLIENT_IP' )) != null) {
			$this->_clientIp = $ip;
		} elseif (($_ip = $this->getServer ( 'HTTP_X_FORWARDED_FOR' )) != null) {
			$ip = strtok ( $_ip, ',' );
			do {
				$ip = ip2long ( $ip );
				if (! (($ip == 0) || ($ip == 0xFFFFFFFF) || ($ip == 0x7F000001) || (($ip >= 0x0A000000) && ($ip <= 0x0AFFFFFF)) || (($ip >= 0xC0A8FFFF) && ($ip <= 0xC0A80000)) || (($ip >= 0xAC1FFFFF) && ($ip <= 0xAC100000)))) {
					$this->_clientIp = long2ip ( $ip );
					return;
				}
			} while ( ($ip = strtok ( ',' )) );
		} elseif (($ip = $this->getServer ( 'HTTP_PROXY_USER' )) != null) {
			$this->_clientIp = $ip;
		} elseif (($ip = $this->getServer ( 'REMOTE_ADDR' )) != null) {
			$this->_clientIp = $ip;
		} else {
			$this->_clientIp = null;
		}
	}

	/**
	 * 返回用户主机名
	 *
	 * @return string user host name, null if cannot be determined
	 */
	public function getUserHost()
	{
		return $this->getServer ( 'REMOTE_HOST' );
	}

	/**
	 * 返回认证用户名
	 *
	 * @return string the username sent via HTTP authentication, null if the username is not given
	 */
	public function getAuthUser()
	{
		return $this->getServer ( 'PHP_AUTH_USER' );
	}

	/**
	 * 返回认证密码
	 *
	 * @return string the password sent via HTTP authentication, null if the password is not given
	 */
	public function getAuthPassword()
	{
		return $this->getServer ( 'PHP_AUTH_PW' );
	}
	private $_port;

	/**
	 * 返回HTTP请求端口
	 *
	 * @return integer port number for insecure requests.
	 * @see setPort()
	 */
	public function getPort()
	{
		if ($this->_port === null) {
			$this->_port = ! $this->getIsSecureConnection () && isset ( $_SERVER ['SERVER_PORT'] ) ? ( int ) $_SERVER ['SERVER_PORT'] : 80;
		}

		return $this->_port;
	}

	/**
	 * 设置HTTP请求端口
	 *
	 * @param integer $value port number.
	 */
	public function setPort($value)
	{
		if ($value != $this->_port) {
			$this->_port = ( int ) $value;
			$this->_hostInfo = null;
		}
	}
	private $_securePort;

	/**
	 * 返回HTTPS请求端口
	 *
	 * @return integer port number for secure requests.
	 * @see setSecurePort()
	 */
	public function getSecurePort()
	{
		if ($this->_securePort === null) {
			$this->_securePort = $this->getIsSecureConnection () && isset ( $_SERVER ['SERVER_PORT'] ) ? ( int ) $_SERVER ['SERVER_PORT'] : 443;
		}

		return $this->_securePort;
	}

	/**
	 * 设置HTTPS请求端口
	 *
	 * @param integer $value port number.
	 */
	public function setSecurePort($value)
	{
		if ($value != $this->_securePort) {
			$this->_securePort = ( int ) $value;
			$this->_hostInfo = null;
		}
	}
	private $_contentTypes;

	/**
	 * 返回由用户可接受的内容类型。
	 * ```php
	 * $_SERVER['HTTP_ACCEPT'] = 'text/plain; q=0.5, application/json; version=1.0, application/xml; version=2.0;';
	 * $types = $request->getAcceptableContentTypes();
	 * print_r($types);
	 * // displays:
	 * // [
	 * // 'application/json' => ['q' => 1, 'version' => '1.0'],
	 * // 'application/xml' => ['q' => 1, 'version' => '2.0'],
	 * // 'text/plain' => ['q' => 0.5],
	 * // ]
	 * ```
	 *
	 * @return array the content types ordered by the quality score. Types with the highest scores
	 *         will be returned first. The array keys are the content types, while the array values
	 *         are the corresponding quality score and other parameters as given in the header.
	 */
	public function getAcceptableContentTypes()
	{
		if ($this->_contentTypes === null) {
			if (isset ( $_SERVER ['HTTP_ACCEPT'] )) {
				$this->_contentTypes = $this->parseAcceptHeader ( $_SERVER ['HTTP_ACCEPT'] );
			} else {
				$this->_contentTypes = [ ];
			}
		}

		return $this->_contentTypes;
	}

	/**
	 * 设置用户可接受的内容类型。
	 * 请参阅 [[getAcceptableContentTypes()]] 的格式参数
	 *
	 * @param array $value the content types that are acceptable by the end user. They should
	 *        be ordered by the preference level.
	 * @see getAcceptableContentTypes()
	 * @see parseAcceptHeader()
	 */
	public function setAcceptableContentTypes($value)
	{
		$this->_contentTypes = $value;
	}

	/**
	 * 返回请求内容类型
	 * The Content-Type header field indicates the MIME type of the data
	 * contained in [[getRawBody()]] or, in the case of the HEAD method, the
	 * media type that would have been sent had the request been a GET.
	 * For the MIME-types the user expects in response, see [[acceptableContentTypes]].
	 *
	 * @return string request content-type. Null is returned if this information is not available.
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.17
	 *       HTTP 1.1 header field definitions
	 */
	public function getContentType()
	{
		if (isset ( $_SERVER ["CONTENT_TYPE"] )) {
			return $_SERVER ["CONTENT_TYPE"];
		} elseif (isset ( $_SERVER ["HTTP_CONTENT_TYPE"] )) {
			// fix bug https://bugs.php.net/bug.php?id=66606
			return $_SERVER ["HTTP_CONTENT_TYPE"];
		}

		return null;
	}
	private $_languages;

	/**
	 * 返回被用户所接受的语言。
	 *
	 * @return array the languages ordered by the preference level. The first element
	 *         represents the most preferred language.
	 */
	public function getAcceptableLanguages()
	{
		if ($this->_languages === null) {
			if (isset ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'] )) {
				$this->_languages = array_keys ( $this->parseAcceptHeader ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'] ) );
			} else {
				$this->_languages = [ ];
			}
		}

		return $this->_languages;
	}

	/**
	 * 设置用户语言
	 *
	 * @param array $value the languages that are acceptable by the end user. They should
	 *        be ordered by the preference level.
	 */
	public function setAcceptableLanguages($value)
	{
		$this->_languages = $value;
	}

	/**
	 * 解析指定的Header头
	 *
	 * ```php
	 * $header = 'text/plain; q=0.5, application/json; version=1.0, application/xml; version=2.0;';
	 * $accepts = $request->parseAcceptHeader($header);
	 * print_r($accepts);
	 * // displays:
	 * // [
	 * // 'application/json' => ['q' => 1, 'version' => '1.0'],
	 * // 'application/xml' => ['q' => 1, 'version' => '2.0'],
	 * // 'text/plain' => ['q' => 0.5],
	 * // ]
	 * ```
	 *
	 * @param string $header the header to be parsed
	 * @return array the acceptable values ordered by their quality score. The values with the highest scores
	 *         will be returned first.
	 */
	public function parseAcceptHeader($header)
	{
		$accepts = [ ];
		foreach ( explode ( ',', $header ) as $i => $part ) {
			$params = preg_split ( '/\s*;\s*/', trim ( $part ), - 1, PREG_SPLIT_NO_EMPTY );
			if (empty ( $params )) {
				continue;
			}
			$values = [
					'q' => [
							$i,
							array_shift ( $params ),
							1
					]
			];
			foreach ( $params as $param ) {
				if (strpos ( $param, '=' ) !== false) {
					list ( $key, $value ) = explode ( '=', $param, 2 );
					if ($key === 'q') {
						$values ['q'] [2] = ( double ) $value;
					} else {
						$values [$key] = $value;
					}
				} else {
					$values [] = $param;
				}
			}
			$accepts [] = $values;
		}

		usort ( $accepts, function ($a, $b)
		{
			$a = $a ['q']; // index, name, q
			$b = $b ['q'];
			if ($a [2] > $b [2]) {
				return - 1;
			} elseif ($a [2] < $b [2]) {
				return 1;
			} elseif ($a [1] === $b [1]) {
				return $a [0] > $b [0] ? 1 : - 1;
			} elseif ($a [1] === '*/*') {
				return 1;
			} elseif ($b [1] === '*/*') {
				return - 1;
			} else {
				$wa = $a [1] [strlen ( $a [1] ) - 1] === '*';
				$wb = $b [1] [strlen ( $b [1] ) - 1] === '*';
				if ($wa xor $wb) {
					return $wa ? 1 : - 1;
				} else {
					return $a [0] > $b [0] ? 1 : - 1;
				}
			}
		} );

		$result = [ ];
		foreach ( $accepts as $accept ) {
			$name = $accept ['q'] [1];
			$accept ['q'] = $accept ['q'] [2];
			$result [$name] = $accept;
		}

		return $result;
	}

	/**
	 * 返回 Etags.
	 *
	 * @return array The entity tags
	 */
	public function getETags()
	{
		if (isset ( $_SERVER ['HTTP_IF_NONE_MATCH'] )) {
			return preg_split ( '/[\s,]+/', $_SERVER ['HTTP_IF_NONE_MATCH'], - 1, PREG_SPLIT_NO_EMPTY );
		} else {
			return [ ];
		}
	}
}