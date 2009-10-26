<?php
/**
 * Query String Management Class
 *
 * This class turns your HTTP query string into an object with dynamic properties
 * allowing you to get, set, and unset key value pairs in your query string for printing
 * within link tags or server redirects.
 * 
 * To retrieve an HTML version of the generated query string, simply print your instantiated variable.
 * To retrieve a non-HTML entity version of the query string, use the url() method.
 *
 * NOTE: This class does NOT modify the $_GET array in any way, shape, or form
 *
 * Please see the example.php file for more information
 *
 * @author Kenaniah Cerny
 * @version 1.0
 * @license http://creativecommons.org/licenses/BSD/ BSD License
 * @copyright Kenaniah Cerny, 2008
 */
class Query_String {
	
	private $_vars = array();
	
	function __construct($initial_array = NULL){
		
    //Populate using the initial array, or import from $_GET by default
		if(isset($initial_array)){
			$this->_vars = (array) $initial_array;
		}else{
			$this->_vars = $_GET;
		}
		
	}
	
	/**
	 * Loads data into the object using an array
	 */
	function set_array($array){
		
		$this->__construct($array);
		
	}
	
	/**
	 * Retrieves data from the object in array format
	 */
	function get_array(){
		
		return $this->_vars;
		
	}
	
	/**
	 * Prints a version of the query string that can be used for HTTP redirects
	 * Deprecated...
	 */
	function url(){
		
		return $this->__toString(false);
		
	}
	
	
	function __get($key){
		
		return $this->_vars[$key];
		
	}
	
	
	function __set($key, $val){
	
		$this->_vars[$key] = $val;
		
	}
		
	
	function __isset($key){
		
		return isset($this->_vars[$key]);
		
	}
	
	
	function __unset($key){
	
		unset($this->_vars[$key]);
	
	}
	
	
	/**
	 * Converts the object into a query string based off the object's properties
	 */
	function __toString(){
		
		$url_encoded = true; // added by Pär Thernström
		
		if(!count($this->_vars)) return "";
		
		$first = true;

		foreach($this->_vars as $key => $val){
			
			if(is_bool($val)){ //Convert to string
				
				$val = $val ? "true" : "false";
			
			}
			
			if($first){
				
				$output = "?";
				$first = false;
				
			}else{
				
				$output .= $url_encoded ? "&amp;" : "&";
				
			}
			
			$output .= urlencode($key)."=".urlencode($val);
			
		}
		
		return $output;
		
	}

}