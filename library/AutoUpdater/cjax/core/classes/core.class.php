<?php
/** ################################################################################################**   
* Copyright (c)  2008  CJ.   
* Permission is granted to copy, distribute and/or modify this document   
* under the terms of the GNU Free Documentation License, Version 1.2   
* or any later version published by the Free Software Foundation;   
* Provided 'as is' with no warranties, nor shall the autor be responsible for any mis-use of the same.     
* A copy of the license is included in the section entitled 'GNU Free Documentation License'.   
*   
*   CJAX  3.1 RC3                $     
*   ajax made easy with cjax                    
*   -- DO NOT REMOVE THIS --                    
*   -- AUTHOR COPYRIGHT MUST REMAIN INTACT -   
*   Written by: CJ Galindo                  
*   Website: https://github.com/cjax/Cjax-Framework                     $      
*   Email: cjxxi@msn.com    
*   Date: 2/12/2007                           $     
*   File Last Changed:  02/20/2011            $     
**####################################################################################################    */   


function cjax_autoload($class)
{
	$file = $class.'.class.php';
	if(file_exists(CJAX_CLASSES_DIR.$file)) {
		require_once CJAX_CLASSES_DIR.$file;
		return true;
	} 
}
spl_autoload_register('cjax_autoload'); // As of PHP 5.3.0
/**
 * Load external classes
 *@package CoreEvents
 * @param string $c
 */
class CoreEvents extends cjaxFormat {
	
	//acts more strict in the kind of information you provide. This coulde be used to identify error etc.
	public $strict = false;
	
	const FLAG_NO_FLAG = 0;
    //default wait for any timouts
    const FLAG_WAIT = 1;
    //skip timeouts
    const FLAG_NO_WAIT = 2;
    
    const FLAG_ELEMENT_BY_ID = 3;
    const FLAG_ELEMENT_BY_CLASS = 4;
    
    const FLAG_DEBUG_DISABLED  = 0;
    const FLAG_DEBUG_ENABLED  = 1;
	
	/**
	 * 
	 * Force the sytem to adapt to a loading or not loading state.
	 * @var unknown_type
	 */
	public $loading = false;
	
	public $post = 0;
	
	public $dir;
	
	public $attach_event = true;
	
	//public static $action_cache = array();
	
	public  static $cjax_data_counter = 0;
	
	/**
	 * default port when connecting to web pages
	 *
	 * @var unknown_type
	 */
	public $port = 80;
	
	/**
	 * if controllers are located in a sub directory
	 *
	 * @var string
	 */
	public $controller_dir = '';
	
	
	public $controller_type = null;
	
	
	/*
	 * hold an object with some formattig helpers
	 * not meant to be added to the package but it was added at some point
	 * @return cjaxFormat
	 */
	public $format;
	
	/**
	 * Where the data is stored before the output
	 *
	 * @var unknown_type
	 */
	private static $out = array();
	
	/**
	 * Check whether or not to call the shutdown function
	 *
	 * @var boolean $_is_shutdown_called
	 */
	private static $_is_shutdown_called = false;
	
	/**
	 * store cache procedure
	 *
	 * @var string $cache
	 */
	public static $cache = array();
	
	/**
	 * hold cache for actions
	 *
	 * @var array
	 */
	public static $actions = array();
	
	/**
	 * specified whether to use the cache system or normal mode
	 *
	 * @var boolean $use_cache
	 */
	static $use_cache;
	
	//new alias to replace $JSevent.
	public $event = "onClick";
	
	/**
	 * Set the text to show when the page is loading
	 * this replaces the "loading.."
	 * 
	 *
	 * @var mixed $text
	 */
	public $text;
	/**
	 * Set the image that shows up when the page is loading
	 * this replaces teh default image
	 *
	 * @var unknown_type
	 */
	public $image;
	/*
	 * This must be set to true before making a CJAX call,
	 * only if the element you are interacting with is an anchor
	 * or an image
	 * 
	 */
	public $link;
	
	/*
	 * The the CJAX console on debug mode
	 */
	public $debug;
	
	/**
	 * Set the default directory when images loading images reside
	 *
	 * @var string $image_dir
	 */
	public $image_dir;
	
