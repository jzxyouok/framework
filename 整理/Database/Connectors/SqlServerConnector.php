<?php
namespace Leaps\Database\Connectors;
use PDO;
class SqlServerConnector extends Connector implements ConnectorInterface
{

    /**
     * PDO连接选项。
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
        $options = $this->getOptions ( $config );
        return $this->createConnection ( $this->getDsn ( $config ), $config, $options );
    }

    /**
     * 从配置创建DNS字符串
     *
     * @param array $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        extract ( $config );
        $port = isset ( $config ['port'] ) ? ',' . $port : '';
        if ( in_array ( 'dblib', $this->getAvailableDrivers () ) ) {
            return "dblib:host={$host}{$port};dbname={$database}";
        } else {
            return "sqlsrv:Server={$host}{$port};Database={$database}";
        }
    }

    /**
     * 获取可用的PDO驱动
     *
     * @return array
     */
    protected function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers ();
    }
}