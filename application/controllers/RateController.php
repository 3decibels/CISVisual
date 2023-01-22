<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class RateController extends Zend_Controller_Action
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
		$this->_model = new RateModel;

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
		//Get the titleID param
		$titleID = $this->_getParam('tid');

		//Oops, no title ID
		if (! $titleID)
		{
			$this->render('title/notitle', null, true);
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
		if (! $this->_cisUser->checkPermission('submit', 'vote'))
		{
			$this->render('denied', null, true);
			return;
		}

		//Set the page title
		$this->view->title = 'CISVisual -- Submit Rating';

		//Send titleID
		$this->view->titleID = $titleID;

		//Freakin title name
		$titleMod = new TitleModel;
		$this->view->titleName = $titleMod->getTitleName($titleID);

		//Authorization token
		$_SESSION['cisvtoken'] = md5(uniqid(mt_rand(), true));
		$this->view->auth = $_SESSION['cisvtoken'];

		//Retrieve previous rating, if any
		$ratingData = array();
		$ratingData = $this->_model->getRating($this->_cisUser->getUserID(), $titleID);
		$this->view->ratingData = $ratingData;

		//Send empty errors array just because!
		$this->view->errors = array();

		//Create and send arrays to the view
		$this->createOptions();

		$this->render('form');
		return;
	}


	/**
	 * Submit a basic rating
	 */
	public function basicAction()
	{
		//Set the page title
		$this->view->title = 'CISVisual -- Submit Rating';

		//Get the titleID param
		$titleID = $this->_getParam('tid');

		//Oops, no title ID
		if (! $titleID)
		{
			$this->render('title/notitle', null, true);
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
		if (! $this->_cisUser->checkPermission('submit', 'vote'))
		{
			$this->render('denied', null, true);
			return;
		}

		//Send titleID
		$this->view->titleID = $titleID;

		//Freakin title name
		$titleMod = new TitleModel;
		$this->view->titleName = $titleMod->getTitleName($titleID);


		//Have we posted yet?
		if ($this->_request->isPost())
		{
			//Perform validation
			$formValues = $this->getRequest()->getPost();
			$formValues['titleID'] = $titleID;

			$errors = $this->_model->validateBasic($formValues);
			if (empty($errors))
			{
				//Set the userID
				$formValues['userID'] = $this->_cisUser->getuserID();
				//Setup and execute add
				$result = $this->_model->processBasic($formValues);
				if ('SUCCESS' == $result)
				{
					$this->view->rating = $formValues['ratingBasic'];
					$this->render('basicsuccess');
				}
				elseif ('FAIL' == $result)
				{
					$this->render('ratefailure');
				}
				return;
			}
			//There were errors...
			$this->view->errors = $errors;
		}
		//If we've hit this point, we need to show the form again

		//Retrieve previous rating, if any
		$ratingData = array();
		$ratingData = $this->_model->getRating($this->_cisUser->getUserID(), $titleID);
		$this->view->ratingData = $ratingData;

		//Create and send arrays to the view
		$this->createOptions();

		//Authorization token
		$_SESSION['cisvtoken'] = md5(uniqid(mt_rand(), true));
		$this->view->auth = $_SESSION['cisvtoken'];

		//Render!
		$this->render('form');
	}


	/**
	 * Submit a batch rating
	 */
	public function batchAction()
	{
		//Set the page title
		$this->view->title = 'CISVisual -- Submit Rating';

		//Get the titleID param
		$titleID = $this->_getParam('tid');

		//Oops, no title ID
		if (! $titleID)
		{
			$this->render('title/notitle', null, true);
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
		if (! $this->_cisUser->checkPermission('submit', 'vote'))
		{
			$this->render('denied', null, true);
			return;
		}

		//Send titleID
		$this->view->titleID = $titleID;

		//Freakin title name
		$titleMod = new TitleModel;
		$this->view->titleName = $titleMod->getTitleName($titleID);


		//Have we posted yet?
		if ($this->_request->isPost())
		{
			//Perform validation
			$formValues = $this->getRequest()->getPost();
			$formValues['titleID'] = $titleID;

			$errors = $this->_model->validateBatch($formValues);
			if (empty($errors))
			{
				//Set the userID
				$formValues['userID'] = $this->_cisUser->getuserID();
				//Setup and execute add
				$result = $this->_model->processBatch($formValues);
				if ('SUCCESS' == $result)
				{
					$this->view->rating = $formValues['ratingTotal'];
					$this->render('batchsuccess');
				}
				elseif ('FAIL' == $result)
				{
					$this->render('ratefailure');
				}
				return;
			}
			//There were errors...
			$this->view->errors = $errors;
		}
		//If we've hit this point, we need to show the form again

		//Retrieve previous rating, if any
		$ratingData = array();
		$ratingData = $this->_model->getRating($this->_cisUser->getUserID(), $titleID);
		//If previous form values entered, bring them over
		if (isset($formValues) && is_array($ratingData))
		{
			//Overwrite database data with submitted data
			$ratingData = array_merge($ratingData, $formValues);
		}
		elseif (isset($formValues))
		{
			$ratingData = $formValues;
			$ratingData['ratingMethod'] = null;
		}
		$this->view->ratingData = $ratingData;

		//Create and send arrays to the view
		$this->createOptions();

		//Authorization token
		$_SESSION['cisvtoken'] = md5(uniqid(mt_rand(), true));
		$this->view->auth = $_SESSION['cisvtoken'];

		//Render!
		$this->render('form');
	}


	/**
	 * Remove a rating
	 */
	public function removeAction()
	{
		//Set the page title
		$this->view->title = 'CISVisual -- Submit Rating';

		//Get the titleID param
		$titleID = $this->_getParam('tid');

		//Oops, no title ID
		if (! $titleID)
		{
			$this->render('title/notitle', null, true);
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
		if (! $this->_cisUser->checkPermission('submit', 'vote'))
		{
			$this->render('denied', null, true);
			return;
		}

		//Send titleID
		$this->view->titleID = $titleID;

		//Freakin title name
		$titleMod = new TitleModel;
		$this->view->titleName = $titleMod->getTitleName($titleID);


		//Have we posted yet?
		if ($this->_request->isPost())
		{
			//Perform validation
			$formValues = $this->getRequest()->getPost();
			$formValues['titleID'] = $titleID;

			$errors = $this->_model->validateRemove($formValues);
			if (empty($errors))
			{
				//Set the userID
				$formValues['userID'] = $this->_cisUser->getuserID();
				//Setup and execute add
				$result = $this->_model->processRemove($formValues);
				if ('SUCCESS' == $result)
				{
					$this->render('removesuccess');
				}
				elseif ('FAIL' == $result)
				{
					$this->render('ratefailure');
				}
				return;
			}
			//There were errors...
			$this->view->errors = $errors;
		}
		//If we've hit this point, we need to show the form again

		//Retrieve previous rating, if any
		$ratingData = array();
		$ratingData = $this->_model->getRating($this->_cisUser->getUserID(), $titleID);
		$this->view->ratingData = $ratingData;

		//Create and send arrays to the view
		$this->createOptions();

		//Authorization token
		$_SESSION['cisvtoken'] = md5(uniqid(mt_rand(), true));
		$this->view->auth = $_SESSION['cisvtoken'];

		//Render!
		$this->render('form');
	}


	/**
	 * Function to create option arrays and send to the view
	 */
	private function createOptions()
	{
		$this->view->ratingBasicOptions = array(
			''	=> '--No Rating--',
			'5'	=> '5 - I Loved It',
			'4'	=> '4 - I Liked It',
			'3'	=> '3 - It Was OK',
			'2'	=> '2 - I Didn\'t Like It',
			'1'	=> '1 - I Hated It'
		);
		$this->view->ratingStoryOptions = $this->view->ratingBasicOptions;
		$this->view->ratingCharacterOptions = array(
			''	=> '--No Rating--',
			'5'	=> '5 - I Loved Them',
			'4'	=> '4 - I Liked Them',
			'3'	=> '3 - They Were OK',
			'2'	=> '2 - I Didn\'t Like Them',
			'1'	=> '1 - I Hated Them'
		);
		$this->view->ratingArtOptions = $this->view->ratingBasicOptions;
		$this->view->ratingMusicOptions = $this->view->ratingBasicOptions;
		$this->view->ratingVoiceOptions = $this->view->ratingBasicOptions;
		$this->view->ratingTotalOptions = array(
			''	=> '--No Rating--',
			'auto'	=> 'Auto-Complete',
			'5'	=> '5 - I Loved It',
			'4'	=> '4 - I Liked It',
			'3'	=> '3 - It Was OK',
			'2'	=> '2 - I Didn\'t Like It',
			'1'	=> '1 - I Hated It'
		);
	}
}
?>
