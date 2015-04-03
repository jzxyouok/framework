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

/**
 * Leaps\Http\Response\CookiesInterface
 *
 * Interface for Leaps\Http\Response\Cookies
 */
interface CookiesInterface
{

	/**
	 * Set if cookies in the bag must be automatically encrypted/decrypted
	 *
	 * @param boolean useEncryption
	 * @return Leaps\Http\CookiesInterface
	 */
	public function useEncryption($useEncryption);

	/**
	 * Returns if the bag is automatically encrypting/decrypting cookies
	 *
	 * @return boolean
	*/
	public function isUsingEncryption();

	/**
	 * Sets a cookie to be sent at the end of the request
	 *
	 * @param string name
	 * @param mixed value
	 * @param int expire
	 * @param string path
	 * @param boolean secure
	 * @param string domain
	 * @param boolean httpOnly
	 * @return Leaps\Http\CookiesInterface
	*/
	public function set($name, $value=null, $expire=0, $path='/', $secure=null, $domain=null, $httpOnly=null);

	/**
	 * Gets a cookie from the bag
	 *
	 * @param string name
	 * @return Leaps\Http\Cookie
	*/
	public function get($name);

	/**
	 * Check if a cookie is defined in the bag or exists in the _COOKIE superglobal
	 *
	 * @param string name
	 * @return boolean
	*/
	public function has($name);

	/**
	 * Deletes a cookie by its name
	 * This method does not removes cookies from the _COOKIE superglobal
	 *
	 * @param string name
	 * @return boolean
	*/
	public function delete($name);

	/**
	 * Sends the cookies to the client
	 *
	 * @return boolean
	*/
	public function send();

	/**
	 * Reset set cookies
	 *
	 * @return Phalcon\Http\CookiesInterface
	*/
	public function reset();

}
