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

use Leaps\Di\Injectable;
use Leaps\Web\ResponseFormatterInterface;

class HtmlFormatter extends Injectable implements ResponseFormatterInterface
{
	/**
	 *
	 * @var string the Content-Type header for the response
	 */
	public $contentType = 'text/html';

	/**
	 * 格式化指定的响应
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
