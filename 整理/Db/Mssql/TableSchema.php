<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2013 TintSoft LLC
 * @license http://www.tintsoft.com/license/
 */

namespace Leaps\Db\Mssql;

/**
 * TableSchema represents the metadata of a database table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class TableSchema extends \Leaps\Db\TableSchema
{
	/**
	 * @var string name of the catalog (database) that this table belongs to.
	 * Defaults to null, meaning no catalog (or the current database).
	 */
	public $catalogName;
}
