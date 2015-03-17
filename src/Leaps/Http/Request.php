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
use Leaps\DiInterface;
use Leaps\Http\Request\Exception;
use Leaps\InvalidConfigException;
use Leaps\Di\InjectionAwareInterface;

class Request extends Base implements RequestInterface, InjectionAwareInterface
{
	public $methodParam = "_method";
	protected $_dependencyInjector;
	protected $_rawBody;
	protected $_url;
	protected $_hostInfo;
	protected $_scriptUrl;
	protected $_baseUrl;
	protected $_pathInfo;

	/**
	 * 初始化
	 */
	public function init()
	{
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see \Leaps\Http\RequestInterface::resolve()
	 */
	public function resolve()
	{
		if (! is_object ( $this->_router )) {
			if (! is_object ( $this->_dependencyInjector )) {
				throw new Exception ( "A dependency injection object is required to access the 'router' service" );
			}
			$this->_router = $this->_dependencyInjector->getShared ( "router" );
		}
		$result = $this->_router->parseRequest ( $this );
		if ($result !== false) {
			list ( $route, $params ) = $result;
			$_GET = array_merge ( $_GET, $params );
			return [
					$route,
					$_GET
			];
		} else {
			throw new \Exception ( "Page not found." );
		}
	}

	/**
	 * Gets a variable from the $_REQUEST superglobal applying filters if needed.
	 * If no parameters are given the $_REQUEST superglobal is returned
	 *
	 * <code>
	 * //Returns value from $_REQUEST["user_email"] without sanitizing
	 * $userEmail = $request->get("user_email");
	 *
	 * //Returns value from $_REQUEST["user_email"] with sanitizing
	 * $userEmail = $request->get("user_email", "email");
	 * </code>
	 *
	 * @param string name
	 * @param string|array filters
	 * @param mixed defaultValue
	 * @param boolean notAllowEmpty
	 * @param boolean noRecursive
	 * @return mixed
	 */
	public function get($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		if ($name !== null) {
			if (isset ( $_REQUEST [$name] )) {
				if ($filters !== null) {
					if (! is_object ( $this->_filter )) {
						if (! is_object ( $this->_dependencyInjector )) {
							throw new Exception ( "A dependency injection object is required to access the 'filter' service" );
						}
						$this->_filter = $this->_dependencyInjector->getShared ( "filter" );
					}
					$_REQUEST [$name] = $this->_filter->sanitize ( $_REQUEST [$name], $filters, $noRecursive );
					if ((empty ( $_REQUEST [$name] ) && $notAllowEmpty === true) || $_REQUEST [$name] === false) {
						return $defaultValue;
					}
					return $_REQUEST [$name];
				} else {
					if (empty ( $_REQUEST [$name] ) && $notAllowEmpty === true) {
						return $defaultValue;
					}
					return $_REQUEST [$name];
				}
			}
			return $defaultValue;
		}
		return $_REQUEST [$name];
	}

	/**
	 * Gets a variable from the $_POST superglobal applying filters if needed
	 * If no parameters are given the $_POST superglobal is returned
	 *
	 * <code>
	 * //Returns value from $_POST["user_email"] without sanitizing
	 * $userEmail = $request->getPost("user_email");
	 *
	 * //Returns value from $_POST["user_email"] with sanitizing
	 * $userEmail = $request->getPost("user_email", "email");
	 * </code>
	 *
	 * @param string name
	 * @param string|array filters
	 * @param mixed defaultValue
	 * @param boolean notAllowEmpty
	 * @param boolean noRecursive
	 * @return mixed
	 */
	public function getPost($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		if ($name !== null) {
			if (isset ( $_POST [$name] )) {
				if ($filters !== null) {
					if (! is_object ( $this->_filter )) {
						if (! is_object ( $this->_dependencyInjector )) {
							throw new Exception ( "A dependency injection object is required to access the 'filter' service" );
						}
						$this->_filter = $this->_dependencyInjector->getShared ( "filter" );
					}

					$_POST [$name] = $this->_filter->sanitize ( $_POST [$name], $filters, $noRecursive );

					if (empty ( $_POST [$name] ) && $notAllowEmpty === true) {
						return $defaultValue;
					}
					return $_POST [$name];
				} else {
					if (empty ( $_POST [$name] ) && $notAllowEmpty === true) {
						return $defaultValue;
					}
					return $_POST;
				}
			}
			return $defaultValue;
		}
		return $_POST;
	}

	/**
	 * Gets variable from $_GET superglobal applying filters if needed
	 * If no parameters are given the $_GET superglobal is returned
	 *
	 * <code>
	 * //Returns value from $_GET["id"] without sanitizing
	 * $id = $request->getQuery("id");
	 *
	 * //Returns value from $_GET["id"] with sanitizing
	 * $id = $request->getQuery("id", "int");
	 *
	 * //Returns value from $_GET["id"] with a default value
	 * $id = $request->getQuery("id", null, 150);
	 * </code>
	 *
	 * @param string name
	 * @param string|array filters
	 * @param mixed defaultValue
	 * @param boolean notAllowEmpty
	 * @param boolean noRecursive
	 * @return mixed
	 */
	public function getQuery($name = null, $filters = null, $defaultValue = null, $notAllowEmpty = false, $noRecursive = false)
	{
		if ($name !== null) {
			if (isset ( $_GET [$name] )) {
				if ($filters !== null) {
					if (! is_object ( $this->_filter )) {
						if (! is_object ( $this->_dependencyInjector )) {
							throw new Exception ( "A dependency injection object is required to access the 'filter' service" );
						}
						$this->_filter = $this->_dependencyInjector->getShared ( "filter" );
					}
					$_GET [$name] = $this->_filter->sanitize ( $_GET [$name], $filters, $noRecursive );
					if (empty ( $_GET [$name] ) && $notAllowEmpty === true) {
						return $defaultValue;
					}
					return $_GET [$name];
				} else {
					if (empty ( $_GET [$name] ) && $notAllowEmpty === true) {
						return $defaultValue;
					}
					return $_GET [$name];
				}
			}
			return $defaultValue;
		}
		return $_GET;
	}

	/**
	 * 返回请求Headers
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		$headers = [ ];
		$contentHeaders = [
				"CONTENT_TYPE" => true,
				"CONTENT_LENGTH" => true
		];
		foreach ( $_SERVER as $name => $value ) {
			if (strncmp ( $name, "HTTP_", 5 ) === 0) {
				$name = ucwords ( strtolower ( str_replace ( "_", " ", substr ( $name, 5 ) ) ) );
				$name = str_replace ( " ", "-", $name );
				$headers [$name] = $value;
			} elseif (isset ( $contentHeaders [$name] )) {
				$name = ucwords ( strtolower ( str_replace ( "_", " ", $name ) ) );
				$name = str_replace ( " ", "-", $name );
				$headers [$name] = $value;
			}
		}
		return $headers;
	}

	/**
	 * 从请求数据获取HTTP头
	 *
	 * @param string header
	 * @return string
	 */
	public final function getHeader($header)
	{
		if (isset ( $_SERVER [$header] )) {
			return $_SERVER [$header];
		} elseif (isset ( $_SERVER ["HTTP_" . $header] )) {
			return $_SERVER ["HTTP_" . $header];
		}
		return "";
	}

	/**
	 * 获取POST原始请求体
	 *
	 * @return string
	 */
	public function getRawBody()
	{
		if (empty ( $this->_rawBody )) {
			$this->_rawBody = file_get_contents ( "php://input" );
		}
		return $this->_rawBody;
	}

	/**
	 * 获取POST原始请求体并解析JSON
	 *
	 * @param boolean associative
	 * @return string
	 */
	public function getJsonRawBody($associative = false)
	{
		$rawBody = $this->getRawBody ();
		if (is_string ( $rawBody )) {
			return json_decode ( $rawBody, $associative );
		}
		return false;
	}

	/**
	 * 返回Server的值
	 *
	 * @param string name
	 * @return mixed
	 */
	public function getServer($name, $defaultValue = null)
	{
		if (isset ( $_SERVER [$name] )) {
			return $_SERVER [$name];
		}
		return $defaultValue;
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
		} elseif (isset ( $_SERVER ["HTTP_X_HTTP_METHOD_OVERRIDE"] )) {
			return strtoupper ( $_SERVER ["HTTP_X_HTTP_METHOD_OVERRIDE"] );
		} elseif (isset ( $_SERVER ["REQUEST_METHOD"] )) {
			return strtoupper ( $_SERVER ["REQUEST_METHOD"] );
		} else {
			return "GET";
		}
	}

