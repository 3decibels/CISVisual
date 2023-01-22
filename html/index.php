<?php

/**
 * CISVisual application bootstrap
 * "index.php"
 * All incoming requests are routed through this file.
 */

//Start the stopwatch
global $stopwatch;
$stopwatch = microtime(true);

//Set error reporting
error_reporting( E_ALL | E_STRICT );

//Always set your timezone when working with dates!
date_default_timezone_set('America/New_York');

//Magic quotes suck
ini_set('magic_quotes_gpc', 'Off');
ini_set('magic_quotes_runtime', 'Off');
ini_set('magic_quotes_sybase', 'Off');

//Setup Zend autoloading
require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

//Setup a variable to point to the application directory
$appDir = dirname(dirname(__FILE__)) . '/application';

//Add the models directory to the include path
set_include_path(
    $appDir . '/models'
    . PATH_SEPARATOR
    . get_include_path()
);

//Make sure to require our common functions library
require_once($appDir . '/lib/common_functions.inc.php');

//Set our site root path and register
//TODO: Is this even needed anymore?
$siteRoot = 'http://cisvisual.net/';
Zend_Registry::set('siteRoot', $siteRoot);

//Register our configuration file
$config = new Zend_Config_Ini("$appDir/etc/config.ini", 'main');
Zend_Registry::set('config', $config);

//Set error reporting output to the browser from the config
ini_set('display_errors', $config->display->errors);

//Setup and register our logger
try {
	$writer = new Zend_Log_Writer_Stream($config->log->path);
	$logger = new Zend_Log($writer);
	Zend_Registry::set('logger', $logger);
} catch (Zend_Exception $e) {
	print "<!-- LOG ERROR -->\n\n";
}

//Setup the database and register it
try {
	$db = Zend_Db::factory(
	    $config->database->adapter,
	    $config->database->params->toArray()
	);
	$db->query('SET NAMES utf8');
} catch (Zend_Db_Adapter_Exception $e) {
	$logger->alert('RDBMS connect failure:' . $e->getMessage());
	die("MySQL Connection Error- RDBMS connect failure. This would be the appropriate time for an \"uguu\"...");
} catch (Zend_Exception $e) {
	$logger->alert('Zend exception in RDBMS connect:' . $e->getMessage());
	die("MySQL Connection Error- Zend Exception. We're all gonna die!");
}
Zend_Registry::set('database', $db);


//============================================================
//Front end controller setup

//Instantiate the front router
$cntrl = Zend_Controller_Front::getInstance();

//Set the controller to throw exceptions
//$cntrl->throwExceptions(true);

//Get our router instance
$router = $cntrl->getRouter();

//Create new routes
//Static routes-----
$route = new Zend_Controller_Router_Route_Static(
	'faq',
	array('controller' => 'index', 'action' => 'faq')
);
$router->addRoute('faq', $route);
$route = new Zend_Controller_Router_Route_Static(
	'about',
	array('controller' => 'index', 'action' => 'about')
);
$router->addRoute('about', $route);
//Dynamic routes-----
$route = new Zend_Controller_Router_Route(
	'title/:tid',
	array(
		'controller'	=> 'title',
		'action'	=> 'view'
	),
	array('tid' => '\d+')
);
$router->addRoute('title', $route);
$route = new Zend_Controller_Router_Route(
	'title/detail/:tid',
	array(
		'controller'	=> 'title',
		'action'	=> 'detail'
	),
	array('tid' => '\d+')
);
$router->addRoute('title/detail', $route);
$route = new Zend_Controller_Router_Route(
        'rate/:tid',
        array(
                'controller'    => 'rate',
                'action'        => 'index'
        ),
        array('tid' => '\d+')
);
$router->addRoute('rate', $route);
$route = new Zend_Controller_Router_Route(
	'list/*',
	array('controller' => 'search', 'action' => 'list')
);
$router->addRoute('list', $route);
$route = new Zend_Controller_Router_Route(
	'admin/review/:tid',
	array(
		'controller'    => 'admin',
		'action'        => 'review'
	),
	array('tid' => '\d+')
);
$router->addRoute('review', $route);

//Dispatch the controller
try {
	$cntrl->run($appDir . '/controllers');
} catch (Zend_Exception $e) {
	include('big_error.phtml');
	exit();
}
	

?>
