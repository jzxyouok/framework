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
namespace Leaps\Cache;

use Leaps\Cache;

class SaeCache extends Cache
{
	/**
	 *
	 * @var \Memcache|\Memcached the Memcache instance
	 */
	private $_cache = null;
	public function init()
	{
		parent::init ();
		$this->_cache = memcache_init ();
	}

	/**
	 * @inheritdoc
	 */
	protected function getValue($key)
	{
		return memcache_get ( $this->_cache, $key );
	}

	/**
	 * @inheritdoc
	 */
	protected function setValue($key, $value, $duration)
	{
		$expire = $duration > 0 ? $duration + time () : 0;
		return memcache_set ( $this->_cache, $key, $value, false, ( int ) $expire );
	}

	/**
	 * @inheritdoc
	 */
	protected function addValue($key, $value, $duration)
	{
		$expire = $duration > 0 ? $duration + time () : 0;
		return memcache_add ( $this->_cache, $key, $value, false, ( int ) $expire );
	}

	/**
	 * @inheritdoc
	 */
	protected function deleteValue($key)
	{
		return memcache_delete ( $this->_cache, $key );
	}

	/**
	 * @inheritdoc
	 */
	protected function flushValues()
	{
		return memcache_flush ( $this->_cache );
	}
}