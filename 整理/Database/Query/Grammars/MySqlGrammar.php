<?php
namespace Leaps\Database\Query\Grammars;

class MySqlGrammar extends Grammar {

	/**
	 * 关键字标识符封装格式。
	 *
	 * @var string
	 */
	protected $wrapper = '`%s`';

}