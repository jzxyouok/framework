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

/**
 * Leaps\Version
 *
 * This class allows to get the installed version of the framework
 */
class Version
{
	/**
	 * The constant referencing the major version.
	 * Returns 0
	 * <code>
	 * echo Leaps\Version::getPart(Leaps\Version::VERSION_MAJOR);
	 * </code>
	 */
	const VERSION_MAJOR = 0;

	/**
	 * The constant referencing the major version.
	 * Returns 1
	 * <code>
	 * echo Leaps\Version::getPart(Leaps\Version::VERSION_MEDIUM);
	 * </code>
	 */
	const VERSION_MEDIUM = 1;

	/**
	 * The constant referencing the major version.
	 * Returns 2
	 * <code>
	 * echo Leaps\Version::getPart(Leaps\Version::VERSION_MINOR);
	 * </code>
	 */
	const VERSION_MINOR = 2;

	/**
	 * The constant referencing the major version.
	 * Returns 3
	 * <code>
	 * echo Leaps\Version::getPart(Leaps\Version::VERSION_SPECIAL);
	 * </code>
	 */
	const VERSION_SPECIAL = 3;

	/**
	 * The constant referencing the major version.
	 * Returns 4
	 * <code>
	 * echo Leaps\Version::getPart(Leaps\Version::VERSION_SPECIAL_NUMBER);
	 * </code>
	 */
	const VERSION_SPECIAL_NUMBER = 4;

	/**
	 * Area where the version number is set.
	 * The format is as follows:
	 * ABBCCDE
	 *
	 * A - Major version
	 * B - Med version (two digits)
	 * C - Min version (two digits)
	 * D - Special release: 1 = Alpha, 2 = Beta, 3 = RC, 4 = Stable
	 * E - Special release version i.e. RC1, Beta2 etc.
	 */
	protected static function _getVersion()
	{
		return [
				5,
				0,
				0,
				1,
				1
		];
	}

	/**
	 * Translates a number to a special release
	 *
	 * If Special release = 1 this function will return ALPHA
	 *
	 * @return string
	 */
	protected final static function _getSpecial($special)
	{
		$suffix = "";

		switch ($special) {
			case 1 :
				$suffix = "Alpha";
				break;
			case 2 :
				$suffix = "Beta";
				break;
			case 3 :
				$suffix = "Rc";
				break;
		}

		return $suffix;
	}

	/**
	 * Returns the active version (string)
	 *
	 * <code>
	 * echo Leaps\Version::get();
	 * </code>
	 *
	 * @return string
	 */
	public static function get()
	{
		$version = self::_getVersion ();

		$major = $version [self::VERSION_MAJOR];
		$medium = $version [self::VERSION_MEDIUM];
		$minor = $version [self::VERSION_MINOR];
		$special = $version [self::VERSION_SPECIAL];
		$specialNumber = $version [self::VERSION_SPECIAL_NUMBER];

		$result = $major . "." . $medium . "." . $minor . " ";
		$suffix = self::_getSpecial ( $special );

		if ($suffix != "") {
			$result .= $suffix . " " . $specialNumber;
		}

		return trim ( $result );
	}

	/**
	 * Returns the numeric active version
	 *
	 * <code>
	 * echo Leaps\Version::getId();
	 * </code>
	 *
	 * @return string
	 */
	public static function getId()
	{
		$version = self::_getVersion ();

		$major = $version [self::VERSION_MAJOR];
		$medium = $version [self::VERSION_MEDIUM];
		$minor = $version [self::VERSION_MINOR];
		$special = $version [self::VERSION_SPECIAL];
		$specialNumber = $version [self::VERSION_SPECIAL_NUMBER];

		return $major . sprintf ( "%02s", $medium ) . sprintf ( "%02s", $minor ) . $special . $specialNumber;
	}

	/**
	 * Returns a specific part of the version.
	 * If the wrong parameter is passed
	 * it will return the full version
	 *
	 * <code>
	 * echo Leaps\Version::getPart(Leaps\Version::VERSION_MAJOR);
	 * </code>
	 *
	 * @return string
	 */
	public static function getPart($part)
	{
		$version = self::_getVersion ();

		switch ($part) {

			case self::VERSION_MAJOR :
			case self::VERSION_MEDIUM :
			case self::VERSION_MINOR :
			case self::VERSION_SPECIAL_NUMBER :
				$result = $version [$part];
				break;

			case self::VERSION_SPECIAL :
				$result = self::_getSpecial ( $version [self::VERSION_SPECIAL] );
				break;

			default :
				$result = self::get ();
				break;
		}

		return $result;
	}
	public function makeA()
	{
		$row = new \stdClass ();
		$order = ! $row ? 0 : $row->order + 1;
		var_dump ( $order );
	}
}