	/**
	 * Get the current version of CJAX FRAMEWORK you are using
	 *
	 * @var string
	 */
	public $version;
	
	/**
	 * Define that you are using an external extension
	 * 
	 *
	 * @var string
	 */
	public $extension;
	
	/**
	 * When using th plugin system, you will need to specify what the pluing base url is
	 * 
	 */
	private $extension_dir;
	
	/**
	 * Tells whether CJAX output has initciated or not, to void duplications
	 *
	 * @var boolean $is_init
	 */
	public $is_init;
	
	/**
	 * Sets the default way of making AJAX calls, it can be either get or post
	 */
	public $method;
	/**
	 * Stores the the waiting procedure for the next action
	 */
	private static $wait;
	
	/**
	 * Path where JavaScript Library is located
	 *
	 * @var string
	 */
	public static $path;
	
	/**
	 * Path where JavaScript Library is located
	 *
	 * @var string
	 */
	private $jsdir = null;	
	
	
	public $extension_sub = false;
	/**
	 * Auto execute methods for extensions
	 *
	 * @param string $method
	 * @param array $args
	 */
	
	public $caller;
	
	private static $cache_type;
	
	/*
	 * sets up the default loading image
	 */
	
	protected static $flags = array();
	
	public static $waiting_flags = array();
	
	public static $const = array();
	
	//reference to see what official flag types are currently being used
	private static $flag_types = array(
		'FLAG_WAIT','FLAG_ELEMENT_GETTER','FLAG_DEBUG'
	);
	
	/**
	 * Helper to generate flags quicker.
	 * @param $flag_id
	 * @param $command_count
	 */
	function setFlag($flag_id,$command_count = 1)
	{
		$flags = array();
		
		switch($flag_id) {
			case 'no_wait':
				$flags = array('FLAG_WAIT'=> CJAX::FLAG_NO_WAIT);
				break;
			case 'debug':
				
				$flags = array('FLAG_DEBUG'=> CJAX::FLAG_DEBUG_ENABLED);
				break;
			default:
				
				if(CJAX::getInstance()->strict) {
					die("Invalid Flag Argument Prodivided");
				}
		}
		
		return self::setFlags($flags, $command_count);
	}
	/**
	 * 
	 * Sets flags for the next set of commands
	 * @param Mixed $flags
	 * @param Int $command_count
	 */
	public function setFlags($flags = array() , $command_count = 1)
	{
		$_flags = array();
		$new_flags = array();
	
		$ajax = CJAX::getInstance();
		if(!self::$const) {
			$reflect = new ReflectionClass($ajax);
	   		$consts = $reflect->getConstants();
	   		self::$const = $flip = array_flip($consts);
		} else {
			$flip = self::$const;
		}
		foreach($flags as $k => $v) {
			if(!isset($flip[$v])) {
				if($ajax->strict) {
					die("Invalid flag was assigned in: $k-$v");
				} else {
					$_flags[$k] = $v;	
				}
			} else {
				$_flags[$k] = $v;//$flip[$v];
			}
		}
		for($i = 0; $i < $command_count; $i++) {
			foreach($_flags as $k => $v) {
				$new_flags[$k][] = $v;
			}
		}
		if(self::$flags) {
			//combine any previous flags
			foreach(self::$flags as $k => $v) {
				if(isset($new_flags[$k])) {
					$new = array_merge($new_flags[$k],$v);
					$new_flags[$k] = $new;
				} else {
					$new_flags[$k] = $v;
				}
			}
		}
		return self::$flags = $new_flags;
	}
	
	public function getFlags()
	{
		return self::$flags;
	}
	
	public function processFlag()
	{
		if(!self::getFlags()) {
			return;
		}
		
		$flags = array();
		
		foreach(self::$flags as $k => $v) {
			if(empty($v)) {
				unset(self::$flags[$k]);
				continue;
			}
			foreach($v as $k2 => $v2) {
				$flags[$k] = $v2;
				unset(self::$flags[$k][$k2]);
				break;
			}
		}
		
		if($flags) {
			$flags = self::xmlIt( 
				array('flags'=> self::mkArray($flags,true) 
				));
			
			return $flags;
		}
	}
	
