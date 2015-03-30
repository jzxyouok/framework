<?php

namespace Leaps\Database\Connectors;
use PDO;
class PostgresConnector extends Connector implements ConnectorInterface
{

    /**
     * 默认的PDO连接选项。
     *
     * @var array
     */
    protected $options = array (
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES => false
    );

    /**
     * (non-PHPdoc)
     *
     * @see \Leaps\Database\Connectors\ConnectorInterface::connect()
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn ( $config );
        $options = $this->getOptions ( $config );
        $connection = $this->createConnection ( $dsn, $config, $options );
        $charset = $config ['charset'];
        $connection->prepare ( "set names '$charset'" )->execute ();
        if ( isset ( $config ['schema'] ) ) {
            $schema = $config ['schema'];

            $connection->prepare ( "set search_path to {$schema}" )->execute ();
        }
        return $connection;
    }

    /**
     * 从配置创建DSN字符串
     *
     * @param array $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        extract ( $config );
        $dsn = "pgsql:host={$host};dbname={$database}";
        if ( isset ( $config ['port'] ) ) {
            $dsn .= ";port={$port}";
        }
        return $dsn;
    }
}