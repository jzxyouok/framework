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
namespace Leaps\Utility;

class Str
{
	const RANDOM_ALNUM = 0;
	const RANDOM_ALPHA = 1;
	const RANDOM_HEXDEC = 2;
	const RANDOM_NUMERIC = 3;
	const RANDOM_NOZERO = 4;

	/**
	 * 生成UUID 单机使用
	 *
	 * @return string
	 */
	public static function uuid()
	{
		$charid = md5 ( uniqid ( mt_rand (), true ) );
		$hyphen = chr ( 45 ); // "-"
		$uuid = chr ( 123 ) . substr ( $charid, 0, 8 ) . $hyphen . substr ( $charid, 8, 4 ) . $hyphen . substr ( $charid, 12, 4 ) . $hyphen . substr ( $charid, 16, 4 ) . $hyphen . substr ( $charid, 20, 12 ) . chr ( 125 );
		return $uuid;
	}

	/**
	 * 生成流水号
	 */
	public static function createSn()
	{
		mt_srand ( ( double ) microtime () * 1000000 );
		return date ( "YmdHis" ) . str_pad ( mt_rand ( 1, 99999 ), 5, "0", STR_PAD_LEFT );
	}

	/**
	 * 生成Guid主键
	 *
	 * @return Boolean
	 */
	public static function keyGen()
	{
		return str_replace ( '-', '', substr ( static::uuid (), 1, - 1 ) );
	}

	/**
	 * 求取字符串长度
	 *
	 * @param string $string 要计算的字符串
	 * @return integer the number of bytes in the given string.
	 */
	public static function byteLength($string)
	{
		return mb_strlen ( $string, '8bit' );
	}

	/**
	 * Returns the portion of string specified by the start and length parameters.
	 * This method ensures the string is treated as a byte array by using `mb_substr()`.
	 *
	 * @param string $string the input string. Must be one character or longer.
	 * @param integer $start the starting position
	 * @param integer $length the desired portion length. If not specified or `null`, there will be
	 *        no limit on length i.e. the output will be until the end of the string.
	 * @return string the extracted part of string, or FALSE on failure or an empty string.
	 * @see http://www.php.net/manual/en/function.substr.php
	 */
	public static function byteSubstr($string, $start, $length = null, $dot = "")
	{
		$length = $length === null ? mb_strlen ( $string, "8bit" ) : $length;
		return mb_substr ( $string, $start, $length, '8bit' ) . ! is_null ( $dot ) ? $dot : '';
	}

	/**
	 * Generates a random string based on the given type.
	 * Type is one of the RANDOM_* constants
	 *
	 * <code>
	 * echo Leaps\Str::random(Leaps\Str::RANDOM_ALNUM); //"aloiwkqz"
	 * </code>
	 *
	 * @param int type
	 * @param int length
	 * @return string
	 */
	public static function random($type = 0, $length = 8)
	{
		$str = "";
		switch ($type) {
			case Str::RANDOM_ALPHA :
				$pool = array_merge ( range ( "a", "z" ), range ( "A", "Z" ) );
				break;

			case Str::RANDOM_HEXDEC :
				$pool = array_merge ( range ( 0, 9 ), range ( "a", "f" ) );
				break;

			case Str::RANDOM_NUMERIC :
				$pool = range ( 0, 9 );
				break;

			case Str::RANDOM_NOZERO :
				$pool = range ( 1, 9 );
				break;

			default :
				// Default type \Leaps\Str::RANDOM_ALNUM
				$pool = array_merge ( range ( 0, 9 ), range ( "a", "z" ), range ( "A", "Z" ) );
				break;
		}

		$end = count ( $pool ) - 1;

		while ( strlen ( $str ) < $length ) {
			$str .= $pool [mt_rand ( 0, $end )];
		}

		return $str;
	}

	/**
	 * 获取一定范围内的随机数字 位数不足补零
	 *
	 * @param integer $min 最小值
	 * @param integer $max 最大值
	 * @return string
	 *
	 */
	public static function randNumber($min, $max)
	{
		return sprintf ( "%0" . strlen ( $max ) . "d", mt_rand ( $min, $max ) );
	}

	/**
	 * Check if a string starts with a given string
	 *
	 * <code>
	 * echo Leaps\Str::startsWith("Hello", "He"); // true
	 * echo Leaps\Str::startsWith("Hello", "he"); // false
	 * echo Leaps\Str::startsWith("Hello", "he", false); // true
	 * </code>
	 *
	 * @param string str
	 * @param string start
	 * @param boolean ignoreCase
	 * @return boolean
	 */
	public static function startsWith($str, $start, $ignoreCase = true)
	{
		return strpos ( $str, $start ) === 0;
	}

	/**
	 * 提取两个字符串之间的值，不包括分隔符
	 *
	 * @param string $string 待提取的只付出
	 * @param string $start 开始字符串
	 * @param string|null $end 结束字符串，省略将返回所有的。
	 * @return bool string substring between $start and $end or false if either string is not found
	 */
	public static function substrBetween($string, $start, $end = null)
	{
		if (($start_pos = strpos ( $string, $start )) !== false) {
			if ($end) {
				if (($end_pos = strpos ( $string, $end, $start_pos + strlen ( $start ) )) !== false) {
					return substr ( $string, $start_pos + strlen ( $start ), $end_pos - ($start_pos + strlen ( $start )) );
				}
			} else {
				return substr ( $string, $start_pos );
			}
		}
		return false;
	}
}