	/**
	 * xml outputer, allows the interaction with xml
	 *
	 * @param xml $xml
	 * @return string
	 */
	function xml($xml)
	{
		if(is_array($xml)) {
			$xml = $this->xmlIt($xml);
		}
		if(!$xml) return false;
		$function = '';
		if(strpos($xml,'<do>')===false) {
			$trace = debug_backtrace();
			$function = "{$trace[1]['function']}";
			if($function=='wait') {
				self::$wait = $xml;
				return true;
			}
			$function = "<do>{$function}</do>";
			if(self::$wait) {
				$function = "{$function}".self::$wait;
				self::$wait = '';
			}
		} else {
			if(self::$wait) {
				$function = "{$function}".self::$wait;
				self::$wait = '';
			}
		}
		
		if($flags = self::processFlag()) {
			$xml .= $flags;
		}
		$data = "{$function}{$xml}";
	
		if($function=='<do>AddEventTo</do>') {
			self::cache("<cjax>$data</cjax>",'actions');
		} else {
			self::$cjax_data_counter++;
			self::cache("<cjax>$data</cjax>");
		}
		
		//is an Iframe
		if($this->get('cjax_iframe')) {
			print $data;
		}
		return $data;
	}
	
	public function args($input)
	{
		return "&--&".$input;
	}

	public function mkArray($array,$json = false)
	{
		if($json) {
			return "<json>".$this->encode(json_encode($array))."</json>";
		}
		$new_array = null;
		if(!empty($array)) {
			foreach($array as $k =>$v) {
				$new_array[] = "<arr><k>$k</k><v>$v</v></arr>";
			}
		}
		$new_array = (($new_array)? implode($new_array) : null);
		return $new_array;
	}

	public function warning($msg=null,$seconds=4)
	{
		$ajax = CJAX::getInstance();
		if(!$msg) {
			$msg = "Invalid Input";
		}
		$ajax->message($ajax->format->message($msg,CJAX::CSS_WARNING),$seconds);		
	}
	
	public function success($msg=null,$seconds=3)
	{
		$ajax = CJAX::getInstance();
		if(!$msg) {
			$msg = "Success!";
		}
		$ajax->message($ajax->format->message($msg,CJAX::CSS_SUCCESS));
	}
	
	public function process($msg=null,$seconds=3)
	{
		$ajax = CJAX::getInstance();
		if(!$msg) {
			$msg = "Processing...";
		}
		$ajax->message($ajax->format->message($msg,CJAX::CSS_PROCESS),$seconds);
	}
	
	public function info($msg=null,$seconds=3)
	{
		$ajax = CJAX::getInstance();
		$ajax->message($ajax->format->message($msg,CJAX::CSS_INFO),$seconds);
	}
	
	public function  error($msg=null,$seconds=15)
	{
		$ajax = CJAX::getInstance();
		if(!$msg) {
			$msg = "Error!";
		}
		$ajax->message($ajax->format->message($msg,CJAX::CSS_ERROR),$seconds);
	}
	
	
	public function __set($setting,$value)
	{
		return $this->set_value($setting,$value);
	}
	
	/**
	 * Setting up the directory where the CJAX FRAMEWORK resides
	 *
	 * @param string $jsdir
	 */
	function js($jsdir,$force=false)
	{
		if($force) {
			self::$path = $jsdir;
			return $this->jsdir = false;
		}
		if(!$this->jsdir && $this->jsdir !==false) {
			$this->jsdir = $jsdir;
		}
	}
	
	/**
	 * Outputs our FRAMEWORK to the browser
	 * @param unknown_type $js_path
	 * @return unknown
	 */
	function head_ref($js_path = null)
	{
		if($this->jsdir) {
			return "<script defer='defer' id='cjax_lib' type='text/javascript' src='{$js_path}cjax.js'></script>\n";
		}
		if(isset(self::$path) && strlen(self::$path) > 0) {
			if(self::$path[strlen(self::$path)-1] =='/') {
				self::$path = substr(self::$path,0,strlen(self::$path) -1);
			}
			return "<script id='cjax_lib' type='text/javascript' src='".self::$path."/core/js/cjax.js'></script>\n";
		}
		
	}

