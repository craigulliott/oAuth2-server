<?php

/**
 * Generic caching library
 * 
 * To create a service which will scale it is necessary to use caching, most certainly 
 * several types of caching, used in a variety of ways.  
 * 
 * This class aims to abstract and drastically simplify the interface to your cache so 
 * that you can use it with with minimal code, giving you more confidence while observing 
 * the DRY principle (don't repeat yourself).  
 * 
 * As your site grows it is a certainty that your cache requirements will change, hopefully
 * it is as simple as adding more servers.  Abstracting the caches and having a standardized
 * interface should allow you to change the architecture of you caching backend without having
 * to change all of your code.
 * 
 * This class is built on top of php's standard memcache library, which was chosen over APC 
 * because it works better in multiple server environments. Consideration was made to limit
 * the memcache references, and if you wanted to use a different cache server it could be achieved
 * by wrapping access in a way which exposed get, set and delete methods.  All you would need to do
 * next would be modifying the get_cache() method below to return your object 
 * 
 * @author Craig Ulliott
 */
class Cache{

	// the memcache object
	private static $cache = null;
	// the array which holds the local cache
	private static $local_cache = array(); 

	// configuration defaults
	private static $compression = true; 
	private static $default_ttl = true; 
	private static $connect_timeout_msec = 100; 
	
	// debug modes
	private static $cache_enabled = true; 
	private static $local_cache_enabled = true; 
	
	//for testing
	private static $debug = false;
	private static $local_cache_debug = false;

	/**
	 * get the memcache object 
	 * 
	 * configuration and cache access is lazy loaded for performance reasons, the first time
	 * you call this method everything is setup and stored internally before the cache object 
	 * is returned, subsequent calls return the internal reference to the cache object 
	 * 
	 * Configuration for the caching servers is stored outside the php
	 *
	 * @return object     a Memcache object
	 * @author Craig Ulliott
	 */
	static function get_cache(){
		
		if( ! self::$cache ){
			
			$config = Configuration::get('cache');
			
			// apply any overides to the configuration
			self::$compression = array_val($config, 'compression', self::$compression);
			self::$default_ttl = array_val($config, 'default_ttl', self::$default_ttl);
			self::$connect_timeout_msec = array_val($config, 'connect_timeout_msec', self::$connect_timeout_msec);
			self::$cache_enabled = array_val($config, 'cache_enabled', self::$cache_enabled);
			self::$local_cache_enabled = array_val($config, 'local_cache_enabled', self::$local_cache_enabled);

			// apply any overides to the debug mode
			self::$debug = array_val($config, 'debug', self::$debug);
			self::$local_cache_debug = array_val($config, 'local_cache_debug', self::$local_cache_debug);

			// build the cache object and connect the servers
			self::$cache = new Memcache();
			
			// get the server list out of the configuration
			foreach( array_val($config, 'servers') as $machine_name ){
				// load the configuration block for each server
				$server_config = Configuration::get('cache', $machine_name);
				// setup this servers connection
				self::$cache->addServer($server_config['host'], $server_config['port'], false, $server_config['weight'], 1, 1, false, null); //, self::$connect_timeout_msec); 
			}
		}
		
		return self::$cache;
	}


		
	/**
	 * disable both caches
	 *
	 * @return void
	 * @author Craig Ulliott
	 */
	static function disable(){
		self::$local_cache_enabled = false; 
		self::disable_local();
	}

	/**
	 * disable just the local cache
	 *
	 * @return void
	 * @author Craig Ulliott
	 */
	static function disable_local(){
		self::$local_cache_enabled = false;
	}

	/**
	 * enable the cache
	 *
	 * @return void
	 * @author Craig Ulliott
	 */
	static function enable(){
		self::$local_cache_enabled = true; 
		self::enable_local();
	}

	/**
	 * enable the local cache
	 *
	 * @return void
	 * @author Craig Ulliott
	 */
	static function enable_local(){
		self::$local_cache_enabled = true;
	}

