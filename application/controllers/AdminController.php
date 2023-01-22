<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class AdminController extends Zend_Controller_Action
{
	/**
	 * @var CisUserModel $_cisUser
	 */
	protected $_cisUser;
	private $_permitted = false;		//User is not permitted to use admin functions until cleared

	public function init()
	{
		//Setup some basic variables
		$config = Zend_Registry::get('config');
		$this->_cisUser = new CisUserModel($config->forum->path);
		$this->_cisUser->aclSetup();
		$this->_model = new AdminModel;

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

		//User logged in?
		if (! $this->_cisUser->checkLogged())
		{
			//Uh oh, log in please!
			$this->render('login', null, true);
			return;
		}

		//Make sure we have permission to view the freakin admin console
		$this->_cisUser->aclSetup();
		if (! $this->_cisUser->checkPermission('view', 'adminConsole'))
		{
			//Not permitted, too bad
			$this->render('denied', null, true);
			return;
		}

		//Having cleared the permission constructs without returning, we must have permission to be here!
		$this->_permitted = true;
	}

	/**
	 * Function that displays the admin index
	 */
	public function indexAction()
	{
		//Permitted?
		if (false == $this->_permitted) { return; }

		//We need an edit model
		$edits = new EditModel;
		//Set the title
		$this->view->title = 'CISVisual -- Administration';

		//Get the page data
		$this->view->stats = $this->_model->getIndexStats();
	}


	/**
	 * Function that displays the approval list
	 */
	public function pendingAction()
	{
		//Permitted?
		if (false == $this->_permitted) { return; }

		//Set the title
		$this->view->title = 'CISVisual -- Incoming Titles';

		//Grab the titles info
		$this->view->titles = $this->_model->getPendingTitles();
	}


	/**
	 * Function to process title reviews
	 */
	public function reviewAction()
	{
		//Permitted?
		if (false == $this->_permitted) { return; }

		//Set the title
		$this->view->title = 'CISVisual -- Title Approval';

		//Get the titleID
		$titleID = $this->_getParam('tid', null);


		//Make sure we have permission to approve a title
		if (! $this->_cisUser->checkPermission('approve', 'title'))
		{
			//Not permitted, too bad
			$this->render('denied', null, true);
			return;
		}


		//Is the title valid?
		if (! $this->_model->checkPending($titleID))
		{
			$this->render('admin/notitle', null, true);
			return;
		}

		//Initialize errors for posterity
		$errors = array();

		//Have we submitted the form yet?
		if ($this->_request->isPost())
		{
			//Turn POST array into our values variable
			$formValues = $this->_request->getPost();
			//This un-escaping is still just ridiculous
			foreach ($formValues as $key => $value)
			{
				if (! is_array($value))
				{
					$formValues[$key] = stripslashes($value);
				}
			}
			$formValues['titleID'] = $titleID;

			//Check for errors
			$errors = $this->_model->validateReview($formValues);
			if (empty($errors))
			{
				//No errors, let's process the data
				$result = $this->_model->processReview($formValues);
				$this->view->message = $result['message'];
				if ('SUCCESS' == $result['result'])
				{
					//Success!
					$this->render('admin/reviewsuccess', null, true);
					return;
				}
				else
				{
					//Failure. Crap.
					$this->render('admin/reviewfailure', null, true);
					return;
				}
			}
		}
		//If we made it this far, we have to display the form

		//TODO: Implement XSS security token.

		//Form requires a view helper
		$config = Zend_Registry::get('config');
		$this->view->addHelperPath($config->helper->path, 'My_View_Helper');

		//Grab the title data
		$this->view->data = $this->_model->getReviewData($titleID);

		//Pass the errors to the view
		$this->view->errors = $errors;
	}
}
?>