	/**
	 * initciates the process of sending the javascript file to the application
	 *	
	 * @param optional boolean $echo
	 * @return string
	 */
	function init($echo = false)
	{
		$ajax = CJAX::getInstance();
		self::clearCache();
		if($ajax->is_init) {
			
			return;
		}
		if ($echo) {
			echo $this->head_ref ($this->jsdir);
			$this->is_init = true;
			return;
		} else {
			$href = $this->head_ref ($this->jsdir);
			$this->is_init = true;
			return $href;
		}
	}
		
	/**
	 *  Tell whether of not the a ajax request has been placed
	 *
	 * Sunday August 3 2008 added functionality:
	 * 
	 * @return boolean
	 */
	 function request($callback = null, &$params = null)
	 {
	 	$r = self::loading(); 
	 	if(!$r && $callback) {
	 		if(is_array($callback) ) {
	 			if(substr($callback[0],0,4)=='self') {
	 				$arr = debug_backtrace();
		 			$trace = $arr[1];
		 			$class = $trace['class'];
	 				$class = $class;
	 				$callback[0] =$class;
	 			}
	 			if(!$params) $params = array();
	 			$r = call_user_func_array($callback,$params);
	 		} else {
	 			$r = call_user_func($callback);
	 		}
	 		exit();
	 	}
	 	if(self::loading()) {
	 		return false;
	 	}
	 	return true;
	 }
	 
	 function setRequest($request = true)
	 {
	 	if($request) {
		 	$_GET['cjax'] = time();
		 	$_REQUEST['cjax'] = time();
	 	} else {
	 		$_GET['cjax'] = '';
	 		$_REQUEST['cjax'] = '';
	 	}
	 }

	
	/**
	 * Encode special data to void conflicts with javascript
	 *
	 * @param string $data
	 * @return encoded string
	 */
	function encode($data)
	{
		//$data = str_replace("+","[plus]",$data);
		return urlencode($data);
	}
    
    /**
     * Converts an array into xml..
     */
    function xmlIt($input = array())
    {
    	$new = array();
    	if(is_array($input) && $input) {
			foreach ($input as $k =>$v) {
				if($v) {
					$new[] =  "<$k>$v</$k>";
				}
			}
			return $xml = implode($new);
		}
    }
    
	
	function save($setting,$value)
	{
		@session_start();
		$_SESSION[$setting] = $value;
		if (!headers_sent()) {
			@setcookie($setting,$value);
		}
	}
	
	function getSetting($setting)
	{
		if(isset($_SESSION[$setting])) {
			return $_SESSION[$setting];
		} else if(isset($_COOKIE[$setting])) {
			return $_COOKIE[$setting];
		}
	}

	/**
	 * Used for loading "fly" events
	 *
	 * @param string $add
	 */
	function cache($add=null,$cache_id = null)
	{
		if(!self::$_is_shutdown_called) {
			$bol = register_shutdown_function(array('CoreEvents','saveSCache'));
			self::$_is_shutdown_called = true;
			self::$use_cache = true;
		}
		if($cache_id) {
			if($cache_id=='actions') {
				self::$actions[] = $add;
			} else {
				self::$cache[$cache_id] = $add;
			}
		} else {
			self::$cache[self::$cjax_data_counter] = $add;
		}
		
		if($add==null){
			return self::$cache;
		}
	}
	
	
	function removeCache($count) 
	{
		if(!self::$cache ) {
			return true;
		}
	
		if($count==count(array_keys(self::$cache))) {
			self::$cache = array();
			if(self::$cjax_data_counter > 0) {
				self::$cjax_data_counter--;
			}
		} else if($count < self::$cjax_data_counter) {
			foreach(self::$cache as $k => $v) {
				if(!$count) {
					break;
				}
				$max = max(array_flip(self::$cache));
				unset(self::$cache[$max]);
				$count--;
			}
		}
		
	}	
	
