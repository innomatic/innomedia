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
require_once('innomedia/InnomediaBlock.php');
require_once('InnomaticMediaUser.php');

class UserBlock extends InnomediaBlock
{
    public function run(WebAppRequest $request, WebAppResponse $response)
    {
    	$user = new InnomaticMediaUser();
    	if ($userid = $user->isLoggedIn()) {
    		$this->set('user_loggedin', '1');
    		$this->set('userid', $userid);
    		
    		$userdata = $user->getData();
    		$this->set('username', $userdata['username']);
    		
    		// @todo Here we should set user and point data variables
    		
    	} else {
    		$this->set('user_loggedin', '0');
    	}    	
    }
}