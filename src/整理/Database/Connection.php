<?php
namespace Leaps\Database;
use PDO;
use Closure;
use DateTime;
use Leaps\Database\Query\Processors\Processor;
class Connection implements ConnectionInterface
{

    /**
     * 主动PDO连接。
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * 查询语法实例。
     *
     * @var \Leaps\Database\Query\Grammars\Grammar
     */
    protected $queryGrammar;

    /**
     * 语法模式实例。
     *
     * @var \Leaps\Database\Schema\Grammars\Grammar
     */
    protected $schemaGrammar;

    /**
     * 查询后置处理程序的实例。
     *
     * @var \Leaps\Database\Query\Processors\Processor
     */
    protected $postProcessor;

    /**
     * 事件分派器实例。
     *
     * @var \Leaps\Events\Dispatcher
     */
    protected $events;

    /**
     * paginator环境实例。
     *
     * @var \Leaps\Pagination\Paginator
     */
    protected $paginator;

    /**
     * 缓存管理器实例。
     *
     * @var \Leaps\Cache\CacheManger
     */
    protected $cache;

    /**
     * 默认连接的抓取模式。
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_ASSOC;

    /**
     * 所有的查询运行与连接。
     *
     * @var array
     */
    protected $queryLog = array ();

    /**
     * 是否查询记录。
     *
     * @var bool
     */
    protected $loggingQueries = true;

    /**
     * Indicates if the connection is in a "dry run".
     *
     * @var bool
     */
    protected $pretending = false;

    /**
     * 数据库名称
     *
     * @var string
     */
    protected $database;

    /**
     * 标前缀
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * 数据库连接配置选项。
     *
     * @var array
     */
    protected $config = array ();

