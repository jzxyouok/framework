<?php
// +----------------------------------------------------------------------
// | Leaps Framework [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author XuTongle <xutongle@gmail.com>
// +----------------------------------------------------------------------
namespace Leaps;

class Socket extends Base
{
	/**
	 * 要连接的主机
	 * @var string
	 */
	public $host;

	/**
	 * 要连接的端口
	 * @var int
	 */
	public $port;

	/**
	 * 使用的连接协议
	 * @var string
	 */
	public $protocol = "tcp";

	/**
	 * 是否持久连接
	 * @var boolean
	 */
	public $persistent = false;

	/**
	 * 超时时间
	 * @var int
	 */
	public $timeout = 30;

	/**
	 * socket连接句柄
	 * @var resource
	 */
	private $socket;

	private $connected = false;

	private $error = array ();

	/**
	 * 初始化
	 */
	public function init()
	{
		if (! is_numeric ( $this->protocol )) {
			$this->protocol = getprotobyname ( $this->protocol );
		}
	}

	/**
	 * 发起连接
	 *
	 * @return boolean
	 */
	public function connect()
	{
		if ($this->socket != null) {
			$this->disconnect ();
		}
		if ($this->persistent == true) {
			$this->socket = @pfsockopen ( $this->host, $this->port, $errNum, $errStr, $this->timeout );
		} else {
			$this->socket = fsockopen ( $this->host, $this->port, $errNum, $errStr, $this->timeout );
		}
		if (! empty ( $errNum ) || ! empty ( $errStr )) {
			$this->error ( $errStr, $errNum );
		}
		$this->connected = is_resource ( $this->socket );
		return $this->connected;
	}


	public function error($errStr, $errNum)
	{
	}

	/**
	 * 写入数据
	 *
	 * @param unknown $data
	 * @return boolean|number
	 */
	public function write($data)
	{
		if (! $this->connected) {
			if (! $this->connect ()) {
				return false;
			}
		}
		return fwrite ( $this->socket, $data, strlen ( $data ) );
	}

	/**
	 * 读取数据
	 *
	 * @param number $length
	 * @return boolean|string
	 */
	public function read($length = 1024)
	{
		if (! $this->connected) {
			if (! $this->connect ()) {
				return false;
			}
		}

		if (! feof ( $this->socket)) {
			return fread ( $this->socket, $length );
		} else {
			return false;
		}
	}

	/**
	 * 关闭Socket连接
	 *
	 * @return boolean
	 */
	public function disconnect()
	{
		if (! is_resource ( $this->socket )) {
			$this->connected = false;
			return true;
		}
		$this->connected = ! fclose ( $this->socket );

		if (! $this->connected) {
			$this->socket = null;
		}
		return ! $this->connected;
	}

	/**
	 * 关闭Socket连接
	 */
	public function __destruct()
	{
		$this->disconnect ();
	}
}