	/**
	 * turn on the debug mode
	 *
	 * @return void
	 * @author Craig Ulliott
	 */
	static function debug(){
		self::$debug = true; 
	}

	/**
	 * add something to the cache
	 * 
	 * you can add any variable to the cache which can be serialized, using a string as
	 * the key.  If you are using memcache as your server then two consideration are that
	 * the size of a single entry is capped at 1MB, and that performance issues will arise 
	 * with very large arrays as content is serialized before it is stored.
	 *
	 * calls to this method are also timed and logged
	 *
	 * @param string $key     the key your entry will be associated with, which you will need
	 *                        to fetch the content at a later data
	 * @param mixed $content  the content you are caching, which can be anything serializable
	 * @param int $ttl        the time to live for the cache entry, in seconds.  It may be 
	 *                        interesting to note that after the ttl has been reached content 
	 *                        is not expunged from the cache, it simply returns null if you try 
	 *                        to get it.  For this reason it is normal and healthy to have a full 
	 *                        cache, you should be using the hits and misses counts to gauge your 
	 *                        cache effectiveness
	 * @param bool $local     if true then the data will be stored in local ram too, this makes 
	 *                        the data available extremely quickly for subsequent calls to get
	 *                        this data during this script execution.  However for large datasets
	 *                        or scripts which run for a long time you will run out of memory.  
	 *                        For this reason it is defaulted to false (aka off)
	 * @return void           We do not need to return a status or success from a caching operation 
	 *                        because a cache is not supposed to be used for persistent data, ever.           
	 *                        If a cache fails, you will get notice errors in your logs.
	 * @author Craig Ulliott
	 */
	static function add($key, $content, $ttl = null, $local = false){
		
		//if no $ttl is provided then use the default
		if( $ttl === FALSE || $ttl === NULL ){
			$ttl = self::$default_ttl;
		}
		
		// is the local cache enabled and we want to cache locally
		if( $local && self::$local_cache_enabled ){
			self::local_cache($key,$content);
		}
		
		// is the main cache enabled
		if(self::$cache_enabled){

			// to time the request
			$start_time = microtime(true);	

			// add to the cache
			$success = self::get_cache()->set($key, $content, self::$compression, $ttl);

			// how long the request took
			$duration = microtime(true) - $start_time;
			
			// log the request
			Log::add_query('cache', ($success?'SET OK:':'SET FAILED:').$key, $duration);

		}
		
	}
	
	/**
	 * use this to add something to the local cache only
	 *
	 * @param string $key     the key your entry will be associated with, which you will need
	 *                        to fetch the content at a later data
	 * @param mixed $content  the content you are caching, which can be any variable
	 * @return void           We do not need to return a status or success from a caching operation 
	 *                        because a cache is not supposed to be used for persistent data, ever.           
	 * @author Craig Ulliott
	 */
	static function local_cache($key, $content){
		// is the local cache enabled
		if( self::$local_cache_enabled ) {
			// put the item in the local cache
			self::$local_cache[$key] = $content;
		}
	}
	
	/**
	 * delete an item out of both caches
	 *
	 * calls to this method are also timed and logged
	 * 
	 * @param string $key     the key which is associated with your cache entry
	 * @param bool $local     if true then the data will be removed from local cache too
	 * @return void           We do not need to return a status or success from a caching operation 
	 *                        because a cache is not supposed to be used for persistent data, ever.           
	 *                        If a cache fails, you will get notice errors in your logs.
	 * @author Craig Ulliott
	 */
	static function delete($key, $local = true){

		// to time the request
		$start_time = microtime(true);	

		// delete from the cache
		$success = self::get_cache()->delete($key);

		// how long the request took
		$duration = microtime(true) - $start_time;
		
		// log the request
		Log::add_query('cache', ($success?'DELETE OK:':'DELETE FAILED:').$key, $duration);

		// are we deleting from the local cache too
		if( $local ){
			unset(self::$local_cache[$key]);
		}
		
		return $success;
	}
		
