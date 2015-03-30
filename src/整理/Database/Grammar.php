<?php
namespace Leaps\Database;

abstract class Grammar {

	/**
	 * 表前缀
	 *
	 * @var string
	 */
	protected $tablePrefix = '';

	/**
	 * 包装一个数组的值
	 *
	 * @param  array  $values
	 * @return array
	 */
	public function wrapArray(array $values)
	{
		return array_map(array($this, 'wrap'), $values);
	}

	/**
	 * 包装一个表在关键字标识符。
	 *
	 * @param  string  $table
	 * @return string
	 */
	public function wrapTable($table)
	{
		if ($this->isExpression($table)) return $this->getValue($table);
		return $this->wrap($this->tablePrefix.$table);
	}

	/**
	 * 包装一个值在关键字标识符。
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function wrap($value)
	{
		if ($this->isExpression($value)) return $this->getValue($value);
if (strpos(strtolower($value), ' as ') !== false)
		{
			$segments = explode(' ', $value);
			return $this->wrap($segments[0]).' as '.$this->wrap($segments[2]);
		}
		$wrapped = array();
		$segments = explode('.', $value);
		foreach ($segments as $key => $segment)
		{
			if ($key == 0 and count($segments) > 1)
			{
				$wrapped[] = $this->wrapTable($segment);
			}
			else
			{
				$wrapped[] = $this->wrapValue($segment);
			}
		}

		return implode('.', $wrapped);
	}

	/**
	 * 包装单一字符串在关键字标识符。
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function wrapValue($value)
	{
		return $value !== '*' ? sprintf($this->wrapper, $value) : $value;
	}

	/**
	 * 将一个数组的列的名称转换成一个分隔的字符串。
	 *
	 * @param  array   $columns
	 * @return string
	 */
	public function columnize(array $columns)
	{
		return implode(', ', array_map(array($this, 'wrap'), $columns));
	}

	/**
	 * Create query parameter place-holders for an array.
	 *
	 * @param  array   $values
	 * @return string
	 */
	public function parameterize(array $values)
	{
		return implode(', ', array_map(array($this, 'parameter'), $values));
	}

	/**
	 * Get the appropriate query parameter place-holder for a value.
	 *
	 * @param  mixed   $value
	 * @return string
	 */
	public function parameter($value)
	{
		return $this->isExpression($value) ? $this->getValue($value) : '?';
	}

	/**
	 * Get the value of a raw expression.
	 *
	 * @param  \Leaps\Database\Query\Expression  $expression
	 * @return string
	 */
	public function getValue($expression)
	{
		return $expression->getValue();
	}

	/**
	 * Determine if the given value is a raw expression.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	public function isExpression($value)
	{
		return $value instanceof Query\Expression;
	}

	/**
	 * Get the format for database stored dates.
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return 'Y-m-d H:i:s';
	}

	/**
	 * Get the grammar's table prefix.
	 *
	 * @return string
	 */
	public function getTablePrefix()
	{
		return $this->tablePrefix;
	}

	/**
	 * Set the grammar's table prefix.
	 *
	 * @param  string  $prefix
	 * @return \Leaps\Database\Grammar
	 */
	public function setTablePrefix($prefix)
	{
		$this->tablePrefix = $prefix;

		return $this;
	}

}