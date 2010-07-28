<?php

/**
 * Base business logic for the API methods
 *
 * @package API
 */
class API {
	
	/**
	 * Current API endpoint
	 *
	 * @var string
	 */
	public static $endpoint = '';
	
	/**
	 * Params passed to the API
	 *
	 * @var array
	 */
	public static $params = array();
	
	/**
	 * Current http request method, GET, POST or DELETE 
	 *
	 * @var string
	 */
	public static $request_method = '';
	
	/**
	 * The method all data passes through to be output
	 *
	 * @param array $response
	 */	
	protected static function encode_response(array $response) {
		self::log_response($response);
		return json_encode($response);
	}
	
	/**
	 * Return an encoded list of results
	 *
	 * @param array $results 
	 * @param string $message - (optional) Success message 
	 * @return string
	 */
	public static function results(array $results, $message = '') {
		
		// and models in the results array are converted to a hash representation
		foreach( $results as $k => $v ){
			if( $v instanceOf Model ){
				// a models values, keyed with the global object id
				$results[$v->_object_id()] = $v->_to_array();
				// unset the original
				unset($results[$k]);
			}
		}
		
		// standardized way to output responses
		return self::encode_response(array(
			'results' => $results,
			'message' => $message,
			'success' => 1,			
		));	
	}
	
	/**
	 * Return a single model, this is a wrapper for results
	 *
	 * @param array $results 
	 * @param string $message - (optional) Success message 
	 * @return string
	 */
	public static function result(Model $model, $message = '') {
		
		// the to array method to get the params out of a model
		$results = array();
		
		// a models values, wrapped in an array because we always return an keyed hash of
		$results[$model->_object_id()] = $model->_to_array();
		
		// standardized way to output responses
		return self::results($results, $message);
		
	}
	
	/**
	 * Return an encoded success message
	 *
	 * @param string $message - (optional) Success message
	 * @return string
	 */
	public static function success($message = '') {
		return self::encode_response((array(
			'message' => $message,
			'success' => 1,
		)));
	}	
	
	/**
	 * Return an encoded error message
	 *
	 * @param string $message - (optional) Error message
	 * @return string
	 */
	public static function error($message = '') {
		return self::encode_response((array(
			'message' => $message,
			'success' => 0,
		)));
	}
		
	/**
	 * Throw an exception if the given parameters don't exist
	 *
	 * @param array $current_params 
	 * @param string|array $required_params 
	 * @return void
	 */
	public static function require_params(array $current_params, $required_params) {
		
		// to make the code simple, and allow a single key or array of keys
		if ( ! is_array($required_params) ) {
			$required_params = array($required_params);
		}
		
		$missing_params = array();

		// create a list of missing parameters
		foreach ( $required_params as $param ) {
			if ( !isset($current_params[$param]) || empty($current_params[$param]) ) {
				$missing_params[] = $param;
			}
		}
		
		// if any params are missing, throw an exception
		if ( count($missing_params) > 0 ) {
			throw new Exception("Missing required parameter".(isset($missing_params[1]) ? "s" : "").": ".implode(', ', $missing_params));
		}
	}
	
	/**
	 * Send API response data to a log file
	 *
	 * @param array $response - Expects a success key
	 * @return void
	 */
	protected static function log_response($response) {		
		
		// the data to log
		$data = array(
			'date' => time(),			
			// 'requestID' => self::getRequestID(),
			// 'consumer_key' => self::$consumerKey,
			'remote_addr' => array_val($_SERVER, 'REMOTE_ADDR'),			
			// 'personID' => (self::$currentUser instanceOf User ? self::$currentUser->getID() : NULL),
			'method' => self::$request_method,
			'success' => (int) array_val($response, 'success'),
			'query_str' => array_val($_SERVER, 'QUERY_STRING'),
			'local_host' => php_uname('n'),				
			'duration' => (microtime(true) - START_TIME),
		);
		
		// log
		log_to_file('api_method_log', $data);
	}
	
}
