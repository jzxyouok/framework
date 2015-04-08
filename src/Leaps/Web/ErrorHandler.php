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
use Leaps\Http\Response;
use Leaps\Core\Exception;
use Leaps\Core\ErrorException;
use Leaps\Core\UserException;

class ErrorHandler extends \Leaps\Core\ErrorHandler
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
					//ini_set ( 'display_errors', 1 );
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
	public function renderCallStackItem($file, $line, $class, $method, $args, $index = 0)
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
		if ($exception instanceof \Leaps\Core\Exception) {
			$name = $exception->getName ();
		} else {
			$name = 'Error';
		}
		if ($code) {
			$name .= " (#$code)";
		}
		if ($exception instanceof \Leaps\Core\UserException) {
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
		$view .= "	<h1>" . $this->htmlEncode ( $name ) . "</h1>";
		$view .= "	<h2>" . nl2br ( $this->htmlEncode ( $message ) ) . "</h2>";
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
		} elseif ($exception instanceof \Leaps\Core\Exception) {
			$view .= $this->htmlEncode ( $exception->getName () . ' – ' . get_class ( $exception ) );
		} else {
			$view .= $this->htmlEncode ( get_class ( $exception ) );
		}
		$view .= "</title>";
		$view .= "<style>	body{font-family:'Microsoft Yahei',Verdana,arial,sans-serif;font-size:14px}a{text-decoration:none;color:#174b73}a:hover{text-decoration:none;color:#f60}h1,h2,h3,p,img,ul li{font-family:Arial,sans-serif;color:#505050}h1{border-bottom:1px solid #DDD;padding:8px 0;font-size:25px}ul{list-style:none}.notice{padding:10px;margin:5px;color:#666;background:#fcfcfc;border:1px solid #e0e0e0}.title{margin:4px 0;color:#F60;font-weight:bold}.message,#trace{padding:1em;border:solid 1px #000;margin:10px 0;background:#FFD;line-height:150%}.message{background:#FFD;color:#2e2e2e;border:1px solid #e0e0e0}.request{background:#e7f7ff;border:1px solid #e0e0e0;color:#535353}.call-stack{margin-top:30px;margin-bottom:40px}.call-stack ul li{margin:1px 0}.call-stack ul li .element-wrap{cursor:pointer;padding:15px 0}.call-stack ul li.application .element-wrap{background-color:#fafafa}.call-stack ul li .element-wrap:hover{background-color:#edf9ff}.call-stack ul li .element{min-width:860px;margin:0 auto;padding:0 50px;position:relative}.call-stack ul li a{color:#505050}.call-stack ul li a:hover{color:#000}.call-stack ul li .item-number{width:45px;display:inline-block}.call-stack ul li .text{color:#aaa}.call-stack ul li.application .text{color:#505050}.call-stack ul li .at{position:absolute;right:110px;color:#aaa}.call-stack ul li.application .at{color:#505050}.call-stack ul li .line{position:absolute;right:50px;width:60px;text-align:right}.call-stack ul li .code-wrap{display:none;position:relative}.call-stack ul li.application .code-wrap{display:block}.call-stack ul li .error-line,.call-stack ul li .hover-line{background-color:#ffebeb;position:absolute;width:100%;z-index:100;margin-top:-61px}.call-stack ul li .hover-line{background:0}.call-stack ul li .hover-line.hover,.call-stack ul li .hover-line:hover{background:#edf9ff!important}.call-stack ul li .code{min-width:860px;margin:15px auto;padding:0 50px;position:relative}.call-stack ul li .code .lines-item{position:absolute;z-index:200;display:block;width:25px;text-align:right;color:#aaa;line-height:20px;font-size:12px;margin-top:-63px;font-family:Consolas,Courier New,monospace}.call-stack ul li .code pre{position:relative;z-index:200;left:50px;line-height:20px;font-size:12px;font-family:Consolas,Courier New,monospace;display:inline}@ -moz-document url-prefix(){.call-stack ul li .code pre{line-height:20px}}.request{min-width:860px;margin:0 auto;padding:15px 50px}.request pre{font-size:14px;line-height:18px;font-family:Consolas,Courier New,monospace;display:inline;word-wrap:break-word}pre .subst,pre .title{font-weight:normal;color:#505050}pre .comment,pre .template_comment,pre .javadoc,pre .diff .header{color:#808080;font-style:italic}pre .annotation,pre .decorator,pre .preprocessor,pre .doctype,pre .pi,pre .chunk,pre .shebang,pre .apache .cbracket,pre .prompt,pre .http .title{color:#808000}pre .tag,pre .pi{background:#efefef}pre .tag .title,pre .id,pre .attr_selector,pre .pseudo,pre .literal,pre .keyword,pre .hexcolor,pre .css .function,pre .ini .title,pre .css .class,pre .list .title,pre .clojure .title,pre .nginx .title,pre .tex .command,pre .request,pre .status{color:#000080}pre .attribute,pre .rules .keyword,pre .number,pre .date,pre .regexp,pre .tex .special{color:#00a}pre .number,pre .regexp{font-weight:normal}pre .string,pre .value,pre .filter .argument,pre .css .function .params,pre .apache .tag{color:#0a0}pre .symbol,pre .ruby .symbol .string,pre .char,pre .tex .formula{color:#505050;background:#d0eded;font-style:italic}pre .phpdoc,pre .yardoctag,pre .javadoctag{text-decoration:underline}pre .variable,pre .envvar,pre .apache .sqbracket,pre .nginx .built_in{color:#a00}pre .addition{background:#baeeba}pre .deletion{background:#ffc8bd}pre .diff .change{background:#bccff9}</style>";
		$view .= "</head>";
		$view .= "<body>";
		$view .= "<div class=\"notice\">";
		if ($exception instanceof \Leaps\Core\ErrorException) {
			$view .= "<h1><span>" . $this->htmlEncode ( $exception->getName () ) . "</span> &ndash; " . $this->addTypeLinks ( get_class ( $exception ) ) . "</h1>";
		} else {
			$view .= "<h1>";
			if ($exception instanceof \Leaps\Web\HttpException) {
				$view .= '<span>' . $this->createHttpStatusLink ( $exception->statusCode, $this->htmlEncode ( $exception->getName () ) ) . '</span> &ndash; ' . $this->addTypeLinks ( get_class ( $exception ) );
			} elseif ($exception instanceof \Leaps\Core\Exception) {
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
		$view .= " <a href=\"http://leaps.tintsoft.com/\">Leaps Framework</a>/" . $this->createFrameworkVersionLink () . "</p>";
		$view .= "<span style='color: silver'> { Fast & Simple OOP PHP Framework } -- [ WE CAN DO IT JUST LIKE IT ]</span>";
		$view .= "<script type=\"text/javascript\">var hljs = new function() {function l(o) {return o.replace(/&/gm, \"&amp;\").replace(/</gm, \"&lt;\").replace(/>/gm, \"&gt;\")}function b(p) {for (var o = p.firstChild; o; o = o.nextSibling) {if (o.nodeName == \"CODE\") {return o}if (!(o.nodeType == 3 && o.nodeValue.match(/\s+/))) {break}}}function h(p, o) {return Array.prototype.map.call(p.childNodes, function(q) {if (q.nodeType == 3) {return o ? q.nodeValue.replace(/\\n/g, \"\") : q.nodeValue}if (q.nodeName == \"BR\") {return \"\\n\"}return h(q, o)}).join(\"\")}function a(q) {var p = (q.className + \" \" + q.parentNode.className).split(/\\s+/);p = p.map(function(r) {return r.replace(/^language-/, \"\")});for (var o = 0; o < p.length; o++) {if (e[p[o]] || p[o] == \"no-highlight\") {return p[o]}}}function c(q) {var o = [];(function p(r, s) {for (var t = r.firstChild; t; t = t.nextSibling) {if (t.nodeType == 3) {s += t.nodeValue.length} else {if (t.nodeName == \"BR\") {s += 1} else {if (t.nodeType == 1) {o.push({event: \"start\",offset: s,node: t});s = p(t, s);o.push({event: \"stop\",offset: s,node: t})}}}}return s})(q, 0);return o}function j(x, v, w) {var p = 0;var y = \"\";var r = [];function t() {if (x.length && v.length) {if (x[0].offset != v[0].offset) {return (x[0].offset < v[0].offset) ? x : v} else {return v[0].event == \"start\" ? x : v}} else {return x.length ? x : v}}function s(A) {function z(B) {return \" \" + B.nodeName + '=\"' + l(B.value) + '\"'}return \"<\" + A.nodeName + Array.prototype.map.call(A.attributes, z).join(\"\") + \">\"}while (x.length || v.length) {var u = t().splice(0, 1)[0];y += l(w.substr(p, u.offset - p));p = u.offset;if (u.event == \"start\") {y += s(u.node);r.push(u.node)} else {if (u.event == \"stop\") {var o, q = r.length;do {q--;o = r[q];y += (\"</\" + o.nodeName.toLowerCase() + \">\")} while (o != u.node);r.splice(q, 1);while (q < r.length) {y += s(r[q]);q++}}}}return y + l(w.substr(p))}function f(q) {function o(s, r) {return RegExp(s, \"m\" + (q.cI ? \"i\" : \"\") + (r ? \"g\" : \"\"))}function p(y, w) {if (y.compiled) {return}y.compiled = true;var s = [];if (y.k) {var r = {};function z(A, t) {t.split(\" \").forEach(function(B) {var C = B.split(\"|\");r[C[0]] = [A, C[1] ? Number(C[1]) : 1];s.push(C[0])})}y.lR = o(y.l || hljs.IR, true);if (typeof y.k == \"string\") {z(\"keyword\", y.k)} else {for (var x in y.k) {if (!y.k.hasOwnProperty(x)) {continue}z(x, y.k[x])}}y.k = r}if (w) {if (y.bWK) {y.b = \"\\\\b(\" + s.join(\"|\") + \")\\\\s\"}y.bR = o(y.b ? y.b : \"\\\\B|\\\\b\");if (!y.e && !y.eW) {y.e = \"\\\\B|\\\\b\"}if (y.e) {y.eR = o(y.e)}y.tE = y.e || \"\";if (y.eW && w.tE) {y.tE += (y.e ? \"|\" : \"\") + w.tE}}if (y.i) {y.iR = o(y.i)}if (y.r === undefined) {y.r = 1}if (!y.c) {y.c = []}for (var v = 0; v < y.c.length; v++) {if (y.c[v] == \"self\") {y.c[v] = y}p(y.c[v], y)}if (y.starts) {p(y.starts, w)}var u = [];for (var v = 0; v < y.c.length; v++) {u.push(y.c[v].b)}if (y.tE) {u.push(y.tE)}if (y.i) {u.push(y.i)}y.t = u.length ? o(u.join(\"|\"), true) : {exec: function(t) {return null}}}p(q)}function d(D, E) {function o(r, M) {for (var L = 0; L < M.c.length; L++) {var K = M.c[L].bR.exec(r);if (K && K.index == 0) {return M.c[L]}}}function s(K, r) {if (K.e && K.eR.test(r)) {return K}if (K.eW) {return s(K.parent, r)}}function t(r, K) {return K.i && K.iR.test(r)}function y(L, r) {var K = F.cI ? r[0].toLowerCase() : r[0];return L.k.hasOwnProperty(K) && L.k[K]}function G() {var K = l(w);if (!A.k) {return K}var r = \"\";var N = 0;A.lR.lastIndex = 0;var L = A.lR.exec(K);while (L) {r += K.substr(N, L.index - N);var M = y(A, L);if (M) {v += M[1];r += '<span class=\"' + M[0] + '\">' + L[0] + \"</span>\"} else {r += L[0]}N = A.lR.lastIndex;L = A.lR.exec(K)}return r + K.substr(N)}function z() {if (A.sL && !e[A.sL]) {return l(w)}var r = A.sL ? d(A.sL, w) : g(w);if (A.r > 0) {v += r.keyword_count;B += r.r}return '<span class=\"' + r.language + '\">' + r.value + \"</span>\"}function J() {return A.sL !== undefined ? z() : G()}function I(L, r) {var K = L.cN ? '<span class=\"' + L.cN + '\">' : \"\";if (L.rB) {x += K;w = \"\"} else {if (L.eB) {x += l(r) + K;w = \"\"} else {x += K;w = r}}A = Object.create(L, {parent: {value: A}});B += L.r}function C(K, r) {w += K;if (r === undefined) {x += J();return 0}var L = o(r, A);if (L) {x += J();I(L, r);return L.rB ? 0 : r.length}var M = s(A, r);if (M) {if (!(M.rE || M.eE)) {w += r}x += J();do {if (A.cN) {x += \"</span>\"}A = A.parent} while (A != M.parent);if (M.eE) {x += l(r)}w = \"\";if (M.starts) {I(M.starts, \"\")}return M.rE ? 0 : r.length}if (t(r, A)) {throw \"Illegal\"}w += r;return r.length || 1}var F = e[D];f(F);var A = F;var w = \"\";var B = 0;var v = 0;var x = \"\";try {var u, q, p = 0;while (true) {A.t.lastIndex = p;u = A.t.exec(E);if (!u) {break}q = C(E.substr(p, u.index - p), u[0]);p = u.index + q}C(E.substr(p));return {r: B,keyword_count: v,value: x,language: D}} catch (H) {if (H == \"Illegal\") {return {r: 0,keyword_count: 0,value: l(E)}} else {throw H}}}function g(s) {var o = {keyword_count: 0,r: 0,value: l(s)};var q = o;for (var p in e) {if (!e.hasOwnProperty(p)) {continue}var r = d(p, s);r.language = p;if (r.keyword_count + r.r > q.keyword_count + q.r) {q = r}if (r.keyword_count + r.r > o.keyword_count + o.r) {q = o;o = r}}if (q.language) {o.second_best = q}return o}function i(q, p, o) {if (p) {q = q.replace(/^((<[^>]+>|\t)+)/gm, function(r, v, u, t) {return v.replace(/\t/g, p)})}if (o) {q = q.replace(/\\n/g, \"<br>\")}return q}function m(r, u, p) {var v = h(r, p);var t = a(r);if (t == \"no-highlight\") {return}var w = t ? d(t, v) : g(v);t = w.language;var o = c(r);if (o.length) {var q = document.createElement(\"pre\");q.innerHTML = w.value;w.value = j(o, c(q), v)}w.value = i(w.value, u, p);var s = r.className;if (!s.match(\"(\\\\s|^)(language-)?\" + t + \"(\\\\s|$)\")) {s = s ? (s + \" \" + t) : t}r.innerHTML = w.value;r.className = s;r.result = {language: t,kw: w.keyword_count,re: w.r};if (w.second_best) {r.second_best = {language: w.second_best.language,kw: w.second_best.keyword_count,re: w.second_best.r}}}function n() {if (n.called) {return}n.called = true;Array.prototype.map.call(document.getElementsByTagName(\"pre\"), b).filter(Boolean).forEach(function(o) {m(o, hljs.tabReplace)})}function k() {window.addEventListener(\"DOMContentLoaded\", n, false);window.addEventListener(\"load\", n, false)}var e = {};this.LANGUAGES = e;this.highlight = d;this.highlightAuto = g;this.fixMarkup = i;this.highlightBlock = m;this.initHighlighting = n;this.initHighlightingOnLoad = k;this.IR = \"[a-zA-Z][a-zA-Z0-9_]*\";this.UIR = \"[a-zA-Z_][a-zA-Z0-9_]*\";this.NR = \"\\\\b\\\\d+(\\\\.\\\\d+)?\";this.CNR = \"(\\\\b0[xX][a-fA-F0-9]+|(\\\\b\\\\d+(\\\\.\\\\d*)?|\\\\.\\\\d+)([eE][-+]?\\\\d+)?)\";this.BNR = \"\\\\b(0b[01]+)\";this.RSR = \"!|!=|!==|%|%=|&|&&|&=|\\\\*|\\\\*=|\\\\+|\\\\+=|,|\\\\.|-|-=|/|/=|:|;|<|<<|<<=|<=|=|==|===|>|>=|>>|>>=|>>>|>>>=|\\\\?|\\\\[|\\\\{|\\\\(|\\\\^|\\\\^=|\\\\||\\\\|=|\\\\|\\\\||~\";this.BE = {b: \"\\\\\\\\\\\\[\\\\s\\\\S]\",r: 0};this.ASM = {cN: \"string\",b: \"'\",e: \"'\",i: \"\\\\n\",c: [this.BE],r: 0};this.QSM = {cN: \"string\",b: '\"',e: '\"',i: \"\\\\n\",c: [this.BE],r: 0};this.CLCM = {cN: \"comment\",b: \"//\",e: \"$\"};this.CBLCLM = {cN: \"comment\",b: \"/\\\\*\",e: \"\\\\*/\"};this.HCM = {cN: \"comment\",b: \"#\",e: \"$\"};this.NM = {cN: \"number\",b: this.NR,r: 0};this.CNM = {cN: \"number\",b: this.CNR,r: 0};this.BNM = {cN: \"number\",b: this.BNR,r: 0};this.inherit = function(q, r) {var o = {};for (var p in q) {o[p] = q[p]}if (r) {for (var p in r) {o[p] = r[p]}}return o}}();hljs.LANGUAGES.php = function(a) {var e = {cN: \"variable\",b: \"\\\\$+[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*\"};var b = [a.inherit(a.ASM, {i: null}), a.inherit(a.QSM, {i: null}),{cN: \"string\",b: 'b\"',e: '\"',c: [a.BE]}, {cN: \"string\",b: \"b'\",e: \"'\",c: [a.BE]}];var c = [a.BNM, a.CNM];var d = {cN: \"title\",b: a.UIR};return {cI: true,k: \"and include_once list abstract global private echo interface as static endswitch array null if endwhile or const for endforeach self var while isset public protected exit foreach throw elseif include __FILE__ empty require_once do xor return implements parent clone use __CLASS__ __LINE__ else break print eval new catch __METHOD__ case exception php_user_filter default die require __FUNCTION__ enddeclare final try this switch continue endfor endif declare unset true false namespace trait goto instanceof insteadof __DIR__ __NAMESPACE__ __halt_compiler\",c: [a.CLCM, a.HCM,{cN: \"comment\",b: \"/\\\\*\",e: \"\\\\*/\",c: [{cN: \"phpdoc\",b: \"\\\\s@[A-Za-z]+\"}]}, {cN: \"comment\",eB: true,b: \"__halt_compiler.+?;\",eW: true}, {cN: \"string\",b: \"<<<['\\\"]?\\\\w+['\\\"]?$\",e: \"^\\\\w+;\",c: [a.BE]}, {cN: \"preprocessor\",b: \"<\\\\?php\",r: 10}, {cN: \"preprocessor\",b: \"\\\\?>\"},e,{cN: \"function\",bWK: true,e: \"{\",k: \"function\",i: \"\\\\$|\\\\[|%\",c: [d,{cN: \"params\",b: \"\\\\(\",e: \"\\\\)\",c: [\"self\", e, a.CBLCLM].concat(b).concat(c)}]}, {cN: \"class\",bWK: true,e: \"{\",k: \"class\",i: \"[:\\\\(\\\\$]\",c: [{bWK: true,eW: true,k: \"extends\",c: [d]},d]}, {b: \"=>\"}].concat(b).concat(c)}}(hljs);
		(function(e, t) {function n(e, t, n, r) {var o, i, u, l, a, c, s, f, p, d;if ((t ? t.ownerDocument || t : U) !== H && q(t), t = t || H, n = n || [], !e || \"string\" != typeof e) return n;if (1 !== (l = t.nodeType) && 9 !== l) return [];if (O && !r) {if (o = Ct.exec(e)) if (u = o[1]) {if (9 === l) {if (i = t.getElementById(u), !i || !i.parentNode) return n;if (i.id === u) return n.push(i), n} else if (t.ownerDocument && (i = t.ownerDocument.getElementById(u)) && j(t, i) && i.id === u) return n.push(i), n} else {if (o[2]) return ot.apply(n, t.getElementsByTagName(e)), n;if ((u = o[3]) && S.getElementsByClassName && t.getElementsByClassName) return ot.apply(n, t.getElementsByClassName(u)), n}if (S.qsa && (!k || !k.test(e))) {if (f = s = G, p = t, d = 9 === l && e, 1 === l && \"object\" !== t.nodeName.toLowerCase()) {for (c = g(e), (s = t.getAttribute(\"id\")) ? f = s.replace(Tt, \"\\\\$&\") : t.setAttribute(\"id\", f), f = \"[id='\" + f + \"'] \", a = c.length; a--;) c[a] = f + m(c[a]);p = mt.test(e) && t.parentNode || t, d = c.join(\",\")}if (d) try {return ot.apply(n, p.querySelectorAll(d)), n} catch (h) {} finally {s || t.removeAttribute(\"id\")}}}return w(e.replace(dt, \"$1\"), t, n, r)}function r(e) {return xt.test(e + \"\")}function o() {function e(n, r) {return t.push(n += \" \") > L.cacheLength && delete e[t.shift()], e[n] = r}var t = [];return e}function i(e) {return e[G] = !0, e}function u(e) {var t = H.createElement(\"div\");try {return !!e(t)} catch (n) {return !1} finally {t.parentNode && t.parentNode.removeChild(t), t = null}}function l(e, t, n) {e = e.split(\"|\");for (var r, o = e.length, i = n ? null : t; o--;)(r = L.attrHandle[e[o]]) && r !== t || (L.attrHandle[e[o]] = i)}function a(e, t) {var n = e.getAttributeNode(t);return n && n.specified ? n.value : e[t] === !0 ? t.toLowerCase() : null}function c(e, t) {return e.getAttribute(t, \"type\" === t.toLowerCase() ? 1 : 2)}function s(e) {return \"input\" === e.nodeName.toLowerCase() ? e.defaultValue : t}function f(e, t) {var n = t && e,r = n && 1 === e.nodeType && 1 === t.nodeType && (~t.sourceIndex || _) - (~e.sourceIndex || _);if (r) return r;if (n) for (; n = n.nextSibling;) if (n === t) return -1;return e ? 1 : -1}function p(e) {return function(t) {var n = t.nodeName.toLowerCase();return \"input\" === n && t.type === e}}function d(e) {return function(t) {var n = t.nodeName.toLowerCase();return (\"input\" === n || \"button\" === n) && t.type === e}}function h(e) {return i(function(t) {return t = +t, i(function(n, r) {for (var o, i = e([], n.length, t), u = i.length; u--;) n[o = i[u]] && (n[o] = !(r[o] = n[o]))})})}function g(e, t) {var r, o, i, u, l, a, c, s = K[e + \" \"];if (s) return t ? 0 : s.slice(0);for (l = e, a = [], c = L.preFilter; l;) {(!r || (o = ht.exec(l))) && (o && (l = l.slice(o[0].length) || l), a.push(i = [])), r = !1, (o = gt.exec(l)) && (r = o.shift(), i.push({value: r,type: o[0].replace(dt, \" \")}), l = l.slice(r.length));for (u in L.filter)!(o = bt[u].exec(l)) || c[u] && !(o = c[u](o)) || (r = o.shift(), i.push({value: r,type: u,matches: o}), l = l.slice(r.length));if (!r) break}return t ? l.length : l ? n.error(e) : K(e, a).slice(0)}function m(e) {for (var t = 0, n = e.length, r = \"\"; n > t; t++) r += e[t].value;return r}function y(e, t, n) {var r = t.dir,o = n && \"parentNode\" === r,i = X++;return t.first ?function(t, n, i) {for (; t = t[r];) if (1 === t.nodeType || o) return e(t, n, i)} : function(t, n, u) {var l, a, c, s = V + \" \" + i;if (u) {for (; t = t[r];) if ((1 === t.nodeType || o) && e(t, n, u)) return !0} else for (; t = t[r];) if (1 === t.nodeType || o) if (c = t[G] || (t[G] = {}), (a = c[r]) && a[0] === s) {if ((l = a[1]) === !0 || l === D) return l === !0} else if (a = c[r] = [s], a[1] = e(t, n, u) || D, a[1] === !0) return !0}}function v(e) {return e.length > 1 ?function(t, n, r) {for (var o = e.length; o--;) if (!e[o](t, n, r)) return !1;return !0} : e[0]}function N(e, t, n, r, o) {for (var i, u = [], l = 0, a = e.length, c = null != t; a > l; l++)(i = e[l]) && (!n || n(i, r, o)) && (u.push(i), c && t.push(l));return u}function b(e, t, n, r, o, u) {return r && !r[G] && (r = b(r)), o && !o[G] && (o = b(o, u)), i(function(i, u, l, a) {var c, s, f, p = [],d = [],h = u.length,g = i || E(t || \"*\", l.nodeType ? [l] : l, []),m = !e || !i && t ? g : N(g, p, e, l, a),y = n ? o || (i ? e : h || r) ? [] : u : m;if (n && n(m, y, l, a), r) for (c = N(y, d), r(c, [], l, a), s = c.length; s--;)(f = c[s]) && (y[d[s]] = !(m[d[s]] = f));if (i) {if (o || e) {if (o) {for (c = [], s = y.length; s--;)(f = y[s]) && c.push(m[s] = f);o(null, y = [], c, a)}for (s = y.length; s--;)(f = y[s]) && (c = o ? ut.call(i, f) : p[s]) > -1 && (i[c] = !(u[c] = f))}} else y = N(y === u ? y.splice(h, y.length) : y), o ? o(null, u, y, a) : ot.apply(u, y)})}function x(e) {for (var t, n, r, o = e.length, i = L.relative[e[0].type], u = i || L.relative[\" \"], l = i ? 1 : 0, a = y(function(e) {return e === t}, u, !0), c = y(function(e) {return ut.call(t, e) > -1}, u, !0), s = [function(e, n, r) {return !i && (r || n !== P) || ((t = n).nodeType ? a(e, n, r) : c(e, n, r))}]; o > l; l++) if (n = L.relative[e[l].type]) s = [y(v(s), n)];else {if (n = L.filter[e[l].type].apply(null, e[l].matches), n[G]) {for (r = ++l; o > r && !L.relative[e[r].type]; r++);return b(l > 1 && v(s), l > 1 && m(e.slice(0, l - 1).concat({value: \" \" === e[l - 2].type ? \"*\" : \"\"})).replace(dt, \"$1\"), n, r > l && x(e.slice(l, r)), o > r && x(e = e.slice(r)), o > r && m(e))}s.push(n)}return v(s)}function C(e, t) {var r = 0,o = t.length > 0,u = e.length > 0,l = function(i, l, a, c, s) {var f, p, d, h = [],g = 0,m = \"0\",y = i && [],v = null != s,b = P,x = i || u && L.find.TAG(\"*\", s && l.parentNode || l),C = V += null == b ? 1 : Math.random() || .1;for (v && (P = l !== H && l, D = r); null != (f = x[m]); m++) {if (u && f) {for (p = 0; d = e[p++];) if (d(f, l, a)) {c.push(f);break}v && (V = C, D = ++r)}o && ((f = !d && f) && g--, i && y.push(f))}if (g += m, o && m !== g) {for (p = 0; d = t[p++];) d(y, h, l, a);if (i) {if (g > 0) for (; m--;) y[m] || h[m] || (h[m] = nt.call(c));h = N(h)}ot.apply(c, h), v && !i && h.length > 0 && g + t.length > 1 && n.uniqueSort(c)}return v && (V = C, P = b), y};return o ? i(l) : l}function E(e, t, r) {for (var o = 0, i = t.length; i > o; o++) n(e, t[o], r);return r}function w(e, t, n, r) {var o, i, u, l, a, c = g(e);if (!r && 1 === c.length) {if (i = c[0] = c[0].slice(0), i.length > 2 && \"ID\" === (u = i[0]).type && S.getById && 9 === t.nodeType && O && L.relative[i[1].type]) {if (t = (L.find.ID(u.matches[0].replace(At, St), t) || [])[0], !t) return n;e = e.slice(i.shift().value.length)}for (o = bt.needsContext.test(e) ? 0 : i.length; o-- && (u = i[o], !L.relative[l = u.type]);) if ((a = L.find[l]) && (r = a(u.matches[0].replace(At, St), mt.test(i[0].type) && t.parentNode || t))) {if (i.splice(o, 1), e = r.length && m(i), !e) return ot.apply(n, r), n;break}}return R(e, c)(r, t, !O, n, mt.test(e)), n}function T() {}var A, S, D, L, B, I, R, P, $, q, H, M, O, k, F, z, j, G = \"sizzle\" + -new Date,U = e.document,V = 0,X = 0,J = o(),K = o(),Q = o(),W = !1,Y = function() {return 0},Z = typeof t,_ = 1 << 31,et = {}.hasOwnProperty,tt = [],nt = tt.pop,rt = tt.push,ot = tt.push,it = tt.slice,ut = tt.indexOf ||function(e) {for (var t = 0, n = this.length; n > t; t++) if (this[t] === e) return t;return -1}, lt = \"checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped\", at = \"[\\\\x20\\\\t\\\\r\\\\n\\\\f]\", ct = \"(?:\\\\\\\\.|[\\\\w-]|[^\\\\x00-\\\\xa0])+\", st = ct.replace(\"w\", \"w#\"),ft = \"\\\\[\" + at + \"*(\" + ct + \")\" + at + \"*(?:([*^$|!~]?=)\" + at + \"*(?:(['\\\"])((?:\\\\.|[^\\\\])*?)\\\\3|(\" + st + \")|)|)\" + at + \"*\\\\]\", pt = \":(\" + ct + \")(?:\\\\(((['\\\"])((?:\\\\\\\\.|[^\\\\\\\\])*?)\\\\3|((?:\\\\\\\\.|[^\\\\\\\\()[\\\\]]|\" + ft.replace(3, 8) + \")*)|.*)\\\\)|)\", dt = RegExp(\"^\" + at + \"+|((?:^|[^\\\\\\\\])(?:\\\\\\\\.)*)\" + at + \"+$\", \"g\"), ht = RegExp(\"^\" + at + \"*,\" + at + \"*\"), gt = RegExp(\"^\" + at + \"*([>+~]|\" + at + \")\" + at + \"*\"), mt = RegExp(at + \"*[+~]\"), yt = RegExp(\"=\" + at + \"*([^\\]'\\\"]*)\" + at + \"*\\\\]\", \"g\"), vt = RegExp(pt), Nt = RegExp(\"^\" + st + \"$\"), bt = {ID: RegExp(\"^#(\" + ct + \")\"),CLASS: RegExp(\"^\\\\.(\" + ct + \")\"),TAG: RegExp(\"^(\" + ct.replace(\"w\", \"w*\") + \")\"),ATTR: RegExp(\"^\" + ft),PSEUDO: RegExp(\"^\" + pt),CHILD: RegExp(\"^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\\\(\" + at + \"*(even|odd|(([+-]|)(\\\\d*)n|)\" + at + \"*(?:([+-]|)\" + at + \"*(\\\\d+)|))\" + at + \"*\\\\)|)\", \"i\"),bool: RegExp(\"^(?:\" + lt + \")$\", \"i\"),needsContext: RegExp(\"^\" + at + \"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\\\(\" + at + \"*((?:-\\\\d)?\\\\d*)\" + at + \"*\\\\)|)(?=[^-]|$)\", \"i\")}, xt = /^[^{]+\\{\\s*\\[native \\w/, Ct = /^(?:#([\\w-]+)|(\\w+)|\\.([\\w-]+))$/,Et = /^(?:input|select|textarea|button)$/i, wt = /^h\\d$/i,Tt = /'|\\\\/g, At = RegExp(\"\\\\\\\\([\\\\da-f]{1,6}\" + at + \"?|(\" + at + \")|.)\", \"ig\"), St = function(e, t, n) {var r = \"0x\" + t - 65536;return r !== r || n ? t : 0 > r ? String.fromCharCode(r + 65536) : String.fromCharCode(55296 | r >> 10, 56320 | 1023 & r)};try {ot.apply(tt = it.call(U.childNodes), U.childNodes), tt[U.childNodes.length].nodeType} catch (Dt) {ot = {apply: tt.length ? function(e, t) {rt.apply(e, it.call(t))} : function(e, t) {for (var n = e.length, r = 0; e[n++] = t[r++];);e.length = n - 1}}}I = n.isXML = function(e) {var t = e && (e.ownerDocument || e).documentElement;return t ? \"HTML\" !== t.nodeName : !1}, S = n.support = {}, q = n.setDocument = function(e) {var n = e ? e.ownerDocument || e : U;return n !== H && 9 === n.nodeType && n.documentElement ? (H = n, M = n.documentElement, O = !I(n), S.attributes = u(function(e) {return e.innerHTML = \"<a href='#'></a>\", l(\"type|href|height|width\", c, \"#\" === e.firstChild.getAttribute(\"href\")), l(lt, a, null == e.getAttribute(\"disabled\")), e.className = \"i\", !e.getAttribute(\"className\")}), S.input = u(function(e) {return e.innerHTML = \"<input>\", e.firstChild.setAttribute(\"value\", \"\"), \"\" === e.firstChild.getAttribute(\"value\")}), l(\"value\", s, S.attributes && S.input), S.getElementsByTagName = u(function(e) {return e.appendChild(n.createComment(\"\")), !e.getElementsByTagName(\"*\").length}), S.getElementsByClassName = u(function(e) {return e.innerHTML = \"<div class='a'></div><div class='a i'></div>\", e.firstChild.className = \"i\", 2 === e.getElementsByClassName(\"i\").length}), S.getById = u(function(e) {return M.appendChild(e).id = G, !n.getElementsByName || !n.getElementsByName(G).length}), S.getById ? (L.find.ID = function(e, t) {if (typeof t.getElementById !== Z && O) {var n = t.getElementById(e);return n && n.parentNode ? [n] : []}}, L.filter.ID = function(e) {var t = e.replace(At, St);return function(e) {return e.getAttribute(\"id\") === t}}) : (delete L.find.ID, L.filter.ID = function(e) {var t = e.replace(At, St);return function(e) {var n = typeof e.getAttributeNode !== Z && e.getAttributeNode(\"id\");return n && n.value === t}}), L.find.TAG = S.getElementsByTagName ?function(e, n) {return typeof n.getElementsByTagName !== Z ? n.getElementsByTagName(e) : t} : function(e, t) {var n, r = [],o = 0,i = t.getElementsByTagName(e);if (\"*\" === e) {for (; n = i[o++];) 1 === n.nodeType && r.push(n);return r}return i}, L.find.CLASS = S.getElementsByClassName &&function(e, n) {return typeof n.getElementsByClassName !== Z && O ? n.getElementsByClassName(e) : t}, F = [], k = [], (S.qsa = r(n.querySelectorAll)) && (u(function(e) {e.innerHTML = \"<select><option selected=''></option></select>\", e.querySelectorAll(\"[selected]\").length || k.push(\"\\\\[\" + at + \"*(?:value|\" + lt + \")\"),		e.querySelectorAll(\":checked\").length || k.push(\":checked\")}), u(function(e) {var t = n.createElement(\"input\");t.setAttribute(\"type\", \"hidden\"), e.appendChild(t).setAttribute(\"t\", \"\"), e.querySelectorAll(\"[t^='']\").length && k.push(\"[*^$]=\" + at + \"*(?:''|\\\"\\\")\"), e.querySelectorAll(\":enabled\").length || k.push(\":enabled\", \":disabled\"), e.querySelectorAll(\"*,:x\"), k.push(\",.*:\")})), (S.matchesSelector = r(z = M.webkitMatchesSelector || M.mozMatchesSelector || M.oMatchesSelector || M.msMatchesSelector)) && u(function(e) {S.disconnectedMatch = z.call(e, \"div\"), z.call(e, \"[s!='']:x\"), F.push(\"!=\", pt)}), k = k.length && RegExp(k.join(\"|\")), F = F.length && RegExp(F.join(\"|\")), j = r(M.contains) || M.compareDocumentPosition ?function(e, t) {var n = 9 === e.nodeType ? e.documentElement : e,r = t && t.parentNode;return e === r || !(!r || 1 !== r.nodeType || !(n.contains ? n.contains(r) : e.compareDocumentPosition && 16 & e.compareDocumentPosition(r)))} : function(e, t) {if (t) for (; t = t.parentNode;) if (t === e) return !0;return !1}, S.sortDetached = u(function(e) {return 1 & e.compareDocumentPosition(n.createElement(\"div\"))}), Y = M.compareDocumentPosition ?function(e, t) {if (e === t) return W = !0, 0;var r = t.compareDocumentPosition && e.compareDocumentPosition && e.compareDocumentPosition(t);return r ? 1 & r || !S.sortDetached && t.compareDocumentPosition(e) === r ? e === n || j(U, e) ? -1 : t === n || j(U, t) ? 1 : $ ? ut.call($, e) - ut.call($, t) : 0 : 4 & r ? -1 : 1 : e.compareDocumentPosition ? -1 : 1} : function(e, t) {var r, o = 0,i = e.parentNode,u = t.parentNode,l = [e],a = [t];if (e === t) return W = !0, 0;if (!i || !u) return e === n ? -1 : t === n ? 1 : i ? -1 : u ? 1 : $ ? ut.call($, e) - ut.call($, t) : 0;if (i === u) return f(e, t);for (r = e; r = r.parentNode;) l.unshift(r);for (r = t; r = r.parentNode;) a.unshift(r);for (; l[o] === a[o];) o++;return o ? f(l[o], a[o]) : l[o] === U ? -1 : a[o] === U ? 1 : 0}, n) : H}, n.matches = function(e, t) {return n(e, null, null, t)}, n.matchesSelector = function(e, t) {if ((e.ownerDocument || e) !== H && q(e), t = t.replace(yt, \"='$1']\"), !(!S.matchesSelector || !O || F && F.test(t) || k && k.test(t))) try {var r = z.call(e, t);if (r || S.disconnectedMatch || e.document && 11 !== e.document.nodeType) return r} catch (o) {}return n(t, H, null, [e]).length > 0}, n.contains = function(e, t) {return (e.ownerDocument || e) !== H && q(e), j(e, t)}, n.attr = function(e, n) {(e.ownerDocument || e) !== H && q(e);var r = L.attrHandle[n.toLowerCase()],o = r && et.call(L.attrHandle, n.toLowerCase()) ? r(e, n, !O) : t;return o === t ? S.attributes || !O ? e.getAttribute(n) : (o = e.getAttributeNode(n)) && o.specified ? o.value : null : o}, n.error = function(e) {throw Error(\"Syntax error, unrecognized expression: \" + e)}, n.uniqueSort = function(e) {var t, n = [],r = 0,o = 0;if (W = !S.detectDuplicates, $ = !S.sortStable && e.slice(0), e.sort(Y), W) {for (; t = e[o++];) t === e[o] && (r = n.push(o));for (; r--;) e.splice(n[r], 1)}return e}, B = n.getText = function(e) {var t, n = \"\",r = 0,o = e.nodeType;if (o) {if (1 === o || 9 === o || 11 === o) {if (\"string\" == typeof e.textContent) return e.textContent;for (e = e.firstChild; e; e = e.nextSibling) n += B(e)} else if (3 === o || 4 === o) return e.nodeValue} else for (; t = e[r]; r++) n += B(t);return n}, L = n.selectors = {cacheLength: 50,createPseudo: i,match: bt,attrHandle: {},find: {},relative: {\">\": {dir: \"parentNode\",first: !0},\" \": {dir: \"parentNode\"},\"+\": {dir: \"previousSibling\",first: !0},\"~\": {dir: \"previousSibling\"}},preFilter: {ATTR: function(e) {return e[1] = e[1].replace(At, St), e[3] = (e[4] || e[5] || \"\").replace(At, St), \"~=\" === e[2] && (e[3] = \" \" + e[3] + \" \"), e.slice(0, 4)},CHILD: function(e) {return e[1] = e[1].toLowerCase(), \"nth\" === e[1].slice(0, 3) ? (e[3] || n.error(e[0]), e[4] = +(e[4] ? e[5] + (e[6] || 1) : 2 * (\"even\" === e[3] || \"odd\" === e[3])),		e[5] = +(e[7] + e[8] || \"odd\" === e[3])) : e[3] && n.error(e[0]), e},PSEUDO: function(e) {var n, r = !e[5] && e[2];return bt.CHILD.test(e[0]) ? null : (e[3] && e[4] !== t ? e[2] = e[4] : r && vt.test(r) && (n = g(r, !0)) && (n = r.indexOf(\")\", r.length - n) - r.length) && (e[0] = e[0].slice(0, n), e[2] = r.slice(0, n)), e.slice(0, 3))}},filter: {TAG: function(e) {var t = e.replace(At, St).toLowerCase();return \"*\" === e ?function() {return !0} : function(e) {return e.nodeName && e.nodeName.toLowerCase() === t}},CLASS: function(e) {var t = J[e + \" \"];return t || (t = RegExp(\"(^|\" + at + \")\" + e + \"(\" + at + \"|$)\")) && J(e, function(e) {return t.test(\"string\" == typeof e.className && e.className || typeof e.getAttribute !== Z && e.getAttribute(\"class\") || \"\")})},ATTR: function(e, t, r) {return function(o) {var i = n.attr(o, e);return null == i ? \"!=\" === t : t ? (i += \"\", \"=\" === t ? i === r : \"!=\" === t ? i !== r : \"^=\" === t ? r && 0 === i.indexOf(r) : \"*=\" === t ? r && i.indexOf(r) > -1 : \"$=\" === t ? r && i.slice(-r.length) === r : \"~=\" === t ? (\" \" + i + \" \").indexOf(r) > -1 : \"|=\" === t ? i === r || i.slice(0, r.length + 1) === r + \"-\" : !1) : !0}},CHILD: function(e, t, n, r, o) {var i = \"nth\" !== e.slice(0, 3),u = \"last\" !== e.slice(-4),l = \"of-type\" === t;return 1 === r && 0 === o ?function(e) {return !!e.parentNode} : function(t, n, a) {var c, s, f, p, d, h, g = i !== u ? \"nextSibling\" : \"previousSibling\",m = t.parentNode,y = l && t.nodeName.toLowerCase(),v = !a && !l;if (m) {if (i) {for (; g;) {for (f = t; f = f[g];) if (l ? f.nodeName.toLowerCase() === y : 1 === f.nodeType) return !1;h = g = \"only\" === e && !h && \"nextSibling\"}return !0}if (h = [u ? m.firstChild : m.lastChild], u && v) {for (s = m[G] || (m[G] = {}), c = s[e] || [], d = c[0] === V && c[1], p = c[0] === V && c[2], f = d && m.childNodes[d]; f = ++d && f && f[g] || (p = d = 0) || h.pop();) if (1 === f.nodeType && ++p && f === t) {s[e] = [V, d, p];break}} else if (v && (c = (t[G] || (t[G] = {}))[e]) && c[0] === V) p = c[1];else for (;(f = ++d && f && f[g] || (p = d = 0) || h.pop()) && ((l ? f.nodeName.toLowerCase() !== y : 1 !== f.nodeType) || !++p || (v && ((f[G] || (f[G] = {}))[e] = [V, p]), f !== t)););return p -= o, p === r || 0 === p % r && p / r >= 0}}},PSEUDO: function(e, t) {var r, o = L.pseudos[e] || L.setFilters[e.toLowerCase()] || n.error(\"unsupported pseudo: \" + e);return o[G] ? o(t) : o.length > 1 ? (r = [e, e, \"\", t], L.setFilters.hasOwnProperty(e.toLowerCase()) ? i(function(e, n) {for (var r, i = o(e, t), u = i.length; u--;) r = ut.call(e, i[u]), e[r] = !(n[r] = i[u])}) : function(e) {return o(e, 0, r)}) : o}},pseudos: {not: i(function(e) {var t = [],n = [],r = R(e.replace(dt, \"$1\"));return r[G] ? i(function(e, t, n, o) {for (var i, u = r(e, null, o, []), l = e.length; l--;)(i = u[l]) && (e[l] = !(t[l] = i))}) : function(e, o, i) {return t[0] = e, r(t, null, i, n), !n.pop()}}),has: i(function(e) {return function(t) {return n(e, t).length > 0}}),contains: i(function(e) {return function(t) {return (t.textContent || t.innerText || B(t)).indexOf(e) > -1}}),lang: i(function(e) {return Nt.test(e || \"\") || n.error(\"unsupported lang: \" + e), e = e.replace(At, St).toLowerCase(), function(t) {var n;do if (n = O ? t.lang : t.getAttribute(\"xml:lang\") || t.getAttribute(\"lang\")) return n = n.toLowerCase(), n === e || 0 === n.indexOf(e + \"-\");while ((t = t.parentNode) && 1 === t.nodeType);return !1}}),target: function(t) {var n = e.location && e.location.hash;return n && n.slice(1) === t.id},root: function(e) {return e === M},focus: function(e) {return e === H.activeElement && (!H.hasFocus || H.hasFocus()) && !! (e.type || e.href || ~e.tabIndex)},enabled: function(e) {return e.disabled === !1},disabled: function(e) {return e.disabled === !0},checked: function(e) {var t = e.nodeName.toLowerCase();return \"input\" === t && !! e.checked || \"option\" === t && !! e.selected},selected: function(e) {return e.parentNode && e.parentNode.selectedIndex, e.selected === !0},empty: function(e) {for (e = e.firstChild; e; e = e.nextSibling) if (e.nodeName > \"@\" || 3 === e.nodeType || 4 === e.nodeType) return !1;return !0},parent: function(e) {return !L.pseudos.empty(e)},header: function(e) {return wt.test(e.nodeName)},input: function(e) {return Et.test(e.nodeName)},button: function(e) {var t = e.nodeName.toLowerCase();return \"input\" === t && \"button\" === e.type || \"button\" === t},text: function(e) {var t;return \"input\" === e.nodeName.toLowerCase() && \"text\" === e.type && (null == (t = e.getAttribute(\"type\")) || t.toLowerCase() === e.type)},first: h(function() {return [0]}),last: h(function(e, t) {return [t - 1]}),eq: h(function(e, t, n) {return [0 > n ? n + t : n]}),even: h(function(e, t) {for (var n = 0; t > n; n += 2) e.push(n);return e}),odd: h(function(e, t) {for (var n = 1; t > n; n += 2) e.push(n);return e}),lt: h(function(e, t, n) {for (var r = 0 > n ? n + t : n; --r >= 0;) e.push(r);return e}),gt: h(function(e, t, n) {for (var r = 0 > n ? n + t : n; t > ++r;) e.push(r);return e})}};for (A in {radio: !0,checkbox: !0,file: !0,password: !0,image: !0}) L.pseudos[A] = p(A);for (A in {submit: !0,reset: !0}) L.pseudos[A] = d(A);R = n.compile = function(e, t) {var n, r = [],o = [],i = Q[e + \" \"];if (!i) {for (t || (t = g(e)), n = t.length; n--;) i = x(t[n]), i[G] ? r.push(i) : o.push(i);i = Q(e, C(o, r))}return i}, L.pseudos.nth = L.pseudos.eq, T.prototype = L.filters = L.pseudos, L.setFilters = new T, S.sortStable = G.split(\"\").sort(Y).join(\"\") === G, q(), [0, 0].sort(Y), S.detectDuplicates = W, \"function\" == typeof define && define.amd ? define(function() {return n}) : e.Sizzle = n})(window);window.onload = function() {var codeBlocks = Sizzle('pre'),callStackItems = Sizzle('.call-stack-item');for (var i = 0, imax = codeBlocks.length; i < imax; ++i) {hljs.highlightBlock(codeBlocks[i], '    ');}document.onmousemove = function(e) {var event = e || window.event,clientY = event.clientY,lineFound = false,hoverLines = Sizzle('.hover-line');for (var i = 0, imax = codeBlocks.length - 1; i < imax; ++i) {var lines = codeBlocks[i].getClientRects();for (var j = 0, jmax = lines.length; j < jmax; ++j) {if (clientY >= lines[j].top && clientY <= lines[j].bottom) {lineFound = true;break;}}if (lineFound) {break;}}for (var k = 0, kmax = hoverLines.length; k < kmax; ++k) {hoverLines[k].className = 'hover-line';}if (lineFound) {var line = Sizzle('.call-stack-item:eq(' + i + ') .hover-line:eq(' + j + ')')[0];if (line) {line.className = 'hover-line hover';}}};var refreshCallStackItemCode = function(callStackItem) {if (!Sizzle('pre', callStackItem)[0]) {return;}var top = callStackItem.offsetTop - window.pageYOffset,lines = Sizzle('pre', callStackItem)[0].getClientRects(),lineNumbers = Sizzle('.lines-item', callStackItem),errorLine = Sizzle('.error-line', callStackItem)[0],hoverLines = Sizzle('.hover-line', callStackItem);for (var i = 0, imax = lines.length; i < imax; ++i) {if (!lineNumbers[i]) {continue;}lineNumbers[i].style.top = parseInt(lines[i].top - top) + 'px';hoverLines[i].style.top = parseInt(lines[i].top - top - 3) + 'px';hoverLines[i].style.height = parseInt(lines[i].bottom - lines[i].top + 6) + 'px';if (parseInt(callStackItem.getAttribute('data-line')) == i) {errorLine.style.top = parseInt(lines[i].top - top - 3) + 'px';errorLine.style.height = parseInt(lines[i].bottom - lines[i].top + 6) + 'px';}}};for (var i = 0, imax = callStackItems.length; i < imax; ++i) {refreshCallStackItemCode(callStackItems[i]);Sizzle('.element-wrap', callStackItems[i])[0].addEventListener('click', function() {var callStackItem = this.parentNode,code = Sizzle('.code-wrap', callStackItem)[0];code.style.display = window.getComputedStyle(code).display == 'block' ? 'none' : 'block';refreshCallStackItemCode(callStackItem);});}};</script></div></body></html>";
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
		return htmlspecialchars ( $text, ENT_QUOTES, Kernel::getDi()->charset );
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
			if ($exception instanceof \Leaps\Core\Exception) {
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
		if ($exception instanceof \Leaps\Core\Exception || $exception instanceof \Leaps\Core\InvalidCallException || $exception instanceof \Leaps\Core\InvalidParamException || $exception instanceof \Leaps\Core\UnknownMethodException) {
			return $exception->getName ();
		}
		return null;
	}
}