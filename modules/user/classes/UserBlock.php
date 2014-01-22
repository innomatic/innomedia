<?php

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