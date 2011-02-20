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


/**
 * renamed from singleton to cjax_singleton to void problems with some hosts, having todo with name space
 */
if(!class_exists('cjax_singleton')){
	class cjax_singleton {
		static $instances = array();  // array of instance names
		
	    static  function getInstance ($class,$param=null){
	    // implements the 'singleton' design pattern.	
	        if (!array_key_exists($class, self::$instances)) {
	            // instance does not exist, so create it
	            self::$instances[$class] = new $class;
	        }
	        $instance =& self::$instances[$class];
	        return $instance;   
	    }   
	}
}
