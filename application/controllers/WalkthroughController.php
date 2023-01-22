<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class WalkthroughController extends Zend_Controller_Action
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
		$this->_model = new WalkthroughModel;

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

		//Forms require a view helper
		$this->view->addHelperPath($config->helper->path, 'My_View_Helper');
	}

	
	/**
	 * Submit a walkthrough
	 */
	public function addAction()
	{
		//Set the page title
		$this->view->title = 'CISVisual -- Submit a Walkthough';

		//Get the titleID param
		$titleID = $this->_getParam('tid');

		//Oops, no title ID
		if (! $titleID)
		{
			$this->view->content = 'No title specified for walkthough addition.';
			$this->render('error', null, true);
			return;
		}

		//Uh oh, no login...
		if (! $this->_cisUser->checkLogged())
		{
			$this->render('login', null, true);
			return;
		}

		//Egads, not permitted!
		$this->_cisUser->aclSetup();
		if (! $this->_cisUser->checkPermission('submit', 'walkthrough'))
		{
			$this->render('denied', null, true);
			return;
		}

		//Send titleID
		$this->view->titleID = $titleID;

		//Freakin title name
		$titleMod = new TitleModel;
		$this->view->titleName = $titleMod->getTitleName($titleID);

		//Array for posterity!
		$errors = array();

		//Have we posted yet?
		if ($this->_request->isPost())
		{
			//Get vars from post
			$formValues = $this->getRequest()->getPost();
			//Stupid, stupid, stupid
			$formValues = strip_array($formValues);
			$formValues['titleID'] = $titleID;

			//Perform validation
			$errors = $this->_model->validateAdd($formValues);
			if (empty($errors))
			{
				//Set the userID
				$formValues['userID'] = $this->_cisUser->getuserID();
				//Setup and execute add
				$result = $this->_model->processAdd($formValues);
				if ('SUCCESS' == $result)
				{
					$this->render('addsuccess');
				}
				elseif ('FAIL' == $result)
				{
					$this->render('error', null, true);
				}
				return;
			}
			//There were errors...
			$this->view->errors = $errors;
		}
		//If we've hit this point, we need to show the form 

		//Authorization token
		$_SESSION['cisvtoken'] = md5(uniqid(mt_rand(), true));
		$this->view->auth = $_SESSION['cisvtoken'];

		//Send errors and values to the view
		$this->view->errors = $errors;
		$this->view->values = $formValues;

		//We are in add mode, not edit
		$this->view->edit = false;

		//This form has a script
		$header['scripts'][] = 'walkthrough_add.js';
		$this->view->header = $header;
	}


	/**
	 * Function to display a walkthrough
	 */
	public function viewAction()
	{
		//Get the titleID param
		$walkID = $this->_getParam('wid');

		//Oops, no title ID
		if (!$walkID)
		{
			$this->view->content = 'No walkthrough ID specified.';
			$this->view->title = 'CISVisual - Error';
			$this->render('error', null, true);
		        return;
		}

		$walkthrough = $this->_model->getWalkthrough($walkID);

		//No walkthrough?
		if (false === $walkthrough)
		{
			$this->view->content = 'Invalid walkthrough ID.';
			$this->view->title = 'CISVisual - Error';
			$this->render('error', null, true);
			return;
		}

		//Increment the access counter
		$this->_model->incrementCounter($walkID);


		//Send the walkthrough to the view
		$this->view->walkthrough = $walkthrough['walkText'];
		$this->view->title = $walkthrough['walkTitle'] . ' -- CISVisual';
	}


	/**
	 * Function to force a file download of the walkthrough
	 */
	public function downloadAction()
	{
		//Get the titleID param
		$walkID = $this->_getParam('wid');

		//Oops, no title ID
		if (!$walkID)
		{
			$this->view->content = 'No walkthrough ID specified.';
			$this->view->title = 'CISVisual - Error';
			$this->render('error', null, true);
			return;
		}

		$walkthrough = $this->_model->getWalkthrough($walkID);

		//No walkthrough
		if (false === $walkthrough)
		{
			$this->view->content = 'Invalid walkthrough ID.';
			$this->view->title = 'CISVisual - Error';
			$this->render('error', null, true);
			return;
		}

		//Increment the access counter
		$this->_model->incrementCounter($walkID);

		//Get rid of output compression or IE will fail
		if(ini_get('zlib.output_compression'))
		ini_set('zlib.output_compression', 'Off');

		//Header control
		$response = $this->getResponse();
		$response->clearAllHeaders();
		$response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
		$response->setHeader('Content-Description', 'File Transfer', true);
		$response->setHeader('Content-Type', 'application/octet-stream', true);
		$response->setHeader('Content-Length', strlen($walkthrough['walkText']), true);
		$response->setHeader('Content-Disposition', 'attachment; filename="' . $walkthrough['walkTitle'] . '.txt";', true);

		//Set script execution time limit in case of large files
		set_time_limit(0);

		//Send the output text to the view
		$this->view->walkthrough = $walkthrough['walkText'];
	}
}

?>
