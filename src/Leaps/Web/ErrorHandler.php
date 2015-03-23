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
use Leaps\Exception;
use Leaps\ErrorException;
use Leaps\UserException;
use Leaps\Http\Response;

class ErrorHandler extends \Leaps\ErrorHandler
{
	/**
	 * 响应对象
	 *
	 * @var \Leaps\Http\Response
	 */
	public $response;

	/**
	 * 显示最大数量的源代码行。默认为19。
	 *
	 * @var integer
	 */
	public $maxSourceLines = 19;

	/**
	 * 最大数量的跟踪显示源代码行。默认为13。
	 *
	 * @var integer
	 */
	public $maxTraceSourceLines = 13;

	/**
	 * 渲染异常
	 *
	 * @param \Exception $exception the exception to be rendered.
	 */
	protected function renderException($exception)
	{
		if (! is_object ( $this->response )) {
			if (! is_object ( $this->_dependencyInjector )) {
				throw new Exception ( "A dependency injection object is required to access the 'response' service" );
			}
			$this->response = $this->_dependencyInjector->getShared ( "response" );
		}
		$useErrorView = $this->response->format === Response::FORMAT_HTML && (Kernel::$env != Kernel::DEVELOPMENT || $exception instanceof UserException);

		if ($this->response->format === Response::FORMAT_HTML) {
			if (isset ( $_SERVER ['HTTP_X_REQUESTED_WITH'] ) && $_SERVER ['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' || Kernel::$env == Kernel::TEST) {
				// AJAX request
				$this->response->data = '<pre>' . $this->htmlEncode ( $this->convertExceptionToString ( $exception ) ) . '</pre>';
			} else {
				// if there is an error during error rendering it's useful to
				// display PHP error in debug mode instead of a blank screen
				if (Kernel::$env == Kernel::DEVELOPMENT) {
					// ini_set ( 'display_errors', 1 );
				}
				if ($useErrorView) {
					$this->response->data = $this->renderErrorView ( $exception );
				} else {
					$this->response->data = $this->renderExceptionView ( $exception );
				}
			}
			echo $this->response->data;
			exit ();
		} elseif ($this->response->format === Response::FORMAT_RAW) {
			$this->response->data = $exception;
		} else {
			$this->response->data = $this->convertExceptionToArray ( $exception );
		}
		if ($exception instanceof HttpException) {
			$this->response->setStatusCode ( $exception->statusCode );
		} else {
			$this->response->setStatusCode ( 500 );
		}
		$this->response->send ();
	}

	/**
	 * 渲染堆栈
	 *
	 * @param string|null $file name where call has happened.
	 * @param integer|null $line number on which call has happened.
	 * @param string|null $class called class name.
	 * @param string|null $method called function/method name.
	 * @param integer $index number of the call stack element.
	 * @param array $args array of method arguments.
	 * @return string HTML content of the rendered call stack element.
	 */
	public function renderCallStackItem($file, $line, $class, $method, $args, $index)
	{
		$lines = [ ];
		$begin = $end = 0;
		if ($file !== null && $line !== null) {
			$line --; // adjust line number from one-based to zero-based
			$lines = @file ( $file );
			if ($line < 0 || $lines === false || ($lineCount = count ( $lines )) < $line + 1) {
				return '';
			}
			$half = ( int ) (($index == 1 ? $this->maxSourceLines : $this->maxTraceSourceLines) / 2);
			$begin = $line - $half > 0 ? $line - $half : 0;
			$end = $line + $half < $lineCount ? $line + $half : $lineCount - 1;
		}
		$view = "";
		$view .= "<li class=\"call-stack-item\" data-line=\"" . ( int ) ($line - $begin) . "\">";
		$view .= "<div class=\"element-wrap\"><div class=\"element\">";
		$view .= "<span class=\"item-number\">" . ( int ) $index . ".</span><span class=\"text\">";
		if ($file !== null) {
			$view .= "in  " . $this->htmlEncode ( $file );
		}
		$view .= "</span>";
		if ($method !== null) {
			$view .= "<span class=\"call\">";
			if ($file !== null) {
				$view .= " &ndash; ";
			}
			if ($class !== null) {
				$view .= $this->addTypeLinks ( $class ) . "::";
			}
			$view .= $this->addTypeLinks ( $method . '()' );
			$view .= "</span>";
		}
		$view .= "<span class=\"at\">";
		if ($line !== null) {
			$view .= "at line";
		}
		$view .= "</span><span class=\"line\">";
		if ($line !== null) {
			$view .= ( int ) $line + 1;
		}
		$view .= "</span></div></div>";

		if (! empty ( $lines )) {
			$view .= "<div class=\"code-wrap\">";
			$view .= "<div class=\"error-line\"></div>";
			for($i = $begin; $i <= $end; ++ $i) {
				$view .= "<div class=\"hover-line\"></div>";
			}
			$view .= "<div class=\"code\">";
			for($i = $begin; $i <= $end; ++ $i) {
				$view .= "<span class=\"lines-item\">";
				$view .= ( int ) ($i + 1);
				$view .= "</span>";
			}
			$view .= "<pre>";
			// fill empty lines with a whitespace to avoid rendering problems in opera
			for($i = $begin; $i <= $end; ++ $i) {
				$view .= (trim ( $lines [$i] ) == '') ? " \n" : $this->htmlEncode ( $lines [$i] );
			}
			$view .= "</pre></div></div>";
		}
		$view .= "</li>";

		return $view;
	}

	/**
	 * Renders a view file as a PHP script.
	 *
	 * @param string $_file_ the view file.
	 * @param array $_params_ the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @return string the rendering result
	 */
	public function renderErrorView($exception)
	{
		if ($exception instanceof \Leaps\Web\HttpException) {
			$code = $exception->statusCode;
		} else {
			$code = $exception->getCode ();
		}
		if ($exception instanceof \Leaps\Exception) {
			$name = $exception->getName ();
		} else {
			$name = 'Error';
		}
		if ($code) {
			$name .= " (#$code)";
		}
		if ($exception instanceof \Leaps\UserException) {
			$message = $exception->getMessage ();
		} else {
			$message = 'An internal server error occurred.';
		}
		$view = "<!DOCTYPE html>";
		$view .= "<html>";
		$view .= "<head>";
		$view .= "	<meta charset=\"utf-8\" />";
		$view .= "	<title>" . $this->htmlEncode ( $name ) . "</title>";
		$view .= "	<style>";
		$view .= "		body{font:normal 9pt \"Verdana\";color:#000;background:#fff}";
		$view .= "		h1{font:normal 18pt \"Verdana\";color:#f00;margin-bottom:.5em}";
		$view .= "		h2{font:normal 14pt \"Verdana\";color:#800000;margin-bottom:.5em}";
		$view .= "		h3{font:bold 11pt \"Verdana\"}";
		$view .= "		p{font:normal 9pt \"Verdana\";color:#000}";
		$view .= "		.version{color:gray;font-size:8pt;border-top:1px solid #aaa;padding-top:1em;margin-bottom:1em}";
		$view .= "	</style>";
		$view .= "</head>";
		$view .= "<body>";
		$view .= "	<h1><?= $handler->htmlEncode($name) ?></h1>";
		$view .= "	<h2><?= nl2br($handler->htmlEncode($message)) ?></h2>";
		$view .= "	<p>";
		$view .= "		The above error occurred while the Web server was processing your request.";
		$view .= "	</p>";
		$view .= "	<p>";
		$view .= "		Please contact us if you think this is a server error. Thank you.";
		$view .= "	</p>";
		$view .= "	<div class=\"version\">";
		$view .= date ( 'Y-m-d H:i:s', time () );
		$view .= "	</div>";
		$view .= "</body>";
		$view .= "</html>";
		return $view;
	}

	/**
	 * Renders a view file as a PHP script.
	 *
	 * @param string $_file_ the view file.
	 * @param array $_params_ the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @return string the rendering result
	 */
	public function renderExceptionView($exception)
	{
		$view = "<!doctype html>";
		$view .= "<html lang=\"en-us\">";
		$view .= "<head>";
		$view .= "<meta charset=\"utf-8\" />";
		$view .= "<title>";
		if ($exception instanceof \Leaps\Web\HttpException) {
			$view .= ( int ) $exception->statusCode . ' ' . $this->htmlEncode ( $exception->getName () );
		} elseif ($exception instanceof \Leaps\Exception) {
			$view .= $this->htmlEncode ( $exception->getName () . ' – ' . get_class ( $exception ) );
		} else {
			$view .= $this->htmlEncode ( get_class ( $exception ) );
		}
		$view .= "</title>";
		$view .= "<style>	body{font-family:'Microsoft Yahei',Verdana,arial,sans-serif;font-size:14px}a{text-decoration:none;color:#174b73}a:hover{text-decoration:none;color:#f60}h1,h2,h3,p,img,ul li{font-family:Arial,sans-serif;color:#505050}h1{border-bottom:1px solid #DDD;padding:8px 0;font-size:25px}ul{list-style:none}.notice{padding:10px;margin:5px;color:#666;background:#fcfcfc;border:1px solid #e0e0e0}.title{margin:4px 0;color:#F60;font-weight:bold}.message,#trace{padding:1em;border:solid 1px #000;margin:10px 0;background:#FFD;line-height:150%}.message{background:#FFD;color:#2e2e2e;border:1px solid #e0e0e0}.request{background:#e7f7ff;border:1px solid #e0e0e0;color:#535353}.call-stack{margin-top:30px;margin-bottom:40px}.call-stack ul li{margin:1px 0}.call-stack ul li .element-wrap{cursor:pointer;padding:15px 0}.call-stack ul li.application .element-wrap{background-color:#fafafa}.call-stack ul li .element-wrap:hover{background-color:#edf9ff}.call-stack ul li .element{min-width:860px;margin:0 auto;padding:0 50px;position:relative}.call-stack ul li a{color:#505050}.call-stack ul li a:hover{color:#000}.call-stack ul li .item-number{width:45px;display:inline-block}.call-stack ul li .text{color:#aaa}.call-stack ul li.application .text{color:#505050}.call-stack ul li .at{position:absolute;right:110px;color:#aaa}.call-stack ul li.application .at{color:#505050}.call-stack ul li .line{position:absolute;right:50px;width:60px;text-align:right}.call-stack ul li .code-wrap{display:none;position:relative}.call-stack ul li.application .code-wrap{display:block}.call-stack ul li .error-line,.call-stack ul li .hover-line{background-color:#ffebeb;position:absolute;width:100%;z-index:100;margin-top:-61px}.call-stack ul li .hover-line{background:0}.call-stack ul li .hover-line.hover,.call-stack ul li .hover-line:hover{background:#edf9ff!important}.call-stack ul li .code{min-width:860px;margin:15px auto;padding:0 50px;position:relative}.call-stack ul li .code .lines-item{position:absolute;z-index:200;display:block;width:25px;text-align:right;color:#aaa;line-height:20px;font-size:12px;margin-top:-63px;font-family:Consolas,Courier New,monospace}.call-stack ul li .code pre{position:relative;z-index:200;left:50px;line-height:20px;font-size:12px;font-family:Consolas,Courier New,monospace;display:inline}@ -moz-document url-prefix(){.call-stack ul li .code pre{line-height:20px}}.request{min-width:860px;margin:0 auto;padding:15px 50px}.request pre{font-size:14px;line-height:18px;font-family:Consolas,Courier New,monospace;display:inline;word-wrap:break-word}pre .subst,pre .title{font-weight:normal;color:#505050}pre .comment,pre .template_comment,pre .javadoc,pre .diff .header{color:#808080;font-style:italic}pre .annotation,pre .decorator,pre .preprocessor,pre .doctype,pre .pi,pre .chunk,pre .shebang,pre .apache .cbracket,pre .prompt,pre .http .title{color:#808000}pre .tag,pre .pi{background:#efefef}pre .tag .title,pre .id,pre .attr_selector,pre .pseudo,pre .literal,pre .keyword,pre .hexcolor,pre .css .function,pre .ini .title,pre .css .class,pre .list .title,pre .clojure .title,pre .nginx .title,pre .tex .command,pre .request,pre .status{color:#000080}pre .attribute,pre .rules .keyword,pre .number,pre .date,pre .regexp,pre .tex .special{color:#00a}pre .number,pre .regexp{font-weight:normal}pre .string,pre .value,pre .filter .argument,pre .css .function .params,pre .apache .tag{color:#0a0}pre .symbol,pre .ruby .symbol .string,pre .char,pre .tex .formula{color:#505050;background:#d0eded;font-style:italic}pre .phpdoc,pre .yardoctag,pre .javadoctag{text-decoration:underline}pre .variable,pre .envvar,pre .apache .sqbracket,pre .nginx .built_in{color:#a00}pre .addition{background:#baeeba}pre .deletion{background:#ffc8bd}pre .diff .change{background:#bccff9}</style>";
		$view .= "</head>";
		$view .= "<body>";
		$view .= "<div class=\"notice\">";
		if ($exception instanceof \Leaps\ErrorException) {
			$view .= "<h1><span>" . $this->htmlEncode ( $exception->getName () ) . "</span> &ndash; " . $this->addTypeLinks ( get_class ( $exception ) ) . "</h1>";
		} else {
			$view .= "<h1>";
			if ($exception instanceof \Leaps\Web\HttpException) {
				$view .= '<span>' . $this->createHttpStatusLink ( $exception->statusCode, $this->htmlEncode ( $exception->getName () ) ) . '</span> &ndash; ' . $this->addTypeLinks ( get_class ( $exception ) );
			} elseif ($exception instanceof \Leaps\Exception) {
				$view .= '<span>' . $this->htmlEncode ( $exception->getName () ) . '</span> &ndash; ' . $this->addTypeLinks ( get_class ( $exception ) );
			} else {
				$view .= '<span>' . $this->htmlEncode ( get_class ( $exception ) ) . '</span>';
			}
			$view .= "</h1>";
		}
		$view .= "<div>您可以选择 [ <A HREF=\"javascript:window.location.reload();\">重试</A> ] 或者 [<A HREF=\"javascript:history.back()\">返回</A> ]</div>";
		$view .= "<p><strong>错误位置:</strong> FILE: <span class=\"red\">" . $exception->getFile () . "</span> LINE: <span class=\"red\">" . $exception->getLine () . "</span></p><div class=\"title\">[ Error Message ]</div>";
		$view .= "<div class=\"message\">" . nl2br ( $this->htmlEncode ( $exception->getMessage () ) ) . "</div>";
		$view .= "<div class=\"title\">[ Stack Trace ]</div><div class=\"debug\"><div class=\"call-stack\"><ul>";
		$view .= $this->renderCallStackItem ( $exception->getFile (), $exception->getLine (), null, null, 1 );
		for($i = 0, $trace = $exception->getTrace (), $length = count ( $trace ); $i < $length; ++ $i) {
			$view .= $this->renderCallStackItem ( @$trace [$i] ['file'] ?  : null, @$trace [$i] ['line'] ?  : null, @$trace [$i] ['class'] ?  : null, @$trace [$i] ['function'] ?  : null, $i + 2 );
		}
		$view .= "</ul></div></div>";
		$view .= "</div>";
		$view .= "<div align=\"center\" style=\"color: #FF3300; margin: 5pt; font-family: Verdana\">" . date ( 'Y-m-d, H:i:s' ) . " " . $this->createServerInformationLink ();
		$view .= "<a href=\"http://leaps.tintsoft.com/\">Leaps Framework</a>/" . $this->createFrameworkVersionLink () . "</p>";
		$view .= "<span style='color: silver'> { Fast & Simple OOP PHP Framework } -- [ WE CAN DO IT JUST LIKE IT ]</span>";

		$view .= "<script type=\"text/javascript\">
				window.onload = function() {
					var codeBlocks = Sizzle('pre'),	callStackItems = Sizzle('.call-stack-item');
					for (var i = 0, imax = codeBlocks.length; i < imax; ++i) {hljs.highlightBlock(codeBlocks[i], '    ');}
					document.onmousemove = function(e) {
						var event = e || window.event,clientY = event.clientY,lineFound = false,hoverLines = Sizzle('.hover-line');
						for (var i = 0, imax = codeBlocks.length - 1; i < imax; ++i) {
							var lines = codeBlocks[i].getClientRects();
							for (var j = 0, jmax = lines.length; j < jmax; ++j) {
								if (clientY >= lines[j].top && clientY <= lines[j].bottom) {
									lineFound = true;
									break;
								}
							}
							if (lineFound) {
								break;
							}
						}

						for (var k = 0, kmax = hoverLines.length; k < kmax; ++k) {
							hoverLines[k].className = 'hover-line';
						}
						if (lineFound) {
							var line = Sizzle('.call-stack-item:eq(' + i + ') .hover-line:eq(' + j + ')')[0];
							if (line) {
								line.className = 'hover-line hover';
							}
						}
					};

					var refreshCallStackItemCode = function(callStackItem) {
						if (!Sizzle('pre', callStackItem)[0]) {
							return;
						}
						var top = callStackItem.offsetTop - window.pageYOffset,	lines = Sizzle('pre', callStackItem)[0].getClientRects(),lineNumbers = Sizzle('.lines-item', callStackItem),errorLine = Sizzle('.error-line', callStackItem)[0],hoverLines = Sizzle('.hover-line', callStackItem);
						for (var i = 0, imax = lines.length; i < imax; ++i) {
							if (!lineNumbers[i]) {
								continue;
							}
							lineNumbers[i].style.top = parseInt(lines[i].top - top) + 'px';
							hoverLines[i].style.top = parseInt(lines[i].top - top - 3) + 'px';
							hoverLines[i].style.height = parseInt(lines[i].bottom - lines[i].top + 6) + 'px';
							if (parseInt(callStackItem.getAttribute('data-line')) == i) {
								errorLine.style.top = parseInt(lines[i].top - top - 3) + 'px';
								errorLine.style.height = parseInt(lines[i].bottom - lines[i].top + 6) + 'px';
							}
						}
					};

					for (var i = 0, imax = callStackItems.length; i < imax; ++i) {
						refreshCallStackItemCode(callStackItems[i]);
						Sizzle('.element-wrap', callStackItems[i])[0].addEventListener('click', function() {
							var callStackItem = this.parentNode,
							code = Sizzle('.code-wrap', callStackItem)[0];
							code.style.display = window.getComputedStyle(code).display == 'block' ? 'none' : 'block';
							refreshCallStackItemCode(callStackItem);
						});
					}
				};</script>";
		$view .= "</div></body></html>";
		return $view;
	}

	/**
	 * Converts an exception into an array.
	 *
	 * @param \Exception $exception the exception being converted
	 * @return array the array representation of the exception.
	 */
	protected function convertExceptionToArray($exception)
	{
		if (Kernel::$env != Kernel::DEVELOPMENT && ! $exception instanceof UserException && ! $exception instanceof HttpException) {
			$exception = new HttpException ( 500, 'There was an error at the server.' );
		}

		$array = [
				'name' => ($exception instanceof Exception || $exception instanceof ErrorException) ? $exception->getName () : 'Exception',
				'message' => $exception->getMessage (),
				'code' => $exception->getCode ()
		];
		if ($exception instanceof HttpException) {
			$array ['status'] = $exception->statusCode;
		}
		if (Kernel::$env == Kernel::DEVELOPMENT) {
			$array ['type'] = get_class ( $exception );
			if (! $exception instanceof UserException) {
				$array ['file'] = $exception->getFile ();
				$array ['line'] = $exception->getLine ();
				$array ['stack-trace'] = explode ( "\n", $exception->getTraceAsString () );
				// if ($exception instanceof \yii\db\Exception) {
				// $array ['error-info'] = $exception->errorInfo;
				// }
			}
		}
		if (($prev = $exception->getPrevious ()) !== null) {
			$array ['previous'] = $this->convertExceptionToArray ( $prev );
		}

		return $array;
	}

	/**
	 * 将特殊字符转换为HTML实体
	 *
	 * @param string $text to encode.
	 * @return string encoded original text.
	 */
	public function htmlEncode($text)
	{
		return htmlspecialchars ( $text, ENT_QUOTES, Kernel::$app->charset );
	}

	/**
	 * Adds informational links to the given PHP type/class.
	 *
	 * @param string $code type/class name to be linkified.
	 * @return string linkified with HTML type/class name.
	 */
	public function addTypeLinks($code)
	{
		if (preg_match ( '/(.*?)::([^(]+)/', $code, $matches )) {
			$class = $matches [1];
			$method = $matches [2];
			$text = $this->htmlEncode ( $class ) . '::' . $this->htmlEncode ( $method );
		} else {
			$class = $code;
			$method = null;
			$text = $this->htmlEncode ( $class );
		}

		$url = $this->getTypeUrl ( $class, $method );

		if (! $url) {
			return $text;
		}

		return '<a href="' . $url . '" target="_blank">' . $text . '</a>';
	}

	/**
	 * Returns the informational link URL for a given PHP type/class.
	 *
	 * @param string $class the type or class name.
	 * @param string|null $method the method name.
	 * @return string|null the informational link URL.
	 * @see addTypeLinks()
	 */
	protected function getTypeUrl($class, $method)
	{
		if (strpos ( $class, 'Leaps\\' ) !== 0) {
			return null;
		}
		$page = $this->htmlEncode ( strtolower ( str_replace ( '\\', '-', $class ) ) );
		$url = "http://leaps.tintsoft.com/doc/$page.html";
		if ($method) {
			$url .= "#$method()-detail";
		}
		return $url;
	}

	/**
	 * Renders the previous exception stack for a given Exception.
	 *
	 * @param \Exception $exception the exception whose precursors should be rendered.
	 * @return string HTML content of the rendered previous exceptions.
	 *         Empty string if there are none.
	 */
	public function renderPreviousExceptions($exception)
	{
		$view = "";
		if (($previous = $exception->getPrevious ()) !== null) {
			$exception = $previous;
			$view = "<div class=\"previous\">";
			$view = "<span class=\"arrow\">&crarr;</span>";
			$view = "<h2>";
			$view = "<span>Caused by:</span>";
			if ($exception instanceof \Leaps\Exception) {
				$view = "<span>" . $this->htmlEncode ( $exception->getName () ) . "</span> &ndash;" . $this->addTypeLinks ( get_class ( $exception ) );
			} else {
				$view = "<span>" . $this->htmlEncode ( get_class ( $exception ) ) . "</span>";
			}
			$view = "</h2>";
			$view = "<h3><?= nl2br($handler->htmlEncode($exception->getMessage())) ?></h3>";
			$view = "<p>in <span class=\"file\">" . $exception->getFile () . "</span> at line <span class=\"line\">" . $exception->getLine () . "</span></p>";
			$view = $this->renderPreviousExceptions ( $exception );
			$view = "</div>";
			return $view;
		} else {
			return $view;
		}
	}

	/**
	 * 是否是核心文件
	 *
	 * @param string $file name to be checked.
	 * @return boolean whether given name of the file belongs to the framework.
	 */
	public function isCoreFile($file)
	{
		return $file === null || strpos ( realpath ( $file ), YII2_PATH . DIRECTORY_SEPARATOR ) === 0;
	}

	/**
	 * 创建HTTP状态码连接
	 *
	 * @param integer $statusCode to be used to generate information link.
	 * @param string $statusDescription Description to display after the the status code.
	 * @return string generated HTML with HTTP status code information.
	 */
	public function createHttpStatusLink($statusCode, $statusDescription)
	{
		return '<a href="http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#' . ( int ) $statusCode . '" target="_blank">HTTP ' . ( int ) $statusCode . ' &ndash; ' . $statusDescription . '</a>';
	}

	/**
	 * 创建Web服务器版本连接
	 *
	 * @return string server software information hyperlink.
	 */
	public function createServerInformationLink()
	{
		$serverUrls = [
				'http://httpd.apache.org/' => [
						'apache'
				],
				'http://nginx.org/' => [
						'nginx'
				],
				'http://lighttpd.net/' => [
						'lighttpd'
				],
				'http://gwan.com/' => [
						'g-wan',
						'gwan'
				],
				'http://iis.net/' => [
						'iis',
						'services'
				],
				'http://php.net/manual/en/features.commandline.webserver.php' => [
						'development'
				]
		];
		if (isset ( $_SERVER ['SERVER_SOFTWARE'] )) {
			foreach ( $serverUrls as $url => $keywords ) {
				foreach ( $keywords as $keyword ) {
					if (stripos ( $_SERVER ['SERVER_SOFTWARE'], $keyword ) !== false) {
						return '<a href="' . $url . '" target="_blank">' . $this->htmlEncode ( $_SERVER ['SERVER_SOFTWARE'] ) . '</a>';
					}
				}
			}
		}

		return '';
	}

	/**
	 * 创建框架版本连接
	 *
	 * @return string framework version information hyperlink.
	 */
	public function createFrameworkVersionLink()
	{
		return '<a href="http://github.com/yiisoft/yii2/" target="_blank">' . $this->htmlEncode ( Kernel::getVersion () ) . '</a>';
	}

	/**
	 * 将参数数组转换为字符串表示
	 *
	 * @param array $args arguments array to be converted
	 * @return string string representation of the arguments array
	 */
	public function argumentsToString($args)
	{
		$count = 0;
		$isAssoc = $args !== array_values ( $args );

		foreach ( $args as $key => $value ) {
			$count ++;
			if ($count >= 5) {
				if ($count > 5) {
					unset ( $args [$key] );
				} else {
					$args [$key] = '...';
				}
				continue;
			}

			if (is_object ( $value )) {
				$args [$key] = '<span class="title">' . $this->htmlEncode ( get_class ( $value ) ) . '</span>';
			} elseif (is_bool ( $value )) {
				$args [$key] = '<span class="keyword">' . ($value ? 'true' : 'false') . '</span>';
			} elseif (is_string ( $value )) {
				$fullValue = $this->htmlEncode ( $value );
				if (mb_strlen ( $value, 'utf8' ) > 32) {
					$displayValue = $this->htmlEncode ( mb_substr ( $value, 0, 32, 'utf8' ) ) . '...';
					$args [$key] = "<span class=\"string\" title=\"$fullValue\">'$displayValue'</span>";
				} else {
					$args [$key] = "<span class=\"string\">'$fullValue'</span>";
				}
			} elseif (is_array ( $value )) {
				$args [$key] = '[' . $this->argumentsToString ( $value ) . ']';
			} elseif ($value === null) {
				$args [$key] = '<span class="keyword">null</span>';
			} elseif (is_resource ( $value )) {
				$args [$key] = '<span class="keyword">resource</span>';
			} else {
				$args [$key] = '<span class="number">' . $value . '</span>';
			}

			if (is_string ( $key )) {
				$args [$key] = '<span class="string">\'' . $this->htmlEncode ( $key ) . "'</span> => $args[$key]";
			} elseif ($isAssoc) {
				$args [$key] = "<span class=\"number\">$key</span> => $args[$key]";
			}
		}
		$out = implode ( ", ", $args );
		return $out;
	}

	/**
	 * 返回人类可读的异常名称
	 *
	 * @param \Exception $exception
	 * @return string
	 */
	public function getExceptionName($exception)
	{
		if ($exception instanceof \Leaps\Exception || $exception instanceof \Leaps\InvalidCallException || $exception instanceof \Leaps\InvalidParamException || $exception instanceof \Leaps\UnknownMethodException) {
			return $exception->getName ();
		}
		return null;
	}
}