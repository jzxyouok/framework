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

class Arr
{

	/**
	 * 递归合并两个数组
	 *
	 * @param array $array1 数组1
	 * @param array $array2  数组2
	 * @return array 合并后的数组
	 */
	public static function mergeArray($array1, $array2) {
		foreach ($array2 as $key => $value) {
			if (empty($value)) {
				$array1[$key] = $value;
			} else if (!isset($array1[$key])) {
				$array1[$key] = $value;
			} elseif (is_array($array1[$key]) && is_array($value)) {
				$array1[$key] = self::mergeArray($array1[$key], $array2[$key]);
			} elseif (is_numeric($key) && $array1[$key] !== $array2[$key]) {
				$array1[] = $value;
			} else
				$array1[$key] = $value;
		}
		return $array1;
	}

	/**
	 * 按指定key合并两个数组
	 *
	 * @param string key 合并数组的参照值
	 * @param array $array1 要合并数组
	 * @param array $array2 要合并数组
	 * @return array 返回合并的数组
	 */
	public static function mergeArrayWithKey($key, array $array1, array $array2)
	{
		if (! $key || ! $array1 || ! $array2) {
			return [];
		}
		$array1 = static::rebuildArrayWithKey ( $key, $array1 );
		$array2 = static::rebuildArrayWithKey ( $key, $array2 );
		$tmp = [];
		foreach ( $array1 as $key => $array ) {
			if (isset ( $array2 [$key] )) {
				$tmp [$key] = array_merge ( $array, $array2 [$key] );
				unset ( $array2 [$key] );
			} else {
				$tmp [$key] = $array;
			}
		}
		return array_merge ( $tmp, ( array ) $array2 );
	}

	/**
	 * 按指定key合并两个数组
	 *
	 * @param string key 合并数组的参照值
	 * @param array $array1 要合并数组
	 * @param array $array2 要合并数组
	 * @return array 返回合并的数组
	 */
	public static function filterArrayWithKey($key, array $array1, array $array2)
	{
		if (! $key || ! $array1 || ! $array2) {
			return [];
		}
		$array1 = static::rebuildArrayWithKey ( $key, $array1 );
		$array2 = static::rebuildArrayWithKey ( $key, $array2 );
		$tmp = [];
		foreach ( $array1 as $key => $array ) {
			if (isset ( $array2 [$key] )) {
				$tmp [$key] = array_merge ( $array, $array2 [$key] );
			}
		}
		return $tmp;
	}

	/**
	 * 按指定KEY重新生成数组
	 *
	 * @param string key 重新生成数组的参照值
	 * @param array $array 要重新生成的数组
	 * @return array 返回重新生成后的数组
	 */
	public static function rebuildArrayWithKey($key, array $array)
	{
		if (! $key || ! $array) {
			return [];
		}
		$tmp = [];
		foreach ( $array as $_array ) {
			if (isset ( $_array [$key] )) {
				$tmp [$_array [$key]] = $_array;
			}
		}
		return $tmp;
	}
}