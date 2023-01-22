<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class TitleController extends Zend_Controller_Action
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
		$this->_cisUser->aclSetup();
		$this->_model = new TitleModel;

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


	/**
	 * Default action fowards control to the VN list
	 */
	public function indexAction()
	{
		$this->_forward('list', 'search');
	}


	/**
	 * Function to add a title to the database
	 */
	public function addAction()
	{
		//Start by setting the page title
		$this->view->title = 'CISVisual -- Add a Title';		

		//To add a title, we must be logged in
		if (! $this->_cisUser->checkLogged())
		{
			//Uh oh, log in please!
			$this->render('login', null, true);
			return;
		}

		//Make sure we have permission to submit a title
		if (! $this->_cisUser->checkPermission('submit', 'title'))
		{
			//Not permitted, too bad
			$this->render('denied', null, true);
			return;
		}

		//For posterity, we must initialize the errors array
		$errors = array();
		
		//Have we posted yet?
		if ($this->_request->isPost())
		{
			//Perform validation
			$formValues = $this->getRequest()->getPost();
			//I can't believe I have to strip slashes...
			foreach ($formValues as $key => $value)
			{
				$formValues[$key] = stripslashes($value);
			}

			$errors = $this->_model->validateAdd($formValues);
			if (empty($errors))
			{
				//Set the status based on ACL permissions
				$formValues['titleStatus'] = 'pending';
				//Set the userID
				$formValues['userID'] = $this->_cisUser->getuserID();
				//Setup and execute add
				$result = $this->_model->processAdd($formValues);
				if ('SUCCESS' == $result)
				{
					$this->render('title/addsuccess', null, true);
					return;
				}
				else
				{
					$this->render('title/addfailure', null, true);
					return;
				}
			}
		}

		//Form requires a view helper
		$config = Zend_Registry::get('config');
		$this->view->addHelperPath($config->helper->path, 'My_View_Helper');

		//Set the form mode and action patth
		$this->view->mode = 'add';
		$this->view->action = '/title/add';

		//Generate a form instance token
		$_SESSION['cisvtoken'] = hash('md5',(uniqid(mt_rand(), true)));
		$this->view->auth = $_SESSION['cisvtoken'];

		//Create the choice arrays
		$this->view->titleTypeOptions = array(
			'false'		=> '--Select One--',
			'doujin'	=> 'Doujin Game',
			'commercial'	=> 'Commercial Game'
		);
		$this->view->titlePlotOptions = array(
			'false'		=> '--Select One--',
			'branching'	=> 'Branching (Provides Choices)',
			'linear'	=> 'Linear (No Choices)'
		);
		$this->view->titleAvailableOptions = array(
			'false'		=> '--Select One--',
			'none'		=> 'None',
			'localized'	=> 'Fan Translated',
			'licensed'	=> 'Commercially Licensed'
		);

		//Send the values and errors arrays to the view
		$this->view->formValues = $formValues;
		$this->view->errors = $errors;

	}


	/**
	 * Function to view a title
	 */
	public function viewAction()
	{
		//Set the ID and retrieve the title data
		$titleID = $this->_getParam('tid');
		$titleData = $this->_model->getSingleBasic($titleID);

		//If the title doesn't exist or is pending, redirect.
		if (! is_array($titleData))
		{
			$this->render('title/notitle', null, true);
			return;
		}

		//Get our alternate names data now that we've confirmed it's a valid ID
		$alternates = $this->_model->getAlternates($titleID);
		if (! empty($alternates))
		{
			$flag = false;
			$list = '';
			foreach ($alternates as $alt)
			{
				$list .= ($flag)? ', ' : '';
				$list .= $alt['titleName'];
				$flag = true;
			}
			$this->view->alternateList = $list;
		}


		//Set the page title
		$this->view->title = $titleData['titleName'] . ' -- CISVisual';

		//Send the title data to the view
		$this->view->titleData = $titleData;

		//Send rating data and round the weighted avg
		$this->view->rating = $this->_model->getBasicRating($titleID);
		if ('--Not Enough Ratings Recorded--' != $this->view->rating['weighted'])
		{
			$this->view->rating['weighted'] = round($this->view->rating['weighted'], 1);
		}


		//Build the image URL if applicable
		if ('1' == $titleData['titleImage'])
		{
			$imageUrl = $this->_request->getBaseUrl() . '/images/title/' . $titleID . '.' . $titleData['titleImageType'];
			$imageOutput = '<img class="float" src="' . $imageUrl . '" alt="" />';
		}
		else
		{
//			$imageOutput = '<p class="indent">This title does not yet have an image. <a href="">Add one now!</a></p>';
			$imageOutput = '';
		}
		$this->view->imageOutput = $imageOutput;


		//Build the walktrhough output
		$walkModel = new WalkthroughModel();
		$this->view->walkthroughs = $walkModel->getList($titleID);
	}



	/**
	 * Function to view detailed rating data on a title
	 */
	public function detailAction()
	{
		//Set the ID and retrieve the title data
		$titleID = $this->_getParam('tid');
		$titleName = $this->_model->getTitleName($titleID);

		//If the title doesn't exist or is pending, redirect.
		if (empty($titleName))
		{
			$this->render('title/notitle', null, true);
			return;
		}

		//Set the page title
		$this->view->title = 'Statistics for ' . $titleName . ' -- CISVisual';

		//Send the title name to the view
		$this->view->titleName = $titleName;
		
		//Send titleID
		$this->view->titleID = $titleID;

		//Get and send detail and graph data
		$this->view->rating = $this->_model->getDetailedRating($titleID);
		$this->view->graph = $this->_model->getGraph($titleID);
	}

}
?>
