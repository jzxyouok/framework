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
 * Leaps\Http\HeadersInterface
 *
 * Interface for Leaps\Http\Headers compatible bags
 */
interface HeadersInterface
{

	/**
	 * Sets a header to be sent at the end of the request
	 *
	 * @param string name
	 * @param string value
	 */
	public function set($name, $value);

	/**
	 * Gets a header value from the internal bag
	 *
	 * @param string name
	 * @return string
	*/
	public function get($name, $default = null, $first = true);

	/**
	 * Sets a raw header to be sent at the end of the request
	 *
	 * @param string header
	*/
	public function setRaw($header);

	/**
	 * Sends the headers to the client
	 *
	 * @return boolean
	*/
	public function send();

	/**
	 * Reset set headers
	 *
	*/
	public function reset();

	/**
	 * Restore a Leaps\Http\Headers object
	 *
	 * @param array data
	 * @return Leaps\Http\HeadersInterface
	*/
	public static function __set_state($data);

}