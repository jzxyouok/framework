<?php
/*
 +------------------------------------------------------------------------+
 | Leaps Framework                                                        |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)           |
 +------------------------------------------------------------------------+
 | This source file is subject to the Apache License that is bundled      |
 | with this package in the file docs/LICENSE.txt.                        |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@tintsoft.com so we can send you a copy immediately.         |
 +------------------------------------------------------------------------+
 | Authors: XuTongle <xutongle@gmail.com>                                 |
 +------------------------------------------------------------------------+
 */

namespace Leaps\Router;

use Leaps\Base;

class UrlRule extends Base  implements UrlRuleInterface
{

	/**
	 * 设置 [[mode]] 这个值标记,这条规则只用于URL解析
	 */
	const PARSING_ONLY = 1;

	/**
	 * 设置 [[mode]] 这个值标记,这个规则仅供URL创建
	 */
	const CREATION_ONLY = 2;

	/**
	 * 这条规则的名称。如果没有设置,它将使用 [[pattern]] 作为名称
	 *
	 * @var string
	 */
	public $name;

	/**
	 * 用于解析和创建模式的路径信息的URL的一部分。
	 *
	 * @var string
	 * @see host
	 */
	public $pattern;

	/**
	 * 模式用于解析和创建主机信息的URL的一部分。
	 *
	 * @var string
	 * @see pattern
	 */
	public $host;

	/**
	 *
	 * @var string the route to the controller action
	 */
	public $route;

	/**
	 *
	 * @var array the default GET parameters (name => value) that this rule
	 *      provides. When this rule
	 *      is used to parse the incoming request, the values declared in this
	 *      property will be
	 *      injected into $_GET.
	 */
	public $defaults = [];

	/**
	 *
	 * @var string the URL suffix used for this rule. For example, ".html" can
	 *      be used so that the
	 *      URL looks like pointing to a static HTML page. If not, the value of
	 *      [[UrlManager::suffix]] will be used.
	*/
	public $suffix;

	/**
	 *
	 * @var string array HTTP verb (e.g. GET, POST, DELETE) that this rule
	 *      should match. Use array
	 *      to represent multiple verbs that this rule may match. If this
	 *      property is not set, the
	 *      rule can match any verb. Note that this property is only used when
	 *      parsing a request. It
	 *      is ignored for URL creation.
	 */
	public $verb;

	/**
	 *
	 * @var integer a value indicating if this rule should be used for both
	 *      request parsing and URL
	 *      creation, parsing only, or creation only. If not set or 0, it means
	 *      the rule is both
	 *      request parsing and URL creation. If it is [[PARSING_ONLY]], the
	 *      rule is for request
	 *      parsing only. If it is [[CREATION_ONLY]], the rule is for URL
	 *      creation only.
	 */
	public $mode;

	/**
	 *
	 * @var string the template for generating a new URL. This is derived from
	 *      [[pattern]] and is
	 *      used in generating URL.
	 */
	private $_template;
	/**
	 *
	 * @var string the regex for matching the route part. This is used in
	 *      generating URL.
	 */
	private $_routeRule;
	/**
	 *
	 * @var array list of regex for matching parameters. This is used in
	 *      generating URL.
	 */
	private $_paramRules = [];
	/**
	 *
	 * @var array list of parameters used in the route.
	*/
	private $_routeParams = [];


	/**
	 * 初始化这个规则
	 */
	public function init()
	{
		if ($this->pattern === null) {
			throw new \Exception ( 'UrlRule::pattern must be set.' );
		}
		if ($this->route === null) {
			throw new \Exception ( 'UrlRule::route must be set.' );
		}
		if ($this->verb !== null) {
			if (is_array ( $this->verb )) {
				foreach ( $this->verb as $i => $verb ) {
					$this->verb [$i] = strtoupper ( $verb );
				}
			} else {
				$this->verb = [strtoupper ( $this->verb ) ];
			}
		}
		if ($this->name === null) {
			$this->name = $this->pattern;
		}

		$this->pattern = trim ( $this->pattern, '/' );

		if ($this->host !== null) {
			$this->pattern = rtrim ( $this->host, '/' ) . rtrim ( '/' . $this->pattern, '/' ) . '/';
		} elseif ($this->pattern === '') {
			$this->_template = '';
			$this->pattern = '#^$#u';
			return;
		} else {
			$this->pattern = '/' . $this->pattern . '/';
		}

		$this->route = trim ( $this->route, '/' );
		if (strpos ( $this->route, '<' ) !== false && preg_match_all ( '/<(\w+)>/', $this->route, $matches )) {
			foreach ( $matches [1] as $name ) {
				$this->_routeParams [$name] = "<$name>";
			}
		}

		$tr = [ '.' => '\\.','*' => '\\*','$' => '\\$','[' => '\\[',']' => '\\]','(' => '\\(',')' => '\\)' ];
		$tr2 = [];
		if (preg_match_all ( '/<(\w+):?([^>]+)?>/', $this->pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER )) {
			foreach ( $matches as $match ) {
				$name = $match [1] [0];
				$pattern = isset ( $match [2] [0] ) ? $match [2] [0] : '[^\/]+';
				if (isset ( $this->defaults [$name] )) {
					$length = strlen ( $match [0] [0] );
					$offset = $match [0] [1];
					if ($offset > 1 && $this->pattern [$offset - 1] === '/' && $this->pattern [$offset + $length] === '/') {
						$tr ["/<$name>"] = "(/(?P<$name>$pattern))?";
					} else {
						$tr ["<$name>"] = "(?P<$name>$pattern)?";
					}
				} else {
					$tr ["<$name>"] = "(?P<$name>$pattern)";
				}
				if (isset ( $this->_routeParams [$name] )) {
					$tr2 ["<$name>"] = "(?P<$name>$pattern)";
				} else {
					$this->_paramRules [$name] = $pattern === '[^\/]+' ? '' : "#^$pattern$#";
				}
			}
		}

		$this->_template = preg_replace ( '/<(\w+):?([^>]+)?>/', '<$1>', $this->pattern );
		$this->pattern = '#^' . trim ( strtr ( $this->_template, $tr ), '/' ) . '$#u';

		if (! empty ( $this->_routeParams )) {
			$this->_routeRule = '#^' . strtr ( $this->route, $tr2 ) . '$#u';
		}
	}

