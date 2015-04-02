<?php
namespace Leaps\Database;
class ConnectionResolver implements ConnectionResolverInterface
{

    /**
     * 所有注册的连接。
     *
     * @var array
     */
    protected $connections = array ();

    /**
     * 默认连接的名字。
     *
     * @var string
     */
    protected $default;

    /**
     * 创建一个新的连接解析器实例。
     *
     * @param array $connections
     * @return void
     */
    public function __construct(array $connections = array())
    {
        foreach ( $connections as $name => $connection ) {
            $this->addConnection ( $name, $connection );
        }
    }

    /**
     * 获得数据库连接实例。
     *
     * @param string $name
     * @return \Leaps\Database\Connection
     */
    public function connection($name = null)
    {
        if ( is_null ( $name ) ) $name = $this->getDefaultConnection ();
        return $this->connections [$name];
    }

    /**
     * 添加一个连接到该解析器
     *
     * @param string $name
     * @param \Leaps\Database\Connection $connection
     * @return void
     */
    public function addConnection($name, Connection $connection)
    {
        $this->connections [$name] = $connection;
    }

    /**
     * 检查连接已经被注册。
     *
     * @param string $name
     * @return bool
     */
    public function hasConnection($name)
    {
        return isset ( $this->connections [$name] );
    }

    /**
     * 获取默认连接的名字。
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->default;
    }

    /**
     * 设置默认连接的名字。
     *
     * @param string $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->default = $name;
    }
}