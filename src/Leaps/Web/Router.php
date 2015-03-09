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
use Leaps\Base;

class Router extends Base
{

	/**
	 * 启用漂亮的URL解析
	 *
	 * @var bool
	 */
	public $enablePrettyUrl = false;

	/**
	 * 是否启用严格解析。如果启用了严格的解析,传入的请求的URL必须匹配的至少一个[[rules]]为了被视为有效请求。
	 * 否则,路径信息请求将被视为所请求的一部分路由。这个属性只在[[urlFormat]]是path。
	 *
	 * @var boolean
	 */
	public $enableStrictParsing = false;

	/**
	 * 启用URLRule缓存
	 *
	 * @var bool
	 */
	public $enableRuleCache = true;

	/**
	 * 路由规则
	 *
	 * @var array
	 */
	public $rules = [ ];

	/**
	 * 自定义URL后缀
	 *
	 * @var string
	 */
	public $suffix = '';

	public $cache = 'cache';

	/**
	 * 是否显示脚本名称
	 *
	 * @var boolean
	 */
	public $showScriptName = false;
	public $ruleConfig = [ 'className' => 'Leaps\Web\UrlRule' ];
	public $routeParam = 'r';
	private $_baseUrl;
	private $_hostInfo;
	private $request;


	/**
	 * (non-PHPdoc)
	 *
	 * @see \Leaps\Base::init()
	 */
	public function init()
	{
		parent::init ();
		if (! $this->enablePrettyUrl || empty ( $this->rules )) {
			return;
		}
		if (is_string($this->cache)) {
			$this->cache = Kernel::$app->get('cache');
		}
		if ($this->enableRuleCache) {
			$cacheKey = __CLASS__;
			$hash = md5 ( json_encode ( $this->rules ) );
			if (($data = $this->cache->get ( $cacheKey )) !== false && isset ( $data [1] ) && $data [1] === $hash) {
				$this->rules = $data [0];
			} else {
				$this->rules = $this->buildRules ( $this->rules );
				$this->cache->set ( $cacheKey, [ $this->rules,$hash ], 'router' );
			}
		} else {
			$this->rules = $this->buildRules ( $this->rules );
		}
	}

	/**
	 * 增加额外的URLRule
	 *
	 * 该方法调用 [[buildRules()]]解析规则并添加到现有的[[rules]].
	 *
	 * 如果没有启用漂亮的URL，该方法将什么都不做
	 *
	 * @param array $rules 规则
	 * @param boolean $append 添加到现有规则
	 */
	public function addRules($rules, $append = true)
	{
		if (! $this->enablePrettyUrl) {
			return;
		}
		$rules = $this->buildRules ( $rules );
		if ($append) {
			$this->rules = array_merge ( $this->rules, $rules );
		} else {
			$this->rules = array_merge ( $rules, $this->rules );
		}
	}

	/**
	 * 从Rule配置建立规则实例
	 *
	 * @param array $rules 规则
	 * @return UrlRuleInterface[] 返回规则对象实例
	 * @throws InvalidConfigException 如果Url规则不合法
	 */
	protected function buildRules($rules)
	{
		$compiledRules = [ ];
		$verbs = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';
		foreach ( $rules as $key => $rule ) {
			if (is_string ( $rule )) {
				$rule = [ 'route' => $rule ];
				if (preg_match ( "/^((?:(".$verbs."),)*(".$verbs."))\\s+(.*)$/", $key, $matches )) {
					$rule ['verb'] = explode ( ',', $matches [1] );
					$rule ['mode'] = UrlRule::PARSING_ONLY;
					$key = $matches [4];
				}
				$rule ['pattern'] = $key;
			}
			if (is_array ( $rule )) {
				$rule = Kernel::createObject ( array_merge ( $this->ruleConfig, $rule ) );
			}
			if (! $rule instanceof UrlRuleInterface) {
				throw new \Exception ( 'URL rule class must implement UrlRuleInterface.' );
			}
			$compiledRules [] = $rule;
		}
		return $compiledRules;
	}

	/**
	 * 解析请求
	 *
	 * @param unknown $request
	 * @return unknown|boolean|multitype:multitype: string |multitype:string multitype:
	 */
	public function parseRequest($request)
	{
		if ($this->enablePrettyUrl) {
			$pathInfo = $request->getPathInfo ();
			/* @var $rule UrlRule */
			foreach ( $this->rules as $rule ) {
				if (($result = $rule->parseRequest ( $this, $request )) !== false) {
					return $result;
				}
			}

			if ($this->enableStrictParsing) {
				return false;
			}

			$suffix = ( string ) $this->suffix;
			if ($suffix !== '' && $pathInfo !== '') {
				$n = strlen ( $this->suffix );
				if (substr_compare ( $pathInfo, $this->suffix, - $n, $n ) === 0) {
					$pathInfo = substr ( $pathInfo, 0, - $n );
					if ($pathInfo === '') {
						// suffix alone is not allowed
						return false;
					}
				} else {
					// suffix doesn't match
					return false;
				}
			}

			return [ $pathInfo,[ ] ];
		} else {
			$route = $request->getQueryParam ( $this->routeParam, '' );
			if (is_array ( $route )) {
				$route = '';
			}
			return [ ( string ) $route,[ ] ];
		}
	}

