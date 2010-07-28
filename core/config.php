<?php

// option to force the configuration files to be read again from files
if ( isset($_REQUEST['flush_config']) ) {
	define('FLUSH_CONFIG', true);
}

// a couple settings below rely on the host name
$hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : php_uname('a');

// this cant live in a configuration file, because we need it to load cached configurations
define('APC_PREFIX', 'apc_'.md5($hostname));

// get the main configuration from the core.ini file
$config = Configuration::get('core');

// dev, staging, or production?
define('ENV', array_val_required($config, 'environment'));

// dev-specific stuff
if ( ENV == 'dev' ) {
	// use the current time as the revision ID
	define('REVISION_ID', time());
	
	// don't use the rev id in the mc prefix in dev, because the rev id is always different
	define('MEMCACHED_PREFIX', $hostname.'_'.Configuration::get_value('memcached_prefix', 'cache'));
}
// production
else {
	// used the stored revision ID from the last production push
	define('REVISION_ID', Configuration::get_value('revision_id', 'revision'));
	
	// the combo of hostname + env + revision_id *should* keep our caches from running into each other
	define('MEMCACHED_PREFIX', $hostname.Configuration::get_value('memcached_prefix', 'cache').REVISION_ID.'_');	
}

// this is used to set up the URIs below and help catch dev vs production environments
define('ROOT_DOMAIN', array_val_required($config, 'root_domain'));

// constants
define('MYSQL_DATETIME','Y-m-d H:i:s');
define('MYSQL_DATE','Y-m-d');

// protocol and http request method
define('PROTOCOL', ( isset($_SERVER['HTTPS']) ? 'https' : 'http' ) );

// it can be overriden for testing, and it defaults to the $_SERVER global value
define('REQUEST_METHOD', strtoupper(array_val($_REQUEST, 'request_method', $_SERVER['REQUEST_METHOD'])));

// fail if this is not an accepted REQUEST_METHOD
if( REQUEST_METHOD != 'POST' && REQUEST_METHOD != 'PUT' && REQUEST_METHOD != 'GET' && REQUEST_METHOD != 'DELETE'){
	die(REQUEST_METHOD.' is not an accepted http request_method');
}

// default API method
define('DEFAULT_API_METHOD', array_val_required($config, 'default_api_method'));
