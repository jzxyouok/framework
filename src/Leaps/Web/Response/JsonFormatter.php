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

use Leaps;
use yii\base\Component;
use yii\helpers\Json;

class JsonFormatter extends Component implements ResponseFormatterInterface
{
	/**
	 * @var boolean whether to use JSONP response format. When this is true, the [[Response::data|response data]]
	 * must be an array consisting of `data` and `callback` members. The latter should be a JavaScript
	 * function name while the former will be passed to this function as a parameter.
	 */
	public $useJsonp = false;


	/**
	 * Formats the specified response.
	 * @param Response $response the response to be formatted.
	 */
	public function format($response)
	{
		if ($this->useJsonp) {
			$this->formatJsonp($response);
		} else {
			$this->formatJson($response);
		}
	}

	/**
	 * Formats response data in JSON format.
	 * @param Response $response
	 */
	protected function formatJson($response)
	{
		$response->getHeaders()->set('Content-Type', 'application/json; charset=UTF-8');
		$response->content = Json::encode($response->data);
	}

	/**
	 * Formats response data in JSONP format.
	 * @param Response $response
	 */
	protected function formatJsonp($response)
	{
		$response->getHeaders()->set('Content-Type', 'application/javascript; charset=UTF-8');
		if (is_array($response->data) && isset($response->data['data'], $response->data['callback'])) {
			$response->content = sprintf('%s(%s);', $response->data['callback'], Json::encode($response->data['data']));
		} else {
			$response->content = '';
			Yii::warning("The 'jsonp' response requires that the data be an array consisting of both 'data' and 'callback' elements.", __METHOD__);
		}
	}
}
