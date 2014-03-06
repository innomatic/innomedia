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

class InnomaticMediaUser
{
	// Login responses
	
	const RESPONSE_USERNAME_NOT_FOUND = 1;
	const RESPONSE_WRONG_PASSWORD = 2;
	const RESPONSE_MUST_CHANGE_PASSWORD = 3;
	const RESPONSE_LOGIN_OK = 4;
	
	// Logout responses
	
	const RESPONSE_LOGOUT_OK = 5;
	
	// Change password responses
	
	const RESPONSE_PASSWORD_CHANGED = 6;
	const RESPONSE_WRONG_OLD_PASSWORD = 7;
	const RESPONSE_WRONG_NEW_PASSWORD = 8;
	const RESPONSE_NEW_PASSWORD_SAME_AS_OLD_PASSWORD = 9;
	
	// Stub users array
	private $users = array();
	
	// Current user id
	protected $id;

	/**
	 * @author Alex Pagnoni
	 * @since 1.0
	 */
	public function __construct()
	{
		// Stub users
		$this->users['alex.pagnoni@innoteam.it'] = array('id' => 1, 'point_id' => '23', 'password' => '123', 'activated' => true);
		$this->users['paolo.guanciarossa@innoteam.it'] = array('id' => 2, 'point_id' => '35','password' => 'abc', 'activated' => false);
		$this->users['salvatore.pollaci@innoteam.it'] = array('id' => 3, 'point_id' => '44','password' => 'xyz', 'activated' => false);
		
		// Checks if a user has been already logged
		
		$this->id = self::isLoggedIn();		
	}
	
	public function getUserById($id)
	{
		foreach ($this->users as $email => $user) {
			if ($user['id'] == $id) {
				return array_merge(array('username' => $email), $this->users[$email]);
			}
		}
		return false;
	}

	/**
	 * Returns the user id of this object.
	 * To check if the user is logged in, you should use isLoggedIn() method.
	 * 
	 * @author Alex Pagnoni
	 * @since 1.0
	 * @return integer User id.
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Returns data for current user.
	 * 
	 * @author Alex Pagnoni
	 * @since 1.0
	 * @return mixed Array with user data, or false if the user is not set.
	 */
	public function getData()
	{
		if ($this->id != false) {
			return $this->getUserById($this->id);
		}
		
		return false;
	}
	
	/**
	 * Checks if the user is logged and returns its user id if logged, or false otherwise.
	 * 
	 * @todo It should also check if the user still exists. Eg. the user is logged in but has been deleted or unactivated.
	 * @author Alex Pagnoni
	 * @since 1.0
	 * @return integer Id of the logged user, or false if not set.
	 */
	public function isLoggedIn()
	{
		$session = \Innomedia\InnomediaContext::instance('\Innomedia\InnomediaContext')->getSession();
		
		if ($session->isValid('userid')) {
			return $session->get('userid');
		}
		return false;
	}
	
	/*
	 * This user class does not implement user registration.
	 * 
	public function register();
	public function delete();
	*/
	
	/**
	 * Logs in the user.
	 * 
	 * @todo It should add a delay in case of wrong login to prevent brute force attacks.
	 * @author Alex Pagnoni
	 * @since 1.0
	 * @param string $username
	 * @param string $password
	 * @return integer Response code.
	 */
	public function login($username, $password)
	{
		if (!isset($this->users[$username])) {
			return self::RESPONSE_USERNAME_NOT_FOUND;
		}
		
		if ($this->users[$username]['password'] != $password) {
			return self::RESPONSE_WRONG_PASSWORD;
		}
		
		if ($this->users[$username]['activated'] == false) {
			return self::RESPONSE_MUST_CHANGE_PASSWORD;
		}
		
		// The users exists, the password is right and the user has already been activated
		
		$session = \Innomedia\InnomediaContext::instance('\Innomedia\InnomediaContext')->getSession();
		$session->put('userid', $this->users[$username]['id']);
		return self::RESPONSE_LOGIN_OK;
	}
	
	/**
	 * Changes the user password.
	 * 
	 * @todo It should add a delay in case of wrong login to prevent brute force attacks.
	 * @author Alex Pagnoni
	 * @since 1.0
	 * @param unknown $old
	 * @param unknown $new
	 */
	public function changePassword($username = '', $old, $new_a, $new_b)
	{
		if (strlen($username)) {
			if (!isset($this->users[$username])) {
				return self::RESPONSE_USERNAME_NOT_FOUND;
			}
			
			$id = $this->users[$username]['id'];
		} else {
			$id = $this->id;
		}
		
		if ($id != false) {
			$user = $this->getUserById($id);
			
			// Useful only if we store password in clear
			if ($old != $user['password']) {
				return self::RESPONSE_WRONG_OLD_PASSWORD;
			}
			
			if ($new_a != $new_b) {
				return self::RESPONSE_WRONG_NEW_PASSWORD;
			}
			
			if ($new_a == $old) {
				return self::RESPONSE_NEW_PASSWORD_SAME_AS_OLD_PASSWORD;
			}
			
			// @todo Here we should really change the password
			// ...
			
			return self::RESPONSE_PASSWORD_CHANGED;
		}
		
		return false;
	}
	
	/**
	 * Logs out the user.
	 * 
	 * @author Alex Pagnoni
	 * @since 1.0
	 * @return integer Always InnomaticMediaUser::RESPONSE_LOGOUT_OK
	 */
	public function logout()
	{
		$session = \Innomedia\InnomediaContext::instance('\Innomedia\InnomediaContext')->getSession();
		
		if ($session->isValid('userid')) {
			$session->remove('userid');
		}
		
		return self::RESPONSE_LOGOUT_OK;
	}
}