<?php

/**
 * Generic logging framework
 * 
 * The modern web application has dependencies on a lot of services, from databases and 
 * caches to REST APIs.  With so many dependencies it is often difficult to track down 
 * bugs and bottlenecks.  
 * 
 * The goal of this class is to provide a simple lightweight mechanism to log all queries to
 * any external service.  It is designed to normalize the different services into human readable 
 * strings and duration in seconds that each query took.  It should also be possible to disable 
 * all logging for production.
 *
 * @author Craig Ulliott
 */
class Log {
	
	/**
	 * Internal query store
	 */
	private static $queries = array();
	
	/**
	 * Store enabled/disabled state of various types
	 */
	private static $enabled = array();
	
	/**
	 * Valid query types
	 */
	private static $types = array('network', 'nal', 'database', 'cache', 'aws');
	
	/**
	 * Make sure the given type is valid
	 *
	 * @param string $type   The type of query
	 * @return string        the type entered will be returned, unless it was not recognised
	 *                       in which case an exception will be thrown
	 */
	private static function validate_type($type) {
		
		// allow people to use type in a case insensitive manner
		$type = strtolower($type);
		
		// if the type is recognised then return it
		if ( in_array($type, self::$types) ) {
			return $type;
		}
		
		// the type was not recognised
		throw new Exception("Invalid query type: {$type} (must be one of ".implode(',', self::$types).")");
	}
	
	/**
	 * Add a query to the log
	 *
	 * @param string $type       a string corresponding to a recognized query type, an exception 
	 *                           will be thrown if the query type is not recognized
	 * @param string $query      a string to log which describes this query
	 * @param float $duration    the number of seconds the query took
	 * @return void              
	 * @author Craig Ulliott
	 */
	public static function add_query($type, $query, $duration = 0) {
		
		// check the type is a recognised one
		$type = self::validate_type($type);
		
		// store the query in the internal array
		if( self::is_enabled($type) ){
			self::$queries[$type][] = array($query, $duration);
		}
	}
	
	/**
	 * Returns the number of queries that have been added for the given type
	 *
	 * @param string $type 
	 * @return int
	 * @author Craig Ulliott
	 */
	public static function get_count($type) {
		
		// check the type is a recognised one
		$type = self::validate_type($type);

		return isset(self::$queries[$type]) ? count(self::$queries[$type]) : 0;
	}
	
	/**
	 * Returns the number of queries stored for all the valid typea in a keyed array
	 *
	 * @return array    a hash of all the query types with the number of queries
	 *                  that have been logged against them
	 * @author Craig Ulliott
	 */
	public static function get_all_counts() {
		
		// to hold the result
		$return = array();
		
		// get the count and builed a keyed array for each type
		foreach( self::$types as $type ){
			$return[$type] = isset(self::$queries[$type]) ? count(self::$queries[$type]) : 0;
		}
		
		return $return;
	}
	
	/**
	 * return a given number of queries, most recent first, of a given type
	 *
	 * @param string $type 
	 * @param int $count    (optional, defaults to FALSE) the number of recent queries to return
	 *                      if FALSE is given then all the queries are returned
	 * @return array
	 */
	public static function get_last_queries($type, $count = FALSE) {

		// check the type is a recognised one
		$type = self::validate_type($type);

		$last_queries = array();
		
		// are we returning all the queries
		if( $count === FALSE ){
			return isset(self::$queries[$type]) ? self::$queries[$type] : array();
		}
		
		// we are returning a specified number of queries, most recent first
		if ( isset(self::$queries[$type]) ) {
		 	return array_slice(self::$queries[$type], (self::get_count($type) - $count), $count);
		}
		
		// safe default if none were found
		return array();
	}
	
	/**
	 * Return enabled/disabled state for a given type
	 *
	 * @param string $type 
	 * @return bool
	 */
	public static function is_enabled($type) {
		$type = self::validate_type($type);
		return (isset(self::$enabled[$type]) && self::$enabled[$type]);
	}
	
	/**
	 * enabled logging for a given type
	 *
	 * @param string $type 
	 * @return void
	 */
	public static function enable($type) {
		$type = self::validate_type($type);
		self::$enabled[$type] = true;
	}
	
	/**
	 * enabled logging for a given type
	 *
	 * @param string $type 
	 * @return void
	 */
	public static function disable($type) {
		$type = self::validate_type($type);
		self::$enabled[$type] = false;
	}
	
	/**
	 * Enable all query logs
	 *
	 * @return void
	 */
	public static function enable_all() {
		foreach ( self::$types as $type ) {
			self::enable($type);
		}
	}
	
	/**
	 * Disable all query logs
	 *
	 * @return void
	 */
	public static function disable_all() {
		foreach ( self::$types as $type ) {
			self::disable($type);
		}
	}
	
	/**
	 * a wrapper function to log to a file, useful for bin scripts
	 *
	 * @param string $msg 
	 * @param string $log_file 
	 * @return bool
	 * @author Craig Ulliott
	 */
	public static function to_file($msg, $log_file){
		$log_file = SITE_ROOT.$log_file;
		$date_stamp = date('H:i:s', time());
		$msg = "$date_stamp | ".trim($msg)."\n";
		return file_put_contents($log_file, $msg, FILE_APPEND);
	}
	
}

