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
namespace Leaps\Session\Adapter;

use Leaps\Session\Adapter;
use Leaps\Cache\MemCacheServer;
use Leaps\Session\AdapterInterface;
use Leaps\Core\InvalidConfigException;

/**
 * Phalcon\Session\Adapter\Memcache
 *
 * This adapter store sessions in memcache
 *
 * <code>
 * $session = new \Phalcon\Session\Adapter\Memcache(array(
 * 'uniqueId' => 'my-private-app'
 * 'host' => '127.0.0.1',
 * 'port' => 11211,
 * 'persistent' => TRUE,
 * 'lifetime' => 3600,
 * 'prefix' => 'my_'
 * ));
 *
 * $session->start();
 *
 * $session->set('var', 'some-value');
 *
 * echo $session->get('var');
 * </code>
 */
class Memcache extends Adapter implements AdapterInterface
{
	public $useMemcached = false;
	public $persistentId;
	public $options;
	public $username;
	public $password;
	protected $_memcache = NULL;
	protected $_lifetime = 8600;

	public function init()
	{
		parent::init ();
		$this->addServers ( $this->getMemcache (), $this->getServers () );
		session_set_save_handler ( [
				$this,
				"open"
		], [
				$this,
				"close"
		], [
				$this,
				"read"
		], [
				$this,
				"write"
		], [
				$this,
				"destroy"
		], [
				$this,
				"gc"
		] );
	}

	/**
	 *
	 * @param \Memcached $cache
	 * @param array $servers
	 */
	protected function addMemcachedServers($memcache, $servers)
	{
		$existingServers = [ ];
		if ($this->persistentId !== null) {
			foreach ( $memcache->getServerList () as $s ) {
				$existingServers [$s ['host'] . ':' . $s ['port']] = true;
			}
		}
		foreach ( $servers as $server ) {
			if (empty ( $existingServers ) || ! isset ( $existingServers [$server->host . ':' . $server->port] )) {
				$memcache->addServer ( $server->host, $server->port, $server->weight );
			}
		}
	}
	protected function addServers($memcache, $servers)
	{
		if (empty ( $servers )) {
			$servers = [
					new MemCacheServer ( [
							'host' => '127.0.0.1',
							'port' => 11211
					] )
			];
		} else {
			foreach ( $servers as $server ) {
				if ($server->host === null) {
					throw new InvalidConfigException ( "The 'host' property must be specified for every memcache server." );
				}
			}
		}
		if ($this->useMemcached) {
			$this->addMemcachedServers ( $memcache, $servers );
		} else {
			$this->addMemcacheServers ( $memcache, $servers );
		}
	}

	/**
	 * Returns the underlying memcache (or memcached) object.
	 *
	 * @return \Memcache|\Memcached the memcache (or memcached) object used by this cache component.
	 * @throws InvalidConfigException if memcache or memcached extension is not loaded
	 */
	public function getMemcache()
	{
		if ($this->_memcache === null) {
			$extension = $this->useMemcached ? 'memcached' : 'memcache';
			if (! extension_loaded ( $extension )) {
				throw new InvalidConfigException ( "MemCache requires PHP $extension extension to be loaded." );
			}

			if ($this->useMemcached) {
				$this->_memcache = $this->persistentId !== null ? new \Memcached ( $this->persistentId ) : new \Memcached ();
				if ($this->username !== null || $this->password !== null) {
					$this->_memcache->setOption ( \Memcached::OPT_BINARY_PROTOCOL, true );
					$this->_memcache->setSaslAuthData ( $this->username, $this->password );
				}
				if (! empty ( $this->options )) {
					$this->_memcache->setOptions ( $this->options );
				}
			} else {
				$this->_memcache = new \Memcache ();
			}
		}
		return $this->_memcache;
	}

	/**
	 * Returns the memcache or memcached server configurations.
	 *
	 * @return MemCacheServer[] list of memcache server configurations.
	 */
	public function getServers()
	{
		return $this->_servers;
	}

	/**
	 *
	 * @param array $config list of memcache or memcached server configurations. Each element must be an array
	 *        with the following keys: host, port, persistent, weight, timeout, retryInterval, status.
	 * @see http://php.net/manual/en/memcache.addserver.php
	 * @see http://php.net/manual/en/memcached.addserver.php
	 */
	public function setServers($config)
	{
		foreach ( $config as $c ) {
			$this->_servers [] = new MemCacheServer ( $c );
		}
	}
	public function open()
	{
		return true;
	}
	public function close()
	{
		return true;
	}

	/**
	 *
	 * @ERROR!!!
	 *
	 * @param string sessionId
	 * @return mixed
	 */
	public function read($sessionId)
	{
		return $this->_memcache->get ( $sessionId, $this->_lifetime );
	}

	/**
	 *
	 * @ERROR!!!
	 *
	 * @param string sessionId
	 * @param string data
	 */
	public function write($sessionId, $data)
	{
		$expire = $this->_lifetime > 0 ? $this->_lifetime + time () : 0;
		return $this->useMemcached ? $this->_memcache->set ( $sessionId, $data, $expire ) : $this->_memcache->set ( $sessionId, $data, 0, $expire );
	}

	/**
	 *
	 * @ERROR!!!
	 *
	 * @param string sessionId
	 * @return boolean
	 */
	public function destroy($session_id = null)
	{
		if ($session_id === null) {
			$session_id = $this->getId ();
		}
		return $this->_memcache->delete ( $session_id, 0 );
	}

	/**
	 * @ERROR!!!
	 */
	public function gc()
	{
		return true;
	}
}
