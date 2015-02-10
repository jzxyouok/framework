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
namespace Leaps\Web\Response;

use Leaps\Kernel;
use Leaps\Di\Injectable;
use Leaps\Web\ResponseFormatterInterface;
use yii\helpers\Json;

class JsonFormatter extends Injectable implements ResponseFormatterInterface
{
	/**
	 *
	 * @var boolean 是否是JSONP请求
	 */
	public $useJsonp = false;

	/**
	 * 格式化响应数据
	 *
	 * @param Response $response the response to be formatted.
	 */
	public function format($response)
	{
		if ($this->useJsonp) {
			$this->formatJsonp ( $response );
		} else {
			$this->formatJson ( $response );
		}
	}

	/**
	 * 格式化成JSON格式
	 *
	 * @param Response $response
	 */
	protected function formatJson($response)
	{
		$response->getHeaders ()->set ( 'Content-Type', 'application/json; charset=UTF-8' );
		$response->content = Json::encode ( $response->data );
	}

	/**
	 * 格式化成JSONP格式
	 *
	 * @param Response $response
	 */
	protected function formatJsonp($response)
	{
		$response->getHeaders ()->set ( 'Content-Type', 'application/javascript; charset=UTF-8' );
		if (is_array ( $response->data ) && isset ( $response->data ['data'], $response->data ['callback'] )) {
			$response->content = sprintf ( '%s(%s);', $response->data ['callback'], Json::encode ( $response->data ['data'] ) );
		} else {
			$response->content = '';
			Kernel::warning ( "The 'jsonp' response requires that the data be an array consisting of both 'data' and 'callback' elements.", __METHOD__ );
		}
	}
}
