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

use Leaps\Logger;
use Leaps\Di\ContainerInterface;
use Leaps\Core\UnknownClassException;
use Leaps\Core\InvalidConfigException;
use Leaps\Core\InvalidParamException;
use Leaps\Di\Exception as DiException;

class Kernel
{

	/**
	 *
	 * @var string constant used for when in testing mode
	 */
	const TEST = 'test';

	/**
	 *
	 * @var string constant used for when in development
	 */
	const DEVELOPMENT = 'development';

	/**
	 *
	 * @var string constant used for when in production
	 */
	const PRODUCTION = 'production';

	/**
	 *
	 * @var string constant used for when testing the app in a staging env.
	 */
	const STAGING = 'staging';

	/**
	 *
	 * @var string The Leaps environment
	 */
	public static $env = Kernel::PRODUCTION;
	public static $loadedModules = [ ];

	/**
	 * 核心实例DI
	 *
	 * @var Leaps\Di\ContainerInteface
	 */
	public static $app;

	/**
	 * classMap
	 *
	 * @var array
	 */
	private static $classMap = [ ];
	private static $aliases = [ ];

	/**
	 * 自动装载器
	 *
	 * @param string $className 类的完全限定名称
	 */
	public static function autoload($className)
	{
		if (isset ( static::$classMap [$className] )) {
			$classFile = static::$classMap [$className];
			if ($classFile [0] === '@') {
				$classFile = static::getAlias ( $classFile );
			}
		} elseif (strpos ( $className, '\\' ) !== false) {
			$classFile = static::getAlias ( '@' . str_replace ( '\\', '/', $className ) . '.php', false );
			if ($classFile === false || ! is_file ( $classFile )) {

				return;
			}
		} else {
			return;
		}
		include ($classFile);
		if (static::$env == static::DEVELOPMENT && ! class_exists ( $className, false ) && ! interface_exists ( $className, false ) && ! trait_exists ( $className, false )) {
			throw new UnknownClassException ( "Unable to find '$className' in file: $classFile. Namespace missing?" );
		}
	}

	/**
	 * 获取classmap
	 *
	 * @return multitype:
	 */
	public static function getClassMap($className = '')
	{
		if ('' === $className) {
			return static::$classMap;
		} elseif (isset ( static::$classMap [$className] )) {
			return static::$classMap [$className];
		} else {
			return null;
		}
	}

	/**
	 * 注册classmap
	 *
	 * @param array $classMap 类文件名映射
	 */
	public static function addClassMap($className, $map = '')
	{
		if (is_array ( $className )) {
			static::$classMap = array_merge ( static::$classMap, $className );
		} else {
			static::$classMap [$className] = $map;
		}
	}

	/**
	 * 注册一个路径别名。
	 *
	 * @throws InvalidParamException 如果路径是无效的别名
	 * @see getAlias()
	 */
	public static function setAlias($alias, $path)
	{
		if (strncmp ( $alias, '@', 1 )) {
			$alias = '@' . $alias;
		}
		$pos = strpos ( $alias, '/' );
		$root = $pos === false ? $alias : substr ( $alias, 0, $pos );
		if ($path !== null) {
			$path = strncmp ( $path, '@', 1 ) ? rtrim ( $path, '\\/' ) : static::getAlias ( $path );
			if (! isset ( static::$aliases [$root] )) {
				if ($pos === false) {
					static::$aliases [$root] = $path;
				} else {
					static::$aliases [$root] = [
							$alias => $path
					];
				}
			} elseif (is_string ( static::$aliases [$root] )) {
				if ($pos === false) {
					static::$aliases [$root] = $path;
				} else {
					static::$aliases [$root] = [
							$alias => $path,
							$root => static::$aliases [$root]
					];
				}
			} else {
				static::$aliases [$root] [$alias] = $path;
				krsort ( static::$aliases [$root] );
			}
		} elseif (isset ( static::$aliases [$root] )) {
			if (is_array ( static::$aliases [$root] )) {
				unset ( static::$aliases [$root] [$alias] );
			} elseif ($pos === false) {
				unset ( static::$aliases [$root] );
			}
		}
	}

