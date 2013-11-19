<?php

require_once('innomedia/InnomediaBlock.php');
require_once('InnomaticMediaUser.php');

class UserChangePasswordBlock extends InnomediaBlock {
    public function run(WebAppRequest $request, WebAppResponse $response) {
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
    	 
    	// Check the changepassword parameter
    	
    	if ( !is_null($request->getParameter('changepassword')) ) {
    		$user = new InnomaticMediaUser();

    		// Check the change password response
    		
    		switch ($user->changePassword(
    				$request->getParameter('username'),
    				$request->getParameter('oldpassword'),
    				$request->getParameter('newpassword1'),
    				$request->getParameter('newpassword2'))) {
    			case InnomaticMediaUser::RESPONSE_USERNAME_NOT_FOUND:
    				$this->set('change_password_response', 'username_not_found');
    				break; 

    			case InnomaticMediaUser::RESPONSE_WRONG_OLD_PASSWORD:
    				$this->set('change_password_response', 'wrong_old_password');
    				break; 

    			case InnomaticMediaUser::RESPONSE_WRONG_NEW_PASSWORD:
    				$this->set('change_password_response', 'wrong_new_password');
    				break;

    			case InnomaticMediaUser::RESPONSE_NEW_PASSWORD_SAME_AS_OLD_PASSWORD:
    				$this->set('change_password_response', 'new_password_same_as_old');
    				break;

    			case InnomaticMediaUser::RESPONSE_PASSWORD_CHANGED:
    				$this->set('change_password_response', 'ok');
    				break;
    		}
    	}
    }
}