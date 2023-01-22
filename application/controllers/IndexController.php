<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class IndexController extends Zend_Controller_Action
{
	/**
	 * @var CisUserModel $_cisUser
	 */
	protected $_cisUser;

	public function init()
	{
		//Setup some basic variables
		$config = Zend_Registry::get('config');
		$this->_cisUser = new CisUserModel($config->forum->path);

		//Begin putting together our view
		$this->view->baseUrl = $this->_request->getBaseUrl();
		$this->view->adminMail = $config->admin->email;
		$this->view->version = $config->version;

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
	}

	public function indexAction()
	{

		//Set the title
		$this->view->title = 'CISVisual -- Visual Novels';

		//If people are referred from fuckhead's site (nnl1.com), they get my special notice instead of the normal main page
		if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'nnl1.com'))
		{
			$this->render('index/nnl', null, true);
			return;
		}
		

		//Enable the keyword and description meta tags for the index
		$this->view->metaKey = true;

		//Config for the SMF news SSI
		$config = Zend_Registry::get('config');	
		$this->view->forumPath = $config->forum->path;
		$this->view->news = $config->forum->news->toArray();
	}

	public function faqAction()
	{
		$this->view->title = 'CISVisual -- FAQ';
	}

	public function aboutAction()
	{
		$this->view->title = 'CISVisual -- About CISVisual';
	}
}
?>
