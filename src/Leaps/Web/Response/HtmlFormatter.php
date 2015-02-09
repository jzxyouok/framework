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

use yii\base\Component;

class HtmlResponseFormatter extends Component implements ResponseFormatterInterface
{
	/**
	 *
	 * @var string the Content-Type header for the response
	 */
	public $contentType = 'text/html';

	/**
	 * Formats the specified response.
	 *
	 * @param Response $response the response to be formatted.
	 */
	public function format($response)
	{
		if (stripos ( $this->contentType, 'charset' ) === false) {
			$this->contentType .= '; charset=' . $response->charset;
		}
		$response->getHeaders ()->set ( 'Content-Type', $this->contentType );
		$response->content = $response->data;
	}
}
