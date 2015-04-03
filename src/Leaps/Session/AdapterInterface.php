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
namespace Leaps\Session;

/**
 * Leaps\Session\AdapterInterface
 *
 * Interface for Leaps\Session adapters
 */
interface AdapterInterface
{

	/**
	 * Starts session, optionally using an adapter
	 *
	 * @param array options
	 */
	public function start();

	/**
	 * Sets session options
	 *
	 * @param array options
	*/
	public function setOptions($options);

	/**
	 * Get internal options
	 *
	 * @return array
	*/
	public function getOptions();

	/**
	 * Gets a session variable from an application context
	 *
	 * @param string index
	 * @param mixed defaultValue
	 * @return mixed
	*/
	public function get($index, $defaultValue = null);

	/**
	 * Sets a session variable in an application context
	 *
	 * @param string index
	 * @param string value
	*/
	public function set($index, $value);

	/**
	 * Check whether a session variable is set in an application context
	 *
	 * @param string index
	 * @return boolean
	*/
	public function has($index);

	/**
	 * Removes a session variable from an application context
	 *
	 * @param string index
	*/
	public function remove($index);

	/**
	 * Returns active session id
	 *
	 * @return string
	*/
	public function getId();

	/**
	 * Check whether the session has been started
	 *
	 * @return boolean
	*/
	public function isStarted();

	/**
	 * Destroys the active session
	 *
	 * @return boolean
	*/
	public function destroy();

}