	/**
	 * 返回请求内容类型
	 *
	 * @return mixed
	 */
	public function getContentType()
	{
		if (isset ( $_SERVER ["CONTENT_TYPE"] )) {
			return $_SERVER ["CONTENT_TYPE"];
		} elseif ($_SERVER ["HTTP_CONTENT_TYPE"]) {
			return $_SERVER ["HTTP_CONTENT_TYPE"];
		} else {
			return null;
		}
	}

	/**
	 * 返回 user agent。
	 *
	 * @return string
	 */
	public function getUserAgent()
	{
		if (isset ( $_SERVER ["HTTP_USER_AGENT"] )) {
			return $_SERVER ["HTTP_USER_AGENT"];
		}
		return "";
	}

	/**
	 * 返回URL来路
	 *
	 * @return string
	 */
	public function getReferrer()
	{
		if (isset ( $_SERVER ["HTTP_REFERER"] )) {
			return $_SERVER ["HTTP_REFERER"];
		}
		return "";
	}

	/**
	 * 获取活动服务器名称
	 *
	 * @return string
	 */
	public function getServerName()
	{
		if (isset ( $_SERVER ["SERVER_NAME"] )) {
			return $_SERVER ["SERVER_NAME"];
		}
		return "localhost";
	}

	/**
	 * 返回服务器端口
	 *
	 * @return integer server port number
	 */
	public function getServerPort()
	{
		if (isset ( $_SERVER ["SERVER_PORT"] )) {
			return $_SERVER ["SERVER_PORT"];
		}
		return 80;
	}

