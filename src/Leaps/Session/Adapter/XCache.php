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
namespace Leaps\Session\Adapter;

use Leaps\Core\Exception;
use Leaps\Session\Adapter;
use Leaps\Session\AdapterInterface;

/**
 * Leaps\Session\Adapter\XCache
 *
 * This adapter store sessions in plain files
 *
 * <code>
 * $session = new \Leaps\Session\Adapter\XCache(array(
 * 'uniqueId' => 'my-private-app'
 * ));
 *
 * $session->start();
 *
 * $session->set('var', 'some-value');
 *
 * echo $session->get('var');
 * </code>
 */
class XCache extends Adapter implements AdapterInterface
{
	/**
	 * (non-PHPdoc)
	 * @see \Leaps\Session\Adapter::init()
	 */
	public function init()
	{
		if (! $this->test ()) {
			throw new Exception ( "The xcache extension isn't available" );
		}
		session_set_save_handler ( [$this,	"open"], [	$this,	"close"], [$this,	"read"	], [$this,	"write"], [$this,	"destroy"], [	$this,	"gc"] );
	}
	public function open($save_path, $session_name)
	{
		return true;
	}
	public function close()
	{
		return true;
	}
	public function read($id)
	{
		$sess_id = 'sess_' . $id;
		if (! xcache_isset ( $sess_id ))
			return;
		return ( string ) xcache_get ( $sess_id );
	}
	public function write($id, $session_data)
	{
		$sess_id = 'sess_' . $id;
		return xcache_set ( $sess_id, $session_data, ini_get ( "session.gc_maxlifetime" ) );
	}
	public function destroy($id)
	{
		$sess_id = 'sess_' . $id;
		if (! xcache_isset ( $sess_id ))
			return true;
		return xcache_unset ( $sess_id );
	}
	public function gc($maxlifetime)
	{
		return true;
	}
	public function test()
	{
		return extension_loaded ( 'xcache' );
	}
}
