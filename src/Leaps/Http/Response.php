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

use Leaps\Http\ResponseInterface;
use Leaps\Http\Response\Exception;
use Leaps\Http\Response\HeadersInterface;
use Leaps\Http\Response\CookiesInterface;
// use Leaps\Mvc\UrlInterface;
// use Leaps\Mvc\ViewInterface;
use Leaps\Http\Response\Headers;
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
class Response implements ResponseInterface, InjectionAwareInterface
{
	protected $_sent = false;
	protected $_content = null;
	protected $_headers = null;
	protected $_cookies = null;
	protected $_file = null;
	protected $_dependencyInjector;
	protected $_statusCodes = [
			// INFORMATIONAL CODES
			100 => "Continue",
			101 => "Switching Protocols",
			102 => "Processing",
			// SUCCESS CODES
			200 => "OK",
			201 => "Created",
			202 => "Accepted",
			203 => "Non-Authoritative Information",
			204 => "No Content",
			205 => "Reset Content",
			206 => "Partial Content",
			207 => "Multi-status",
			208 => "Already Reported",
			// REDIRECTION CODES
			300 => "Multiple Choices",
			301 => "Moved Permanently",
			302 => "Found",
			303 => "See Other",
			304 => "Not Modified",
			305 => "Use Proxy",
			306 => "Switch Proxy", // Deprecated
			307 => "Temporary Redirect",
			// CLIENT ERROR
			400 => "Bad Request",
			401 => "Unauthorized",
			402 => "Payment Required",
			403 => "Forbidden",
			404 => "Not Found",
			405 => "Method Not Allowed",
			406 => "Not Acceptable",
			407 => "Proxy Authentication Required",
			408 => "Request Time-out",
			409 => "Conflict",
			410 => "Gone",
			411 => "Length Required",
			412 => "Precondition Failed",
			413 => "Request Entity Too Large",
			414 => "Request-URI Too Large",
			415 => "Unsupported Media Type",
			416 => "Requested range not satisfiable",
			417 => "Expectation Failed",
			418 => "I'm a teapot",
			422 => "Unprocessable Entity",
			423 => "Locked",
			424 => "Failed Dependency",
			425 => "Unordered Collection",
			426 => "Upgrade Required",
			428 => "Precondition Required",
			429 => "Too Many Requests",
			431 => "Request Header Fields Too Large",
			// SERVER ERROR
			500 => "Internal Server Error",
			501 => "Not Implemented",
			502 => "Bad Gateway",
			503 => "Service Unavailable",
			504 => "Gateway Time-out",
			505 => "HTTP Version not supported",
			506 => "Variant Also Negotiates",
			507 => "Insufficient Storage",
			508 => "Loop Detected",
			511 => "Network Authentication Required"
	];

