<?php

/**
 * Database Helper 
 * 
 * In a production hosting environment for an application of much scale, it is unlikely that
 * we are connecting to a single database on 'localhost'.  Actually, in our production environment 
 * we have multiple databases, satisfying different data, all running on seperate machines.
 *  
 * This is a wrapper for mysqli, and was constructed with the following goals in mind
 * 
 * 1). 	provide an non intrusive interface to your data,  SQL is a powerful and simple way to work with
 * 		your databases.  I find it annoying when frameworks try to abstract away this useful tool from you, 
 * 		this class aims to provide a simplified access to SQL so you can concerntrate time on your queries
 * 		not how you excecute them.  For this reason the class will also not alter your sql queries in anyway, 
 * 		you must be mindful of things like limits and escaping of variables used to build your queries
 * 
 * 2).	It should be quick to use, instantiating a database connection everytime you need one, or keeping 
 * 		a global variable hanging around is not very productive.  It breaks the DRY principle for a start.
 * 		Code like $row = DB:get_row('select * from admin_users'); is nice to look at and easy to remember.
 * 		Also there is not a good enough reason for fetching one row at a time, hiding the useful data in 
 * 		PHPs memory and providing ->next() and ->previous() functionality just slows things down and clutters
 * 		up your code.
 * 
 * 3).	It should abstract access to multiple database servers for you, and simplify and secure the connection 
 * 		options such as username and password
 * 
 * 4).	It should not open resources until you ask for them, but when you do ask for them it should not have to
 * 		be in an implicit way, opening a connection to a database is implied by sending a query to one
 * 
 * @author Craig Ulliott
 */
class DB {

	/**
	 * an array of objects keyed by configuration name, each which represent open 
	 * connections to a MySQL Server
	 *
	 * @var array
	 */
	static private $db = array();
	
	/**
	 * returns an object which represents a mysqli connection to a specified database server
	 * 
	 * this static method is used by this library to lazy load a connection to a database, 
	 * any method in the database class which requires a mysqli connection will request the
	 * connection through this method.  This provides a single place to manage database 
	 * connection configurations and open connenctions
	 *
	 * @param string $db 	optional string representing the database configuration to use
	 * 						other than the default of db1
	 * @return object		Returns an object which represents the connection to a 
	 * 						MySQL Server
	 * @author Craig Ulliott
	 */
	static private function get_db($db = 'db1') {
		
		// has a connection already been made to this database
		if( ! self::connected($db) ){

			// create mysqli connection and store in private variable 
			self::$db[$db] = self::create_connection($db);
			
			// if we couldn't connect
			if ( mysqli_connect_errno() ) {

				// try once more
				sleep(.1);

				// create mysqli connection
				self::$db[$db] = self::create_connection($db);

				// if we couldn't connect again, then throw an error
				if ( mysqli_connect_errno() ) {
					// trigger an error
					trigger_error('Could not connect to the database '.$db, E_USER_ERROR);
				}
				
			}
		}
		
		// return the connection object
		return self::$db[$db];
	}
	
	/**
	 * loads configuration for and creates a connection object for a mysql database
	 * 
	 * configuration for databases is loaded from db.ini in the configuration
	 * directory of the framework, multiple databases can be defined in that
	 * configuration.  Configuration options for each database are server, username, 
	 * password, database and port.
	 * 
	 * This is a private method which loads and parses that configuration, 
	 * then creates and returns a mysqli connection object 
	 *
	 * @param string $db 	string representing the database configuration to use
	 * @return object		Returns an object which represents the connection to a 
	 * 						MySQL Server
	 * @author Craig Ulliott
	 */
	static private function create_connection($db) {

		// get the configuration from the db configuration, for the database specified by $db
		$config = Configuration::get('db',$db);

		// create mysqli connection 
		return new mysqli( 
			array_val($config, 'server'),
			array_val($config, 'username'),
			array_val($config, 'password'),
			array_val($config, 'database'),
			array_val($config, 'port')
		);
	}

	/**
	 * returns a boolean representing if we have already opened a connection 
	 * to a database server
	 * 
	 * when an operation such as a query is called on a database, we load
	 * configuration for and then open a connection to that database.  The 
	 * act of doing this last minute is known as Lazy Loading. 
	 * 
	 * This method returns true if that initial connection has already been made, 
	 * but for performance reasons it does not ping the database server.  So when 
	 * this method returns true, it is possible that the server connection has 
	 * dropped, this is rare.
	 * 
	 * This private method is used to determine if a connection needs to be established
	 * or if one is already avaliable
	 *
	 * @param string $db 	optional string representing the database configuration to use
	 * 						other than the default of db1
	 * @return bool			returns TRUE if a connection has already been opened
	 * 						and FALSE if has not
	 * @author Craig Ulliott
	 */
	static private function connected($db = 'db1') {
		return array_key_exists($db, self::$db);
	}
	
