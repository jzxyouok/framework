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