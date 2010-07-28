<?php

/**
 * Batch API methods, these are methods which are not in the context of a model
 *
 * @package API
 */
class Batch_API {
	
	/**
	 * run multiple other api methods
	 *
	 * @param string $lilurl
	 * @return string - results
	 */
	public static function run($params) {

		API::requireParams($params, array('batch_requests'));

		$batch_requests = json_decode($params['batch_requests'], 1);	
		
		$results = array();
		
		// call each of these requests and return the results
		foreach( $batch_requests as $request ){
			$results[] = json_decode(file_get_contents('http://api-cluster.whereivebeen.com/?'.http_build_query($request)), 1);
		}

		return API::results($results);

	}
	
}