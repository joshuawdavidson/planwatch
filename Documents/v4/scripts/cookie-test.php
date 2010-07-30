<?php
/*
COOKIE-TEST.PHP

tests to see if the cookie got set.
if not, adds a session variable and redirects.
*/

if (!$_GET['redirect_page']) $_GET['redirect_page']='/';

if (user_verify_fingerprint($_GET[$_SERVER['AUTH_COOKIE']]) && !$_COOKIE[$_SERVER['AUTH_COOKIE']])
{
	if (!$_GET['newuser']) redirect("$_GET[redirect_page]/sid=".$_GET[$_SERVER['AUTH_COOKIE']]);
	else redirect("$nu/sid=".$_GET[$_SERVER['AUTH_COOKIE']]);
}
else
{
	if ($newuser) redirect("/firstlogin");
	else redirect($_GET['redirect_page']);
}
?>