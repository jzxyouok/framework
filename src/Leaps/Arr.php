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
}