<?php

namespace Leaps\Database\Connectors;
use PDO;
use Leaps\Container\Container;
use Leaps\Database\MySqlConnection;
use Leaps\Database\SQLiteConnection;
use Leaps\Database\PostgresConnection;
use Leaps\Database\SqlServerConnection;
class ConnectionFactory
{

    /**
     * Ioc容器实例
     *
     * @var \Leaps\Container\Container
     */
    protected $container;

    /**
     * 创建一个新的连接工厂实例。
     *
     * @param \Leaps\Container\Container $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * 基于配置建立一个PDO连接。
     *
     * @param array $config
     * @param string $name
     * @return \Leaps\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        $config = $this->parseConfig ( $config, $name );
        $pdo = $this->createConnector ( $config )->connect ( $config );
        return $this->createConnection ( $config ['driver'], $pdo, $config ['database'], $config ['prefix'], $config );
    }

    /**
     * 解析和准备数据库配置。
     *
     * @param array $config
     * @param string $name
     * @return array
     */
    protected function parseConfig(array $config, $name)
    {
        return array_add ( array_add ( $config, 'prefix', '' ), 'name', $name );
    }

    /**
     * 基于配置创建一个连接器实例。
     *
     * @param array $config
     * @return \Leaps\Database\Connectors\ConnectorInterface
     */
    public function createConnector(array $config)
    {
        if ( ! isset ( $config ['driver'] ) ) {
            throw new \InvalidArgumentException ( "A driver must be specified." );
        }
        switch ($config ['driver']) {
            case 'mysql' :
                return new MySqlConnector ();

            case 'pgsql' :
                return new PostgresConnector ();

            case 'sqlite' :
                return new SQLiteConnector ();

            case 'sqlsrv' :
                return new SqlServerConnector ();
        }
        throw new \InvalidArgumentException ( "Unsupported driver [{$config['driver']}]" );
    }

    /**
     * 创建一个新的连接实例。
     *
     * @param string $driver
     * @param PDO $connection
     * @param string $database
     * @param string $prefix
     * @param array $config
     * @return \Leaps\Database\Connection
     */
    protected function createConnection($driver, PDO $connection, $database, $prefix = '', $config = null)
    {
        if ( $this->container->bound ( $key = "db.connection.{$driver}" ) ) {
            return $this->container->make ( $key, array (
                    $connection,
                    $database,
                    $prefix,
                    $config
            ) );
        }
        switch ($driver) {
            case 'mysql' :
                return new MySqlConnection ( $connection, $database, $prefix, $config );

            case 'pgsql' :
                return new PostgresConnection ( $connection, $database, $prefix, $config );

            case 'sqlite' :
                return new SQLiteConnection ( $connection, $database, $prefix, $config );

            case 'sqlsrv' :
                return new SqlServerConnection ( $connection, $database, $prefix, $config );
        }

        throw new \InvalidArgumentException ( "Unsupported driver [$driver]" );
    }
}
