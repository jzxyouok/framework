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

interface UrlRuleInterface
{

	/**
	 * 根据路由和参数解析URL地址
	 *
	 * @param UrlManager $manager the URL manager
	 * @param Request $request the request component
	 * @return array boolean parsing result. The route and the parameters are returned as an array.
	 *         If false, it means this rule cannot be used to parse this path info.
	 */
	public function parseRequest($manager, $request);

	/**
	 * 根据路由和参数创建URL地址
	 *
	 * @param UrlManager $manager the URL manager
	 * @param string $route the route. It should not have slashes at the beginning or the end.
	 * @param array $params the parameters
	 * @return string boolean created URL, or false if this rule cannot be used for creating this URL.
	*/
	public function createUrl($manager, $route, $params);
}