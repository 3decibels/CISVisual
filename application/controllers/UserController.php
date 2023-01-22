<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class UserController extends Zend_Controller_Action
{
	/**
	 * @var CisUserModel $_cisUser
	 */
	protected $_session;

	public function init()
	{
		//Setup some basic variables
		$config = Zend_Registry::get('config');
		$this->_session = new SessionModel();

		//Begin putting together our view
		$this->view->baseUrl = $this->_request->getBaseUrl();
		$this->view->adminMail = $config->admin->email;
		$this->view->version = $config->version;

		//Send the username to the view. Send none if not logged in.
		$this->view->username = ($this->_session->checkLogged())? $this->_session->getUserDisplay() : '' ;

		//Set the redirection url for user login/logout
		$redirect = urlencode($this->_request->getRequestUri());
	}


	/**
	 * Function to register a new user
	 */
	public function registerAction()
	{
		//Currently logged in users cannot register a new one
		if ($this->_session->checkLogged())
		{
			$this->view->content = 'Cannot register a new account from an existing one.';
			$this->view->render('error', null, true);
			return;
		}

	}
}
