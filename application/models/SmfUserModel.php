<?php

class SmfUserModel
{
	protected $context;

	/**
	 * Class constructor
	 * param: string $path Path to SMF forum
	 */
	public function __construct($path)
	{
		//Require the forum SSI file
		require_once($path . 'SSI.php');
		
		//Store the user context
		$this->context = $context;
	}


	/**
	 * Checks to see if the user is logged in
	 * return: bool true if logged in, false if not
	 */
	public function checkLogged()
	{
		//Should be self-explanitory
		if ($this->context['user']['is_guest'])
		{
			return false;
		}
		else
		{
			return true;
		}
	}


	/**
	 * Gets the username
	 * return: string username
	 */
	public function getUser()
	{
		return $this->context['user']['username'];
	}


	/**
         * Gets the user ID
         * return: string user ID
         */
        public function getUserID()
        {
                return $this->context['user']['id'];
        }


	/**
	 * Gets the character set
	 * return: string character set
	 */
	public function getCharset()
	{
		return $this->context['character_set'];
	}


	/**
	 * Sets the SMF login/logout redirect urls
	 * @param: string $url redirection url
	 */
	public function setRedirect($url)
	{
		$_SESSION['login_url'] = $url;
		$_SESSION['logout_url'] = $url;
	}
}

?>
