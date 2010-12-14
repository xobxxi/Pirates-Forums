<?php

// config
$static   = 'piratesoffline.org';
$original = 'piratesforums.com';

if ($_SERVER['HTTP_HOST'] == $static) {
    header("HTTP/1.1 301 Moved Permanently");
	header("Location: http://{$original}{$_SERVER['REQUEST_URI']}");
}

echo 'Static resources';
