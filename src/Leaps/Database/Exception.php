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
namespace Laravel\Database;

class Exception extends \Exception {

	/**
	 * The inner exception.
	 *
	 * @var Exception
	 */
	protected $inner;

	/**
	 * Create a new database exception instance.
	 *
	 * @param  string     $sql
	 * @param  array      $bindings
	 * @param  Exception  $inner
	 * @return void
	 */
	public function __construct($sql, $bindings, \Exception $inner)
	{
		$this->inner = $inner;

		$this->setMessage($sql, $bindings);

		// Set the exception code
		$this->code = $inner->getCode();
	}

	/**
	 * Get the inner exception.
	 *
	 * @return Exception
	 */
	public function getInner()
	{
		return $this->inner;
	}

	/**
	 * Set the exception message to include the SQL and bindings.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return void
	 */
	protected function setMessage($sql, $bindings)
	{
		$this->message = $this->inner->getMessage();

		$this->message .= "\n\nSQL: ".$sql."\n\nBindings: ".var_export($bindings, true);
	}

}