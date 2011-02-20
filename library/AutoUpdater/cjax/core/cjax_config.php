<?php
/** ################################################################################################**   
* Copyright (c)  2008  CJ.   
* Permission is granted to copy, distribute and/or modify this document   
* under the terms of the GNU Free Documentation License, Version 1.2   
* or any later version published by the Free Software Foundation;   
* Provided 'as is' with no warranties, nor shall the autor be responsible for any mis-use of the same.     
* A copy of the license is included in the section entitled 'GNU Free Documentation License'.   
*   
*   CJAX  3.1                $     
*   ajax made easy with cjax                    
*   -- DO NOT REMOVE THIS --                    
*   -- AUTHOR COPYRIGHT MUST REMAIN INTACT -   
*   Written by: CJ Galindo                  
*   Website: cjax.net                     $      
*   Email: cjxxi@msn.com    
*   Date: 2/12/2007                           $     
*   File Last Changed:  02/19/2011            $     
**####################################################################################################    */   


#define('JSDIR','http://yoursite.com/cjax'); //Enter the url where CJAX is located
if(!defined('CJAX_DIR_CORE')) define('CJAX_DIR_CORE',dirname(__file__));
if(!defined('CJAX_DEFINED')) define('CJAX_DEFINED',1);
If(!defined('CJAX_UNDEFINED')) define('CJAX_UNDEFINED',null);

$undefined = CJAX_UNDEFINED;
$JS_DIR = CJAX_UNDEFINED;

if(!defined('CJAX_CORE_DIR')) define('CJAX_CORE_DIR',dirname(__file__));
if(!defined('CJAX_DIR')) define('CJAX_DIR',dirname(CJAX_CORE_DIR)); 
if(!defined('CJAX_PLUGIN_DIR')) define('CJAX_PLUGIN_DIR',CJAX_DIR.DIRECTORY_SEPARATOR.'plugins');
if(!defined('CJAX_CLASSES_DIR')) define('CJAX_CLASSES_DIR',CJAX_CORE_DIR.DIRECTORY_SEPARATOR .'classes'.DIRECTORY_SEPARATOR);
require_once CJAX_CLASSES_DIR.'format.class.php';
require_once CJAX_CLASSES_DIR.'std.class.php';
require_once CJAX_CLASSES_DIR.'cjax.class.php';

if(!class_exists('CJAX')) {
class CJAX extends CJAX_FRAMEWORK {
		const CSS_SUCCESS = 1;
	    const CSS_WARNING = 2;
	    const CSS_INFO = 3;
	    const CSS_ERROR = 4;
	    const CSS_PROCESS = 5;
	    
	    private static $CJAX;
		
		/**
		 * get an instance of CJAX
		 * with singleton pattern 
		 * @return CJAX_FRAMEWORK OBJECT
		 */
		public static function getInstance()
		{
			$CJAX = ((self::$CJAX)? self::$CJAX:self::$CJAX = new CJAX_FRAMEWORK);
			if(!isset($ajax->format) || !$ajax->format) {
				$CJAX->format = new cjaxFormat();
			}
			return $CJAX;
		}
	}
}

if(defined('JSDIR')) {
	$JS_DIR = JSDIR;
} else if(defined('IN_SAMPLES')) {
	$dir = $_SERVER['PHP_SELF'];
	while (strrpos($dir,'cjax/')  !== false || $dir=='') {
			$dir = substr($dir,0,strlen($dir) - 1);
	}
	CJAX_FRAMEWORK::path('http://'.$_SERVER['HTTP_HOST'].$dir);
} else {
	$undefined = CJAX_DEFINED;
}

if($undefined) {
	$dir  = 'http://'.dirname($_SERVER['HTTP_HOST'].$_SERVER["SCRIPT_NAME"]).'/cjax/core/js/cjax.js';
	
	//$info = CJAX_FRAMEWORK::getFile('cjax/core/version.php');

	/*if(trim(strpos($info,"CJAX") !==false)) {
		CJAX_FRAMEWORK::$path = CJAX_FRAMEWORK::remotePath();
	}*/
	if(is_dir('cjax/')) {
		$JS_DIR  = "cjax/core/js/";
	} else if(is_dir('core/js/')) {
		$JS_DIR  = "core/js/";
	} else if(is_dir('../cjax')) {
		$JS_DIR  = "../cjax/core/js/";
	} else {
		$dir = str_replace(DIRECTORY_SEPARATOR,'/',getcwd());
		$f = str_replace(DIRECTORY_SEPARATOR,'/',__file__);
		$f = str_replace($dir,'',$f);
		$f=(dirname(dirname($f))).'/';
		$JS_DIR  = ltrim($f .'core/js/','/');
	}
}

$ajax = CJAX::getInstance();
$ajax->js($JS_DIR);