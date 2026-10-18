<?php
/* FORM_SHIM.php
a pointer for scripts that use essential functions for form processing
*/

include_once('/var/www/planwatch.org/siteconfig.php');
include_once('essential.php');

if ($_POST['action']=='send_recovery_email')
{
	$userinfo=user_read_info($_POST['username']);
	$email=$userinfo['email'];
	unset($userinfo);
	$nonce=uniqid();
	file_put_contents("$_SERVER[DOCUMENT_ROOT]/../sessions/$nonce.recover",$_POST['username']);

	email_send("$email","Set a new passphrase for planwatch.org","Click here to set a new passphrase:\n https://planwatch.org/user/recover/$nonce \n\n Questions or problems? Reply to this message and Josh will get back to you.",FALSE,FALSE,"help@planwatch.org");
	output("Thanks!","<h1>Thanks!</h1>You should have a recovery email in your inbox.");
}

if(isset($_POST['fingerbox']))
{
	redirect("/read/$_POST[fingerbox]");
	exit;
}

if (isset($_POST['invite_url']))
{
	email_send("$_POST[recipient]","$_POST[requester] has invited you to planwatch.org","Click here to set up your account:\n $_POST[invite_url]\n\n Questions or problems? Email help@planwatch.org\n\n$_POST[personal]",FALSE,FALSE,"invite@planwatch.org");
	output("Thanks!","<h1>Thanks!</h1>$_POST[recipient] has been sent an invitation to planwatch.org.");
}

if (isset($_GET['action']) && $_GET['action']=='smiley_writekeys')
{
	include_once('smiley_functions.php');
	smiley_writekeys($smiley,$keys);
	exit;
}

if (isset($_GET['action']) && $_GET['action']=='invite')
{
	$filename=$_SERVER['USER_ROOT']."/stats/".md5($_GET['email']).".invite";
	$used_filename=$_SERVER['USER_ROOT']."/stats/".md5($_GET['email']).".used.invite";
	if (file_exists($filename) || file_exists($used_filename)) echo "You have already invited that person.";
	else
	{
		$file=fopen($filename,'w');
		fwrite($file,serialize($_GET));
		fclose($file);
	}
//	echo "$filename ".serialize($_GET);exit;
	email_send($_GET['email'],"invite","$_GET[name]:\n\nyou have been invited to planwatch. click this link to accept: https://planwatch.org/user/accept_invite/$_SERVER[USER]/".md5("$_GET[email]"),FALSE,FALSE,"system@planwatch.org");
}

if (isset($_POST['action']) && $_POST['action']=='upload smiley')
{
	include_once('smiley_functions.php');
	smiley_writenew($newsmiley,$newsmileyname);
	exit;
}

if (isset($_GET['action']) && $_GET['action']=='archive_search')
{
	Header("Location: https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$_GET[writer]/search/".urlencode($_GET['keyword']));
	exit;
}

if (isset($_POST['action']) && $_POST['action']=='sendmessage')
{
	include_once('send.php');
	send_find($_POST['message'],$user,$_POST['recipient']);
	redirect("/send/$_POST[recipient]");
	exit;
}

if (isset($_POST['action']) && $_POST['action']=='login')
{
	// print_r($_POST);
	login($_POST['user'],$_POST['pass'],$_POST['remember']);
	exit;
}

if (isset($_POST['data']) && $_POST['data'] && $_POST['filename'] && user_is_administrator())
{
	file_put_contents($_POST['filename'],stripslashes($_POST['data']));
	redirect('/');
	exit;
}

//if ($_SERVER['USER']=='jwdavidson') print_r($_POST['action']);

if (isset($_POST['action']) && $_POST['action']=='snitch_archive')
{
	redirect("/snitch/$_POST[reverse]$_POST[threshhold]$_POST[units]/$_POST[begindate]");
	exit;
}

if (isset($_POST['action']) && $_POST['action']=='write_css')
{
	file_put_contents("$_SERVER[USER_ROOT]/user_css.txt",'$extra_css="'.$_POST['css_data'].'";');
	redirect('/');
	exit;
}

if(isset($_POST['archivelist']))
{
	if($_POST['username'] && $_POST['archivelist'])
	{
		redirect("/read/$_POST[username]/".implode(',',$_POST[archivelist]));
		exit;
	}
	elseif ($_POST['username'] && $_POST['threshhold'])
	{
		redirect("/read/$_POST[username]/$_POST[reverse]$_POST[threshhold]$_POST[units]/$_POST[startyear]/$_POST[startmonth]/$_POST[startdom]/$_POST[starttime]".":00");
		exit;
	}
}


redirect('/');

?>