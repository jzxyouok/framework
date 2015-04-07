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
namespace Laravel\Database\Query\Grammars;

use Laravel\Database\Query;

class Postgres extends Grammar {

	/**
	 * Compile a SQL INSERT and get ID statement from a Query instance.
	 *
	 * @param  Query   $query
	 * @param  array   $values
	 * @param  string  $column
	 * @return string
	 */
	public function insert_get_id(Query $query, $values, $column)
	{
		return $this->insert($query, $values)." RETURNING $column";
	}

}