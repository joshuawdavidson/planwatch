<?php
/* FORM_SHIM.php
a pointer for scripts that use essential functions for form processing
*/

if ($_POST['invite_url'])
{
	mail("$_POST[recipient]","$_POST[requester] has invited you to planwatch.org","Click here to set up your account:\n $_POST[invite_url]\n\n Questions or problems? Email help@planwatch.org\n\n$_POST[personal]","From: invite@planwatch.org");
	output("Thanks!","<h1>Thanks!</h1>$_POST[recipient] has been sent an invitation to planwatch.org.");
}

if ($_GET['action']=='smiley_writekeys')
{
	include_once('smiley_functions.php');
	smiley_writekeys($smiley,$keys);
	exit;
}

if ($_GET['action']=='invite')
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
	mail($_GET['email'],"invite","$_GET[name]:\n\nyou have been invited to planwatch. click this link to accept: http://planwatch.org/user/accept_invite/$_SERVER[USER]/".md5("$_GET[email]"),"From: system@planwatch.org");
}

if ($_POST['action']=='upload smiley')
{
	include_once('smiley_functions.php');
	smiley_writenew($newsmiley,$newsmileyname);
	exit;
}

if ($_GET['action']=='archive_search') 
{
	Header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$_GET[writer]/search/".urlencode($_GET['keyword']));
	exit;
}

if ($_POST['action']=='sendmessage')
{
	include_once('send.php');
	send_find($_POST['message'],$user,$_POST['recipient']);
	redirect("/send/$_POST[recipient]");
	exit;
}

if ($_POST['action']=='login')
{
	login($_POST['user'],$_POST['pass'],$_POST['remember']);
	exit;
}

if ($_GET['action']=='login_ajax')
{
	login($_GET['user'],$_GET['pass'],$_GET['remember'],"/watched/$_GET[user]");
	exit;
}

if ($_POST['data'] && $_POST['filename'] && user_is_administrator())
{
	file_put_contents($_POST['filename'],stripslashes($_POST['data']));
	redirect('/');
	exit;
}

//if ($_SERVER['USER']=='jwdavidson') print_r($_POST['action']);

if ($_POST['action']=='snitch_archive')
{
	redirect("/snitch/$_POST[reverse]$_POST[threshhold]$_POST[units]/$_POST[begindate]"); 
	exit;
}

//if ($_SERVER['USER']=='jwdavidson') print_r($_POST);
if ($_POST['action']=='write_css')
{
	file_put_contents("$_SERVER[USER_ROOT]/user_css.txt",'$extra_css="'.$_POST['css_data'].'";');
	redirect('/');
	exit;
}

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


redirect('/');
/*
if ($action=='aggregate_add_feed')
{
	$_COOKIES['urls'].=','.$new_feed;
	setcookie($_COOKIES['urls'];
}
*/
?>