	/**
	 * 创建一个相对URL。 使用 [[createAbsoluteUrl()]] 创建绝对URL
	 *
	 * @param string $route the route
	 * @param array $params the parameters (name-value pairs)
	 * @return string the created URL
	 */
	public function createUrl($params = array())
	{
		$params = ( array ) $params;
		$anchor = isset ( $params ['#'] ) ? '#' . $params ['#'] : '';
		unset ( $params ['#'] );

		$route = trim ( $params [0], '/' );
		unset ( $params [0] );
		$baseUrl = $this->getBaseUrl ();
		if ($this->enablePrettyUrl) {
			/**
			 *
			 * @var UrlRule $rule
			 */
			foreach ( $this->rules as $rule ) {
				if (($url = $rule->createUrl ( $this, $route, $params )) !== false) {
					if ($rule->host !== null) {
						if ($baseUrl !== '' && ($pos = strpos ( $url, '/', 8 )) !== false) {
							return substr ( $url, 0, $pos ) . $baseUrl . substr ( $url, $pos );
						} else {
							return $url . $baseUrl . $anchor;
						}
					} else {
						return "$baseUrl/{$url}{$anchor}";
					}
				}
			}

			if ($this->suffix !== null) {
				$route .= $this->suffix;
			}
			if (! empty ( $params )) {
				$route .= '?' . http_build_query ( $params );
			}
			return "$baseUrl/{$route}{$anchor}";
		} else {
			$url = "$baseUrl?{$this->routeParam}=" . urlencode ( $route );
			if (! empty ( $params ) && ($query = http_build_query ( $params )) !== '') {
				$url .= '&' . $query;
			}
			return $url . $anchor;
		}
	}

	/**
	 * 创建绝对URL This method prepends the URL created by [[createUrl()]] with the
	 * [[hostInfo]].
	 *
	 * @param string $route the route
	 * @param array $params the parameters (name-value pairs)
	 * @return string the created URL
	 * @see createUrl()
	 */
	public function createAbsoluteUrl($params, $schema = null)
	{
		$params = ( array ) $params;
		$url = $this->createUrl ( $params );
		if (strpos ( $url, '://' ) === false) {
			$url = $this->getHostInfo ( $schema ) . $url;
		}
		if ($schema !== null && ($pos = strpos ( $url, '://' )) !== false) {
			$url = $schema . substr ( $url, $pos );
		}
		return $url;
	}

	/**
	 * Returns the base URL that is used by [[createUrl()]] to prepend URLs it
	 * creates.
	 * It defaults
	 * to [[Request::scriptUrl]] if [[showScriptName]] is true or
	 * [[enablePrettyUrl]] is false;
	 * otherwise, it defaults to [[Request::baseUrl]].
	 *
	 * @return string the base URL that is used by [[createUrl()]] to prepend
	 *         URLs it creates.
	 *
	 */
	public function getBaseUrl()
	{
		if ($this->_baseUrl === null) {
			$this->_baseUrl = $this->showScriptName || ! $this->enablePrettyUrl ? $this->request->getScriptUrl () : $this->request->getBaseUrl ();
		}
		return $this->_baseUrl;
	}

	/**
	 * Sets the base URL that is used by [[createUrl()]] to prepend URLs it
	 * creates.
	 *
	 * @param string $value the base URL that is used by [[createUrl()]] to
	 *        prepend URLs
	 *        it creates.
	 */
	public function setBaseUrl($value)
	{
		$this->_baseUrl = rtrim ( $value, '/' );
	}

	/**
	 * Returns the host info that is used by [[createAbsoluteUrl()]] to prepend
	 * URLs it creates.
	 *
	 * @return string the host info (e.g. "http://www.example.com") that is used
	 *         by
	 *         [[createAbsoluteUrl()]] to prepend URLs it creates.
	 *
	 */
	public function getHostInfo()
	{
		if ($this->_hostInfo === null) {
			$this->_hostInfo = $this->request->getHostInfo ();
		}
		return $this->_hostInfo;
	}

	/**
	 * Sets the host info that is used by [[createAbsoluteUrl()]] to prepend
	 * URLs it creates.
	 *
	 * @param string $value the host info (e.g. "http://www.example.com") that
	 *        is used by
	 *        [[createAbsoluteUrl()]] to prepend URLs it creates.
	 */
	public function setHostInfo($value)
	{
		$this->_hostInfo = rtrim ( $value, '/' );
	}
}