	/**
	 * close a connection to a database server
	 * 
	 * if a conection has already been opened to a database server then it is closed
	 * and the variable holding the object representing this connection is unset. If this
	 * method is called for a database which has not been connected, or already been closed
	 * with this method, then an warning error is triggered.
	 *
	 * @param string $db 	optional string representing the database configuration to use
	 * 						other than the default of db1
	 * @return bool			returns TRUE on success or FALSE on failure
	 * @author Craig Ulliott
	 */
	static public function close($db = 'db1') {
		// are we connected to it
		if( self::connected($db) ){
			// close the connection and record if the closure was a success or not
			$result = self::$db[$db]->close();
			// unset the local variable holding the connection thread
			unset(self::$db[$db]);
			// return a boolean representation of if this connection was successfullt closed
			return $result;
		}
		// a warning is thrown if a connection is not setup for this database
		trigger_error('you are not currently connected to database '.$db);
	}
	
	/**
	 * Pings a server connection, or tries to reconnect if the connection has gone down
	 * 
	 * Checks whether the connection to the server is working. If it has gone down, and 
	 * global option mysqli.reconnect is enabled an automatic reconnection is attempted.
	 * 
	 * This function can be used by clients that remain idle for a long while, to check 
	 * whether the server has closed the connection and reconnect if necessary
	 * 
	 * If this method is called for a database which has not been connected, or has been 
	 * closed via the close() method, then an warning error is triggered.
	 *
	 * @param string $db 	optional string representing the database configuration to use
	 * 						other than the default of db1
	 * @return bool			returns TRUE on success or FALSE on failure
	 * @author Craig Ulliott
	 */
	static public function ping($db = 'db1') {
		// are we connected to it
		if( self::connected($db) ){
			// ping the database server
			return self::$db[$db]->ping();
		}
		// a warning is thrown if a connection is not setup for this database
		trigger_error('you are not currently connected to database '.$db);
	}
		
	/**
	 * Apply backtick quotes to identifiers
	 * 
	 * Takes a string or an array of strings, escapes special characters and wraps the
	 * output in backticks.  This method is primarily used to wrap identifiers in an SQL
	 * statement
	 * 
	 * @param string|array $input 		string or array of strings which will be sanitized
	 * @return string|array 			the newly sanitized string or array of strings.  The 
	 * 									response of this method is guaranteed to be of the 
	 * 									same type as the $input (array or string)
	 * @author Craig Ulliott
	 */
	static public function bt($input) {
		return self::s($input, '`');
	}

	/**
	 * Sanitize input for use in an SQL statement
	 * 
	 * Takes a string or an array of strings and escapes special characters for use in
	 * a SQL statement, taking into account the current charset of the connection.  The 
	 * output is also returned wrapped in single quotes, this $wrap_character can be 
	 * overridden by the second optional parameter
	 *
	 * @param string|array $input 		string or array of strings which will be sanitized
	 * @param string $wrap_character 	optional variable to overide the character 
	 * 									used to wrap the input after sanitizing
	 * @return string|array 			the newly sanitized string or array of strings.  The 
	 * 									response of this method is guaranteed to be of the 
	 * 									same type as the $input (array or string)
	 * @example core/lib/db/s.phps
	 * @author Craig Ulliott
	 */
	static public function s($input, $wrap_character = "'") {
		// if its an array, pass it in recursively
		if ( is_array($input) ) {
			foreach ( $input as $key => $val ) {
				$input[$key] = self::s($val, $wrap_character);
			}
			
		}else{
			// we dont modify some values with special meanings
			switch( true ){
				case $input === 'NULL':
				case $input === 'NOW()':
					break;
				case $input === NULL:
					$input = 'NULL';
					break;
				default:
					// escape the string to make it safe for mysql and wrap it in quotes
					$input = $wrap_character.self::get_db()->real_escape_string($input).$wrap_character;
			}
		}
		return $input;
	} 
	
	/**
	 * Performs a query on the database
	 * 
	 * This is the prefered method for preforming SQL queries other than SELECT, SHOW, DESCRIBE 
	 * or EXPLAIN queries.  For those types of query you should use the get_row or get_array 
	 * methods which wrap this method with functionality to get the data out in a more useful way
	 * 
	 * Queries are timed and the query and duration (but not the result) are passed to the query log 
	 * 
	 * If an error occurs then a DBException is raised, DBExceptions have a useful variable which 
	 * contains the query that caused the error, it can be accessed via $exception->getQuery()
	 *
	 * @param string $sql 		The query string, which should be properly escaped
	 * @return bool|object 		Returns FALSE on failure. For successful SELECT, SHOW, DESCRIBE or 
	 * 							EXPLAIN queries will return a result object. For other successful 
	 * 							queries will return TRUE.  
	 * @author Craig Ulliott
	 */
	static public function query($sql, $db = 'db1') {
		
		//time every query
		$start_time = microtime(true);
		
		// execute the query
		$result = self::get_db()->query($sql);
		
		// how long the request took
		$duration = microtime(true) - $start_time;
		
		//log the query if we have access to out Log class
		if( class_exists('Log') ) {
			Log::add_query('database', $sql, $duration);
		}
		
		// is there a problem
		if ( $result === false) {
			// what was the error message
			$error = self::get_db()->error;
			//create and throw an exception
			$exception = new DBException($error);
			// DBException objects have a query variable so we can see what query caused the error
			$exception->setQuery($sql);
			// throw this exception up
			throw $exception;
		}
		
		return $result;
		
	}
	
