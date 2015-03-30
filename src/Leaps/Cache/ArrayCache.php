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

class ArrayCache extends Adapter
{
	private $_cache;

	/**
	 * @inheritdoc
	 */
	public function exists($key)
	{
		$key = $this->buildKey ( $key );
		return isset ( $this->_cache [$key] ) && ($this->_cache [$key] [1] === 0 || $this->_cache [$key] [1] > microtime ( true ));
	}

	/**
	 * @inheritdoc
	 */
	protected function getValue($key)
	{
		if (isset ( $this->_cache [$key] ) && ($this->_cache [$key] [1] === 0 || $this->_cache [$key] [1] > microtime ( true ))) {
			return $this->_cache [$key] [0];
		} else {
			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function setValue($key, $value, $duration)
	{
		$this->_cache [$key] = [
				$value,
				$duration === 0 ? 0 : microtime ( true ) + $duration
		];
		return true;
	}

	/**
	 * @inheritdoc
	 */
	protected function addValue($key, $value, $duration)
	{
		if (isset ( $this->_cache [$key] ) && ($this->_cache [$key] [1] === 0 || $this->_cache [$key] [1] > microtime ( true ))) {
			return false;
		} else {
			$this->_cache [$key] = [
					$value,
					$duration === 0 ? 0 : microtime ( true ) + $duration
			];
			return true;
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function deleteValue($key)
	{
		unset ( $this->_cache [$key] );
		return true;
	}

	/**
	 * @inheritdoc
	 */
	protected function flushValues()
	{
		$this->_cache = [ ];
		return true;
	}
}