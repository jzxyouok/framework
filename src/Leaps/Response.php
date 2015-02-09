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
namespace Leaps;

use Leaps\Di\Injectable;

class Response extends Injectable
{
	/**
	 *
	 * @var integer 退出状态 0-254，0为正常结束。
	 */
	public $exitStatus = 0;

	protected $_dependencyInjector;

	/**
	 * 设置DI容器
	 *
	 * @param Leaps\DiInterface dependencyInjector
	 */
	public function setDI(DiInterface $dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;
	}

	/**
	 * 获取Di容器
	 *
	 * @return Leaps\DiInterface
	 */
	public function getDI()
	{
		$dependencyInjector = $this->_dependencyInjector;
		if (! is_object ( $dependencyInjector )) {
			$dependencyInjector = \Leaps\Di::getDefault ();
			if (! is_object ( $dependencyInjector )) {
				throw new Exception ( "A dependency injection object is required to access the 'url' service" );
			}
			$this->_dependencyInjector = $dependencyInjector;
		}
		return $dependencyInjector;
	}

	/**
	 * 发送响应到客户端
	 */
	public function send()
	{
	}

	/**
	 * 清除所有缓冲区数据
	 */
	public function clearOutputBuffers()
	{
		for($level = ob_get_level (); $level > 0; -- $level) {
			if (! @ob_end_clean ()) {
				ob_clean ();
			}
		}
	}
}