	/**
	 * get an entry, or entries out of the cache
	 *
	 * @param string|array $key   a key or array of keys for data to be loaded from the cache
	 * @param bool $local         if true then the data will be removed from local cache too
	 * @return mixed              will return the data which exists in the cache, associated with
	 *                            the provided key, or will return false if the data does not exit 
	 *                            or has expired.  If an array of keys was provided then a keyed array 
	 *                            of cache entries will be returned guaranteed in the same order as it
	 *                            was entered
	 * @author Craig Ulliott
	 */
	static function get($key, $local = true){

		$results_local = array();
		$results_mc = array();
		
		//try the local cache first
		if( self::$local_cache_enabled && $local ){ 
			//local cache with an array of keys
			$i=0;
			if( is_array($key) ){
				$hit = $miss = 0;
				foreach( $key as $k=>$v ){
					if( isset(self::$local_cache[$v]) ){

						//the local cache can store the fact that there was no key, for these false values we take the key away but dont actually return anything
						if( self::$local_cache[$v] ){
							$results_local[$v] = self::$local_cache[$v];
						}

						$i++;
						$hit++;
						unset($key[$k]);
						
					}else{
						
						$miss++;
						
					}
				}

			}
			if(!$key){ 
				return $results_local; 
			}
		
			//local cache with one key
			if( ! is_array($key) ){
				if( isset(self::$local_cache[$key]) ){

					$result = self::$local_cache[$key];

					return $result;
				}

			}
		}

		//is the memcache cache enabled
		if( self::$cache_enabled ){ 
			//use memcached for an array of keys (will pick up any not cached locally)
			if( is_array($key) ){

				// to time the request
				$start_time = microtime(true);	

				// try and get the others
				$results_mc = self::get_cache()->get($key);
				
				// how long the request took
				$duration = microtime(true) - $start_time;

				if( $results_mc ){
					
					Log::add_query('cache', 'GET HIT:'.count($results_mc).' keys : '.implode("\n",$key), $duration);
					Log::add_query('cache', 'GET MISS:'.( count($key) - count($results_mc) ).' keys :'.implode("\n",$key), $duration);
					
					//cache this locally for super fast retrieval in this session (good for recursive functions)
					if( $local ){
						foreach( $results_mc as $key => $content ){
							self::local_cache($key, $content);
						}
					}
					return array_merge($results_local, $results_mc);
					
				}else{
					
					Log::add_query('cache', 'GET MISS: '.implode("\n",$key).' keys', $duration);

					//we didnt get any others just return the local ones
					return $results_local; 
				}
				
			}else{

				// to time the request
				$start_time = microtime(true);	

				//return one result from memcached
				$result = self::get_cache()->get($key);

				// how long the request took
				$duration = microtime(true) - $start_time;

				// log the request
				Log::add_query('cache', ($result?'GET OK:':'GET FAILED:').$key, $duration);
				
				//cache this locally for super fast retrieval in this session (good for recursive functions)
				// we even store it locally if it was empty, we want to know it was empty so we dont try and get it twice
				if( $local ){
					self::local_cache($key,$result);
				}
				return $result;
			}
		}

		//return false in the form we recieved it
		if( is_array($key) ){
			return array();
		}else{
			return false;	
		}
	}
	
	/**
	 * returns an array representing various stats about the memcache servers
	 *
	 * @return array
	 * @author Craig Ulliott
	 */
	static function get_extended_stats(){
		return self::get_cache()->getExtendedStats();
	}
	
	/**
	 * clear the local cache 
	 * 
	 * this is useful for scripts which take a long time to run, as when using the 
	 * local-cache it is possible to create a memory leak
	 *
	 * @return void
	 * @author Craig Ulliott
	*/
	static function flush(){
		self::$local_cache = array();
	}
}