    /**
     * 穿件一个新的数据库连接实例
     *
     * @param PDO $pdo
     * @param string $database
     * @param string $tablePrefix
     * @param array $config
     * @return void
     */
    public function __construct(PDO $pdo, $database = '', $tablePrefix = '', array $config = array())
    {
        $this->pdo = $pdo;
        $this->database = $database;
        $this->tablePrefix = $tablePrefix;
        $this->config = $config;
        $this->useDefaultQueryGrammar ();
        $this->useDefaultPostProcessor ();
    }

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar ();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Leaps\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new Query\Grammars\Grammar ();
    }

    /**
     * Set the schema grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultSchemaGrammar()
    {
        $this->schemaGrammar = $this->getDefaultSchemaGrammar ();
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Leaps\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor ();
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Leaps\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processors\Processor ();
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Leaps\Database\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if ( is_null ( $this->schemaGrammar ) ) {
            $this->useDefaultSchemaGrammar ();
        }
        return new Schema\Builder ( $this );
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param string $table
     * @return \Leaps\Database\Query\Builder
     */
    public function table($table)
    {
        $processor = $this->getPostProcessor ();
        $query = new Query\Builder ( $this, $this->getQueryGrammar (), $processor );
        return $query->from ( $table );
    }

    /**
     * Get a new raw query expression.
     *
     * @param mixed $value
     * @return \Leaps\Database\Query\Expression
     */
    public function raw($value)
    {
        return new Query\Expression ( $value );
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param string $query
     * @param array $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = array())
    {
        $records = $this->select ( $query, $bindings );
        return count ( $records ) > 0 ? reset ( $records ) : null;
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function select($query, $bindings = array())
    {
        return $this->run ( $query, $bindings, function ($me, $query, $bindings)
        {
            if ( $me->pretending () ) return array ();
            $statement = $me->getPdo ()->prepare ( $query );
            $statement->execute ( $me->prepareBindings ( $bindings ) );
            return $statement->fetchAll ( $me->getFetchMode () );
        } );
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function insert($query, $bindings = array())
    {
        return $this->statement ( $query, $bindings );
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function update($query, $bindings = array())
    {
        return $this->affectingStatement ( $query, $bindings );
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function delete($query, $bindings = array())
    {
        return $this->affectingStatement ( $query, $bindings );
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function statement($query, $bindings = array())
    {
        return $this->run ( $query, $bindings, function ($me, $query, $bindings)
        {
            if ( $me->pretending () ) return true;
            $bindings = $me->prepareBindings ( $bindings );
            return $me->getPdo ()->prepare ( $query )->execute ( $bindings );
        } );
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = array())
    {
        return $this->run ( $query, $bindings, function ($me, $query, $bindings)
        {
            if ( $me->pretending () ) return 0;
            $statement = $me->getPdo ()->prepare ( $query );
            $statement->execute ( $me->prepareBindings ( $bindings ) );
            return $statement->rowCount ();
        } );
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param string $query
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->run ( $query, array (), function ($me, $query, $bindings)
        {
            if ( $me->pretending () ) return true;
            return ( bool ) $me->getPdo ()->exec ( $query );
        } );
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param array $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar ();
        foreach ( $bindings as $key => $value ) {
            if ( $value instanceof DateTime ) {
                $bindings [$key] = $value->format ( $grammar->getDateFormat () );
            } elseif ( $value === false ) {
                $bindings [$key] = 0;
            }
        }

        return $bindings;
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param Closure $callback
     * @return mixed
     */
    public function transaction(Closure $callback)
    {
        $this->pdo->beginTransaction ();
        try {
            $result = $callback ( $this );
            $this->pdo->commit ();
        }
        catch ( \Exception $e ) {
            $this->pdo->rollBack ();
            throw $e;
        }
        return $result;
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param Closure $callback
     * @return array
     */
    public function pretend(Closure $callback)
    {
        $this->pretending = true;
        $this->queryLog = array ();
        $callback ( $this );
        $this->pretending = false;
        return $this->queryLog;
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @return mixed
     */
    protected function run($query, $bindings, Closure $callback)
    {
        $start = microtime ( true );
        try {
            $result = $callback ( $this, $query, $bindings );
        }
        catch ( \Exception $e ) {
            $this->handleQueryException ( $e, $query, $bindings );
        }
        $time = $this->getElapsedTime ( $start );
        $this->logQuery ( $query, $bindings, $time );
        return $result;
    }

    /**
     * Handle an exception that occurred during a query.
     *
     * @param Exception $e
     * @param string $query
     * @param array $bindings
     * @return void
     */
    protected function handleQueryException(\Exception $e, $query, $bindings)
    {
        $bindings = var_export ( $bindings, true );
        $message = $e->getMessage () . " (SQL: {$query}) (Bindings: {$bindings})";
        throw new \Exception ( $message );
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param string $query
     * @param array $bindings
     * @param $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        if ( isset ( $this->events ) ) {
            $this->events->fire ( 'leaps.query', array (
                    $query,
                    $bindings,
                    $time,
                    $this->getName ()
            ) );
        }
        if ( ! $this->loggingQueries ) return;
        $this->queryLog [] = compact ( 'query', 'bindings', 'time' );
    }

    /**
     * Register a database query listener with the connection.
     *
     * @param Closure $callback
     * @return void
     */
    public function listen(Closure $callback)
    {
        if ( isset ( $this->events ) ) {
            $this->events->listen ( 'leaps.query', $callback );
        }
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param int $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round ( (microtime ( true ) - $start) * 1000, 2 );
    }

    /**
     * Get a Doctrine Schema Column instance.
     *
     * @param string $table
     * @param string $column
     * @return \Doctrine\DBAL\Schema\Column
     */
    public function getDoctrineColumn($table, $column)
    {
        $schema = $this->getDoctrineSchemaManager ();
        return $schema->listTableDetails ( $table )->getColumn ( $column );
    }

    /**
     * Get the Doctrine DBAL schema manager for the connection.
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getDoctrineSchemaManager()
    {
        return $this->getDoctrineDriver ()->getSchemaManager ( $this->getDoctrineConnection () );
    }

    /**
     * Get the Doctrine DBAL database connection instance.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDoctrineConnection()
    {
        $driver = $this->getDoctrineDriver ();
        $data = array (
                'pdo' => $this->pdo,
                'dbname' => $this->getConfig ( 'database' )
        );
        return new \Doctrine\DBAL\Connection ( $data, $driver );
    }

    /**
     * Get the currently used PDO connection.
     *
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Get the database connection name.
     *
     * @return string null
     */
    public function getName()
    {
        return $this->getConfig ( 'name' );
    }

    /**
     * Get an option from the configuration options.
     *
     * @param string $option
     * @return mixed
     */
    public function getConfig($option)
    {
        return array_get ( $this->config, $option );
    }

    /**
     * Get the PDO driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return $this->pdo->getAttribute ( \PDO::ATTR_DRIVER_NAME );
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return \Leaps\Database\Query\Grammars\Grammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param \Leaps\Database\Query\Grammars\Grammar
     * @return void
     */
    public function setQueryGrammar(Query\Grammars\Grammar $grammar)
    {
        $this->queryGrammar = $grammar;
    }

    /**
     * Get the schema grammar used by the connection.
     *
     * @return \Leaps\Database\Query\Grammars\Grammar
     */
    public function getSchemaGrammar()
    {
        return $this->schemaGrammar;
    }

    /**
     * Set the schema grammar used by the connection.
     *
     * @param \Leaps\Database\Schema\Grammars\Grammar
     * @return void
     */
    public function setSchemaGrammar(Schema\Grammars\Grammar $grammar)
    {
        $this->schemaGrammar = $grammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return \Leaps\Database\Query\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * @param \Leaps\Database\Query\Processors\Processor
     * @return void
     */
    public function setPostProcessor(Processor $processor)
    {
        $this->postProcessor = $processor;
    }

    /**
     * Get the event dispatcher used by the connection.
     *
     * @return \Leaps\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param \Leaps\Events\Dispatcher
     * @return void
     */
    public function setEventDispatcher(\Leaps\Events\Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the paginator environment instance.
     *
     * @return \Leaps\Pagination\Environment
     */
    public function getPaginator()
    {
        if ( $this->paginator instanceof Closure ) {
            $this->paginator = call_user_func ( $this->paginator );
        }
        return $this->paginator;
    }

    /**
     * 设置分页环境实例。
     *
     * @param \Leaps\Pagination\Environment|\Closure $paginator
     * @return void
     */
    public function setPaginator($paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * 获取缓存管理器实例。
     *
     * @return \Leaps\Cache\CacheManager
     */
    public function getCacheManager()
    {
        if ( $this->cache instanceof Closure ) {
            $this->cache = call_user_func ( $this->cache );
        }
        return $this->cache;
    }

    /**
     * 设置缓存管理器实例的连接。
     *
     * @param \Leaps\Cache\CacheManager|\Closure $cache
     * @return void
     */
    public function setCacheManager($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Determine if the connection in a "dry run".
     *
     * @return bool
     */
    public function pretending()
    {
        return $this->pretending === true;
    }

    /**
     * 获取默认获取模式
     *
     * @return int
     */
    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * 设置默认获取模式。
     *
     * @param int $fetchMode
     * @return int
     */
    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;
    }

    /**
     * 获取查询日志
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * 清空查询日志
     *
     * @return void
     */
    public function flushQueryLog()
    {
        $this->queryLog = array ();
    }

    /**
     * 启用查询日志。
     *
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }

    /**
     * 禁用查询日志。
     *
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
    }

    /**
     * 获得连接数据库的名称。
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * 设置连接数据库的名称。
     *
     * @param string $database
     * @return string
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;
    }

    /**
     * 获取表前缀
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * 设置表前缀
     *
     * @param string $prefix
     * @return void
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
        $this->getQueryGrammar ()->setTablePrefix ( $prefix );
    }

    /**
     * Set the table prefix and return the grammar.
     *
     * @param \Leaps\Database\Grammar $grammar
     * @return \Leaps\Database\Grammar
     */
    public function withTablePrefix(Grammar $grammar)
    {
        $grammar->setTablePrefix ( $this->tablePrefix );
        return $grammar;
    }
}
