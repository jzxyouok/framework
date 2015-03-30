<?php
namespace Leaps\Database\Capsule;
use PDO;
use Leaps\Support\Fluent;
use Leaps\Events\Dispatcher;
use Leaps\Cache\CacheManager;
use Leaps\Container\Container;
use Leaps\Database\DatabaseManager;
use Leaps\Database\Eloquent\Model as Eloquent;
use Leaps\Database\Connectors\ConnectionFactory;
class Manager
{

    /**
     * 全局范围实例
     *
     * @var \Leaps\Database\Capsule\Manager
     */
    protected static $instance;

    /**
     * Create a new database capsule manager.
     *
     * @param \Leaps\Container\Container $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $this->setupContainer ( $container );
        $this->setupDefaultConfiguration ();
        $this->setupManager ();
    }

    /**
     * Setup the IoC container instance.
     *
     * @param \Leaps\Container\Container $container
     * @return void
     */
    protected function setupContainer($container)
    {
        $this->container = $container ?  : new Container ();
        $this->container->instance ( 'config', new Fluent () );
    }

    /**
     * Setup the default database configuration options.
     *
     * @return void
     */
    protected function setupDefaultConfiguration()
    {
        $this->container ['config'] ['database.fetch'] = PDO::FETCH_ASSOC;
        $this->container ['config'] ['database.default'] = 'default';
    }

    /**
     * Build the database manager instance.
     *
     * @return void
     */
    protected function setupManager()
    {
        $factory = new ConnectionFactory ( $this->container );
        $this->manager = new DatabaseManager ( $this->container, $factory );
    }

    /**
     * Get a connection instance from the global manager.
     *
     * @param string $connection
     * @return \Leaps\Database\Connection
     */
    public static function connection($connection = null)
    {
        return static::$instance->getConnection ( $connection );
    }

    /**
     * Get a fluent query builder instance.
     *
     * @param string $table
     * @param string $connection
     * @return \Leaps\Database\Query\Builder
     */
    public static function table($table, $connection = null)
    {
        return static::$instance->connection ( $connection )->table ( $table );
    }

    /**
     * Get a schema builder instance.
     *
     * @param string $connection
     * @return \Leaps\Database\Schema\Builder
     */
    public static function schema($connection = null)
    {
        return static::$instance->connection ( $connection )->getSchemaBuilder ();
    }

    /**
     * Get a registered connection instance.
     *
     * @param string $name
     * @return \Leaps\Database\Connection
     */
    public function getConnection($name = null)
    {
        return $this->manager->connection ( $name );
    }

    /**
     * Register a connection with the manager.
     *
     * @param array $config
     * @param string $name
     * @return void
     */
    public function addConnection(array $config, $name = 'default')
    {
        $connections = $this->container ['config'] ['database.connections'];
        $connections [$name] = $config;
        $this->container ['config'] ['database.connections'] = $connections;
    }

    /**
     * Bootstrap Eloquent so it is ready for usage.
     *
     * @return void
     */
    public function bootEloquent()
    {
        Eloquent::setConnectionResolver ( $this->manager );
        if ( $dispatcher = $this->getEventDispatcher () ) {
            Eloquent::setEventDispatcher ( $dispatcher );
        }
    }

    /**
     * Set the fetch mode for the database connections.
     *
     * @param int $fetchMode
     * @return \Leaps\Database\Capsule\Manager
     */
    public function setFetchMode($fetchMode)
    {
        $this->container ['config'] ['database.fetch'] = $fetchMode;
        return $this;
    }

    /**
     * Make this capsule instance available globally.
     *
     * @return void
     */
    public function setAsGlobal()
    {
        static::$instance = $this;
    }

    /**
     * Get the current event dispatcher instance.
     *
     * @return \Leaps\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        if ( $this->container->bound ( 'events' ) ) {
            return $this->container ['events'];
        }
    }

    /**
     * Set the event dispatcher instance to be used by connections.
     *
     * @param \Leaps\Events\Dispatcher $dispatcher
     * @return void
     */
    public function setEventDispatcher(Dispatcher $dispatcher)
    {
        $this->container->instance ( 'events', $dispatcher );
    }

    /**
     * Get the current cache manager instance.
     *
     * @return \Leaps\Cache\Manager
     */
    public function getCacheManager()
    {
        if ( $this->container->bound ( 'cache' ) ) {
            return $this->container ['cache'];
        }
    }

    /**
     * Set the cache manager to bse used by connections.
     *
     * @param \Leaps\Cache\CacheManager $cache
     * @return void
     */
    public function setCacheManager(CacheManager $cache)
    {
        $this->container->instance ( 'cache', $cache );
    }

    /**
     * Get the IoC container instance.
     *
     * @return \Leaps\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * @param \Leaps\Container\Container $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}