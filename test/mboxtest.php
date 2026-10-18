<?php

$mailbox_fn="$rootdir/test/submit_inbox";

$mailbox=file_read_contents($mailbox_fn);
$mail_list=explode("\n\nFrom ",$mailbox);
foreach($mail_list as $i=>$message)
{
	list($headers,$body)=explode("\n\n",$message,2);
	preg_match("|Subject: (.*)\s|",$headers,$subject);
	$message_subject=trim($subject[1]);
	if (fingerprint_verify($message_subject))
	{
		global $pwuserinfo,$userinfo,$preferences,$user,$pass;
		$pwuserinfo=getenv('PWUSERINFO');
		parse_str($pwuserinfo,$userinfo);
		parse_str($pwuserinfo,$preferences);
		$user=$username;
		$pass=$password;

		preg_match("|Date: (.*)\s|",$headers,$date);
		$edit=".".strtotime($date[1]);

		if (strpos($message,'#private#')!==FALSE)
		{
			$message=str_replace('#private#','',$message);
			$private='.p';
		}
		if (strpos($message,'#nolinebreaks#')!==FALSE) $nolinebreaks=TRUE;

		include_once('setplan.php');
		putenv('BLOGPOST=TRUE');		
		if (updateplan($edit,$body,$private,$nolinebreaks,$user))
		{
			$written[$i]=TRUE;
			echo "$edit written<br>\n";
		}
		else
		{
			preg_match("|Return-path: (.*)\s|",$from,$date);
			mail(
				$from[1],
				"Post Failed",
				"Your post failed for some reason. Here it is:\n\n$message",
				"From: help@planwatch.org\nReturn-path: help@planwatch.org\nReply-to: help@planwatch.org\n"
				);
			echo "$edit not written<br>\n";
		}
	}

//	$mailbox=str_replace("\n\nFrom $message",'',$mailbox);
}

if (count($written)==count($mail_list))
{
	echo "whole mailbox processed.";
//	file_write_contents($mailbox_fn,'');
}
?>