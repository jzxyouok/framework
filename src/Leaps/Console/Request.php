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
namespace Leaps\Console;

class Request implements \Leaps\Http\RequestInterface
{
	private $_params;

	/**
	 * 获取命令行参数
	 *
	 * @return array the command line arguments. It does not include the entry script name.
	 */
	public function getParams()
	{
		if (! isset ( $this->_params )) {
			if (isset ( $_SERVER ['argv'] )) {
				$this->_params = $_SERVER ['argv'];
				array_shift ( $this->_params );
			} else {
				$this->_params = [ ];
			}
		}
		return $this->_params;
	}

	/**
	 * 设置命令行参数
	 *
	 * @param array $params the command line arguments
	 */
	public function setParams($params)
	{
		$this->_params = $params;
	}

	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Http\RequestInterface::resolve()
	 */
	public function resolve()
	{
		$rawParams = $this->getParams ();
		if (isset ( $rawParams [0] )) {
			$route = $rawParams [0];
			array_shift ( $rawParams );
		} else {
			$route = '';
		}
		$params = [ ];
		foreach ( $rawParams as $param ) {
			if (preg_match ( '/^--(\w+)(=(.*))?$/', $param, $matches )) {
				$name = $matches [1];
				if ($name !== Application::OPTION_APPCONFIG) {
					$params [$name] = isset ( $matches [3] ) ? $matches [3] : true;
				}
			} else {
				$params [] = $param;
			}
		}
		return [
				$route,
				$params
		];
	}
}