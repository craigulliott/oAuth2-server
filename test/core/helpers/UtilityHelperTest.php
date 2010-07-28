<?php

require_once 'header.php';

class UtilityHelperTest extends Test {
    
	/**
	 * test the helper function to add params to a url
	 */
	public function test_http_add_params(){
		
		$new_url = http_add_params('http://www.whereivebeen.com', 'new=params');
		$this->assertEquals('http://www.whereivebeen.com?new=params', $new_url);

		$new_url = http_add_params('http://ad.doubleclick.net/clk;222748503;44958519;r?http://www.monogramstravel.com', 'new=params');
		$this->assertEquals('http://ad.doubleclick.net/clk;222748503;44958519;r?http%3A%2F%2Fwww.monogramstravel.com=&new=params', $new_url);
		
	}
	
	public function test_zero_pad(){
		$this->assertEquals( zero_pad(12, 4), '0012' );
	}	
	
}