	/**
	 * 解析给定的请求并返回相应的路由和参数。
	 *
	 * @param UrlManager $manager the URL manager
	 * @param Request $request the request component
	 * @return array boolean parsing result. The route and the parameters are
	 *         returned as an array.
	 *         If false, it means this rule cannot be used to parse this path
	 *         info.
	 */
	public function parseRequest($manager, $request)
	{
		if ($this->mode === self::CREATION_ONLY) {
			return false;
		}

		if ($this->verb !== null && ! in_array ( $request->getMethod (), $this->verb, true )) {
			return false;
		}

		$pathInfo = $request->getPathInfo ();
		$suffix = ( string ) ($this->suffix === null ? $manager->suffix : $this->suffix);
		if ($suffix !== '' && $pathInfo !== '') {
			$n = strlen ( $suffix );
			if (substr ( $pathInfo, - $n ) === $suffix) {
				$pathInfo = substr ( $pathInfo, 0, - $n );
				if ($pathInfo === '') {
					// suffix alone is not allowed
					return false;
				}
			} else {
				return false;
			}
		}

		if ($this->host !== null) {
			$pathInfo = strtolower ( $request->getHostInfo () ) . ($pathInfo === '' ? '' : '/' . $pathInfo);
		}

		if (! preg_match ( $this->pattern, $pathInfo, $matches )) {
			return false;
		}
		foreach ( $this->defaults as $name => $value ) {
			if (! isset ( $matches [$name] ) || $matches [$name] === '') {
				$matches [$name] = $value;
			}
		}
		$params = $this->defaults;
		$tr = [];
		foreach ( $matches as $name => $value ) {
			if (isset ( $this->_routeParams [$name] )) {
				$tr [$this->_routeParams [$name]] = $value;
				unset ( $params [$name] );
			} elseif (isset ( $this->_paramRules [$name] )) {
				$params [$name] = $value;
			}
		}
		if ($this->_routeRule !== null) {
			$route = strtr ( $this->route, $tr );
		} else {
			$route = $this->route;
		}
		return [ $route,$params ];
	}

	/**
	 * 根据给定的路由创建一个URL和参数。
	 *
	 * @param UrlManager $manager the URL manager
	 * @param string $route the route. It should not have slashes at the beginning or the
	 *        end.
	 * @param array $params the parameters
	 * @return string boolean created URL, or false if this rule cannot be used
	 *         for creating this
	 *         URL.
	 */
	public function createUrl($manager, $route, $params)
	{
		if ($this->mode === self::PARSING_ONLY) {
			return false;
		}

		$tr = [];

		// match the route part first
		if ($route !== $this->route) {
			if ($this->_routeRule !== null && preg_match ( $this->_routeRule, $route, $matches )) {
				foreach ( $this->_routeParams as $name => $token ) {
					if (isset ( $this->defaults [$name] ) && strcmp ( $this->defaults [$name], $matches [$name] ) === 0) {
						$tr [$token] = '';
					} else {
						$tr [$token] = $matches [$name];
					}
				}
			} else {
				return false;
			}
		}

		// match default params
		// if a default param is not in the route pattern, its value must also
		// be matched
		foreach ( $this->defaults as $name => $value ) {
			if (isset ( $this->_routeParams [$name] )) {
				continue;
			}
			if (! isset ( $params [$name] )) {
				return false;
			} elseif (strcmp ( $params [$name], $value ) === 0) { // strcmp will do
				// string conversion
				// automatically
				unset ( $params [$name] );
				if (isset ( $this->_paramRules [$name] )) {
					$tr ["<$name>"] = '';
				}
			} elseif (! isset ( $this->_paramRules [$name] )) {
				return false;
			}
		}

		// match params in the pattern
		foreach ( $this->_paramRules as $name => $rule ) {
			if (isset ( $params [$name] ) && ! is_array ( $params [$name] ) && ($rule === '' || preg_match ( $rule, $params [$name] ))) {
				$tr ["<$name>"] = urlencode ( $params [$name] );
				unset ( $params [$name] );
			} elseif (! isset ( $this->defaults [$name] ) || isset ( $params [$name] )) {
				return false;
			}
		}

		$url = trim ( strtr ( $this->_template, $tr ), '/' );
		if ($this->host !== null) {
			$pos = strpos ( $url, '/', 8 );
			if ($pos !== false) {
				$url = substr ( $url, 0, $pos ) . preg_replace ( '#/+#', '/', substr ( $url, $pos ) );
			}
		} elseif (strpos ( $url, '//' ) !== false) {
			$url = preg_replace ( '#/+#', '/', $url );
		}

		if ($url !== '') {
			$url .= ($this->suffix === null ? $manager->suffix : $this->suffix);
		}

		if (! empty ( $params )) {
			$url .= '?' . http_build_query ( $params );
		}
		return $url;
	}
}