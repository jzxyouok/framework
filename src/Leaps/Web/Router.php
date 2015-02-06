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

use Leaps\Di\Injectable;

class Router extends Injectable
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
	public $rules = [];

	/**
	 * 自定义URL后缀
	 *
	 * @var string
	*/
	public $suffix = '';

	/**
	 * 是否显示脚本名称
	 *
	 * @var boolean
	 */
	public $showScriptName = false;

	public $ruleConfig = ['className' => 'Leaps\Web\UrlRule'];

	private $_baseUrl;
	private $_hostInfo;
	private $request;
}