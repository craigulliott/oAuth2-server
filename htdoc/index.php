<?php

// include the core for the framework
require '../core/header.php';

// the parameters which came in from apache, and from our .htaccess routing table (mod_rewrite)
$api_endpoint = array_val_required($params, 'endpoint');

// api classes are namespaced with the suffix "_API", this ensures internal
// classes (which have an underscore prefix) are not addressable from outside
$api_class = $api_endpoint.'_API';

/**
 * what method are we calling on the API
 * 
 * if we are calling a custom method, then its in the form {REQUEST_METHOD}_{METHOD}
 * 
 * if we are not calling a custom method, then we simply use the {REQUEST_METHOD}
 * 
 * example "http GET /world/hello" would call "world_API::get_hello($params)"
 *
 * @author Craig Ulliott
 */
$api_method = REQUEST_METHOD;
if ( $custom_method = array_val($params, 'method') ){
	$api_method .= '_'.array_val($params, 'method');
}

// the entire API is wrapped in a try/catch so we can create nice json error messages
try{

	// fail if the api method is not a valid name
	if( ! preg_match('/^[a-zA-Z][a-zA-Z_]*[a-zA-Z]$/', $api_endpoint) ){
		throw new exception("Invalid API endpoint");
	}

	// load the FILE containing the API endpoint (sanity checking was done above)
	require_once API_PATH.strtolower($api_endpoint).'.php';

	// fail if the class doesn't exist
	if ( ! class_exists($api_class) ) {
		throw new exception("API class `{$api_class}` does not exist");
	}

	// fail if the requested api method is invalid (unsafe) 
	if( ! preg_match('/^[a-zA-Z][a-zA-Z_]*[a-zA-Z]$/', $api_method) ){
		throw new exception("Invalid API method");
	}

	// fail if the api method doesn't exist in that class
	if ( ! method_exists($api_class, $api_method) ) {
		throw new exception("API method `{$api_method}` does not exist");
	}
	
	// basic sanity checks are complete, call the message and capture the output
	$response = call_user_func_array(array($api_class, $api_method), array($params));
	
	
}catch(exception $e){
	// catch any errors
	error_log($ex->getMessage());
	$response = API::error($ex->getMessage());
	
}



/**
 * this will take some useful debug information and send back to the client via http headers, because headers are
 * essentially public you should only put things here that you don't mind others seeing
 *
 * @author Craig Ulliott
 */
// the excecution time of the script up until this point in seconds
$seconds = (string) ( microtime(true) - START_TIME );
header('Excecution-Time: seconds='.$seconds);
	
// the weight on the framework from the various things we are logging
$types = Log::get_all_counts();
header('Framework-Weight:'.trim( http_build_query($types, null, ', '), ' ,'));




// the content type
header('Content-type: application/json');

// the response
echo $response;
