<?php
namespace Leaps\Database\Connectors;
class SQLiteConnector extends Connector implements ConnectorInterface
{

    /**
     * (non-PHPdoc)
     *
     * @see \Leaps\Database\Connectors\ConnectorInterface::connect()
     */
    public function connect(array $config)
    {
        $options = $this->getOptions ( $config );
        if ( $config ['database'] == ':memory:' ) {
            return $this->createConnection ( 'sqlite::memory:', $config, $options );
        }
        $path = realpath ( $config ['database'] );
        return $this->createConnection ( "sqlite:{$path}", $config, $options );
    }
}