	/**
	 * Returns the auto generated id used in the last query
	 * 
	 * returns the ID generated by a query on a table with a column having the AUTO_INCREMENT 
	 * attribute. If the last query wasn't an INSERT or UPDATE statement or if the modified 
	 * table does not have a column with the AUTO_INCREMENT attribute, this function will 
	 * return zero. 
	 * 
	 * If this method is called for a database which has not been connected, or has been 
	 * closed via the close() method, then an warning error is triggered.
	 *
	 * @param string $db 	optional string representing the database configuration to use
	 * 						other than the default of db1
	 * @return int|string 	The value of the AUTO_INCREMENT field that was updated by 
	 * 						the previous query. Returns zero if there was no previous 
	 * 						query on the connection or if the query did not update an 
	 * 						AUTO_INCREMENT value.  If the number is greater than maximal 
	 * 						int value, a string will be returned
	 * @author Craig Ulliott
	 */
	static public function insert_id($db = 'db1') {
		// are we connected to this database
		if( self::connected($db) ){
			// return the insert_id
			return self::get_db($db)->insert_id;
		}
		// a warning is thrown if a connection is not setup for this database
		trigger_error('you are not currently connected to database '.$db);
	}
	
	/**
	 * Returns the number of rows affected by the last INSERT, UPDATE, REPLACE or DELETE query
	 * 
	 * If this method is called for a database which has not been connected, or has been 
	 * closed via the close() method, then an warning error is triggered.
	 *
	 * @param string $db 	optional string representing the database configuration to use
	 * 						other than the default of db1
	 * @return int|string 	An integer greater than zero indicates the number of rows affected
	 * 						or retrieved. Zero indicates that no records where updated for an 
	 * 						UPDATE statement, no rows matched the WHERE clause in the query or 
	 * 						that no query has yet been executed. -1 indicates that the query 
	 * 						returned an error.  If the number is greater than maximal int value
	 * 						then a string will be returned
	 * @author Craig Ulliott
	 */
	static public function affected_rows($db = 'db1') {
		// are we connected to this database
		if( self::connected($db) ){
			// return the affected_rows
			return self::get_db($db)->affected_rows;
		}
		// a warning is thrown if a connection is not setup for this database
		trigger_error('you are not currently connected to database '.$db);
	}
	
	/**
	 * excecute a query and return the first row in a keyed array of columns
	 * 
	 * the array returned by this method represents the first row in the set of results 
	 * fetched with your query, it does not apply a 'limit 1' to your query, so if you
	 * expect your query might return more than one row (even though you wont be able to 
	 * access more than the first), you should use 'limit 1' to reduce the traffic between 
	 * your webserver and your database
	 * 
	 * for valid select queries this method is guaranteed to return an array, if no results are
	 * found then you will get back an empty array (which will conviniently eval to false) this 
	 * allows you to write neater code around your database queries
	 * 
	 * if the SQL for your query is invalid, then a DBException is throw, DBExceptions have a 
	 * useful variable which contains the query that caused the error, it can be accessed via 
	 * $exception->getQuery()
	 * 
	 * this method is actually a wrapper for get_array, where the optional $first_row parameter
	 * if set to true.  Writing the code in this way keeps things neat and allows us to observe 
	 * the DRY principle
	 *
	 * @param string $sql 	The query string, which should be properly escaped
	 * @param string $db 	optional string representing the database configuration to use
	 * 						other than the default of db1
	 * @return array 		an array representing a single row of results, it is keyed with
	 * 						the column names
	 * @author Craig Ulliott
	 */
	static public function get_row($sql, $db = 'db1') {
		return self::get_array($sql, $db, true);
	}
	
	
	/**
	 * excecute a query and return an array of rows, each row as an array, keyed by column 
	 * 
	 * the array returned by this method represents all the rows in the result set fetched 
	 * with your query
	 * 
	 * for valid select queries this method is guaranteed to return an array, if no results are
	 * found then you will get back an empty array (which will conviniently eval to false) this 
	 * allows you to write neater code around your database queries
	 * 
	 * if the SQL for your query is invalid, then a DBException is throw, DBExceptions have a 
	 * useful variable which contains the query that caused the error, it can be accessed via 
	 * $exception->getQuery()
	 * 
	 * @param string $sql 	The query string, which should be properly escaped
	 * @param string $db 	optional string representing the database configuration to use
	 * 						other than the default of db1
	 * @return array 		an array representing a single row of results, it is keyed with
	 * 						the column names
	 * @author Craig Ulliott
	 */
	static public function get_array($sql, $db = 'db1', $first_row = false) {

		$result = self::query($sql);

		$array = array();
		while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
			//do we only want the first row
			if( $first_row ){
				return $row;
			}
			//else we are building an array of all the rows
			$array[] = $row;
		}
		$result->close();
		return $array;
			
	}
	
}

