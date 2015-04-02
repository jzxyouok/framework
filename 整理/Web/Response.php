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
namespace Leaps\Web;

use Leaps\Kernel;
use Leaps\InvalidConfigException;
use Leaps\InvalidParamException;
use Leaps\Web\Response\CookieCollection;
use Leaps\Web\Response\HeaderCollection;

class Response extends \Leaps\Response
{

	/**
	 * 设置Header集合
	 *
	 * @param Leaps\Web\Response\CookieCollection headers
	 * @return Leaps\Web\Response\CookieCollection
	 */
	public function setHeaders(HeaderCollection $headers)
	{
		$this->_headers = $headers;
		return $this;
	}

	/**
	 * 返回Header集合
	 *
	 * @return Leaps\Web\Response\CookieCollection
	 */
	public function getHeaders()
	{
		if ($this->_headers === null) {
			$this->_headers = new HeaderCollection ();
		}
		return $this->_headers;
	}

	/**
	 * 设置一个响应头
	 *
	 * <code>
	 * $response->setHeader("Content-Type", "text/plain");
	 * </code>
	 *
	 * @param string name
	 * @param string value
	 * @return Phalcon\Http\ResponseInterface
	 */
	public function setHeader($name, $value)
	{
		$headers = $this->getHeaders ();
		$headers->set ( $name, $value );
		return $this;
	}

	/**
	 * 重置Header集合
	 *
	 * @return Phalcon\Http\ResponseInterface
	 */
	public function resetHeaders()
	{
		$headers = $this->getHeaders ();
		$headers->removeAll ();
		return $this;
	}
	private $_cookies;

	/**
	 * 设置Cookie集合
	 *
	 * @param Leaps\Web\Response\CookieCollection cookies
	 * @return Leaps\Web\Response\CookieCollection
	 */
	public function setCookies(CookieCollection $cookies)
	{
		$this->_cookies = $cookies;
		return $this;
	}

	/**
	 * 返回Cookie集合
	 *
	 * @return Leaps\Web\Response\CookieCollection
	 */
	public function getCookies()
	{
		if ($this->_cookies === null) {
			$this->_cookies = new CookieCollection ();
		}
		return $this->_cookies;
	}

	/**
	 * 设置Cookie
	 *
	 * @param array $config
	 */
	public function setCookie($config = [])
	{
		$cookies = $this->getCookies ();
		$cookies->add ( new \Leaps\Web\Cookie ( $config ) );
		return $this;
	}





	/**
	 * 发送Cookie到客户端
	 */
	protected function sendCookies()
	{
	}


	/**
	 * 发送文件到浏览器
	 *
	 * Note that this method only prepares the response for file sending. The file is not sent
	 * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
	 *
	 * @param string $filePath the path of the file to be sent.
	 * @param string $attachmentName the file name shown to the user. If null, it will be determined from `$filePath`.
	 * @param array $options additional options for sending the file. The following options are supported:
	 *
	 *        - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
	 *        - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
	 *        meaning a download dialog will pop up.
	 *
	 * @return static the response object itself
	 */
	public function sendFile($filePath, $attachmentName = null, $options = [])
	{
		if (! isset ( $options ['mimeType'] )) {
			$options ['mimeType'] = FileHelper::getMimeTypeByExtension ( $filePath );
		}
		if ($attachmentName === null) {
			$attachmentName = basename ( $filePath );
		}
		$handle = fopen ( $filePath, 'rb' );
		$this->sendStreamAsFile ( $handle, $attachmentName, $options );

		return $this;
	}


}