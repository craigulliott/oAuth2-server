<?php

class Test extends PHPUnit_Framework_TestCase {
	
	static $user = null;
	
	/**
	 * the test framework is given a user to aid calling tests asif we were logged in
	 *
	 * @param User $user 
	 * @author Craig
	 */
	final static public function setUser(UserModel $user = null) {
		self::$user = $user;
	}

	/**
	 * get the user we are using to test with
	 *
	 * @param User $user 
	 * @author Craig
	 */
	final static public function getUser() {
		return self::$user;
	}
	
	/**
	 * a helper method to test the presence of an array of keys
	 *
	 * @param array $keys 
	 * @param array $array 
	 * @return void
	 * @author Craig
	 */
	function assertArrayHasKeys(array $keys, array $array){
		
		foreach( $keys as $key ){
			$this->assertArrayHasKey($key, $array);
		}
		
	}
	
	/**
	 * a helper method to test the presence of multiple values in an array
	 *
	 * @param array $values 
	 * @param array $array 
	 * @return void
	 * @author Craig
	 */
	function assertContainsMultiple(array $values, array $array){
		
		foreach( $values as $value ){
			$this->assertContains($value, $array);
		}
		
	}
	
	/**
	 * a helper method to ensure we have exactly the expected number of elements
	 *
	 * @param array $keys 
	 * @param array $array 
	 * @return void
	 * @author Craig
	 */
	function assertArrayCount(array $array, $count){
		
		if( count($array) != $count ){
			$this->fail('Failed asserting that array had at least '.$count.' elements');
		}
		
	}
	
	/**
	 * a helper method to test we have atleast (or equal to) the expected number of elements
	 *
	 * @param array $keys 
	 * @param array $array 
	 * @return void
	 * @author Craig
	 */
	function assertArrayCountGreaterThanOrEqual(array $array, $count){
		
		if( count($array) < $count ){
			$this->fail('Failed asserting that array had at least '.$count.' elements');
		}
		
	}
	
	/**
	 * a helper method to test arrays (multidimensional) are identical
	 *
	 * @param array $array1 
	 * @param array $array2
	 * @return void
	 * @author Craig
	 */
	function assertArrayEquals(array $array1, array $array2){
		if( $difference = array_diff_assoc_recursive($array1, $array2) ){
			ob_start();
			var_dump($difference);
			$difference = ob_get_contents();
			ob_end_clean();
			$this->fail('Arrays were expected to be identical but had this difference'."\n".$difference);
		}
		
	}
	
	/**
	 * a helper method to assert a variable is empty
	 *
	 * @param array $variable 
	 * @return void
	 * @author Craig
	 */
	function assertEmpty($variable){
		
		if( ! empty($variable) ){
			$this->fail('Variable was expected to be empty');
		}
		
	}
	
	/**
	 * a helper method to assert the uri passed to the view is as expected
	 *
	 * @param string $expected_uri 
	 * @return void
	 * @author Craig
	 */
	function assertViewURI($expected_uri){
		
		if( ! View::getURI() === $expected_uri ){
			$this->fail('URI '.View::getURI().'  was expected to be '.$expected_uri);
		}
		
	}
	
	/**
	 * a helper method to assert the view vars have the expected keys
	 *
	 * @return void
	 * @author Craig
	 */
	function assertViewVarsKeys(array $keys){
		
		$this->assertArrayHasKeys($keys, View::getVars());
		
	}
	
	/**
	 * Assert that the API's JSON response contains success=1
	 *
	 * @param string $response_json
	 * @return void
	 */
	public function assertAPISuccess($response_json) {
		$response = json_decode($response_json, 1);
		$this->assertEquals($response['success'], 1);
	}
	
	
}