	/**
	 * Saves the cache
	 *
	 * @return string
	 */
	public static function saveSCache()
	{
		$ajax = CJAX::getInstance();
		if(!self::loading()) {
			if(!self::$cache && !self::$actions) {
				return;
			}
			if(!self::$cache) {
				self::$cache = self::$actions;
			} else {
				if(self::$actions) {
					self::$cache = array_merge(self::$cache,self::$actions);
				}
			}
			
			print ("<xml class='cjax'>".implode(self::$cache)."</xml>");
			
			return;
		}  else {
			if(isset(self::$cache_type) && strlen(self::$cache_type) > 0) {
				$cache = self::$cache_type;
			} else {
				$cache = 'cjax_cache';
			}
			if(!self::$cache && !self::$actions) {
				self::clearCache();
				return;
			}
			if(!self::$cache) {
				self::$cache = self::$actions;
			} else {
				if(self::$actions) {
					self::$cache = array_merge(self::$cache,self::$actions);
				}
			}
			
			$debug = ($ajax->debug? 1 : 0);
			$out = "CJAX.loading = false;\nCJAX.process_all(\"". str_replace(array("\n","\t"), "", implode(self::$cache))."\",true,$debug);";
			GLOBAL $_SESSION;
			if(!isset($_SESSION) ) {
				@session_start();
			}
			
			
			if(isset($_SESSION['cache_type'])) {
				$cache = $_SESSION['cache_type'];
			} elseif (isset($_COOKIE['cache_type'])) {
				$cache = $_COOKIE['cache_type'];
			}
			$_SESSION['cjax_cache'] = $out;
			if (!headers_sent()) {
				@setcookie ('cjax_cache',$out,false);
			}
			return true;
		}
	}
	
	
	function cacheType($type='cjax_cache')
	{
		self::$cache_type = $type;
	}
	
	/**
	 * write to a file in file system, used as an alrernative to for cache
	 *
	 * @param string $content
	 * @param string $flag
	 */
 	function file_write($content,$flag='w') {
 		$dir = str_replace('classes','js',__file__);
 		$dir = str_replace('core.class.php','cjax.js.php',$dir);
 		$filename = $dir;
        if (file_exists($filename)) {
            if (!is_writable($filename)) {
                if (!chmod($filename, 0666)) {
                     echo "CJAX: Error! file ($filename) is not writable, Not enough permission";
                     exit;
                };
            }
        }
        if (!$fp = @fopen($filename, $flag)) {
			echo "CJAX: Error! file ($filename) is not writable, Not enough permission";
 			exit;
        }
        if (fwrite($fp, $content) === FALSE) {
			echo "Cannot write to file ($filename)";
			exit;
        }
        if (!fclose($fp)) {
            echo "Cannot close file ($filename)";
            exit;
        }
    }
    
    function OS_SLASH()
    {
		$pos = strpos(PHP_OS, 'WIN');	    
		if ($pos !== false) {
    		return '\\';
    	}
    	return '/';
    }
	// error handler function
	/**
	 * Yet to implement
	 *
	 * @param string $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 * @return string
	 */
	function CJAXErrorHandler($errno, $errstr, $errfile, $errline)
	{
	    switch ($errno) {
	    case E_USER_ERROR:
	        echo "<b>CJAX:</b> [$errno] $errstr<br />\n";
	        echo "  Fatal error on line $errline in file $errfile";
	        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
	        echo "Aborting...<br />\n";
	        exit(1);
	        break;
	
	    case E_USER_WARNING:
	        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
	        break;
	
	    case E_USER_NOTICE:
	        echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
	        break;
	
	    default:
	        echo "Unknown error type: [$errno] $errstr<br />\n";
	        break;
	    }
	
	    /* Don't execute PHP internal error handler */
	    return true;
	}
	
	function clearCache()
	{
		//$old_err = set_error_handler(array('self','CJAXErrorHandler'));
		GLOBAL $_SESSION;
		if(!isset($_SESSION)) {
			@session_start();
		}
		$_SESSION['cjax_cache'] = '';
		if (!headers_sent()) {
			@setcookie('cjax_cache','',false);
		}
		//set_error_handler($old_err);
	}
	
	/**
	 * returns the current cache
	 * 
	 * @return unknown_type
	 */
	function getCache()
	{
		return self::$cache;
	}
	
	/**
	 * Image directory where loading images are loaded from
	 *
	 * @param unknown_type $dir
	 */
	function image_dir($dir = '')
	{
		$this->image_dir = $dir;
	}

