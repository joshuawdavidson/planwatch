<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if(isset($_SERVER['USER']) && $_SERVER['USER']=='www-data') {$_SERVER['USER']='';}

// i use this for debugging.
if(isset($_COOKIE['josh']) && $_COOKIE['josh']==1)
{
	define("IS_JOSH",1);
}
else
{
	define("IS_JOSH",0);
}


$_SERVER['DEBUG_INFO']='';
$_SERVER['ERRORS']='';
$_SERVER['ERROR_DETAILS']='';

$_SERVER['API_KEY_FLICKR']="";

// these should be somewhere not web-accessible.
$_SERVER['PWUSERS_DIR']="/var/www/planwatch.org/pwusers";
$_SERVER['SESSION_DIR']="/var/www/planwatch.org/sessions";
$_SERVER['SESSION_COOKIE']="sdfjasdlk0fja_sldkfj";

// email sending account
$_SERVER['ga']="postmaster@mg.planwatch.org";
$_SERVER['ga_pass']="";

$_SERVER['mailgun_base_url']="https://api.mailgun.net/v3/mg.planwatch.org";
$_SERVER['mailgun_skey']="";

// set these values if you're installing in a subdirectory
$_SERVER['FILE_ROOT']=$_SERVER['DOCUMENT_ROOT'];
$_SERVER['WEB_ROOT']="";
$_SERVER['VERSION_ROOT']="$_SERVER[DOCUMENT_ROOT]/v4";

// you can change the cookie name to whatever you like.
$_SERVER['AUTH_COOKIE']="dfasdgasg4gwwgeg23dsfg";

// versions are slightly indeterminate. i bump from time to time
// when it seems appropriate.
$_SERVER['PLANWATCH_VERSION']=4.2+(rand(0,100000)/1000000);;

// INCLUDE PATH CONFIGURATION
// you can put scripts that don't get called directly in any
// of these places. plan_update.php needs to stay in /scripts
//
// feel free to add more or modify these for your configuration
$path="$_SERVER[VERSION_ROOT]".PATH_SEPARATOR
		."$_SERVER[VERSION_ROOT]/backend".PATH_SEPARATOR
		."$_SERVER[VERSION_ROOT]/resources/skins".PATH_SEPARATOR
		."$_SERVER[VERSION_ROOT]/scripts".PATH_SEPARATOR
		."$_SERVER[VERSION_ROOT]/scripts/standard_library";

set_include_path(get_include_path().PATH_SEPARATOR.$path);

// essential handles authentication and has some
// functions needed every page load.
if(!function_exists("is_mobile"))
{
	include_once('essential.php');
}


?>
