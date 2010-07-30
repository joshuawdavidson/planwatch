<?php

foreach($_POST as $i=>$get)
{
	$_POST[$i]=stripslashes(stripslashes($_POST[$i]));
}

if (isset($_POST['message'])) $message = $_POST['message']. "\nReal Name: $_POST[Real_Name]\n$_SERVER[HTTP_USER_AGENT]\n$_POST[email]";
if (isset($_POST['error_id'])) $message.="\nhttp://planwatch.org/report/details/$_POST[error_id]\n";

$success=mail("joshuawdavidson+planwatch@gmail.com",$_POST['subject'],$message,"From: ".$_POST['Real_Name']." <$_POST[email]>\n");

if (!isset($_POST['error_id'])) output("Thanks!","<h1>Thanks</h1> Thank you for contacting RebuildPhoto. We'll do our best to get back to you within 24 hours.");
else output("Got it!","<h1>Got it</h1>The error message has been sent to Josh. <a href='/'>Go to the main page</a>");
exit;
?>