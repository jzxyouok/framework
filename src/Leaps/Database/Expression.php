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

class Expression {

	/**
	 * The value of the database expression.
	 *
	 * @var string
	 */
	protected $value;

	/**
	 * Create a new database expression instance.
	 *
	 * @param  string  $value
	 * @return void
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * Get the string value of the database expression.
	 *
	 * @return string
	 */
	public function get()
	{
		return $this->value;
	}

	/**
	 * Get the string value of the database expression.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->get();
	}

}