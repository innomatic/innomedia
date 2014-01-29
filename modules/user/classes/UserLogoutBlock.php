<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  2008-2014 Innoteam Srl
 * @license    http://www.innomatic.org/license/   BSD License
 * @link       http://www.innomatic.org
 * @since      Class available since Release 1.0.0
 */
require_once('InnomaticMediaUser.php');

class UserLogoutBlock extends \Innomedia\InnomediaBlock
{
    public function run(\Innomatic\Webapp\WebAppRequest $request, \Innomatic\Webapp\WebAppResponse $response)
    {
    	// Logout the current user, if logged in
   		$user = new InnomaticMediaUser();
   		$user->logout();
    }
}