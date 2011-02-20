<?php
@session_start();
header('Content-type: application/x-javascript');
$cache = (isset($_GET['cache_type'])? $_GET['cache_type']:'cjax_cache');

$source = (isset($_SESSION[$cache])? $_SESSION[$cache]:(isset($_COOKIE[$cache])? $_COOKIE[$cache]: null));
if(!$source) {
	echo "//no source available";
} else {
	print $source;
}