	/**
	 * 将路径别名转化为实际的路径。
	 *
	 * @param string $alias 要翻译的别名
	 * @param boolean $throwException 是否抛出异常,如果给定的别名是无效的。 如果这是错误和无效的别名,该方法将返回错误。
	 * @return string boolean
	 * @throws InvalidParamException 如果别名无效$throwException为true
	 * @see setAlias()
	 */
	public static function getAlias($alias, $throwException = true)
	{
		if (strncmp ( $alias, '@', 1 )) { // 不是一个别名
			return $alias;
		}
		$pos = strpos ( $alias, '/' );
		$root = $pos === false ? $alias : substr ( $alias, 0, $pos );
		if (isset ( static::$aliases [$root] )) {
			if (is_string ( static::$aliases [$root] )) {
				return $pos === false ? static::$aliases [$root] : static::$aliases [$root] . substr ( $alias, $pos );
			} else {
				foreach ( static::$aliases [$root] as $name => $path ) {
					if (strpos ( $alias . '/', $name . '/' ) === 0) {
						return $path . substr ( $alias, strlen ( $name ) );
					}
				}
			}
		}
		if ($throwException) {
			throw new InvalidParamException ( "Invalid path alias: $alias" );
		} else {
			return false;
		}
	}

	/**
	 * 返回已注册的跟别名。
	 * 如果别名匹配多个根将返回别名最长的一个。
	 *
	 * @param string $alias 别名
	 * @return string/boolean 跟别名或false
	 */
	public static function getRootAlias($alias)
	{
		$pos = strpos ( $alias, '/' );
		$root = $pos === false ? $alias : substr ( $alias, 0, $pos );
		if (isset ( static::$aliases [$root] )) {
			if (is_string ( static::$aliases [$root] )) {
				return $root;
			} else {
				foreach ( static::$aliases [$root] as $name => $path ) {
					if (strpos ( $alias . '/', $name . '/' ) === 0) {
						return $name;
					}
				}
			}
		}
		return false;
	}

	/**
	 * 创建新的对象
	 * 直接传类名来创建对象
	 * \Leaps\Kernel::createObject('Leaps\HttpClient\Adapter\Curl');
	 * //直接传匿名方法来创建支持参数
	 * \Leaps\Kernel::createObject(function(){
	 * return new \Leaps\HttpClient\Adapter\Curl();
	 * },[]);
	 * 使用类构造方法来创建对象
	 * \Leaps\Kernel::createObject(['className'=>'Leaps\HttpClient\Adapter\Curl','hostIp'=>'127.0.0.1']);
	 *
	 * @param string/array $definition
	 * @param array $parameters
	 * @throws InvalidConfigException
	 * @return object
	 */
	public static function createObject($definition, $parameters = [], $throwException = true)
	{
		$instance = null;
		if (is_string ( $definition )) {
			if (class_exists ( $definition )) {
				$reflection = new \ReflectionClass ( $definition );
				if (is_array ( $parameters )) {
					$instance = $reflection->newInstanceArgs ( $parameters );
				} else {
					$instance = $reflection->newInstance ();
				}
			}
		} elseif (is_object ( $definition )) {
			if ($definition instanceof \Closure) {
				if (is_array ( $definition )) {
					$instance = call_user_func_array ( $definition, $parameters );
				} else {
					$instance = call_user_func ( $definition );
				}
			} else {
				$instance = $definition;
			}
		} elseif (is_array ( $definition ) && isset ( $definition ['className'] )) {
			$className = $definition ['className'];
			unset ( $definition ['className'] );
			$reflection = new \ReflectionClass ( $className );
			if (! empty ( $parameters )) { // 模块初始化
				$parameters [] = $definition;
				$instance = $reflection->newInstanceArgs ( $parameters );
			} else {
				if (empty ( $definition )) {
					$instance = $reflection->newInstance ();
				} else {
					$instance = $reflection->newInstanceArgs ( [
							$definition
					] );
				}
			}
		} elseif (is_array ( $definition ) && $throwException) {
			throw new InvalidConfigException ( 'Object configuration must be an array containing a "className" element.' );
		} elseif ($throwException) {
			throw new InvalidConfigException ( "Unsupported configuration type: " . gettype ( $definition ) );
		}
		/**
		 * Pass the DI itself if the instance implements \Leaps\Di\InjectionAwareInterface
		 */
		if (is_object ( $instance ) && method_exists ( $instance, "setDI" )) {
			$instance->setDI ( self::$app );
		}
		return $instance;
	}

	/**
	 * Returns a string representing the current version of the Leaps framework.
	 *
	 * @return string the version of Leaps framework
	 */
	public static function getVersion()
	{
		return Version::get ();
	}

	/**
	 * Takes a value and checks if it is a Closure or not, if it is it
	 * will return the result of the closure, if not, it will simply return the
	 * value.
	 *
	 * @param mixed $var The value to get
	 * @return mixed
	 */
	public static function value($var)
	{
		return ($var instanceof \Closure) ? $var () : $var;
	}

	/**
	 * Get the "class basename" of a class or object.
	 * The basename is considered to be the name of the
	 * class minus all namespaces.
	 *
	 * @param object|string $class
	 * @return string
	 */
	public static function classBasename($class)
	{
		if (is_object ( $class ))
			$class = get_class ( $class );
		return basename ( str_replace ( '\\', '/', $class ) );
	}

