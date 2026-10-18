#!/usr/bin/php 
<?php 

// script chmoded to 755 
// alias: username.secretword@post.planwatch.org: |/home/planwatc/process_mail.php
// read from stdin 
$fd = fopen("php://stdin", "r"); 
$email = ""; 
while (!feof($fd)) { 
$email .= fread($fd, 1024); 
} 
fclose($fd);

require_once('rfc822_addresses.php');
require_once('mime_parser.php');

$message_file=((IsSet($_SERVER['argv']) && count($_SERVER['argv'])>1) ? $_SERVER['argv'][1] : '/home/planwatc/test.eml');
$mime=new mime_parser_class;
$mime->mbox = 0;
$mime->decode_bodies = 1;
$mime->ignore_syntax_errors = 1;
$parameters=array('Data'=>$email,'SkipBody'=>0);

if(!$mime->Decode($parameters, $decoded))
	mail("joshuawdavidson@gmail.com","mime decode error",'MIME message decoding error: '.$mime->error.' at position '.$mime->error_position."\n");
else
{
	if(!$mime->Analyze($decoded[0], $message_parsed))
		mail("joshuawdavidson@gmail.com","mime error",'MIME message analyse error: '.$mime->error."\n");
}

foreach($message_parsed["To"] as $toline)
{
	list($address,$server)=explode("@",$toline['address']);
	if($server=="post.planwatch.org")
		list($writer,$secretword)=explode(".",$address);
}

if (stristr($message_parsed['Subject'],'private')===TRUE) $private=TRUE;
if (stristr($message_parsed['Subject'],'formatted')===TRUE || stristr($subject,'nolinebreaks')===TRUE || $htmlpart) $nolinebreaks=TRUE;
if (stristr($message_parsed['Subject'],'nofeed')===TRUE) $nofeed="<!--no feed-->";
if (stristr($message_parsed['Subject'],'markdown')===TRUE) $markdown="<!--markdown-->";

$_SERVER['EMAIL_POST']=TRUE;
$_SERVER['EMAIL_FROM']=$message_parsed['From'][0]['address'];

set_include_path('/home/planwatc/public_html/v4:/home/planwatc/public_html/v4/scripts:..:.:/home/planwatc/public_html/v4/backend:/home/planwatc/public_html/v4/resources/skins:/home/planwatc/public_html/v4/scripts/standard_library');
include_once("siteconfig.php");
include_once("essential.php");
include_once('user_info_functions.php');

// find out about the writer
user_read_info($writer,TRUE);

// if the secret word matches, authenticate the user
if($secretword && $secretword==$_SERVER['USERINFO_ARRAY']['secretword'])
{
		$message_parsed['Subject']=str_replace($_SERVER['USERINFO_ARRAY']['removefromtitles'],'',$message_parsed['Subject']);
		$post_params['action'] ='Update Journaling Plan';
		$post_params['writer'] =$writer;
		$post_params['private']=$private;
		$post_params['nolinebreaks']=$nolinebreaks;
		$post_params['nofeed']=$nofeed;
		$post_params['markdown']=$markdown;
		$post_params['newplan']=$message_parsed['Data']."<!--TITLE $message_parsed[Subject]--><!--nolinebreaks-->";//."<!--email post\n".serialize($message_parsed)."-->";
		$post_params['sid']=user_get_fingerprint($_SERVER['USER'],$_SERVER['USERINFO_ARRAY']['userpass']);
		$post_params['mailpost']=1;

		$request_url = "http://planwatch.org/scripts/plan_update.php";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $request_url );       
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1 );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params );
		$status=curl_exec($ch);
		curl_close($ch);
		if($status=='posted') { mail("joshuawdavidson@gmail.com","post success $writer","success"); mail("$from","Entry Posted ".date("F jS g:i:a T")."!","Hi {$_SERVER['USERINFO_ARRAY']['realname']},\n\nI just posted your email to your plan. Here's a copy for you to keep, just in case. If you didn't want this posted, view your plan here to delete it:\n\nhttp://planwatch.org/view\n\nThanks,\nPlanwatch Posting Bot\n\n\n$message","From: post_status@planwatch.org"); }
		else { mail("joshuawdavidson@gmail.com","post fail $writer","fail"); mail("$from","Entry Post Failed ".date("F jS g:i:a T")."!","Hi {$_SERVER['USERINFO_ARRAY']['realname']},\n\nSomething went wrong with your post. Sorry, but josh has been notified. You can go here to post via the web:\n\nhttp://planwatch.org/write\n\nSorry,\nPlanwatch Posting Bot\n\n\n$message","From: post_status@planwatch.org"); }
}
else mail("$from","invalid user","sorry, we couldn't validate you. did you include your secret word? [$writerinfo] [$writer] [$secretword]");



