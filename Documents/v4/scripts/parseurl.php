<?php

/*
PARSEURL.PHP -- part of the planwatch library

handles 404 and 403 errors, url routing
*/

$urlarray=explode('/',str_replace($_SERVER['WEB_ROOT'],'',$_SERVER['REQUEST_URI']));

//var_dump($urlarray);

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
