<?php

/*
PARSEURL.PHP -- part of the planwatch library

handles 404 and 403 errors, url routing
*/

// if(empty($_SERVER['HTTPS']))
// {
// 	//echo "no https";
// 	header("http/1.1 302 Moved" );
// 	header("Location: https://planwatch.org/$_SERVER[REQUEST_URI]");
// 	echo " ";
// 	exit;
// }

include_once('/var/www/planwatch.org/siteconfig.php');


include_once('essential.php');

if(isset($_GET['urlstring'])) $urlarray=explode('/',str_replace($_SERVER['WEB_ROOT'],'',$_GET['urlstring']));
else
{

	// preserve query strings from fake urls
	if(isset($_SERVER['REDIRECT_QUERY_STRING']))
	{
		parse_str($_SERVER['REDIRECT_QUERY_STRING'],$query);
		$_REQUEST=array_merge($_REQUEST,$query);
	}
	$body = file_get_contents('php://input');

	$urlarray=explode('/',str_replace($_SERVER['WEB_ROOT'],'',$_SERVER['REQUEST_URI']));

}
	if (strstr($urlarray[count($urlarray)-1],'sid=')) $urlarray[sizeof($urlarray)-1]='';
	$_SERVER['URL_ARRAY']=$urlarray;


profile('precontent','end');
profile('content','begin');

// everything that used to be in this switch statement was broken out into its
// own file in cases/$urlarray[1].case to make it easier to maintain.
$handler_fn="$_SERVER[FILE_ROOT]/scripts/cases/".strtolower($urlarray[1]).".case";
if (file_exists($handler_fn)) include_once($handler_fn);
else	include_once('cases/home.case');

?>
