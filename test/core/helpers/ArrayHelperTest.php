<?php

require_once 'header.php';

class ArrayHelperTest extends Test {
    
	/**
	 * test the a function
	 */
	public function testarray_val(){
		
		// this will return "hello"
		$array = array('foo' => 'hello');
		$this->assertTrue(array_val($array, 'foo') === "hello");
		
		// this will return NULL
		$array = array('foo' => 'hello');
		$this->assertTrue(array_val($array, 'bar') === NULL);
		
		// this will test the optional default param
		$array = array('foo' => 'hello');
		$this->assertTrue(array_val($array, 'bar', 'default') === 'default');
		
		// testing nested arrays
		$array = array(
			'foo' => array(
				'bar' => 'hello'
			)
		);
		$this->assertTrue(array_val($array, array('foo','bar')) === "hello");
		
	}
	
	/**
	 * test the a_b function, which is basically a with some extra logic to return things like
	 * the string 'false' as boolean false
	 */
	public function testarray_val_bool(){
		
		// basic use
		$this->assertTrue(array_val_bool(array('foo' => 'false'), 'foo') === FALSE);
		$this->assertTrue(array_val_bool(array('foo' => 0)	, 'foo') === FALSE);
		$this->assertTrue(array_val_bool(array('foo' => NULL), 'foo') === FALSE);
		$this->assertTrue(array_val_bool(array('foo' => "0"), 'foo') === FALSE);
		
		$this->assertTrue(array_val_bool(array('foo' => 'true'), 'foo') === TRUE);
		
	}
		
	/**
	 * jsondecode an array from an array value
	 */
	public function testarray_val_json(){
		
		// some json to test against
		$json = json_encode(array('foo' => 'bar'));
		$array = array('arr' => $json);
		
		// target the velue in the array
		$v = array_val_json($array, 'arr');
		// assert the array looks as expected
		$this->assertTrue(array_val($v, 'foo') === 'bar');
		
		// for the array key doesnt exist
		$v = array_val_json($array, 'invalid');
		// assert there was an empty array returned
		$this->assertTrue(is_array($v) && empty($v));

		// for the array key doesnt exist, but a default is used
		$v = array_val_json($array, 'invalid', array('a' => 'b'));
		// assert there was an empty array returned
		$this->assertTrue(array_val($v, 'a') === 'b');
		
		
		
	}
		
	/**
	 * a wrapper for a which safely takes a value out of an array and explodes it as if it was a csv
	 */
	public function testarray_val_csv(){
		
		// some json to test against
		$array = array('arr' => ' a,b ,c ,d, e ,f,g');
		
		// target the value in the array
		$v = array_val_csv($array, 'arr');

		// assert the array looks as expected
		$this->assertArrayEquals($v, array('a','b','c','d','e','f','g'));
				
	}
		
	/**
	 * recursively trim the elemts in an array
	 */
	public function test_trim_r(){
		
		$this->assertArrayEquals(
			trim_r(array(
				'hello' => array(
					'a' => NULL,
					'b' => FALSE,
					'c' => array(
						'bar' => "space at the end ",
						'bar' => " space at the beginning",
					)
				)
			)), 
			array(
				'hello' => array(
					'a' => '',
					'b' => '',
					'c' => array(
						'bar' => "space at the end",
						'bar' => "space at the beginning",
					)
				)
			)
		);
		
	}

}