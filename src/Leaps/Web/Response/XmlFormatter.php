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

use DOMDocument;
use DOMElement;
use DOMText;
use Leaps\Arrayable;
use Leaps\Di\Injectable;
use Leaps\Web\ResponseFormatterInterface;
use yii\helpers\StringHelper;

class XmlResponseFormatter extends Injectable implements ResponseFormatterInterface
{
	/**
	 * @var string the Content-Type header for the response
	 */
	public $contentType = 'application/xml';
	/**
	 * @var string the XML version
	 */
	public $version = '1.0';
	/**
	 * @var string the XML encoding. If not set, it will use the value of [[Response::charset]].
	 */
	public $encoding;
	/**
	 * @var string the name of the root element.
	 */
	public $rootTag = 'response';
	/**
	 * @var string the name of the elements that represent the array elements with numeric keys.
	 */
	public $itemTag = 'item';


	/**
	 * Formats the specified response.
	 * @param Response $response the response to be formatted.
	 */
	public function format($response)
	{
		$charset = $this->encoding === null ? $response->charset : $this->encoding;
		if (stripos($this->contentType, 'charset') === false) {
			$this->contentType .= '; charset=' . $charset;
		}
		$response->getHeaders()->set('Content-Type', $this->contentType);
		$dom = new DOMDocument($this->version, $charset);
		$root = new DOMElement($this->rootTag);
		$dom->appendChild($root);
		$this->buildXml($root, $response->data);
		$response->content = $dom->saveXML();
	}

	/**
	 * @param DOMElement $element
	 * @param mixed $data
	 */
	protected function buildXml($element, $data)
	{
		if (is_object($data)) {
			$child = new DOMElement(StringHelper::basename(get_class($data)));
			$element->appendChild($child);
			if ($data instanceof Arrayable) {
				$this->buildXml($child, $data->toArray());
			} else {
				$array = [];
				foreach ($data as $name => $value) {
					$array[$name] = $value;
				}
				$this->buildXml($child, $array);
			}
		} elseif (is_array($data)) {
			foreach ($data as $name => $value) {
				if (is_int($name) && is_object($value)) {
					$this->buildXml($element, $value);
				} elseif (is_array($value) || is_object($value)) {
					$child = new DOMElement(is_int($name) ? $this->itemTag : $name);
					$element->appendChild($child);
					$this->buildXml($child, $value);
				} else {
					$child = new DOMElement(is_int($name) ? $this->itemTag : $name);
					$element->appendChild($child);
					$child->appendChild(new DOMText((string) $value));
				}
			}
		} else {
			$element->appendChild(new DOMText((string) $data));
		}
	}
}
