<?php

/**
 * Configuration
 * 
 * I have taken a pragmatic approach to site configuration, and this class has the
 * the following features
 * 
 * All configuration options are set up outside of you PHP, there are three main reasons 
 * for taking this approach.  
 * 
 * 1)	It is bad practice to allow your configuration to fall under source control
 * 		While at first glance a history of every configuration change seems like a good 
 * 		practice, the benefits do not in my opinion outweigh the problems.  The most 
 * 		obvious argument against this practice is configuration normally includes a slew 
 * 		of usernames, passwords and keys, basically information you probably don't want 
 * 		to share with all users of your SCM
 * 2)	Different developers on the same project will through the course of the project
 * 		need to setup their own sandboxes of the code, you will either end up with a large 
 * 		switch statement for each developers custom configuration or developers will 
 * 		simply make ad hock changes and then more than likely check them in by accident.  
 * 		External configuration allows them to set up their configuration once 
 * 3)	And last but not least, you likely have staging and production environments which 
 * 		will have different options, different databases and different passwords.  External 
 * 		configuration allows this data to be securely stored only on those machines and 
 * 		enables automated procedures such as production pushes to be simplified.
 *
 * @package default
 * @author Craig Ulliott
 */
class Configuration {
	
	/**
	 * the path to the configuration files
	 *
	 * @author Craig Ulliott
	 */
	static private $path = 'configuration/';

	/**
	 * set the path on the filesystem to serve the configuration files from
	 * 
	 * by default the configuration files are served out of /CORE_PATH/configuration
	 * but in a production setup it is possible that they will be in some other custom
	 * location, such as an NFS mount. You can use this method to override this default  
	 * 
	 * @param string $path    The path to the configuration files on the filesystem
	 * @return void
	 * @author Craig Ulliott
	 */	
	public static function set_path($path){
		// set this path
		self::$path = $path;
	}	

	/**
	 * return the path on the filesystem from where we are serving the configuration
	 * 
	 * if a relative path is given then it is inferred to be relative to frameworks 
	 * CORE_PATH
	 * 
	 * @return string
	 * @author Craig Ulliott
	 */	
	public static function get_path(){

		// is this a relative or absolute path
		$path = substr(self::$path, 0, 1) == '/' ? self::$path : CORE_PATH.self::$path;

		// paths in our framework which represent directories always have a trailing slash
		return substr($path, -1) == '/' ? $path : $path.'/';

	}	
		
	/**
	 * loads a configuration set
	 * 
	 * loads a configuration file from the system, if the optional process_sections
	 * parameter is used then the configuration is broken into sections corresponding 
	 * to headers in the configuration file
	 *
	 * @param string $section 
	 * @param bool $process_sections 
	 * @return void
	 * @author Craig Ulliott
	 */
	static function get($file, $section = null){
		
		// a key to use for setting and getting this from the cache
		$key = APC_PREFIX.'_configuration_'.$file.'_'.$section;

		// try APC first
		if( defined('FLUSH_CONFIG') || ! $configuration = apc_fetch($key) ){
			$configuration_file = self::get_path().$file.'.ini';
			// ensure the configuration file exists
			if( file_exists($configuration_file) ){

				//build an associative array from this configuration file
				$configuration = parse_ini_file($configuration_file, true);

				//add to the cache for next time
				apc_store($key, $configuration);

			}else{
				trigger_error('configuration file "'.$configuration_file.'" for section "'.$section.'" does not exist', E_USER_ERROR);
			}

		}

		// are we returning just one section
		if( $section ){
			return array_val($configuration, $section);
		}

		// pass back the configuration associative array
		return $configuration;
		
	}
	
	/**
	 * Return the value for a single config var (or NULL if it doesn't exist)
	 *
	 * @param string $var - The name of the config var ("root_domain")
	 * @param string $file - The base name of the config file ("core")
	 * @param string $section - (optional) The config file section name
	 * @return mixed
	 */
	public static function get_value($var, $file, $section=NULL) {
		$conf = self::get($file, $section);
		return isset($conf[$var]) ? $conf[$var] : NULL;
	}
}

