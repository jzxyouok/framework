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
namespace Leaps\Database\Eloquent;

class Pivot extends Model
{

	/**
	 * The name of the pivot table's table.
	 *
	 * @var string
	 */
	protected $pivot_table;

	/**
	 * The database connection used for this model.
	 *
	 * @var Laravel\Database\Connection
	 */
	protected $pivot_connection;

	/**
	 * Indicates if the model has update and creation timestamps.
	 *
	 * @var bool
	 */
	public static $timestamps = true;

	/**
	 * Create a new pivot table instance.
	 *
	 * @param string $table
	 * @param string $connection
	 * @return void
	 */
	public function __construct($table, $connection = null)
	{
		$this->pivot_table = $table;
		$this->pivot_connection = $connection;

		parent::__construct ( [ ], true );
	}

	/**
	 * Get the name of the pivot table.
	 *
	 * @return string
	 */
	public function table()
	{
		return $this->pivot_table;
	}

	/**
	 * Get the connection used by the pivot table.
	 *
	 * @return string
	 */
	public function connection()
	{
		return $this->pivot_connection;
	}
}