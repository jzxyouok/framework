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

class Controller extends \Leaps\Controller
{

	/**
	 * 跳转到主页
	 *
	 * You can use this method in an action by returning the [[Response]] directly:
	 *
	 * ```php
	 * // stop executing this action and redirect to home page
	 * return $this->goHome();
	 * ```
	 *
	 * @return Response the current response object
	 */
	public function goHome()
	{
		return Kernel::$app->getResponse ()->redirect ( Kernel::$app->getHomeUrl () );
	}

	/**
	 * 返回上一页
	 *
	 * You can use this method in an action by returning the [[Response]] directly:
	 *
	 * ```php
	 * // stop executing this action and redirect to last visited page
	 * return $this->goBack();
	 * ```
	 *
	 * For this function to work you have to [[User::setReturnUrl()|set the return URL]] in appropriate places before.
	 *
	 * @param string|array $defaultUrl the default return URL in case it was not set previously.
	 *        If this is null and the return URL was not set previously, [[Application::homeUrl]] will be redirected to.
	 *        Please refer to [[User::setReturnUrl()]] on accepted format of the URL.
	 * @return Response the current response object
	 * @see User::getReturnUrl()
	 */
	public function goBack($defaultUrl = null)
	{
		return Kernel::$app->getResponse ()->redirect ( Kernel::$app->getUser ()->getReturnUrl ( $defaultUrl ) );
	}

	/**
	 * 刷新当前页面
	 * This method is a shortcut to [[Response::refresh()]].
	 *
	 * You can use it in an action by returning the [[Response]] directly:
	 *
	 * ```php
	 * // stop executing this action and refresh the current page
	 * return $this->refresh();
	 * ```
	 *
	 * @param string $anchor the anchor that should be appended to the redirection URL.
	 *        Defaults to empty. Make sure the anchor starts with '#' if you want to specify it.
	 * @return Response the response object itself
	 */
	public function refresh($anchor = '')
	{
		return Kernel::$app->getResponse ()->redirect ( Kernel::$app->getRequest ()->getUrl () . $anchor );
	}
}