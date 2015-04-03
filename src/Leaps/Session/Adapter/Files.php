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

use Leaps\Session\AdapterInterface;
use Leaps\Session\Adapter;

/**
 * Leaps\Session\Adapter\Files
 *
 * This adapter store sessions in plain files
 *
 * <code>
 * $session = new \Leaps\Session\Adapter\Files(array(
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
class Files extends Adapter implements AdapterInterface
{
}
