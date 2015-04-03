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
 * Leaps\Session\Adapter
 *
 * Base class for Leaps\Session adapters
 */
abstract class Adapter
{
	protected $_uniqueId;
	protected $_started = false;
	protected $_options;

	/**
	 * Leaps\Session\Adapter constructor
	 *
	 * @param array options
	 */
	public function __construct($options = null)
	{
		if (is_array ( $options )) {
			$this->setOptions ( $options );
		}
	}

	/**
	 * Starts the session (if headers are already sent the session will not be started)
	 *
	 * @return boolean
	 */
	public function start()
	{
		if (! headers_sent ()) {
			session_start ();
			$this->_started = true;
			return true;
		}
		return false;
	}

	/**
	 * Sets session's options
	 *
	 * <code>
	 * session->setOptions(array(
	 * 'uniqueId' => 'my-private-app'
	 * ));
	 * </code>
	 *
	 * @param array options
	 */
	public function setOptions($options)
	{
		if (isset ( $options ["uniqueId"] )) {
			$this->_uniqueId = $options ["uniqueId"];
		}
		$this->_options = $options;
	}

	/**
	 * Get internal options
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Gets a session variable from an application context
	 *
	 * @param string index
	 * @param mixed defaultValue
	 * @param boolean remove
	 * @return mixed
	 */
	public function get($index, $defaultValue = null, $remove = false)
	{
		$key = $this->_uniqueId . $index;
		if (isset ( $_SESSION [$key] )) {
			if (! empty ( $_SESSION [$key] )) {
				$value = $_SESSION [$key];
				if ($remove) {
					unset ( $_SESSION [$key] );
				}
				return $value;
			}
		}
		return $defaultValue;
	}

	/**
	 * Sets a session variable in an application context
	 *
	 * <code>
	 * session->set('auth', 'yes');
	 * </code>
	 *
	 * @param string index
	 * @param string value
	 */
	public function set($index, $value)
	{
		$_SESSION [$this->_uniqueId . $index] = $value;
	}

	/**
	 * Check whether a session variable is set in an application context
	 *
	 * <code>
	 * var_dump($session->has('auth'));
	 * </code>
	 *
	 * @param string index
	 */
	public function has($index)
	{
		return isset ( $_SESSION [$this->_uniqueId . $index] );
	}

	/**
	 * Removes a session variable from an application context
	 *
	 * <code>
	 * $session->remove('auth');
	 * </code>
	 */
	public function remove($index)
	{
		unset ( $_SESSION [$this->_uniqueId . $index] );
	}

	/**
	 * Returns active session id
	 *
	 * <code>
	 * echo $session->getId();
	 * </code>
	 */
	public function getId()
	{
		return session_id ();
	}

	/**
	 * Set the current session id
	 *
	 * <code>
	 * $session->setId($id);
	 * </code>
	 *
	 * @param string id
	 */
	public function setId($id)
	{
		session_id ( $id );
	}

	/**
	 * Check whether the session has been started
	 *
	 * <code>
	 * var_dump($session->isStarted());
	 * </code>
	 */
	public function isStarted()
	{
		return $this->_started;
	}

	/**
	 * Destroys the active session
	 *
	 * <code>
	 * var_dump(session->destroy());
	 * </code>
	 */
	public function destroy()
	{
		$this->_started = false;
		return session_destroy ();
	}

	/**
	 * Alias: Gets a session variable from an application context
	 *
	 * @param string index
	 * @return mixed
	 */
	public function __get($index)
	{
		return $this->get ( $index );
	}

	/**
	 * Alias: Sets a session variable in an application context
	 *
	 * @param string index
	 * @param string value
	 */
	public function __set($index, $value)
	{
		return $this->set ( $index, $value );
	}

	/**
	 * Alias: Check whether a session variable is set in an application context
	 *
	 * @param string index
	 */
	public function __isset($index)
	{
		return $this->has ( $index );
	}

	/**
	 * Alias: Removes a session variable from an application context
	 */
	public function __unset($index)
	{
		return $this->remove ( $index );
	}
}
