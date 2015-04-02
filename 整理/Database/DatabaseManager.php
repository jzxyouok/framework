<?php
namespace Leaps\Database;

use Leaps\Database\Connectors\ConnectionFactory;

class DatabaseManager implements ConnectionResolverInterface {

	/**
	 * 应用实例
	 *
	 * @var \Leaps\Foundation\Application
	 */
	protected $app;

	/**
	 * 数据工厂实例
	 *
	 * @var \Leaps\Database\Connectors\ConnectionFactory
	 */
	protected $factory;

	/**
	 * 活动连接实例。
	 *
	 * @var array
	 */
	protected $connections = array();

	/**
	 * 自定义连接解析器
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * 创建一个新的数据库管理器实例。
	 *
	 * @param  \Leaps\Support\Application  $app
	 * @param  \Leaps\Database\Connectors\ConnectionFactory  $factory
	 * @return void
	 */
	public function __construct($app, ConnectionFactory $factory)
	{
		$this->app = $app;
		$this->factory = $factory;
	}

	/**
	 * 获得数据库连接实例。
	 *
	 * @param  string  $name
	 * @return \Leaps\Database\Connection
	 */
	public function connection($name = null)
	{
		$name = $name ?: $this->getDefaultConnection();
		if ( ! isset($this->connections[$name]))
		{
			$connection = $this->makeConnection($name);
			$this->connections[$name] = $this->prepare($connection);
		}
		return $this->connections[$name];
	}

	/**
	 * 重新连接到给定的数据库。
	 *
	 * @param  string  $name
	 * @return \Leaps\Database\Connection
	 */
	public function reconnect($name = null)
	{
		unset($this->connections[$name]);
		return $this->connection($name);
	}

	/**
	 * 创建数据库连接实例
	 *
	 * @param  string  $name
	 * @return \Leaps\Database\Connection
	 */
	protected function makeConnection($name)
	{
		$config = $this->getConfig($name);
		if (isset($this->extensions[$name]))
		{
			return call_user_func($this->extensions[$name], $config);
		}
		return $this->factory->make($config, $name);
	}

	/**
	 * 准备数据库连接实例。
	 *
	 * @param  \Leaps\Database\Connection  $connection
	 * @return \Leaps\Database\Connection
	 */
	protected function prepare(Connection $connection)
	{
		$connection->setFetchMode($this->app['config']['database.fetch']);
		if ($this->app->bound('events'))
		{
			$connection->setEventDispatcher($this->app['events']);
		}
		$app = $this->app;
		$connection->setCacheManager(function() use ($app)
		{
			return $app['cache'];
		});
		$connection->setPaginator(function() use ($app)
		{
			return $app['paginator'];
		});
		return $connection;
	}

	/**
	 * 从连接获取配置
	 *
	 * @param  string  $name
	 * @return array
	 */
	protected function getConfig($name)
	{
		$name = $name ?: $this->getDefaultConnection();
		$connections = $this->app['config']['database.connections'];
		if (is_null($config = array_get($connections, $name)))
		{
			throw new \InvalidArgumentException("Database [$name] not configured.");
		}
		return $config;
	}

	/**
	 * 获取默认的连接名称
	 *
	 * @return string
	 */
	public function getDefaultConnection()
	{
		return $this->app['config']['database.default'];
	}

	/**
	 * 设置默认的连接名称
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultConnection($name)
	{
		$this->app['config']['database.default'] = $name;
	}

	/**
	 * 注册一个扩展连接解析器。
	 *
	 * @param  string    $name
	 * @param  callable  $resolver
	 * @return void
	 */
	public function extend($name, $resolver)
	{
		$this->extensions[$name] = $resolver;
	}

	/**
	 * 动态传递方法默认连接。
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->connection(), $method), $parameters);
	}

}