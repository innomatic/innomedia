<?php

require_once('innomedia/InnomediaBlock.php');
require_once('InnomaticMediaUser.php');

class UserLoginBlock extends InnomediaBlock {
    public function run(WebAppRequest $request, WebAppResponse $response) {
    	$user = new InnomaticMediaUser();
    	
    	// Check the dologin parameter
    	
    	if ( !is_null($request->getParameter('dologin')) ) {
    		$this->set('username', $request->getParameter('username'));
    		$this->set('password', $request->getParameter('password'));
    		

    		// Check the login response
    		
    		switch ($user->login($request->getParameter('username'), $request->getParameter('password'))) {
    			case InnomaticMediaUser::RESPONSE_USERNAME_NOT_FOUND:
    				$this->set('login_response', 'username_not_found');
    				break; 

    			case InnomaticMediaUser::RESPONSE_WRONG_PASSWORD:
    				$this->set('login_response', 'wrong_password');
    				break;

    			case InnomaticMediaUser::RESPONSE_MUST_CHANGE_PASSWORD:
    				$this->set('login_response', 'must_change_password');
    				break;

    			case InnomaticMediaUser::RESPONSE_LOGIN_OK:
    				$this->set('login_response', 'ok');
    				$this->set('point_id', $user->getPointId());
    				break;
    		}
    	}
    	
    	// Check if the user is logged in
    	$userid = $user->isLoggedIn();
    	if (true) {
    		$this->set('user_loggedin', '1');
    		$this->set('user', $userid);
    	} else {
    		$this->set('user_loggedin', '0');
    	}
    }
}