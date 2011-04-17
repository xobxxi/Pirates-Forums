<?php

// config
$statics   = array(
	'server.piratesforums.com'
	'piratesoffline.org',
	'mcraging.com'
);
$original = 'piratesforums.com';

foreach ($statics as $static)
{
	if ($_SERVER['HTTP_HOST'] == $static) {
	    header("HTTP/1.1 301 Moved Permanently");
		header("Location: http://{$original}{$_SERVER['REQUEST_URI']}");
		break;
	}
}

echo 'Static resources';
