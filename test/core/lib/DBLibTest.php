<?php

require_once 'header.php';

class DBLibTest extends Test {
    
	// in these unit tests we create, edit and delete this database and table
	private $db_name = 'database_for_unit_tests_will_be_deleted';
	private $table_name = 'test_table';
	
    /** 
     * on failure to connect the db class throws a warning error
     */
	public function testConnectToTheDatabase(){
		$db = DB::getDB();
	}
	
    /**
     * reconnect to the database
     * 
     * @depends testConnectToTheDatabase
     */
	public function testReconnectToTheDatabase() {
		DB::reconnect();
	}

	/**
     * reconnect to the database
     * 
     * @depends testReconnectToTheDatabase
     */
	public function testSanitizeMethod() {
		// singlular
		$this->assertEquals(DB::s('hello'), "'hello'");
		
		// escaping the contents of an array (of any depth)
		$this->assertArrayEquals(
			DB::s(array('hello')), 
			array("'hello'")
		);
		$this->assertArrayEquals(
			DB::s(array(
				'hello' => array(
					'a' => NULL,
					'b' => "NOW()",
					'c' => array(
						'bar' => "some value",
						'bar' => "another ' value",
					)
				)
			)), 
			array(
				'hello'=>array(
					'a'=>"NULL",
					'b'=>"NOW()",
					'c' => array(
						'bar' => "'some value'",
						'bar' => "'another \' value'",
					)
				)
			)
		);
		
		// NULL value
		$this->assertEquals(DB::s(NULL), 'NULL');
		
		// special NOW() method
		$this->assertEquals(DB::s('NOW()'), 'NOW()');
		
	}
	
    /**
     * an invalid query should throw a catchable exception
     * 
     * @depends testReconnectToTheDatabase
     * @expectedException DBException
     */
	public function testInvalidBasicQuery() {
		$result = DB::q('i am not a real query');
	}
	
    /**
     * an invalid query should throw a catchable exception with a query field
     * 
     * @depends testReconnectToTheDatabase
     */
	public function testInvalidBasicQueryThrowsExceptionWithQueryField() {
		try{
			$result = DB::q('i am not a real query');
		}catch(Exception $e){
			// check the exception has the query part
			$this->assertEquals($e->getQuery(), 'i am not a real query');
		}
	}
	
    /**
     * try a simple query that ceates a test database
     * 
     * @depends testReconnectToTheDatabase
     */
	public function testCreateDatabase() {
		// be certain your user has the required permissions to create databases and that this is a safe name to use
		$result = DB::q('create database '.$this->db_name);
		// on success we should get back boolean true
		$this->assertTrue($result === TRUE);
	}
	
    /**
     * if the dataabse was created then try a simple query that creates a table in the new database
     * 
     * @depends testCreateDatabase
     */
	public function testCreateTable() {
		// another query that should always work (if our user has the required permissions)
		$sql = 'CREATE TABLE `'.$this->db_name.'`.`'.$this->table_name.'` (
					`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`foobar` VARCHAR( 50 ) NULL
				) ENGINE = MYISAM ;';
		
		$result = DB::q($sql);
		// on success we should get back boolean true
		$this->assertTrue($result === TRUE);
	}
	
    /**
     * insert a new row
     * 
     * @depends testCreateTable
     */
	public function testInsert() {
		// a query that should probably always work
		$result = DB::q('insert into `'.$this->db_name.'`.`'.$this->table_name.'` (foobar) values ('.DB::s('some text').')');
		// on success we should get back boolean true
		$this->assertTrue($result === TRUE);
	}
	
    /**
     * get the primary key value for the row we just inserted, as its the first row it should be the number 1
     * 
     * @depends testInsert
     */
	public function testInsertID() {
		// a query that should probably always work
		$this->assertEquals(DB::insertID(),1);
	}
	
    /**
     * getRow should return a single row (the first in a result set)
     * 
     * @depends testInsert
     */
	public function testGetResultsWithTheBasicQuery() {
		// a query that should probably always work
		$result = DB::q('select * from `'.$this->db_name.'`.`'.$this->table_name.'`');
		// look for the text value we inserted earlier
		$this->assertTrue($result instanceOf mysqli_result);
		
	}
	
    /**
     * getRow should return a single row (the first in a result set)
     * 
     * @depends testInsert
     */
	public function testGetRow() {
		// a query that should probably always work
		$result = DB::getRow('select * from `'.$this->db_name.'`.`'.$this->table_name.'`');
		// look for the text value we inserted earlier
		$this->assertEquals(array_val($result,'foobar'),'some text');
		
	}
	
    /**
     * getArray should return an array containing the same single row returned above
     * 
     * @depends testInsert
     */
	public function testGetArray() {
		// a query that should probably always work
		$result = DB::getArray('select * from `'.$this->db_name.'`.`'.$this->table_name.'`');
		// look for the text value we inserted earlier
		$this->assertEquals(array_val($result,array(0,'foobar')),'some text');
		
	}
	
    /**
     * if the dataabse was created then try a simple query that now deletes it, main reason we do this is to have a clean setup for
     * the next time we want to run this test
     * 
     * @depends testCreateDatabase
     */
	public function testDeleteDatabase() {
		$sql = 'drop database '.$this->db_name;
		$result = DB::q($sql);
		// on success we should get back boolean true
		$this->assertTrue($result === TRUE);
	}
	
    /**
     * close the connection to the database
     * 
     * @depends testReconnectToTheDatabase
     */
	public function testCloseTheDatabase() {
		DB::close();
	}
	
	
}