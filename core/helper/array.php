<?php

/**
 * This method is heavily used throughout the framework, the purpose of it is to 
 * target and return a value in an array, removing the need for lots of calls to 
 * isset(), empty() etc.  Basically it takes the array, a key as a pointer
 * to an element (key can be an array of keys to target an element in a nested array)
 * and an optional default which will be returned if the key doesnt exist
 *
 * @author Craig Ulliott
 */
function array_val($array, $key, $default = null) {
	// an array of keys can be used to look in a multidimensional array
	if( is_array($key) ){
		$k = array_shift($key);
		//if $key is now empty then were at the end
		if( empty($key) ){
			return isset($array[$k]) ? $array[$k] : $default;
		}
		//resursive call
		return isset($array[$k]) ? array_val($array[$k], $key, $default) : $default;
	}
	//key is not an array
	return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * This is the same functionality as array_val(), except if the key doesnt exist  or the value is NULL then an exception is thrown
 *
 * @author Craig Ulliott
 */
function array_val_required($array, $key) {
	
	// safely take the value out of the array
	$return = array_val($array, $key);
	
	// we are satisfied by any value other than NULL
	if( $return === NULL ){
		throw new exception("$key is a required param");
	}
	return $return;
}

/**
 * a wrapper for array_val which explodes the value to an array from a simple csv
 * this method is not aware of escaped characters, its intended use is for simple data
 * such as a comma seperated list of ids
 *
 * @author Craig Ulliott
 */
function array_val_csv(array $array, $key, $default = array()) { 

	// try and get the value out of the array
	if( $val = array_val($array, $key) ){
		// split it into an array, by the comma
		$val_r = explode(',', $val);
		// recursively trim the resulting array
		$val_r = trim_r($val_r);
		// return it
		return $val_r;
	}
	return $default;
}

/**
 * a wrapper for make_bool that safely takes a value out of an array
 *
 * @author Craig Ulliott
 */
function array_val_bool(array $array, $key, $default = false) { 
	return make_bool(array_val($array, $key, $default)); 
}

/**
 * jsondecode an array from an array value, this is a helper method around array_val()
 *
 * @param array $array 
 * @param string $key 
 * @param string $default 
 * @return void
 * @author Craig Ulliott
 */
function array_val_json(array $array, $key, $default = array()) { 
	// get the value out of the array if it exists
	if( $value = array_val($array, $key) ){
		$arr = json_decode($value, 1);
		// if it didnt fail, return it
		if( $arr !== false ){
			// json decode succeded, return the result
			return $arr;
		}
	}
	// return the default
	return $default; 
}

/**
 * recursively trim the elements in an array
 *
 * @param array $array 
 * @param string $to_trim 
 * @return void
 * @author Craig Ulliott
 */
function trim_r(array $array, $to_trim=' ') {
	foreach ( $array as $key => $val ) {
		if ( is_array($val) ) {
			$array[$key] = trim_r($val);
		}
		else {
			$array[$key] = trim($val, $to_trim);
		}
	}
	return $array;
}

/**
 * creates a new array, from the first nested value of each element
 * array(array(1),array(2),array(3)) becomes array(1,2,3)
 *
 * @param string $array 
 * @return array
 * @author Craig Ulliott
 */
function array_suck($array){
	return array_map("reset", $array);
}

/**
 * Convert an array into a CSV string
 *
 * @param array $array 
 * @return string
 */
function array2csv(array $array) {
	$lines = array();
	foreach ( $array as $val ) {
		$lines[] = is_array($val) ? array2csv($val) : '"' . str_replace('"', '""', $val) . '"';
	}
	return implode(",", $lines);
}