	/**
	 * Optional text, replaces the "loading.." text when an ajax call is placed
	 *
	 * @param unknown_type $ms
	 */
	function text($ms = '')
	{
		$this->text = $ms;
	}
	
	/**
	 * Simple debug option to alert any output by AJAX calls
	 *
	 * @param boolean $debug
	 */
	function debug($debug = false)
	{
		$this->debug = $debug;
	}
	
	/**
	 * Require to be set to true before using a text link to execute an AJAX call
	 *
	 * @param boolean $link
	 * @return string
	 */
	function link($link = false)
	{
		$this->link = $link;
	}
	
	/**
	 * if CJAX is not located sircuntacion at the subdirectory level, and 
	 * CJAX is bein called from within a child directory then you will need to specify
	 * the url where CJAX is located (eg. http://yoursite.com/cjax)
	 *
	 * @param string $Path [CJAX URL] 
	 */
	function path($path)
	{
		self::$path = $path;
	}
	
	public static function remotePath()
	{
		$host = $_SERVER['HTTP_HOST'];
		$sname = dirname($_SERVER["SCRIPT_NAME"]);
		return 'http://'.$host.$sname.'/cjax';
	}
	
	public static function getFile($file=null)
	{
		return self::connect($_SERVER['HTTP_HOST'],(isset($_SERVER['SERVER_PORT'])? $_SERVER['SERVER_PORT']:80),$file,true);
	}
	
	public static function connect($host=null, $file=null,$port = 80,$local = false)
	{
		$ajax = CJAX::getInstance();
		
		if(function_exists('curl_init')) {
			$url = 'http://'.$host.'/'.$file;
			$ch = curl_init($url);
			
			if($ajax->post)  {
				curl_setopt($ch, CURLOPT_POST ,1);
				curl_setopt($ch, CURLOPT_REFERER, 'http://google.com');
				curl_setopt($ch, CURLOPT_POSTFIELDS,$ajax->post);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
			}
			curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
			curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
			curl_setopt ($ch, CURLOPT_TIMEOUT, 3);
			$data = curl_exec($ch);
			curl_close($ch);
			
			return $data;
		}
		
		if(!$port) {
			$port = $ajax->port;
			if(!$port) {
				$port = 80;
			}
		} 
		if(!function_exists('fsockopen')) {
			die('no fsockopen: be sure that php function fsockopen is enabled.');
		}
		
		
		$fp = @fsockopen($host,$port,$errno,$errstr);
		if(!$fp) {
			return false;
		}
		if($errstr) {
			die('error:'.$errstr);
		}
		
		if($fp) {
			$base = null;
			if($local) {
				if(isset($_SERVER["SCRIPT_NAME"])) {
					$base =dirname($_SERVER["SCRIPT_NAME"]);
					if(strpos($base,"/",strlen($base)-1)!="/") {
						$base.= "/";
					}
				} else {
					$base = "/";
				}
			} else  {
				$base = "/";
			}
	        @fputs($fp, "GET $base$file HTTP/1.1\r\n");
	        @fputs($fp, "HOST: $host\r\n");
	        @fputs($fp, "Connection: close\r\n\r\n");
			
		} else {
			return false;
		}
		$get_info = false;
		$data= array();
		while (!feof($fp)) {
			if ($get_info) {
					$data[] = fread($fp, 1024);
			} else {
				if (fgets($fp, 1024) == "\r\n") {
					$get_info = true;
				} else {
					//break;
				}
			}
		}
		fclose ( $fp );
		return implode($data);
	}
	
	
	/*
	 * checks for imputs and return values sent throught the $_GET method
	 */
	function get($value=null)
	{
		global $HTTP_POST_VARS, $HTTP_GET_VARS;
		if($value===null) $value= 'cjax';
		$v = isset($_GET[$value])? $_GET[$value] : (isset($_REQUEST[$value])? $_REQUEST[$value]:null);
		if(!$value) {
			return($v)?1:0;
		}
		if(is_array($v)) {
			foreach($v as $k => $kv ) {
				if(!is_array($kv)) {
					$return[$k] =  addslashes($kv);
				} else {
					foreach($kv as $k_level => $v_level2) {
						$return[$k][$k_level] = $v_level2;
					}
				}
			}
			return $return;
		}
		return addslashes($v);
	}

	
	private static $_loading;
	/*
	 * Checks whether or not the actual load method is being iniciated from the "load"
	 * or from an AJAX call
	 */
	public static function loading($set_loading = null)
	{
		$ajax = CJAX::getInstance();
		if(strlen($ajax->get('cjax')) > 10 && is_numeric($ajax->get('cjax'))) {
		      $_loading = false;
		} else {
			$_loading = true;
		}
		if(self::$_loading) {
			return self::$_loading;
		}
		if($set_loading) {
			$_loading = self::$_loading = $set_loading;
		}
		return $_loading;
	}
	

