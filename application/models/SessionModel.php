<?php

/*
	This class provides a layer of authentication and security between the session superglobal
	and the application. It also allows for session	persistance through the use of cookies.
*/


class SessionModel extends AclModel
{
	//Data members
	protected $_userID;
	protected $_userName;
	protected $_userDisplay;
	protected $_userEmail;
	protected $_userRole;
	private $_userTable;
	private $_sessionTable;

	/**
	 * Constructor class 
	 * Looks in the session data for an identity and sets it up if found. If no ident found, sets up a guest identity.
	 * After ident is determined, the ACL is instantiated and the user role defined and stored.
	 */
	public function __construct()
	{
		//Set the database tables to query :: These -MUST- be set before use
		$this->_userTable = 'cis_users';
		$this->_sessionTable = 'cis_user_sessions';

		//See if we have a user session stored
		if ($sessionData = $this->_getSession())
		{
			//If we do, store the data
			$this->_userID = $sessionData['userID'];
			$this->_userName = $sessionData['userName'];
			$this->_userDisplay = $sessionData['userDisplay'];
			$this->_userRole = $sessionData['userRole'];
			$this->_userEmail = $sessionData['userEmail'];
		}
		else
		{
			//No? Just setup guest credentials
			$this->_userID = null;
			$this->_userName = 'guest';
			$this->_userDisplay = 'Guest';
			$this->_userRole = 'guest';
			$this->_userEmail = null;
		}

		//Setup ACL
		$this->aclSetup($this->_userRole);
	}


