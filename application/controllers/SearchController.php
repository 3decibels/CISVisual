<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class SearchController extends Zend_Controller_Action
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
	 * Main search view
	 */
	public function indexAction()
	{
		//Set the page title
		$this->view->title = 'CISVisual -- Search';

		//Temp until the real search stuff gets built
		$this->_forward('list');
	}


	/**
	 * Displays a list of titles
	 */
	public function listAction()
	{
		//Set the page title
		$this->view->title = 'CISVisual -- Visual Novel Index';

		//Sets a base limit value
		$baseLimit = 30;

		$page = $this->_getParam('page', 1);
		$limit = $this->_getParam('limit', $baseLimit);
		$order = $this->_getParam('order', 'name');
		$desc = $this->_hasParam('desc');

		//Filter the page and limits to ensure they are numeric
		if (!ctype_digit($page))
		{
			$page = 1;
		}
		if (!ctype_digit($limit))
		{
			$limit = $baseLimit;
		}

		//Instantiate database
		$db = Zend_Registry::get('database');

		//Build query
		$select = $db->select();
		$select->from('cis_titles',
			array('titleID', 'titleName', 'titleYear', 'titleAvailable'));
		$select->where('titleStatus = \'active\'');
		switch ($order)
		{
			case 'year':
				$args[] = ($desc)? 'titleYear DESC' : 'titleYear';
				$args[] = 'titleName';
				$select->order($args);
				break;
			case 'available':
				$args[] = ($desc)? 'titleAvailable DESC' : 'titleAvailable';
				$args[] = 'titleName';
				$select->order($args);
				break;
			default:
			case 'name':
				$select->order(($desc)? 'titleName DESC' : 'titleName');
				break;
		}
		$select->limitPage($page, $limit);

		//Perform query
		$stmt = $db->query($select);
		$titlesData = $stmt->fetchAll();
		$totalTitles = $db->fetchOne('SELECT COUNT(*) FROM cis_titles WHERE titleStatus = \'active\'');

		//If we returned an empty set render a fitting response
		if (empty($titlesData))
		{
			$this->render('search/listnone', null, true);
			return;
		}

		//Set a basic URL for changing list options
		$url = $this->_request->getBaseUrl() . '/list';
		if ($baseLimit != $limit)
		{
			$url .= '/limit/' . $limit;
		}

		//Build a list URL off of current non-page data for page tabbing
		$pageUrl = $url;
		if ('name' != $order)
                {
                        $pageUrl .= '/order/' . $order;
                }

//Uncomment this block to have switching order types retain the current page number.
//Slightly ridiculous to do since you're entering a whole different page context anyway...
/*		//Add page data retension to the URLs
		if (1 != $page)
                {
                        $url .= '/page/' . $page;
                }
*/

		//Build a URL for the name link
		$nameUrl = $url;
		if ('name' == $order && false === $desc)
		{
			$nameUrl .= '/desc';
		}

		//Build a URL for the year link
		$yearUrl = $url . '/order/year';
                if ('year' == $order && false === $desc || 'year' != $order)
                {
                        $yearUrl .= '/desc';
                }

		//Build a url for the availability link
		$availableUrl = $url . '/order/available';
                if ('available' == $order && false === $desc)
                {
                        $availableUrl .= '/desc';
                }

		//Are we listing descending order?
		if (true === $desc)
		{
			$this->view->desc = true;
		}

		//Send data to view
		$start = $page * $limit - $limit;
		$this->view->data = $titlesData;
		$this->view->start = $start + 1;
		$this->view->end = $start + count($titlesData);
		$this->view->total = $totalTitles;
		$this->view->page = $page;
		$this->view->lastPage = ceil($totalTitles / $limit);
		$this->view->limit = $limit;
		$this->view->pageUrl = $pageUrl;
		$this->view->nameUrl = $nameUrl;
		$this->view->yearUrl = $yearUrl;
		$this->view->availableUrl = $availableUrl;		
	}

}
