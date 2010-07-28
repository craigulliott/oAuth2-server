<?php

// so we can time script execution
define('START_TIME', microtime(true));

// load balancers usually send the remote IP in on this header
// NOTE: if you rely on this functionality, make sure the physical machine is
//       not reachable directly through the internet
if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ){
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}
// load balancers send the precence of SSL in on this header
// NOTE: if you rely on this functionality, make sure the physical machine is
//       not reachable directly through the internet
if( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ){
	define('SSL','true');
}

// ------------------------------------------------------
// useful paths
define('SITE_ROOT', realpath(dirname(__FILE__).'/../').'/');
define('CORE_PATH', SITE_ROOT.'core/');
define('API_PATH', SITE_ROOT.'api/');

define('CONFIGURATION_PATH', CORE_PATH.'configuration/');

// local configuration is often useful when working in a dev environment
if ( file_exists(SITE_ROOT.'config.local.php') ) {
	require_once SITE_ROOT.'config.local.php';
}

// ------------------------------------------------------
// framework lib includes
require_once CORE_PATH.'lib/cache.php';
require_once CORE_PATH.'lib/configuration.php';
require_once CORE_PATH.'lib/db.php';
require_once CORE_PATH.'lib/log.php';
require_once CORE_PATH.'lib/api.php';

// ------------------------------------------------------
// helpers
require_once CORE_PATH.'helper/array.php';
require_once CORE_PATH.'helper/configuration.php';
require_once CORE_PATH.'helper/utility.php';

// ------------------------------------------------------
// exceptions
require_once CORE_PATH.'exception/db.php';
require_once CORE_PATH.'exception/cache.php';

// ------------------------------------------------------
// system configuration
require_once CORE_PATH.'config.php';

// ------------------------------------------------------
// enable all query logs if we're in dev or the mogoose is out to play
if ( ENV == 'dev' || isset($_REQUEST['mongoose7']) ) {
	Log::enable_all();
}

// we dont use globals, instead I like to pass the params around 
$params = $_REQUEST;
// we clear these to prevent developers using them and getting unexpected results
// basically forcing them to stick to my $params standard
$_REQUEST = $_GET = $_POST = array();