	/**
	 * Function to authenticate a user and setup the resulting session
	 */
	public function authenticate($userName, $passwd, $cookie = false)
	{
		//Retrieve the database adapter
		$db = Zend_Registry::get('database');

		//Clear any previous sessions (you never know, I guess)
		$this->_destroySession();
		$this->_destroyCookie();

		//Retrieve the data we need for the password test
		$userData = $db->fetchRow('SELECT userPasswd, userSalt, userEmail, userID, userRole, userDisplay, userName
		   FROM ' . $this->_userTable . ' WHERE userName = ? LIMIT 1', array(strtolower($userName)));

		//No data returned means we have an invalid username
		if (empty($userData))
		{
			return AUTH_BAD_USER;
		}

		//Encrypt and test the password
		$encrypted = $this->_encryptPasswd($userData['userID'], $passwd, $userData['userSalt']);
		if ($encrypted != $userData['userPasswd'])
		{
			return AUTH_BAD_PASSWD;
		}

		//At this point we can assume all has gone well and our user is genuinely authenticated.
		$this->setSession($userData);

		//If we need to set a persiatance cookie, set it!
		if (true === $cookie)
		{
			$this->_setCookie($userData['userID']);
		}

		//Hooray!
		return AUTH_SUCCESS;
	}


	/**
	 * Function to check and see if we're logged in
	 * Returns bool true if logged in, false if not
	 */
	public function checkLogged()
	{
		if (isset($this->_userID))
		{
			return true;
		}
		return false;
	}


	/**
	 * Function to logout current session
	 */
	public function logout()
	{
		$this->_destroySession();
		$this->_destroyCookie();
	}


	/**
	 * Function to force the loading of an identity from a supplied userID.
	 * Loading an ID clears the current set session and resets it to the new ID.
	 */
	public function loadID($userID)
	{
		$db = Zend_Registry::get('database');
		$userData = $db->fetchRow('SELECT userEmail, userRole, userDisplay, userName
		   FROM ' . $this->_userTable . ' WHERE userID = ? LIMIT 1', array($userID));
		$userData['userID'] = $userID;
		$this->_setSession($userData);
	}

	/**
	 * Function to go through the sequence of events necessary to create data for a newly made passwd
	 */
	public function generateNewPasswd($userID, $unencrypted)
	{
		$data['salt'] = $this->_generateSalt();
		$data['passwd'] = $this->_encryptPasswd($userID, $unencrypted, $data['salt']);
		return $data;
	}


	/**
	 * Function to return an encrypted password
	 */
	private function _encryptPasswd($userID, $passwd, $salt)
	{
		//Salts are actually base64 encoded to give us the full strength of the hash
		$newSalt = base64_decode($salt . '==');
		return hash('sha1', $userID . hash('md5', $newSalt) . 'HnNKn' . $passwd);
	}


	/**
	 * Function to generate a new password salt
	 */
	private function _generateSalt()
	{
		//Salts are actually base64 encoded to give us the full strength of the haval128 hash
		$hash = hash('haval128,5', uniqid(mt_rand(), true), true);
		$hash = substr($hash, 5);
		$hash = substr(base64_encode($hash), 0, -2);
	}


	/**
	 * Function to setup a new user session from an array of user data
	 */
	private function _setSession($userData)
	{
		$_SESSION['uid']['userID'] = $userData['userID'];
		$_SESSION['uid']['userName'] = $userData['userName'];
		$_SESSION['uid']['userDisplay'] = $userData['userDisplay'];
		$_SESISON['uid']['userEmail'] = $userDaya['userEmail'];
		$_SESSION['uid']['userRole'] = $userData['userRole'];
		$_SESSION['uid']['generated'] = time();
	}


	/**
	 * Function to set a userID storage cookie
	 */
	private function _setCookie($userID)
	{
		$db = Zend_Registry::get('database');
		//Set the cookie expire time and generate a unique hash
		$expire = time() + 31536000;
		$sessionID = hash('sha1', uniqid(mt_rand(), true));
		//Compile the data into a string
		$cookieData = $userID . '::' . $sessionID;

		//Set the database table data
		$tableName = $this->_sessionTable;
		$tableData = array(
			'sessionID'	=> $sessionID,
			'userID'	=> $userID,
			'setTime'	=> time()
		);

		try {
			$db->beginTransaction();
			$db->insert($tableName, $tableData);		//Store the session in the database
			setcookie('uid', $cookieData, $expire);	//Set the cookie
			$db->commit();
		} catch (Zend_Exception $e) {
			$db->rollBack();		
			$logger = Zend_Registry::get('logger');
			$logger->err('Database error in setting session cookie for userID(' . $userID . '): ' . $e->getMessage());
		} catch (Exception $e) {
			$db->rollBack();		
			$logger = Zend_Registry::get('logger');
			$logger->err('Failure to set session cookie for userID(' . $userID . '): ' . $e->getMessage());
		}
	}


	/**
	 * Function to return raw user session data from the $_SESSION superglobal
	 */
	private function _getSession()
	{
		//Session storage
		if (isset($_SESSION['uid']['generated']))
		{
			//Regenerates session ID every 30 seconds to prevent fixation attacks
			if ($_SESSION['uid']['generated'] < time() - 30)
			{
				session_regenerate_id();
				$_SESSION['uid']['generated'] = time();
			}

			return $this->_getUserDataFromSession();
		}
		elseif (isset($_COOKIE['uid']))
		{
			$db = Zend_Registry::get('database');
			$cookieData = $this->_getCookieData();
			$sessionExists = $db->fetchOne('SELECT COUNT(*) FROM ' . $this->_sessionTable . ' WHERE sessionID = ? AND userID = ? LIMIT 1',
			   array($cookieData['sessionID'], $cookieData['userID']));
			if ($sessionExists)
			{
				//Good cookie, setup the session
				$userData = $db->fetchRow('SELECT userEmail, userRole, userDisplay, userName
				   FROM ' . $this->_userTable . ' WHERE userID = ? LIMIT 1', array($cookieData['userID']));
				$userData['userID'] = $cookieData['userID'];
				$this->_setSession($userData);

				return $userData;
			}
			else
			{
				//Destroy the cookie, it is bad!
				$this->_destroyCookie();
			}
		}

		//By this point, there was no stored data to return
		return false;
	}


	/**
	 * Function to return userID and sessionID from a stored cookie
	 * Returns a keyed array if cookie data found
	 * Returns false if none
	 */
	private function _getCookieData()
	{
		if (isset($_COOKIE['uid']))
		{
			$cookieData = explode("::", $_COOKIE['uid']);	//Split the stored string into an array: [0] userID, [1] sessionID
			$keyedData = array(
				'userID' 	=> $cookieData[0],
				'sessionID'	=> $cookieData[1]
			);
			return $keyedData;
		}
		return false;
	}


	/**
	 * Function to return a full array of user data
	 */
	public function getUserData()
	{
		$user = array(
			'uerID'		=> $this->_userID,
			'userName' 	=> $this->_userName,
			'userDisplay'	=> $this->_userDisplay,
			'userRole'	=> $this->_userRole,
			'userEmail'	=> $this->_userEmail
		);
		return $user;
	}


	/**
	 * Function to return a full array of user data from the $_SESSION superglobal
	 */
	private function _getUserDataFromSession()
	{
		$user = array(
			'uerID'		=> $_SESSION['uid']['userID'],
			'userName' 	=> $_SESSION['uid']['userName'],
			'userDisplay'	=> $_SESSION['uid']['userDisplay'],
			'userRole'	=> $_SESSION['uid']['userRole'],
			'userEmail'	=> $_SESISON['uid']['userEmail']
		);
		return $user;
	}


	/**
	 * Function to destroy a user's session
	 */
	private function _destroySession()
	{
		/*	This is the insecure way of doing it...
		unset($_SESSION['uid']['userID'], $_SESSION['uid']['userName'], $_SESSION['uid']['userDisplay'],
			$_SESSION['uid']['userRole'], $_SESSION['uid']['userEmail'], $_SESSION['uid']['generated']);	*/
		//Much better
		session_unset();
	}


	/**
	 * Function to unset the site sessino persistance cookie
	 */
	private function _destroyCookie()
	{
		//Retrieve cookie data beofre destroying the cookie
		$cookieData = $this->_getCookieData();
		setcookie('uid', '', 1);
		//Delete session hash from the database
		$db = Zend_Registry::get('database');
		$where = array(
			$db->quoteInto('sessionID = ?', $cookieData['sessionID']),
			$db->quoteInto('userID = ?', $cookieData['userID'])
		);
		try {
			$db->delete($this->_sessionTable, $where);
		} catch (Zend_Exception $e) {
			$log = Zend_Registry::get('logger');
			$mssg = 'Failute to delete stored session (UID[' . $cookieData['userID'] . '] SID[' . $cookieData['sessionID'] . ']: ';
			$mssg .= $e->getMessage();
			$log->err($mssg);
		}
	}


	/**
	 * Function to return the display version of the current logged in username
	 */
	public function getUserDisplay()
	{
		return $_userDisplay;
	}
}
