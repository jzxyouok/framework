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

class Cookie
{
	/**
	 *
	 * @var string name of the cookie
	 */
	public $name;
	/**
	 *
	 * @var string value of the cookie
	 */
	public $value = '';
	/**
	 *
	 * @var string domain of the cookie
	 */
	public $domain = '';
	/**
	 *
	 * @var integer the timestamp at which the cookie expires. This is the server timestamp.
	 *      Defaults to 0, meaning "until the browser is closed".
	 */
	public $expire = 0;
	/**
	 *
	 * @var string the path on the server in which the cookie will be available on. The default is '/'.
	 */
	public $path = '/';
	/**
	 *
	 * @var boolean whether cookie should be sent via secure connection
	 */
	public $secure = false;

	/**
	 *
	 * @var boolean whether the cookie should be accessible only through the HTTP protocol.
	 *      By setting this property to true, the cookie will not be accessible by scripting languages,
	 *      such as JavaScript, which can effectively help to reduce identity theft through XSS attacks.
	 */
	public $httpOnly = true;

	/**
	 * Constructor.
	 *
	 * @param string $name The name of the cookie
	 * @param string $value The value of the cookie
	 * @param int|string|\DateTime $expire The time the cookie expires
	 * @param string $path The path on the server in which the cookie will be available on
	 * @param string $domain The domain that the cookie is available to
	 * @param bool $secure Whether the cookie should only be transmitted over a secure HTTPS connection from the client
	 * @param bool $httpOnly Whether the cookie will be made accessible only through the HTTP protocol
	 *
	 * @throws \InvalidArgumentException @api
	 */
	public function __construct($name, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true)
	{
		// from PHP source code
		if (preg_match ( "/[=,; \t\r\n\013\014]/", $name )) {
			throw new \InvalidArgumentException ( sprintf ( 'The cookie name "%s" contains invalid characters.', $name ) );
		}

		if (empty ( $name )) {
			throw new \InvalidArgumentException ( 'The cookie name cannot be empty.' );
		}

		// convert expiration time to a Unix timestamp
		if ($expire instanceof \DateTime) {
			$expire = $expire->format ( 'U' );
		} elseif (! is_numeric ( $expire )) {
			$expire = strtotime ( $expire );
			if (false === $expire || - 1 === $expire) {
				throw new \InvalidArgumentException ( 'The cookie expiration time is not valid.' );
			}
		}

		$this->name = $name;
		$this->value = $value;
		$this->domain = $domain;
		$this->expire = $expire;
		$this->path = empty ( $path ) ? '/' : $path;
		$this->secure = ( bool ) $secure;
		$this->httpOnly = ( bool ) $httpOnly;
	}

	/**
	 * Gets the name of the cookie.
	 *
	 * @return string @api
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Gets the value of the cookie.
	 *
	 * @return string @api
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Gets the domain that the cookie is available to.
	 *
	 * @return string @api
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Gets the time the cookie expires.
	 *
	 * @return int @api
	 */
	public function getExpiresTime()
	{
		return $this->expire;
	}

	/**
	 * Gets the path on the server in which the cookie will be available on.
	 *
	 * @return string @api
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Checks whether the cookie should only be transmitted over a secure HTTPS connection from the client.
	 *
	 * @return bool @api
	 */
	public function isSecure()
	{
		return $this->secure;
	}

	/**
	 * Checks whether the cookie will be made accessible only through the HTTP protocol.
	 *
	 * @return bool @api
	 */
	public function isHttpOnly()
	{
		return $this->httpOnly;
	}

	/**
	 * Whether this cookie is about to be cleared.
	 *
	 * @return bool @api
	 */
	public function isCleared()
	{
		return $this->expire < time ();
	}

	/**
	 * Magic method to turn a cookie object into a string without having to explicitly access [[value]].
	 *
	 * ~~~
	 * if (isset($request->cookies['name'])) {
	 * $value = (string) $request->cookies['name'];
	 * }
	 * ~~~
	 *
	 * @return string The value of the cookie. If the value property is null, an empty string will be returned.
	 */
	public function __toString()
	{
		return ( string ) $this->value;
	}
}