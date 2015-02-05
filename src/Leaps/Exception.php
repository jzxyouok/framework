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

class Exception extends \Exception
{

	/**
	 * 返回异常的用户友好名称
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'System Exception';
	}

	/**
	 * 返回该对象的数组表示。
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->toArrayRecursive ( $this );
	}

	/**
	 * 使用数组递归表示异常和前面的所有异常
	 *
	 * @param \Exception 异常对象
	 * @return array 数组表示的异常
	 */
	protected function toArrayRecursive($exception)
	{
		$array = [ 'type' => get_class ( $exception ),'name' => $exception instanceof self ? $exception->getName () : 'Exception','message' => $exception->getMessage (),'code' => $exception->getCode () ];
		if (($prev = $exception->getPrevious ()) !== null) {
			$array ['previous'] = $this->toArrayRecursive ( $prev );
		}
		return $array;
	}
}