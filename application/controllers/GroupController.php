<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class GroupController extends Zend_Controller_Action
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
		$this->_model = new GroupModel;

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

		//Form requires a view helper
		$config = Zend_Registry::get('config');
		$this->view->addHelperPath($config->helper->path, 'My_View_Helper');
	}

	
	/**
	 * View the rating info for a title
	 */
	public function indexAction()
	{
		//TODO: Make this the list action?
	}


	/**
	 * Function to add a group to the database
	 */
	public function addAction()
	{
		//Set the page title
		$this->view->title = 'Add a Translation Group';

		//Make sure we're logged in
		//Uh oh, no login...
                if (! $this->_cisUser->checkLogged())
                {
	                $this->render('login', null, true);
                        return;
                }
                //Egads, not permitted!
                $this->_cisUser->aclSetup();
                if (! $this->_cisUser->checkPermission('submit', 'group'))
                {
	                $this->render('denied', null, true);
                        return;
                }

		//Initialize data arrays
		$values = array();
		$errors = array();

		//Have we submitted the form yet?
		if ($this->_request->isPost())
		{
			$values = $this->getRequest()->getPost();
			//As usual, this is dumb...
			foreach ($values as $key => $value)
			{
				$values[$key] = stripslashes($value);
			}

			$errors = $this->_model->validateAdd($values);
			if (empty($errors))
			{
				$values['userID'] = $this->_cisUser->getUserID();
				$result = $this->_model->processAdd($values);
				$this->render('group/addsuccess');
				return;
			}
		}

		//Prepare to render the form
		$config = Zend_Registry::get('config');
		$this->view->addHelperPath($config->helper->path, 'My_View_Helper');

		//Generate form instance token
		$_SESSION['cisvtoken'] = hash('md5',uniqid(mt_rand(),true));
		$this->view->auth = $_SESSION['cisvtoken'];

		//Send data arrays to the view
		$this->view->values = $values;
		$this->view->errors = $errors;
	}

}
?>