/*
if (preg_match("/mixed; boundary=(.*)/", $email, $matches)) { 
//	echo "MIXED!<hr>";
	$mixedboundary = $matches[1];
	if($mixedboundary[0]=='"') $mixedboundary=substr($mixedboundary,1,strlen($mixedboundary)-2);
	$mailparts=explode("--$mixedboundary",$email);
//	echo count($mailparts)." message parts<hr>";
	$headers=$mailparts[0];
	foreach($mailparts as $part)
	{
		if(strstr($part,"multipart/alternative"))
		{ 
			if (preg_match("boundary=(.*)/", $part, $matches))
			{
				$altboundary = $matches[1];
				if($altboundary[0]=='"') $altboundary=substr($altboundary,1,strlen($altboundary)-2);
				$typeparts=explode("--$altboundary",$part);
				foreach($typeparts as $tpart)
				{
					if(strstr($tpart,"text/html"))
					{
						$htmlpart=$tpart;
					} 
					if(strstr($tpart,"text/plain"))
					{
						$textpart=$tpart;
					} 
				}
			}
		}	
	}
}
elseif(preg_match("boundary=(.*)/", $email, $matches))
{ 
	$altboundary = $matches[1];
	if($altboundary[0]=='"') $altboundary=substr($altboundary,1,strlen($altboundary)-2);
	$typeparts=explode("--$altboundary",$email);
//	echo "ALT<hr>";
//	echo count($typeparts)." alternatives<hr>";
	$headers=$typeparts[0];
	foreach($typeparts as $tpart)
	{
		if(strstr($tpart,"text/html"))
		{
			$htmlpart=$tpart;
		} 

		if(strstr($tpart,"text/plain"))
		{
			$textpart=$tpart;
		} 
	}
}

if($headers)
{
	if($htmlpart) $email=trim($headers)."\n".trim(quoted_printable_decode($htmlpart));
	else $email="$headers\n$textpart";
}


// handle email 
$lines = explode("\n", $email);

// empty vars 
$from = ""; 
$subject = ""; 
$headers = ""; 
$message = ""; 
$to = ""; 
$splittingheaders = true;


for ($i=0; $i<count($lines); $i++) { 
if ($splittingheaders) { 
	// this is a header 
	$headers .= $lines[$i]."\n";
	
	// look out for special headers
	if (preg_match("/^Subject: (.*)/", $lines[$i], $matches)) { 
		$subject = $matches[1]; 
	} 
	if (preg_match("/^From: (.*)/", $lines[$i], $matches)) { 
		$from = $matches[1]; 
	} 
	if (preg_match("/To: ([^\"]*)@post.planwatch.org/", $lines[$i], $matches)) { 
		$writerinfo = $matches[1];
		list($writer,$secretword)=explode(".",$writerinfo);
	} 
	if (preg_match("/To: \".*\" <(.*)@post.planwatch.org>/", $lines[$i], $matches)) { 
		$writerinfo = $matches[1];
		list($writer,$secretword)=explode(".",$writerinfo);
	} 
//	if (preg_match("Content-Type: ", $lines[$i], $matches)) { 
//		$mimeboundary = $matches[1];
//	} 
//	if (preg_match("Content-Transfer-Encoding: ", $lines[$i], $matches)) { 
//		$mimeboundary = $matches[1];
//	} 

} else { 
// not a header, but message 
	$message .= $lines[$i]."\n"; 
}

if (trim($lines[$i])=="") { 
	// empty line, header section has ended 
	$splittingheaders = false; 
	} 
}
*/
?>
