<?php

/**
 * log data to a file
 *
 * @param string $file 
 * @param array $data - will be converted to CSV
 * @return void
 */
function log_to_file($file, array $data) {
	// parse data to CSV
	$message = array2csv($data);
	file_put_contents('/var/log/apache2/'.$file, $message);
}

// wrapper function to save some typing
function he($str) {
	return htmlentities(utf8_encode($str), ENT_COMPAT, 'UTF-8');
}
// wrapper function to save some typing
function nf($num) {
	return number_format($num);
}
// wrapper function to save some typing
function pr($var, $return=false) {
	$pre = '<pre>'.print_r($var, 1).'</pre>';
	if ( $return ) {
		return $pre;
	}
	else {
		echo $pre;
	}
}
// wrapper function to save some typing
function vd($var, $return=false) {
	$pre = '<pre>'.var_dump($var, 1).'</pre>';
	if ( $return ) {
		return $pre;
	}
	else {
		echo $pre;
	}
}
// wrapper function to save some typing
function low($str) {
	return strtolower($str);
}

// parses a unit time stamp into a pretty human readable date
function pretty_date($timestamp, $date_only=false){
	if( $date_only ){
		return date('l M jS', $timestamp);
	}
	return date('D M jS G:i:s', $timestamp);
}

// parses a integer representing price in cents, into a more human readable form
function pretty_price($cents, $always_show_cents = false){
	$price = '$'.number_format($cents/100);
	if( $always_show_cents && ! stristr($price, '.') ){
		$price .= '.00';
	}
	return $price;
}
/**
 * get the boolean value of a variable, with the extra condition that the string false is actually false
 * this is used primarily when passing the string false from ajax
 *
 * @param string $v 
 * @return bool
 * @author Craig Ulliott
 */
function make_bool($v){
	$v = trim( strtolower($v) );
	return ( $v == 'false' || $v == '0' ) ? false : (bool)$v;
}

/**
 * builds a useful array of numbers representing pagination information from a result set
 *
 * @param string $results 
 * @param string $current_page 
 * @param string $per_page 
 * @return array
 * @author Craig Ulliott
 */
function get_pagination($record_count, $current_page = 1, $per_page = 5) {
	$page_count = ceil($record_count/$per_page);
	$current_page = $current_page < 1 || $current_page > $page_count ? 1 : (int)$current_page;
	$first_record = (($current_page-1) * $per_page) + 1;
	$last_record = ($first_record + $per_page) <= $record_count ? $first_record + $per_page - 1 : $record_count;
	$prev_page = $current_page > 1 ? $current_page - 1 : null;
	$next_page = $current_page < $page_count ? $current_page + 1 : null;

	$link_count = 3;	
	$first_link = $current_page - floor($link_count/2);
	$first_link = $first_link > 0 ? $first_link : 1;
	$last_link = $first_link + $link_count - 1;
	$last_link = $last_link <= $page_count ? $last_link : $page_count;

	if ( $first_link < $last_link + $link_count && $last_link - $link_count >= 1 ) {
		$first_link = $last_link - $link_count + 1;
	}

	return array(
		'current_page' => $current_page,
		'first_record' => $first_record,
		'last_record' => $last_record,
		'page_count' => $page_count,
		'record_count' => $record_count,
		'per_page' => $per_page,
		'next_page' => $next_page,
		'prev_page' => $prev_page,
		'first_link' => $first_link,
		'last_link' => $last_link,
	);		
}

/**
 * builds pagination information from a result set and slices the result set to return the current_page of results
 *
 * @param string $results 
 * @param string $current_page 
 * @param string $per_page 
 * @return array
 * @author Craig Ulliott
 */
function paginate($results, $current_page = 1, $per_page = 5){
	
	$record_count = count($results);
	
	$pagination = get_pagination($record_count, $current_page, $per_page);
	
	// slice the results array
	$result_subset = array_slice($results, ($current_page-1)*$per_page, $per_page, true);
	
	return array($pagination, $result_subset);
	
}

function get_possessive($name) {
	if ( preg_match('/s$/', $name) ) {
		return $name."'";
	} else {
		return $name."'s";
	}
}

function get_gender_pronoun($sex) {
	switch ( strtolower(trim($sex)) ) {
		case 'm':
		case 'male':
			return 'him';
			
		case 'f':
		case 'female':
			return 'her';
			
		default: 
			return 'them';
	}
}

function plural($singular) {
	if ( preg_match('/s$/i', $singular) ) {
    $plural = "{$singular}es";
  }
  elseif ( preg_match('/y$/i', $singular) ) {
    $plural = preg_replace('/y$/i', "ies", $singular);
  }
  else {
    $plural = "{$singular}s";
  }

	return $plural;
}

function singular($plural) {
	return preg_replace(array('/ies$/i', '/[^u]es$/i', '/s$/i'), array('y', '', ''), $plural);
}


/**
 * adds zeros to the begining of a number, to enforce a given length
 * 
 * useful for storing numbers in simpledb which must be queries in a lexicographical way
 *
 * @param string $value 
 * @param string $required_string_length 
 * @return void
 * @author Craig
 */
function zero_pad($value, $required_string_length){
	return str_pad($value, $required_string_length, '0', STR_PAD_LEFT);
}