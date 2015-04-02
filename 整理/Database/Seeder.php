<?php
namespace Leaps\Database;
use Leaps\Console\Command;
use Leaps\Container\Container;
class Seeder
{

    /**
     * 容器实例
     *
     * @var \Leaps\Container\Container
     */
    protected $container;

    /**
     * 控制台命令实例
     *
     * @var \Leaps\Console\Command
     */
    protected $command;

    /**
     * 运行数据库的种子
     *
     * @return void
     */
    public function run()
    {
    }

    /**
     * Seed the given connection from the given path.
     *
     * @param string $class
     * @return void
     */
    public function call($class)
    {
        $this->resolve ( $class )->run ();
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param string $class
     * @return \Leaps\Database\Seeder
     */
    protected function resolve($class)
    {
        if ( isset ( $this->container ) ) {
            $instance = $this->container->make ( $class );
            return $instance->setContainer ( $this->container )->setCommand ( $this->command );
        } else {
            return new $class ();
        }
    }

    /**
     * 设置IoC容器实例
     *
     * @param \Leaps\Container\Container $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * @param \Leaps\Console\Command $command
     * @return void
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }
}