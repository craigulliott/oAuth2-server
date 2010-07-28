<?php

require_once 'header.php';

class LogLibTest extends Test {
    
    /** 
     * if logging is not enabled, enable it
     */
	public function testLogEnable(){
		if( ! Log::isEnabled('database') ){
			Log::enable('database');
		}
	}

    /** 
	 * assert logging is now enabled
	 * 
	 * @depends testLogEnable
     */
	public function testLogEnabledOk(){
		$this->assertTrue(Log::isEnabled('database'));
	}

    /** 
     * add an entry with a duration to the log
     */
	public function testLogSomething(){
		// log something
		Log::addQuery('database', 'test query', 4);
	}
    
    /** 
     * adding an entry of an invalid type should exception
     * 
     * @expectedException Exception
     */
	public function testLogSomethingOfInvalidType(){
		Log::addQuery('invalid type', 'test query', 4);
	}
    
    /** 
     * assert our entry was logged
     * 
     * @depends testLogSomething
     */
	public function testLogGetLastQueries(){
		// the last query should be the one we did in the testLogSomething test 
		$entry = Log::getLastQueries('database');
		
		// did this entry come back
		$this->assertEquals(array_val($entry,array(0,0)), 'test query');
		$this->assertEquals(array_val($entry,array(0,1)), 4);
	}
    
    /** 
     * assert the count works
     * 
     * @depends testLogSomething
     */
	public function testLogCount(){
		$current_count = Log::getCount('database');
		
		// add one more entry
		Log::addQuery('database', 'test query', 4);
		
		// assert the number went up by one
		$this->assertTrue( Log::getCount('database') == $current_count + 1 );
	}
    
	
}