<?php
namespace Leaps\Database;
use Leaps\Database\Eloquent\Model;
use Leaps\Support\ServiceProvider;
use Leaps\Database\Connectors\ConnectionFactory;
class DatabaseServiceProvider extends ServiceProvider {

	/**
	 * 启动应用程序事件。
	 *
	 * @return void
	 */
	public function boot() {
		Model::setConnectionResolver ( $this->app ['db'] );
		Model::setEventDispatcher ( $this->app ['events'] );
	}

	/**
	 * 注册服务
	 *
	 * @return void
	 */
	public function register() {
		$this->app ['db.factory'] = $this->app->share ( function ($app) {
			return new ConnectionFactory ( $app );
		} );
		$this->app ['db'] = $this->app->share ( function ($app) {
			return new DatabaseManager ( $app, $app ['db.factory'] );
		} );
	}
}