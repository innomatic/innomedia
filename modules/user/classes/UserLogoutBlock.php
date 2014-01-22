<?php

require_once('innomedia/InnomediaBlock.php');
require_once('InnomaticMediaUser.php');

class UserLogoutBlock extends InnomediaBlock
{
    public function run(WebAppRequest $request, WebAppResponse $response)
    {
    	// Logout the current user, if logged in
   		$user = new InnomaticMediaUser();
   		$user->logout();
    }
}