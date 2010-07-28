<?php

/**
 * loads a configuration file from the system, if the optional process_sections
 * parameter is used then the configuration is broken into sections coresponding 
 * to headers in the configuration file [headers look like this]
 *
 * @param string $section 
 * @param bool $process_sections 
 * @return void
 * @author Craig
 */
function getConfiguration($file, $section=null){
	
	// a key to use for setting and getting this from the cache
	$key = 'configuration__'.$file.'_'.$section;
		
	// try APC first
	if ( !$configuration = apc_fetch($key) ) {
		$configuration_file = CONFIGURATION_PATH.$file.'.ini';
		// ensure the configuration file exists
		if( file_exists($configuration_file) ){
			
			//build an associative array from this configuration file
			$configuration = parse_ini_file($configuration_file, true);
	
			//add to the cache for next time
			apc_add($key, $configuration);
			
		}else{
			trigger_error('configuration file for '.$section.' does not exist', E_USER_ERROR);
		}
		
	}
	
	// are we returning just one section
	if( $section ){
		return array_val($configuration, $section);
	}
	
	// pass back the configuration associative array
	return $configuration;
}