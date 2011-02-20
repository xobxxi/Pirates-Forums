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

$dir = (dirname(__file__));
require_once $dir.'/cjax/cjax.php';
class ajax  {
			
    function ajax($controller)
    {
		$ajax = CJAX::getInstance();
		$dir = $ajax->get('cjax_dir');
        define('AJAX_CONTROLLER',1);
		
        $getcwd = getcwd();
        
		$c = $ajax->get('load_controller');
		if(!$c && $ajax->get('controller_cd')){
			$dir = $ajax->get('controller_cd');
		} 
		
		if($x = $ajax->get('controller_type')) {
			$c = $x;
		}
		if($c) {
			$controller_type = $controller_dir = $c;
			if(strpos($controller_type,":")!==false) {
				$data = explode(":",$c);
				$controller_dir = $data[0];
				$controller_type = $data[1];
				$_REQUEST['controller_type'] = $data[1];
			}
			$ajax->controller_type = $controller_type;
		}
	        
        $a = ((isset($_REQUEST['a']))? $_REQUEST['a']:null);
        $b = ((isset($_REQUEST['b']))? $_REQUEST['b']:null);
        $c = ((isset($_REQUEST['c']))? $_REQUEST['c']:null);
        $d = ((isset($_REQUEST['d']))? $_REQUEST['d']:null);
        $e = ((isset($_REQUEST['e']))? $_REQUEST['e']:null);
        
        $controller = $ajax->get('controller');
        if(!$controller) {
        	die("controller no defined.");
        }
          
		if($dir && is_file($file = $dir.'/controllers/'.$controller.'.php')) {
			//$file = $dir.'/controllers/'.$controller.'.php';
			//$ajax->dir
		} elseif($ajax->controller_dir) {
        	$file = $ajax->controller_dir.'/controllers/'.$controller.'.php';;
        } else {
        	 $file = 'controllers/'.$controller.'.php';
        }
        if (!is_file($file)) {
            die("controller file: $file not found");
        }
        require_once $file;
        $class = 'controller_'.$controller;
        if(!class_exists($class)) {
            die("controller class not \"{$class}\" found");
        }
        
		$function = (isset($_REQUEST['function'])? $_REQUEST['function']:$ajax->get('section'));
		
        $obj = cjax_singleton::getInstance($class);
		if(!method_exists($obj,$function)) {
        	if(!method_exists($obj,'controller')) {
        		$err = "controller method/function \"$class::$function()\" not found";
				$ajax->error($err);
				die($err); 
        	}
        }
        
        
        unset($_GET['controller']);
        unset($_GET['function']);
        
		if($e!==null) {
			$r = $obj->{$function}($a,$b,$c,$d,$e);
		} else if($d!==null) {
			$r = $obj->{$function}($a,$b,$c,$d);
		}else if($c!==null) {
			$r = $obj->{$function}($a,$b,$c);
		} else if($b!==null) {
			$r = $obj->{$function}($a,$b);
		} else if($a!==null) {
			$r = $obj->{$function}($a);
		} else {
			$r = $obj->{$function}();
		}
    }
    
}
$ajax = CJAX::getInstance();
$controller = $ajax->get('controller');
if($controller) {
	new ajax($controller);
}