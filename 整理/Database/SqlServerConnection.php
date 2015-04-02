<?php
namespace Leaps\Database;
use Closure;
class SqlServerConnection extends Connection
{

    /**
     * 在一个事务中执行一个闭包。
     *
     * @param Closure $callback
     * @return mixed
     */
    public function transaction(Closure $callback)
    {
        $this->pdo->exec ( 'BEGIN TRAN' );
        try {
            $result = $callback ( $this );

            $this->pdo->exec ( 'COMMIT TRAN' );
        }
        catch ( \Exception $e ) {
            $this->pdo->exec ( 'ROLLBACK TRAN' );
            throw $e;
        }
        return $result;
    }

    /**
     * 得到默认的查询语法实例。
     *
     * @return \Leaps\Database\Query\Grammars\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix ( new Query\Grammars\SqlServerGrammar () );
    }

    /**
     * 获得默认模式语法实例。
     *
     * @return \Leaps\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix ( new Schema\Grammars\SqlServerGrammar () );
    }

    /**
     * 得到DBAL驱动。
     *
     * @return \Doctrine\DBAL\Driver
     */
    protected function getDoctrineDriver()
    {
        return new \Doctrine\DBAL\Driver\PDOSqlsrv\Driver ();
    }

    /**
     * 得到默认的后置处理程序实例。
     *
     * @return \Leaps\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processors\SqlServerProcessor ();
    }
}