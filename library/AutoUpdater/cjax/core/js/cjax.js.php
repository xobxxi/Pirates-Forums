<?php
@session_start();
$cache = (isset($_GET['cache_type'])? $_GET['cache_type']:'cjax_cache');

$source = (isset($_SESSION[$cache])? $_SESSION[$cache]:(isset($_COOKIE[$cache])? $_COOKIE[$cache]: null));
if(!$source) {
	echo "//no source available";
} else {
	header('Content-type: application/x-javascript');
	header("Cache-Control: no-cache");
	header("Pragma: no-cache");
	if(isset($_SESSION[$cache])) {
		unset($_SESSION[$cache]);
	}
	if(isset($_COOKIE[$cache])) {
		@setcookie($cache,'',time()-3600);
	}
	print $source;
}