	/**
	 * 配置一个对象的初始属性值。
	 *
	 * @param object $object 对象配置
	 * @param array $properties 属性初始值给定的名称-值对。
	 */
	public static function configure($object, $properties)
	{
		foreach ( $properties as $name => $value ) {
			$object->$name = $value;
		}
	}

	/**
	 * 返回对象的公共成员变量。
	 *
	 * @param object $object 处理的对象
	 * @return array 对象的公共成员变量
	 */
	public static function getObjectVars($object)
	{
		return get_object_vars ( $object );
	}

	/**
	 * Logs a trace message.
	 * Trace messages are logged mainly for development purpose to see
	 * the execution work flow of some code.
	 *
	 * @param string $message the message to be logged.
	 * @param string $category the category of the message.
	 */
	public static function trace($message, $category = 'application')
	{
		if (static::$env == static::DEVELOPMENT) {
			static::getShared ( 'log' )->log ( $message, Logger::LEVEL_TRACE, $category );
		}
	}

	/**
	 * Logs an error message.
	 * An error message is typically logged when an unrecoverable error occurs
	 * during the execution of an application.
	 *
	 * @param string $message the message to be logged.
	 * @param string $category the category of the message.
	 */
	public static function error($message, $category = 'application')
	{
		static::getShared ( 'log' )->log ( $message, Logger::LEVEL_ERROR, $category );
	}

	/**
	 * Logs a warning message.
	 * A warning message is typically logged when an error occurs while the execution
	 * can still continue.
	 *
	 * @param string $message the message to be logged.
	 * @param string $category the category of the message.
	 */
	public static function warning($message, $category = 'application')
	{
		static::getShared ( 'log' )->log ( $message, Logger::LEVEL_WARNING, $category );
	}

	/**
	 * Logs an informative message.
	 * An informative message is typically logged by an application to keep record of
	 * something important (e.g. an administrator logs in).
	 *
	 * @param string $message the message to be logged.
	 * @param string $category the category of the message.
	 */
	public static function info($message, $category = 'application')
	{
		static::getShared ( 'log' )->log ( $message, Logger::LEVEL_INFO, $category );
	}

	/**
	 * 标志着一个代码块的开始分析。
	 * This has to be matched with a call to [[endProfile]] with the same category name.
	 * The begin- and end- calls must also be properly nested. For example,
	 *
	 * ~~~
	 * \Leaps::beginProfile('block1');
	 * // some code to be profiled
	 * \Leaps::beginProfile('block2');
	 * // some other code to be profiled
	 * \Leaps::endProfile('block2');
	 * \Leaps::endProfile('block1');
	 * ~~~
	 *
	 * @param string $token token for the code block
	 * @param string $category the category of this log message
	 * @see endProfile()
	 */
	public static function beginProfile($token, $category = 'application')
	{
		static::getShared ( 'log' )->log ( $token, Logger::LEVEL_PROFILE_BEGIN, $category );
	}

	/**
	 * 为分析标志着一个代码块的结束。
	 * This has to be matched with a previous call to [[beginProfile]] with the same category name.
	 *
	 * @param string $token token for the code block
	 * @param string $category the category of this log message
	 * @see beginProfile()
	 */
	public static function endProfile($token, $category = 'application')
	{
		static::getShared ( 'log' )->log ( $token, Logger::LEVEL_PROFILE_END, $category );
	}

	/**
	 * Returns an HTML hyperlink that can be displayed on your Web page showing "Powered by Leaps Framework" information.
	 *
	 * @return string an HTML hyperlink that can be displayed on your Web page showing "Powered by Leaps Framework" information
	 */
	public static function powered()
	{
		return 'Powered by <a href="http://www.tintsoft.com/" rel="external">Leaps Framework</a>';
	}

	/**
	 * 通过配置文件解析服务配置
	 *
	 * @param string name
	 * @param array parameters
	 * @return mixed
	 */
	public static function get($name, $parameters = null)
	{
		return static::getDi()->get ( $name, $parameters = null );
	}

	public static function getShared($name, $parameters = null)
	{
		return static::getDi()->getShared ( $name, $parameters = null );
	}

	/**
	 * 获取DI实例
	 */
	public static function getDi()
	{
		if (! static::$app) {
			throw new DiException ( "A dependency injection object is required to access the application services" );
		}
		return static::$app;
	}

	/**
	 * Set a default dependency injection container to be obtained into static methods
	 *
	 * @param Leaps\Di\ContainerInterface dependencyInjector
	 */
	public static function setDi(ContainerInterface $dependencyInjector)
	{
		static::$app = $dependencyInjector;
	}
}