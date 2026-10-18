<?php 

// alias: username.secretword@planwatch.mailgun.org

//file_put_contents("$_SERVER[DOCUMENT_ROOT]/temp/post_params.dat",serialize($_POST));

list($address,$server)=explode("@",$_POST['recipient']);
if($server=="planwatch.mailgun.org")
	list($writer,$secretword)=explode(".",$address);

if (stristr($_POST['subject'],'private')===TRUE) $private=TRUE;
if (stristr($_POST['subject'],'formatted')===TRUE || stristr($subject,'nolinebreaks')===TRUE || $htmlpart) $nolinebreaks=TRUE;
if (stristr($_POST['subject'],'nofeed')===TRUE) $nofeed="<!--no feed-->";
if (stristr($_POST['subject'],'markdown')===TRUE) $markdown="<!--markdown-->";

$_SERVER['EMAIL_POST']=TRUE;
$_SERVER['EMAIL_FROM']=$_POST['sender'];
$from=$_POST['sender'];

set_include_path('/Library/WebServer/subdomains/planwatch.org/public_html/v4:/Library/WebServer/subdomains/planwatch.org/public_html/v4/scripts:..:.:/Library/WebServer/subdomains/planwatch.org/public_html/v4/backend:/Library/WebServer/subdomains/planwatch.org/public_html/v4/resources/skins:/Library/WebServer/subdomains/planwatch.org/public_html/v4/scripts/standard_library');
include_once("siteconfig.php");
include_once("essential.php");
include_once('user_info_functions.php');

// find out about the writer
$userinfo=user_read_info($writer,TRUE);

// if the secret word matches, authenticate the user
if($secretword && $secretword==$userinfo['secretword'])
{
		$message_parsed['Subject']=str_replace($userinfo['removefromtitles'],'',$_POST['subject']);
		$post_params['action'] ='Update Journaling Plan';
		$post_params['writer'] =$writer;
		$post_params['private']=$private;
		$post_params['nolinebreaks']=$nolinebreaks;
		$post_params['nofeed']=$nofeed;
		$post_params['markdown']=$markdown;
		$post_params['newplan']=$_POST['stripped-html']."<!--TITLE $message_parsed[Subject]--><!--nolinebreaks-->";
		$post_params['sid']=user_set_sessionkey($writer);
		$post_params['mailpost']=1;

		foreach($_FILES as $file)
		{
			move_uploaded_file($file['tmp_name'],"/Library/WebServer/subdomains/planwatch.org/pwusers/$writer/files/$file[name]");
			if(file_exists("/Library/WebServer/subdomains/planwatch.org/pwusers/$writer/files/$file[name]")) $post_params['newplan'].="<a href='/userfiles/view/$file[name]'>$file[name]</a><br />";
		}

		$request_url = "http://planwatch.org/scripts/plan_update.php";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $request_url );       
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1 );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params );
		$status=curl_exec($ch);
		curl_close($ch);

		user_clear_sessionkey($post_params['sid']);

		//file_put_contents("$_SERVER[DOCUMENT_ROOT]/temp/post_params.dat",serialize($post_params));

}


// respond to mailgun
if(strstr($status,'oste'))
{
	email_send("joshuawdavidson@gmail.com","post success $writer","success");
	email_send("$from","Entry Posted ".date("F jS g:i:a T")."!","Hi {$_SERVER['USERINFO_ARRAY']['realname']},\n\nI just posted your email to your plan. Here's a copy for you to keep, just in case. If you didn't want this posted, view your plan here to delete it:\n\nhttp://planwatch.org/view\n\nThanks,\nPlanwatch Posting Bot\n\n\n$message",FALSE,FALSE,"post_status@planwatch.org");
	header("HTTP/1.1 201 Created");

	echo "Post Created
	<hr>
	$post_params[newplan]";
}
elseif($secretword!='' && $secretword==$userinfo['secretword'])
{
	header("HTTP/1.1 200 OK");
	echo "Post Created
	<hr>
	$post_params[newplan]";
	email_send("joshuawdavidson@gmail.com","post fail $writer","fail $status");// mail("$from","Entry Post Failed ".date("F jS g:i:a T")."!","Hi {$_SERVER['USERINFO_ARRAY']['realname']},\n\nSomething went wrong with your post. Sorry, but josh has been notified. You can go here to post via the web:\n\nhttp://planwatch.org/write\n\nSorry,\nPlanwatch Posting Bot\n\n\n$message","From: post_status@planwatch.org");

/*
	header("HTTP/1.1 500 Internal Server Error");
	echo "We screwed this up. Sorry.
	<hr>
	$post_params[newplan]";
*/
}
else
{
	if (isset($from)) email_send("$from","invalid user","sorry, we couldn't validate you. did you include your secret word?");
	else email_send("joshuawdavidson@gmail.com","invalid user","no $from");

	header("HTTP/1.1 202 Accepted");
	echo "This won't be processed because it failed to authenticate, but Mailgun doesn't support 403 responses.
	<hr>
	$post_params[newplan]";
}

exit;

?>