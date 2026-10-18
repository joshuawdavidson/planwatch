<?php

// alias: username.secretword@planwatch.mailgun.org

//file_put_contents("$_SERVER[DOCUMENT_ROOT]/temp/post_params.dat",serialize($_POST));
include_once('/var/www/planwatch.org/siteconfig.php');
include_once('essential.php');

list($address,$server)=explode("@",$_POST['recipient']);
if($server=="planwatchsend.mailgun.org")
	list($sender,$secretword,$recipient)=explode("__",$address);

$emailrecipient=$recipient;
$recipient=str_replace("-at-","@",$recipient);

$_SERVER['EMAIL_POST']=TRUE;
$_SERVER['EMAIL_FROM']=$_POST['sender'];
$from=$_POST['sender'];

set_include_path('/Library/WebServer/subdomains/planwatch.org/public_html/v4:/Library/WebServer/subdomains/planwatch.org/public_html/v4/scripts:..:.:/Library/WebServer/subdomains/planwatch.org/public_html/v4/backend:/Library/WebServer/subdomains/planwatch.org/public_html/v4/resources/skins:/Library/WebServer/subdomains/planwatch.org/public_html/v4/scripts/standard_library');
include_once("siteconfig.php");
include_once("essential.php");
include_once('user_info_functions.php');

// find out about the writer
$userinfo=user_read_info($sender,TRUE);

// if the secret word matches, authenticate the user
if($secretword && $secretword==$userinfo['secretword'])
{
	$sid=user_set_sessionkey($sender);
	user_verify_sessionkey($sid);
	$message=$_POST['stripped-html'];

	include_once('send.php');
	$success=send_add($sender,$recipient,$message);

	user_clear_sessionkey($sid);
}


// respond to mailgun
//if($success)
//{
	mail("joshuawdavidson@gmail.com","post success $sender","success","From: robot@planwatch.org");
	mail("$from","Send delivered ".date("F jS g:i:a T")."!","Hi {$_SERVER['USERINFO_ARRAY']['realname']},\n\nI just posted your send to $recipient. Here's a copy for you to keep, just in case.\n\nhttp://planwatch.org/send/$recipient\n\nThanks,\nPlanwatch Posting Bot\n\n\n$message","From: $sender__$secretword__$emailrecipient@planwatch.org");
	header("HTTP/1.1 201 Created");
	echo "Post Created
	<hr>
	$post_params[newplan]";
//}
/*
elseif($secretword!='' && $secretword==$userinfo['secretword'])
{
	mail("joshuawdavidson@gmail.com","post fail $sender","fail"); mail("$from","Entry Post Failed ".date("F jS g:i:a T")."!","Hi {$_SERVER['USERINFO_ARRAY']['realname']},\n\nSomething went wrong with your post. Sorry, but josh has been notified. You can go here to post via the web:\n\nhttp://planwatch.org/write\n\nSorry,\nPlanwatch Posting Bot\n\n\n$message","From: post_status@planwatch.org");

	header("HTTP/1.1 500 Internal Server Error");
	echo "We screwed this up. Sorry.
	<hr>
	$post_params[newplan]";
}
else
{
	if (isset($from)) mail("$from","invalid user","sorry, we couldn't validate you. did you include your secret word?");
	else mail("joshuawdavidson@gmail.com","invalid user","no $from\n\n$message");

	header("HTTP/1.1 202 Accepted");
	echo "This won't be processed because it failed to authenticate, but Mailgun doesn't support 403 responses.
	<hr>
	$post_params[newplan]";
}
*/
exit;

?>