	/**
	 * Leaps\Http\Response constructor
	 *
	 * @param string content
	 * @param int code
	 * @param string status
	 */
	public function __construct($content = null, $code = null, $status = null)
	{
		if ($content !== null) {
			$this->_content = $content;
		}
		if ($code !== null) {
			$this->setStatusCode ( $code, $status );
		}
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
	 * Sets the HTTP response code
	 *
	 * <code>
	 * $response->setStatusCode(404, "Not Found");
	 * </code>
	 *
	 * @param int code
	 * @param string message
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setStatusCode($code, $message = null)
	{
		$headers = $this->getHeaders ();
		$currentHeadersRaw = $headers->toArray ();

		/**
		 * We use HTTP/1.1 instead of HTTP/1.0
		 *
		 * Before that we would like to unset any existing HTTP/x.y headers
		 */
		if (is_array ( $currentHeadersRaw )) {
			foreach ( $currentHeadersRaw as $key => $v ) {
				if (is_string ( $key ) && strstr ( $key, "HTTP/" )) {
					$headers->remove ( $key );
				}
			}
		}

		// if an empty message is given we try and grab the default for this
		// status code. If a default doesn't exist, stop here.
		if ($message === null) {
			if (! isset ( $this->_statusCodes [$code] )) {
				throw new Exception ( "Non-standard statuscode given withou a message." );
			}

			$defaultMessage = $this->_statusCodes [$code];
			$message = $defaultMessage;
		}

		$headers->setRaw ( "HTTP/1.1 " . $code . " " . $message );

		/**
		 * We also define a 'Status' header with the HTTP status
		 */
		$headers->set ( "Status", $code . " " . $message );

		$this->_headers = $headers;
		return $this;
	}

	/**
	 * Sets a headers bag for the response externally
	 *
	 * @param Leaps\Http\Response\HeadersInterface headers
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setHeaders($headers)
	{
		$this->_headers = $headers;
		return $this;
	}

	/**
	 * Returns headers set by the user
	 *
	 * @return Leaps\Http\Response\HeadersInterface
	 */
	public function getHeaders()
	{
		$headers = $this->_headers;
		if ($headers === null) {
			/**
			 * A Leaps\Http\Response\Headers bag is temporary used to manage the headers before sent them to the client
			 */
			$headers = new Headers ();
			$this->_headers = $headers;
		}
		return $headers;
	}

	/**
	 * Sets a cookies bag for the response externally
	 *
	 * @param Leaps\Http\Response\CookiesInterface cookies
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setCookies($cookies)
	{
		$this->_cookies = $cookies;
		return $this;
	}

	/**
	 * Returns coookies set by the user
	 *
	 * @return Leaps\Http\Response\CookiesInterface
	 */
	public function getCookies()
	{
		return $this->_cookies;
	}

	/**
	 * Overwrites a header in the response
	 *
	 * <code>
	 * $response->setHeader("Content-Type", "text/plain");
	 * </code>
	 *
	 * @param string name
	 * @param string value
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setHeader($name, $value)
	{
		$headers = $this->getHeaders ();
		$headers->set ( $name, $value );
		return $this;
	}

	/**
	 * Send a raw header to the response
	 *
	 * <code>
	 * $response->setRawHeader("HTTP/1.1 404 Not Found");
	 * </code>
	 *
	 * @param string header
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setRawHeader($header)
	{
		$headers = $this->getHeaders ();
		$headers->setRaw ( $header );
		return $this;
	}

	/**
	 * Resets all the stablished headers
	 *
	 * @return Leaps\Http\ResponseInterface
	 */
	public function resetHeaders()
	{
		$headers = $this->getHeaders ();
		$headers->reset ();
		return $this;
	}

	/**
	 * Sets a Expires header to use HTTP cache
	 *
	 * <code>
	 * $this->response->setExpires(new DateTime());
	 * </code>
	 *
	 * @param DateTime datetime
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setExpires(\DateTime $datetime)
	{
		$headers = $this->getHeaders ();
		$date = clone $datetime;

		/**
		 * All the expiration times are sent in UTC
		 * Change the timezone to utc
		 */
		$date->setTimezone ( new \DateTimeZone ( "UTC" ) );

		/**
		 * The 'Expires' header set this info
		 */
		$this->setHeader ( "Expires", $date->format ( "D, d M Y H:i:s" ) . " GMT" );
		return $this;
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
	 * Sets the response content-type mime, optionally the charset
	 *
	 * <code>
	 * $response->setContentType('application/pdf');
	 * $response->setContentType('text/plain', 'UTF-8');
	 * </code>
	 *
	 * @param string contentType
	 * @param string charset
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setContentType($contentType, $charset = null)
	{
		$headers = $this->getHeaders ();
		$name = "Content-Type";
		if ($charset === null) {
			$headers->set ( $name, $contentType );
		} else {
			$headers->set ( $name, $contentType . "; charset=" . $charset );
		}
		return $this;
	}

	/**
	 * Set a custom ETag
	 *
	 * <code>
	 * $response->setEtag(md5(time()));
	 * </code>
	 *
	 * @param string etag
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setEtag($etag)
	{
		$headers = $this->getHeaders ();
		$headers->set ( "Etag", $etag );
		return $this;
	}

	/**
	 * Redirect by HTTP to another action or URL
	 *
	 * <code>
	 * //Using a string redirect (internal/external)
	 * $response->redirect("posts/index");
	 * $response->redirect("http://en.wikipedia.org", true);
	 * $response->redirect("http://www.example.com/new-location", true, 301);
	 *
	 * //Making a redirection based on a named route
	 * $response->redirect(array(
	 * "for" => "index-lang",
	 * "lang" => "jp",
	 * "controller" => "index"
	 * ));
	 * </code>
	 *
	 * @param string|array location
	 * @param boolean externalRedirect
	 * @param int statusCode
	 * @return Leaps\Http\ResponseInterface
	 */
	public function redirect($location = null, $externalRedirect = false, $statusCode = 302)
	{
		if (! $location) {
			$location = "";
		}

		if ($externalRedirect) {
			$header = $location;
		} else {
			if (is_string ( $location ) && strstr ( $location, "://" )) {
				$matched = preg_match ( "/^[^:\\/?#]++:/", $location );
				if ($matched) {
					$header = $location;
				} else {
					$header = null;
				}
			} else {
				$header = null;
			}
		}

		$dependencyInjector = $this->getDI ();

		if (! $header) {
			$url = $dependencyInjector->getShared ( "url" );
			$header = $url->get ( location );
		}

		if ($dependencyInjector->has ( "view" )) {
			$view = $dependencyInjector->getShared ( "view" );
			$view->disable ();
		}

		/**
		 * The HTTP status is 302 by default, a temporary redirection
		 */
		if ($statusCode < 300 || $statusCode > 308) {
			$statusCode = 302;
			$message = $this->_statusCodes [302];
		} else {
			$message = isset ( $this->_statusCodes [$statusCode] ) ? $this->_statusCodes [$statusCode] : '';
		}

		$this->setStatusCode ( $statusCode, $message );

		/**
		 * Change the current location using 'Location'
		 */
		$this->setHeader ( "Location", header );

		return $this;
	}

	/**
	 * Sets HTTP response body
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
		$this->_content = $content;
		return $this;
	}

	/**
	 * Sets HTTP response body.
	 * The parameter is automatically converted to JSON
	 *
	 * <code>
	 * $response->setJsonContent(array("status" => "OK"));
	 * </code>
	 *
	 * @param mixed content
	 * @param int jsonOptions
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setJsonContent($content, $jsonOptions = 0)
	{
		$this->_content = json_encode ( $content, $jsonOptions );
		return $this;
	}

	/**
	 * Appends a string to the HTTP response body
	 *
	 * @param string content
	 * @return Leaps\Http\ResponseInterface
	 */
	public function appendContent($content)
	{
		$this->_content = $this->getContent () . $content;
		return $this;
	}

	/**
	 * Gets the HTTP response body
	 *
	 * @return string
	 */
	public function getContent()
	{
		return $this->_content;
	}

	/**
	 * Check if the response is already sent
	 *
	 * @return boolean
	 */
	public function isSent()
	{
		return $this->_sent;
	}

	/**
	 * Sends headers to the client
	 *
	 * @return Leaps\Http\ResponseInterface
	 */
	public function sendHeaders()
	{
		$headers = $this->_headers;
		if (is_object ( $headers )) {
			$headers->send ();
		}
		return $this;
	}

	/**
	 * Sends cookies to the client
	 *
	 * @return Leaps\Http\ResponseInterface
	 */
	public function sendCookies()
	{
		$cookies = $this->_cookies;
		if (is_object ( $cookies )) {
			$cookies->send ();
		}
		return $this;
	}

	/**
	 * Prints out HTTP response to the client
	 *
	 * @return Leaps\Http\ResponseInterface
	 */
	public function send()
	{
		if ($this->_sent) {
			throw new Exception ( "Response was already sent" );
		}

		/**
		 * Send headers
		 */
		$headers = $this->_headers;
		if (is_object ( $headers )) {
			$headers->send ();
		}

		/**
		 * Send Cookies/comment>
		 */
		$cookies = $this->_cookies;
		if (is_object ( $cookies )) {
			$cookies->send ();
		}

		/**
		 * Output the response body
		 */
		$content = $this->_content;
		if ($content != null) {
			echo $content;
		} else {
			$file = $this->_file;
			if (is_string ( $file ) && strlen ( $file )) {
				readfile ( $file );
			}
		}

		$this->_sent = true;
		return $this;
	}

	/**
	 * Sets an attached file to be sent at the end of the request
	 *
	 * @param string filePath
	 * @param string attachmentName
	 * @return Leaps\Http\ResponseInterface
	 */
	public function setFileToSend($filePath, $attachmentName = null, $attachment = true)
	{
		if (! is_string ( $attachmentName )) {
			$basePath = basename ( $filePath );
		} else {
			$basePath = $attachmentName;
		}

		if ($attachment) {
			$headers = $this->getHeaders ();

			$headers->setRaw ( "Content-Description: File Transfer" );
			$headers->setRaw ( "Content-Type: application/octet-stream" );
			$headers->setRaw ( "Content-Disposition: attachment; filename=" . $basePath );
			$headers->setRaw ( "Content-Transfer-Encoding: binary" );
		}

		$this->_file = $filePath;
		return $this;
	}
}
