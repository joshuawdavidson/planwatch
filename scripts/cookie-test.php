<?php
/*
COOKIE-TEST.PHP

tests to see if the cookie got set.
if not, adds a session variable and redirects.
*/
include_once('/var/www/planwatch.org/siteconfig.php');
include_once('essential.php');

if (!$_REQUEST['redirect_page']) $_REQUEST['redirect_page']=$_SERVER['WEB_ROOT'];



// if we have a valid session but no cookie
if (user_verify_sessionkey($_REQUEST[$_SERVER['SESSION_COOKIE']]) && $_REQUEST[$_SERVER['SESSION_COOKIE']]!=$_COOKIE[$_SERVER['SESSION_COOKIE']])
{
//	if($_SERVER['USER']=='jwdavidson') { print_r($_REQUEST); echo "<hr>"; print_r($_COOKIE); exit; }
	if (!$_REQUEST['newuser']) redirect("$_REQUEST[redirect_page]/sid=".$_REQUEST[$_SERVER['SESSION_COOKIE']]);
	else redirect("/firstlogin/sid=".$_REQUEST[$_SERVER['SESSION_COOKIE']]);
}
else
{
	// if we have a valid session cookie
	if ($_REQUEST['newuser']) redirect("/firstlogin");
	else redirect($_REQUEST['redirect_page']);
}
?>