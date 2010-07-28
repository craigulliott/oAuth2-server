<?php

require_once 'header.php';

class CacheLibTest extends Test {
    
	// in these unit tests we create, edit and delete entries from the cache, we use this prefix to safely namespace our work
	private $key = 'unit_testing_cache_key';
	
    /** 
     * can we add something to the cache (skip the local cache)
     */
	public function testAddEntryToTheCache(){
		$this->assertTrue(Cache::add($this->key, 'foobar', 10, false));
	}
	
    /** 
     * is our entry in the cache
     */
	public function testFetchFromTheCache(){
		$this->assertEquals(Cache::get($this->key), 'foobar');
	}
	
    /** 
     * delete the entry from the cache
     */
	public function testDeleteFromTheCache(){
		$this->assertTrue(Cache::delete($this->key));
	}
	
	/** 
     * can we add several things to the cache and then return them all at once
     */
	public function testFetchArrayOfKeys(){
		
		// to store all the keys and values we are testing with
		$stored_values = array();
		
		// build and store 26 different values with different keys
		foreach( range('A','Z') as $letter ):
		
			$key = $this->key.'-key-'.$letter;
			$value = $this->key.'-value-'.$letter;
			
			// put them in the cache one at a time
			$this->assertTrue(Cache::add($key, $value, 10, false));
			
			// so we can get them all at once below
			$stored_values[$key] = $value;
			
		endforeach;
		
		// get all the keys simultaneously
		$results = Cache::get( array_keys($stored_values) );
		
		// test all the records were returned as expected
		foreach( $stored_values as $key => $value ):
		
			$this->assertEquals($results[$key], $value);
			
		endforeach;

	}
    
	
}