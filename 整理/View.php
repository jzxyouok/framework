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
namespace Leaps;

use Leaps\Kernel;
use Leaps\Di\Injectable;
use Leaps\Core\InvalidCallException;
use Leaps\Core\InvalidParamException;

class View extends Injectable
{
	/**
	 * 目前呈现的视图文件
	 * @var array
	 */
	private $_viewFiles = [];

	/**
	 * 渲染视图
	 *
	 * 视图可以是以下格式:
	 *
	 * - 路径别名 (例如： "@app/views/site/index");
	 * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
	 * The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
	 * - absolute path within current module (e.g. "/site/index"): the view name starts with a single slash.
	 * The actual view file will be looked for under the [[Module::viewPath|view path]] of the [[Controller::module|current module]].
	 * - relative view (e.g. "index"): the view name does not start with `@` or `/`. The corresponding view file will be
	 * looked for under the [[ViewContextInterface::getViewPath()|view path]] of the view `$context`.
	 * If `$context` is not given, it will be looked for under the directory containing the view currently
	 * being rendered (i.e., this happens when rendering a view within another view).
	 *
	 * @param string $view the view name.
	 * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @param object $context the context to be assigned to the view and can later be accessed via [[context]]
	 *        in the view. If the context implements [[ViewContextInterface]], it may also be used to locate
	 *        the view file corresponding to a relative view name.
	 * @return string the rendering result
	 * @throws InvalidParamException if the view cannot be resolved or the view file does not exist.
	 * @see renderFile()
	 */
	public function render($view, $params = [], $context = null)
	{
		$viewFile = $this->findViewFile ( $view, $context );
		return $this->renderFile ( $viewFile, $params, $context );
	}

	/**
	 * 查找视图文件
	 *
	 * @param string $view 视图名称或路径别名
	 * @param object $context the context to be assigned to the view and can later be accessed via [[context]]
	 *        in the view. If the context implements [[ViewContextInterface]], it may also be used to locate
	 *        the view file corresponding to a relative view name.
	 * @return string the view file path. Note that the file may not exist.
	 * @throws InvalidCallException if a relative view name is given while there is no active context to
	 *         determine the corresponding view file.
	 */
	protected function findViewFile($view, $context = null)
	{
		if (strncmp ( $view, '@', 1 ) === 0) {
			// e.g. "@app/views/main"
			$file = Kernel::getAlias ( $view );
		} elseif (strncmp ( $view, '//', 2 ) === 0) {
			// e.g. "//layouts/main"
			$file = Kernel::$app->getViewPath () . DIRECTORY_SEPARATOR . ltrim ( $view, '/' );
		} elseif (strncmp ( $view, '/', 1 ) === 0) {
			// e.g. "/site/index"
			if (Kernel::$app->controller !== null) {
				$file = Kernel::$app->controller->module->getViewPath () . DIRECTORY_SEPARATOR . ltrim ( $view, '/' );
			} else {
				throw new InvalidCallException ( "Unable to locate view file for view '$view': no active controller." );
			}
		} elseif ($context instanceof ViewContextInterface) {
			$file = $context->getViewPath () . DIRECTORY_SEPARATOR . $view;
		} elseif (($currentViewFile = $this->getViewFile ()) !== false) {
			$file = dirname ( $currentViewFile ) . DIRECTORY_SEPARATOR . $view;
		} else {
			throw new InvalidCallException ( "Unable to resolve view file for view '$view': no active view context." );
		}

		if (pathinfo ( $file, PATHINFO_EXTENSION ) !== '') {
			return $file;
		}
		$path = $file . '.' . $this->defaultExtension;
		if ($this->defaultExtension !== 'php' && ! is_file ( $path )) {
			$path = $file . '.php';
		}

		return $path;
	}

	/**
	 * 渲染视图文件
	 *
	 * If [[theme]] is enabled (not null), it will try to render the themed version of the view file as long
	 * as it is available.
	 *
	 * The method will call [[FileHelper::localize()]] to localize the view file.
	 *
	 * If [[renderers|renderer]] is enabled (not null), the method will use it to render the view file.
	 * Otherwise, it will simply include the view file as a normal PHP file, capture its output and
	 * return it as a string.
	 *
	 * @param string $viewFile the view file. This can be either an absolute file path or an alias of it.
	 * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @param object $context the context that the view should use for rendering the view. If null,
	 * existing [[context]] will be used.
	 * @return string the rendering result
	 * @throws InvalidParamException if the view file does not exist
	 */
	public function renderFile($viewFile, $params = [], $context = null)
	{
		$viewFile = Kernel::getAlias($viewFile);

		if ($this->theme !== null) {
			$viewFile = $this->theme->applyTo($viewFile);
		}
		if (is_file($viewFile)) {
			//$viewFile = FileHelper::localize($viewFile);
		} else {
			throw new InvalidParamException("The view file does not exist: $viewFile");
		}

		$oldContext = $this->context;
		if ($context !== null) {
			$this->context = $context;
		}
		$output = '';
		$this->_viewFiles[] = $viewFile;

		if ($this->beforeRender($viewFile, $params)) {
			Kernel::trace("Rendering view file: $viewFile", __METHOD__);
			$ext = pathinfo($viewFile, PATHINFO_EXTENSION);
			if (isset($this->renderers[$ext])) {
				if (is_array($this->renderers[$ext]) || is_string($this->renderers[$ext])) {
					$this->renderers[$ext] = Kernel::createObject($this->renderers[$ext]);
				}
				/* @var $renderer ViewRenderer */
				$renderer = $this->renderers[$ext];
				$output = $renderer->render($this, $viewFile, $params);
			} else {
				$output = $this->renderPhpFile($viewFile, $params);
			}
			$this->afterRender($viewFile, $params, $output);
		}

		array_pop($this->_viewFiles);
		$this->context = $oldContext;

		return $output;
	}


	/**
	 * 目前呈现的视图文件
	 * @return string|boolean
	 */
	public function getViewFile()
	{
		return end($this->_viewFiles);
	}

	/**
	 * 渲染一个PHP脚本视图
	 *
	 * This method should mainly be called by view renderer or [[renderFile()]].
	 *
	 * @param string $_file_ the view file.
	 * @param array $_params_ the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @return string the rendering result
	 */
	public function renderPhpFile($_file_, $_params_ = [])
	{
		ob_start();
		ob_implicit_flush(false);
		extract($_params_, EXTR_OVERWRITE);
		require($_file_);
		return ob_get_clean();
	}

	/**
	 * 主要在内部使用这个方法来实现动态内容的功能
	 *
	 * @param string $statements the PHP statements to be evaluated.
	 * @return mixed the return value of the PHP statements.
	 */
	public function evaluateDynamicContent($statements)
	{
		return eval ( $statements );
	}

	/**
	 * Marks the beginning of a page.
	 */
	public function beginPage()
	{
		ob_start ();
		ob_implicit_flush ( false );
		$this->event->trigger ( self::EVENT_BEGIN_PAGE );
	}

	/**
	 * Marks the ending of a page.
	 */
	public function endPage()
	{
		$this->event->trigger ( self::EVENT_END_PAGE );
		ob_end_flush ();
	}
}