	/**
	 * Convers input formated sizes into Megabytes
	 * @param $val
	 */
	function toMB($val) {
	  $val = trim($val);
	  $last = strtolower($val[strlen($val) - 1]);
	  switch ($last) {
	    // The 'G' modifier is available since PHP 5.1.0
	    case 'g':
	      $size = $val * 1024;
	      break;
	    case 'k':
	      $size = $val / 1024;
	      break;
	    default:
	      $size = (int) $val;
	  }
	  return $size;
	}
		
	/**
	 * Syntax hilighting a program source file. It calls enscript(1) to parse and
	 * insert HTML tags to produce syntax hilighted version of the source.
	 *
	 * @param  $filename The filename of the source file to be transformed.
	 * @return A text string containing syntax hilighting version of the source,
	 *         in HTML.
	 */
	function syntax_hilight($filename) {
	    if ((substr($filename, -4) == '.php')) {
	        ob_start();
	        show_source($filename);
	        $buffer = ob_get_contents();
	        ob_end_clean();
	    } else {
	        $argv = '-q -p - -E --language=html --color '.escapeshellcmd($filename);
	        $buffer = array();
	
	        exec("enscript $argv", $buffer);
	
	        $buffer = join("\n", $buffer);
	        $buffer = eregi_replace('^.*<PRE>',  '<pre>',  $buffer);
	        $buffer = eregi_replace('</PRE>.*$', '</pre>', $buffer);
	    }
	
	    // Making it XHTML compatible.
	    $buffer = eregi_replace('<FONT COLOR="', '<span style="color:', $buffer);
	    $buffer = eregi_replace('</FONT>', '</style>', $buffer);
	
	    return $buffer;
	}
	
	function __construct()
	{
		$cjax_dir = $this->getSetting('cjax_dir');
		
		if(isset($_SERVER['PHP_SELF'])) {
			$dir = dirname($_SERVER['PHP_SELF']);
			$dir = explode("/",$dir);
			end($dir);
			$dir = $dir[key($dir)];
			
			if($cjax_dir !=$dir) {
				$this->save('cjax_dir',$dir);
			}
		}
		
		
		//check files...
		if(isset($_FILES)  && !empty($_FILES) && $this->get('cjax_iframe')) {
			$error = false;
			foreach($_FILES as $k => $v) {
				switch($v['error']) {
					case UPLOAD_ERR_INI_SIZE:
						$error = true;
						$this->warning("{$v['name']} - File is too big.");
					break;
					case UPLOAD_ERR_FORM_SIZE:
						$error = true;
						$this->warning("{$v['name']} - The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form. ");
					break;
					case UPLOAD_ERR_PARTIAL:
						$error = true;
						$this->warning("{$v['name']} -The uploaded file was only partially uploaded.");
					break;
					case UPLOAD_ERR_NO_FILE:
						$error = true;
						$this->warning("{$v['name']} - No file was uploaded. ");
					break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$error = true;
						$this->warning("{$v['name']} - Missing a temporary folder.");
					break;
					case UPLOAD_ERR_CANT_WRITE:
						$error = true;
						$this->warning("{$v['name']} - Failed to write file to disk.");
					break;
					case UPLOAD_ERR_EXTENSION:
						$error = true;
						$this->warning("{$v['name']} - A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop;<br /> examining the list of loaded extensions with phpinfo() may help.");
					break;
				}
			}
			if($error) {
				exit("cjax_frame-file-upload-error");
			}
		}
	}
}