<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2013 TintSoft LLC
 * @license http://www.tintsoft.com/license/
 */
namespace Leaps\Db;
use Leaps;
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class StaleObjectException extends Exception
{
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return Leaps::t('leaps', 'Stale Object Exception');
	}
}