	/**
	 * 获取服务器IP地址
	 *
	 * @return string
	 */
	public function getServerAddr()
	{
		if (isset ( $_SERVER ["SERVER_ADDR"] )) {
			return $_SERVER ["SERVER_ADDR"];
		}
		return gethostbyname ( "localhost" );
	}

	/**
	 * 获取HTTP模式 (http/https)
	 *
	 * @return string
	 */
	public function getScheme()
	{
		$https = $this->getServer ( "HTTPS" );
		if ($https) {
			if ($https == "off") {
				$scheme = "http";
			} else {
				$scheme = "https";
			}
		} else {
			$scheme = "http";
		}
		return $scheme;
	}

	/**
	 * 返回用户主机名
	 *
	 * @return string user host name, null if cannot be determined
	 */
	public function getUserHost()
	{
		if (isset ( $_SERVER ["REMOTE_HOST"] )) {
			return $_SERVER ["REMOTE_HOST"];
		}
		return "";
	}

	/**
	 * 后返回的请求URL的问号部分。
	 *
	 * @return string part of the request URL that is after the question mark
	 */
	public function getQueryString()
	{
		if (isset ( $_SERVER ["QUERY_STRING"] )) {
			return $_SERVER ["QUERY_STRING"];
		}
		return "";
	}

	/**
	 * Gets auth info accepted by the browser/client from $_SERVER['PHP_AUTH_USER']
	 *
	 * @return array
	 */
	public function getBasicAuth()
	{
		if (isset ( $_SERVER ["PHP_AUTH_USER"] ) && isset ( $_SERVER ["PHP_AUTH_PW"] )) {
			$auth = [ ];
			$auth ["username"] = $_SERVER ["PHP_AUTH_USER"];
			$auth ["password"] = $_SERVER ["PHP_AUTH_PW"];
			return $auth;
		}
		return null;
	}

	/**
	 * Gets auth info accepted by the browser/client from $_SERVER['PHP_AUTH_DIGEST']
	 *
	 * @return array
	 */
	public function getDigestAuth()
	{
		$auth = [ ];
		if (isset ( $_SERVER ["PHP_AUTH_DIGEST"] )) {
			$matches = [ ];
			if (! preg_match_all ( "#(\\w+)=(['\"]?)([^'\" ,]+)\\2#", $_SERVER ["PHP_AUTH_DIGEST"], $matches, 2 )) {
				return $auth;
			}
			if (is_array ( $matches )) {
				foreach ( $matches as $match ) {
					$auth [$match [1]] = $match [3];
				}
			}
		}
		return $auth;
	}

	/**
	 * 返回 Etags.
	 *
	 * @return array The entity tags
	 */
	public function getETags()
	{
		if (isset ( $_SERVER ["HTTP_IF_NONE_MATCH"] )) {
			return preg_split ( "/[\s,]+/", $_SERVER ["HTTP_IF_NONE_MATCH"], - 1, PREG_SPLIT_NO_EMPTY );
		} else {
			return [ ];
		}
	}

