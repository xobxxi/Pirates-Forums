<?php
//@app_header;

/**
 * pending on development
 *
 */
$core_dir = dirname(dirname(__file__));
$cjax_dir = dirname($core_dir);
require_once $core_dir.'/cjax_config.php';

class plugin {
	
	private static $plugins = array();
	private static $instance;
	public static $list;
	
	public static function readDir($resource,$read_directories = false)
	{
		$dir = scandir($resource);
		unset($dir[0],$dir[1]);	
		if(!$read_directories) {
			foreach($dir as $k => $v ) {
				if(is_dir($resource.DIRECTORY_SEPARATOR.$v)) {
					unset($dir[$k]);	
				}
			}
		}
		return $dir;
	}
	
	public static function getInstance()
	{
		if(isset(self::$instance)) {
			return self::$instance;
		}
		
		
		return self::$instance = new plugin;
	}
	
	/**
	 * get the full path of a plugin
	 */
	function file($name)
	{
		$plugin_name = self::$list[$name];
		
		return $plugin_name;
	}
	
	
	function exists($name)
	{
		if(isset(self::$list[$name])) {
			return true;
		}
	}
	
	function __construct() 
	{
		$ajax = CJAX::getInstance();
		$method = $ajax->get('method');
		$params = $ajax->get('params');
		$base = CJAX_PLUGIN_DIR;    
		$plugins = CJAX_DIR."/plugins/"; 
		$file = $base.$method;
		
		$dir = self::readDir(CJAX_PLUGIN_DIR);
		foreach($dir as $k => $v) {
			self::$list[substr($v, 0,strlen($v)-3)]= $v;
		}
		
		
		/*if(file_exists($f ="$plugins$method.php")) {
			include $f;
			$js = file_get_contents($f);
			preg_match("/function\s*$method\s*\(.+\)/",$js,$out);
		
			if(!empty($out)) {
				if(!empty($params)) { 
					$params = implode(",",$params);
				}  
				echo "\n$method($params);\n";
			}
		}else if(file_exists($f = "$plugins$method/$method.php")) {
			$js = file_get_contents($f); 
			echo "$js\n";
		}else if(file_exists($f = "$plugins$method/$method.js")) {
			$js = file_get_contents($f);
			echo "$js\n";
			//$ajax->message($js);   
		} else if(file_exists($f = $file.".php")) {
			$js = include ($f);
			$js = file_get_contents($f);  
			$echo = false;
			if(strpos($js,$method) !==false) {
				$echo = true;
			}  
			if($echo) {
				if(!empty($params)) {
					$params = implode(",",$params);
				}
				echo "\n$method($params);\n";
			}
			include "$file.php";
		} else if(file_exists($file.".js")) {
			$js = file_get_contents($file.".js");
			echo "$js\n";
		} else {
			echo "alert(\"Plugin $method.js does not exist\");\n";
		}*/
		#echo("\nalert(\"{$params[1]}\");");
	}
	
	function log($log)
	{
		echo "\n\nalert(\"$log\");\n";
	}
	
}
