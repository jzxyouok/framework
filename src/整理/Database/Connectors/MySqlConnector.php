<?php
namespace Leaps\Database\Connectors;
class MySqlConnector extends Connector implements ConnectorInterface
{

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
        $collation = $config ['collation'];
        $charset = $config ['charset'];
        $names = "set names '$charset' collate '$collation'";
        $connection->prepare ( $names )->execute ();
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
        $dsn = "mysql:host={$host};dbname={$database}";
        if ( isset ( $config ['port'] ) ) {
            $dsn .= ";port={$port}";
        }
        if ( isset ( $config ['unix_socket'] ) ) {
            $dsn .= ";unix_socket={$config['unix_socket']}";
        }
        return $dsn;
    }
}