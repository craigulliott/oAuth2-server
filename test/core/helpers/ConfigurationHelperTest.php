<?php

require_once 'header.php';

class ConfigurationHelperTest extends Test {
    
	private $expectedDatabaseFields = array('username', 'password', 'server', 'database', 'port'); 
	private $expectedCacheFields = array('port', 'host'); 
	
	/**
	 * test the configuration loads correctly for each configuration file
	 */
	public function testConfiguration(){
		
		// get the database configuration
		$config = getConfiguration('db');
		
		// check we get the main database configuration
        $this->assertArrayHasKey('db1', $config);
		
		// check the database configuraion has all the required variables
        $this->assertArrayHasKeys($this->expectedDatabaseFields, $config['db1']);
		
		// check use of the second parameter to target specific sections works as intended
		$config = getConfiguration('db','db1');
        $this->assertArrayHasKeys($this->expectedDatabaseFields, $config);
		
		// get the cache configuration
		$config = getConfiguration('cache');
		
		// check we get the main database configuration
        $this->assertArrayCountGreaterThanOrEqual($config, 1);
		
		// check we get back a list of servers
		$server_list = array_val($config, 'servers');
		$this->assertArrayCountGreaterThanOrEqual($server_list, 1);
		
        // check we get back the host and port variables for each server
		foreach( $server_list as $server_name ):
			$server_config = array_val($config, $server_name);
        	$this->assertArrayHasKeys($this->expectedCacheFields, $server_config);
		endforeach;

	}
	
}