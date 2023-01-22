<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class ErrorController extends Zend_Controller_Action
{
	/**
	 * @var CisUserModel $_cisUser
	 */
	protected $_cisUser;

	public function errorAction()
	{
		//Set the title
		$this->view->title = 'CISVisual';

		//Grab error type from the error handler
		$errors = $this->_getParam('error_handler');

		//Set some basic view variables
		$this->view->baseUrl = $this->_request->getbaseUrl();
		$config = Zend_Registry::get('config');
		$this->view->version = $config->version;

		//We still need to operate the login/logout box
		$this->_cisUser = new CisUserModel($config->forum->path);
		//Check if we're logged in
		if (! $this->_cisUser->checkLogged())
		{
			//Grab the charset in case we log in
			$this->view->charset = $this->_cisUser->getCharset();
			$this->view->username = '';
		}
		else
		{
			//Grab the session in case we log out
			$this->view->sesc = $_SESSION['rand_code'];
			$this->view->username = $this->_cisUser->getUser();
		}
		//Set the redirection url for SMF login/logout
		$redirect = $config->rooturl . $this->_request->getRequestUri();
		$this->_cisUser->setRedirect($redirect);

		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				//404 Error
				$this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
				$output = <<<_EOH_
<h1>404 - Page Not Found</h1>
<p>&nbsp;</p>
<p>The page you requested was not found. Please double-check the URL and try again.</p>
_EOH_;
				break;
			default:
				//Application error
				$output = <<<_EOH_
<h2>Error</h2>
<p>&nbsp;</p>
<p>An unexpected error occurred with your request. Please try again later.</p>
_EOH_;
				$exception = $errors->exception;
				$log = Zend_Registry::get('logger');
				$log->debug($exception->getMessage());
				break;
		}

		//Clear previous content and send error output to the view
	        $this->getResponse()->clearBody();
                $this->view->output = $output;
	}
}