	/**
	 * 返回输入脚本的物理路径
	 *
	 * @return string the entry script file path
	 */
	public function getScriptFile()
	{
		if (isset ( $_SERVER ["SCRIPT_FILENAME"] )) {
			return $_SERVER ["SCRIPT_FILENAME"];
		}
		return "";
	}

	/**
	 * Gets information about schema, host and port used by the request
	 *
	 * @return string
	 */
	public function getHttpHost()
	{
		/**
		 * Get the server name from _SERVER['HTTP_HOST']
		 */
		$httpHost = $this->getServer ( "HTTP_HOST" );
		if ($httpHost) {
			return $httpHost;
		}

		/**
		 * Get current scheme
		 */
		$scheme = $this->getScheme ();

		/**
		 * Get the server name from _SERVER['SERVER_NAME']
		 */
		$name = $this->getServer ( "SERVER_NAME" );

		/**
		 * Get the server port from _SERVER['SERVER_PORT']
		 */
		$port = $this->getServer ( "SERVER_PORT" );

		/**
		 * If is standard http we return the server name only
		 */
		if ($scheme == "http" && $port == 80) {
			return $name;
		}

		/**
		 * If is standard secure http we return the server name only
		 */
		if ($scheme == "https" && $port == "443") {
			return $name;
		}

		return $name . ":" . $port;
	}

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
		if (! $this->_hostInfo) {
			$this->_hostInfo = $this->getScheme () . '://' . $this->getHttpHost ();
		}
		return $this->_hostInfo;
	}

	/**
	 * 返回入口脚本的相对URL
	 *
	 * @return string the relative URL of the entry script.
	 * @throws InvalidConfigException if unable to determine the entry script URL
	 */
	public function getScriptUrl()
	{
		if (! $this->_scriptUrl) {
			$scriptFile = $this->getScriptFile ();
			$scriptName = basename ( $scriptFile );
			$pos = strpos ( $_SERVER ["PHP_SELF"], "/" . $scriptName );
			if (basename ( $_SERVER ["SCRIPT_NAME"] ) === $scriptName) {
				$this->_scriptUrl = $_SERVER ["SCRIPT_NAME"];
			} elseif (basename ( $_SERVER ["PHP_SELF"] ) === $scriptName) {
				$this->_scriptUrl = $_SERVER ["PHP_SELF"];
			} elseif (isset ( $_SERVER ["ORIG_SCRIPT_NAME"] ) && basename ( $_SERVER ["ORIG_SCRIPT_NAME"] ) === $scriptName) {
				$this->_scriptUrl = $_SERVER ["ORIG_SCRIPT_NAME"];
			} elseif ($pos !== false) {
				$this->_scriptUrl = substr ( $_SERVER ["SCRIPT_NAME"], 0, $pos ) . "/" . $scriptName;
			} elseif (! empty ( $_SERVER ["DOCUMENT_ROOT"] ) && strpos ( scriptFile, $_SERVER ["DOCUMENT_ROOT"] ) === 0) {
				$this->_scriptUrl = str_replace ( "\\", "/", str_replace ( $_SERVER ["DOCUMENT_ROOT"], "", $scriptFile ) );
			} else {
				throw new InvalidConfigException ( "Unable to determine the entry script URL." );
			}
		}
		return $this->_scriptUrl;
	}

	/**
	 * 返回应用程序的相对URL。
	 *
	 * @return string the relative URL for the application
	 * @see setScriptUrl()
	 */
	public function getBaseUrl()
	{
		if ($this->_baseUrl === null) {
			$this->_baseUrl = rtrim ( dirname ( $this->getScriptUrl () ), "\\/" );
		}
		return $this->_baseUrl;
	}

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
	 * 返回当前请求的绝对URL
	 * 这是一个快捷连接 [[hostInfo]] 和 [[url]].
	 *
	 * @return string the currently requested absolute URL.
	 */
	public function getAbsoluteUrl()
	{
		return $this->getHostInfo () . $this->getUrl ();
	}

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
	 * 检测是否是安全请求
	 *
	 * @return boolean
	 */
	public function isSecureRequest()
	{
		return $this->getScheme () === "https";
	}

	/**
	 * 检测是否是指定的Http请求
	 *
	 * @param string|array methods
	 * @return boolean
	 */
	public function isMethod($methods)
	{
		$httpMethod = $this->getMethod ();

		if (is_string ( $methods )) {
			return $methods == $httpMethod;
		} elseif (is_array ( $methods )) {
			foreach ( $methods as $method ) {
				if ($method == $httpMethod) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 *
	 * 是否是GET请求
	 *
	 * @return boolean
	 */
	public function isGet()
	{
		return $this->getMethod () === "GET";
	}

	/**
	 * 是否是 HEAD 请求
	 *
	 * @return boolean
	 */
	public function isHead()
	{
		return $this->getMethod () === "HEAD";
	}

	/**
	 * 是否是 POST 请求
	 *
	 * @return boolean
	 */
	public function isPost()
	{
		return $this->getMethod () === "POST";
	}

	/**
	 * 是否是 PUT 请求
	 *
	 * @return boolean
	 */
	public function isPut()
	{
		return $this->getMethod () === "PUT";
	}

	/**
	 * 是否是 PATCH 请求
	 *
	 * @return boolean
	 */
	public function isPatch()
	{
		return $this->getMethod () === "PATCH";
	}

	/**
	 * 是否是HTTP DELETE请求
	 *
	 * @return boolean
	 */
	public function isDelete()
	{
		return $this->getMethod () === "DELETE";
	}

	/**
	 * 是否是Http OPTIONS 请求
	 *
	 * @return boolean
	 */
	public function isOptions()
	{
		return $this->getMethod () === "OPTIONS";
	}

	/**
	 * 是否是ajax请求
	 *
	 * @return boolean
	 */
	public function isAjax()
	{
		return isset ( $_SERVER ["HTTP_X_REQUESTED_WITH"] ) && $_SERVER ["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest";
	}

	/**
	 * 返回是否是 PJAX 请求
	 *
	 * @return boolean whether this is a PJAX request
	 */
	public function isPjax()
	{
		return $this->isAjax () && ! empty ( $_SERVER ["HTTP_X_PJAX"] );
	}

	/**
	 * 返回是否是 Adobe Flash 或 Flex 请求
	 *
	 * @return boolean whether this is an Adobe Flash or Adobe Flex request.
	 */
	public function isFlash()
	{
		if (isset ( $_SERVER ["HTTP_USER_AGENT"] )) {
			if (stripos ( $_SERVER ["HTTP_USER_AGENT"], "Shockwave" ) !== false || stripos ( $_SERVER ["HTTP_USER_AGENT"], "Flash" ) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 是否是SOAP请求
	 *
	 * @return boolean
	 */
	public function isSoapRequested()
	{
		if (isset ( $_SERVER ["HTTP_SOAPACTION"] )) {
			return true;
		} else {
			$contentType = $this->getContentType ();
			if (! empty ( $contentType )) {
				return stripos ( $contentType, "application/soap+xml" );
			}
		}
		return false;
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
		if (isset ( $_SERVER ["HTTP_X_REWRITE_URL"] )) {
			$requestUri = $_SERVER ["HTTP_X_REWRITE_URL"];
		} elseif (isset ( $_SERVER ["REQUEST_URI"] )) {
			$requestUri = $_SERVER ["REQUEST_URI"];
			if ($requestUri !== "" && substr ( $requestUri, 0, 1 ) !== "/") {
				$requestUri = preg_replace ( "/^(http|https):\/\/[^\/]+/i", "", $requestUri );
			}
		} elseif (isset ( $_SERVER ["ORIG_PATH_INFO"] ) && ! empty ( $_SERVER ["QUERY_STRING"] )) { // IIS 5.0 CGI
			$requestUri .= "?" . $_SERVER ["QUERY_STRING"];
		} else {
			throw new InvalidConfigException ( "Unable to determine the request URI." );
		}
		return $requestUri;
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
		if (! preg_match ( "%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs", $pathInfo )) {
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
	 * 设置依赖注入器
	 *
	 * @param Leaps\DiInterface dependencyInjector
	 */
	public function setDI(DiInterface $dependencyInjector)
	{
		if (! is_object ( $dependencyInjector )) {
			throw new \Leaps\Di\Exception ( "Dependency Injector is invalid" );
		}
		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * 返回依赖注入器实例
	 *
	 * @return Leaps\Di\DiInterface
	 */
	public function getDI()
	{
		$dependencyInjector = $this->_dependencyInjector;
		if (! is_object ( $dependencyInjector )) {
			$dependencyInjector = \Leaps\Di::getDefault ();
		}
		return $dependencyInjector;
	}
}