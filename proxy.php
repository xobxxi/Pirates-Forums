<?php
// proxy
// usage: proxy.php?code=[code]&goto=[file]

$codes = array(
	'dispc' => 'http://disneypotco.com'
);

if (!isset($_GET['code']) OR !isset($_GET['goto']))
{
	die('Invalid Data');
}

$code = $_GET['code'];
$goto = $_GET['goto'];

if (!in_array($code, array_keys($codes)))
{
	die('Invalid proxy code');
}

$url = $codes[$code] . '/' . $goto;

$page = curl_init();
curl_setopt($page, CURLOPT_URL, $url);
curl_setopt($page, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($page, CURLOPT_HEADER, true);
$response = curl_exec($page);
list($headers, $content) = explode("\r\n\r\n", $response, 2);
curl_close($page);

foreach (explode("\r\n", $headers) as $header)
{
	header($header);	
}

echo $content;
exit();