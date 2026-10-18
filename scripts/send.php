<?php

/*
SEND.php

implements send - lightweight messaging between plan users
*/
include_once('/var/www/planwatch.org/siteconfig.php');
include_once('essential.php');

$send_dir="$_SERVER[PWUSERS_DIR]/$_SERVER[USER]/sends";
if (!is_dir($send_dir))	{		$old=umask(0);		mkdir($send_dir,0777);		umask($old);	}

// SEND_FIND()
//
// discovers send messages in regular plan entries
//------------------------------------------------------------------------------
function send_list($filter=FALSE)
{
	$send_dir="$_SERVER[PWUSERS_DIR]/$_SERVER[USER]/sends";

	if($filter=="new") 	$sends=files_list("$_SERVER[USER_ROOT]/sends/","*..new");
	else				$sends=files_list($send_dir,"*");

	if(isset($sends[0]))
	{
		$senders=array();
		foreach($sends as $send)
		{
			$sender=$timecode=$junk=0;
			if(strpos($send,'..')!==0)
			{
				$send_parts = explode("..",$send);
				if(is_array($send_parts))
				{
					$sender=$send_parts[0];
					$timecode=$send_parts[1];
				}
			}
			if(!is_numeric($timecode)) $timecode=filectime("$send_dir/".basename($send));
			$sender=files_decode_safe_name($sender);
			if(($filter=="week" && $timecode > (time()-3600*24*7)) || !$filter || $filter=='new') {$senders[$timecode]=$sender;}
		}

		krsort($senders);
		$senders=array_unique($senders);
		$alias_list=user_list_aliases($_SERVER['USER']);
		foreach($senders as $last_time=>$sender)
		{
			$sendtime=formattime($last_time);
			if (file_exists("$send_dir/$sender..new")) $class='unread';
				else $class='read';

			$alias=array_search($sender,$alias_list);
			if($alias!==FALSE) $displaysender=str_replace("@","<wbr>@",$alias);
			else $displaysender=str_replace("@","<wbr>@",$sender);


			$senders[$last_time]="<li class='$class'><a title='$sender' href='/send/$sender' class='updatename'>$displaysender</a> <a  href='/send/$sender' class='updatetime'>$sendtime</a></li>";
		}

		if(!$filter) $senders[time()-24*7*3600]="<li class='listheader'><a href='#'>Older</a></li>";
		krsort($senders);
		$senders=implode("\n",$senders);
	}
	else $senders="<li class='read'>No sends</li>";
	return $senders;
}

// SEND_FIND()
//
// discovers send messages in regular plan entries
//------------------------------------------------------------------------------
function send_find($plan,$planowner,$timecode)
{
	$send_dir="$_SERVER[PWUSERS_DIR]/$planowner/sends";
	preg_match_all("|!send:([^!]*)!|",$plan,$matches);

	$matches=$matches[1];

	foreach($matches as $i=>$match)
	{
		$summary_begin=strpos($plan,$match);
		$summary=substr($plan,$summary_begin+strlen($match)+1,1024);
		$endtag=strpos($plan,'!spiel!',$summary_begin);
		$message=substr($plan,$summary_begin+strlen($match)+1,$endtag-1);

		$sendto=$match;

		send_add($_SERVER['USER'],$sendto,$message);
	}

	return TRUE;
}


function send_invite_user($requester,$invite_request)
{
	$whitelist=file_get_contents("$_SERVER[FILE_ROOT]/resources/whitelist.txt");
	$requester=str_replace(array("@planwatch.org","@beta","@pwo"),'',$requester);
	if (strstr($requester,'amherst.edu') || strstr($whitelist,$requester) || !strstr($requester,'@'))
	{
		$invite_code=md5($invite_request.time());
		$filename="$_SERVER[FILE_ROOT]/temp/invites/$invite_code.$requester.invite";
		$used_filename="$_SERVER[FILE_ROOT]/temp/invites/$invite_code.$requester.used.invite";
		if (file_exists($filename) || file_exists($used_filename))
		{
			$invite_response="You have already invited that person. \n\noriginal message: $invite_request";
		}
		else
		{
			$file=fopen($filename,'w');
			fwrite($file,serialize(array("email"=>"your@email.org","name"=>"Enter Your Name Here","inviter"=>"$requester")));
			fclose($file);
		}

		if (!$invite_response)
			$invite_response="Copy and paste this invite code into an email: \n\nhttp://planwatch.org/user/accept_invite/$requester/$invite_code\n\nProblems? Ask jwdavidson@planwatch.org\n\n original message: $invite_request.";
	}
	else
	{
		$invite_response="
		Sorry, planworld.net users cannot automatically issue planwatch invites for security reasons.
		Please send jwdavidson@planwatch.org to make your request. \n\noriginal message: $invite_request";
	}

	if(!strstr($requester,'@')) $success=send_add_xmlrpc("invite","$requester@planwatch.org",$invite_response);
	else $success=send_add_xmlrpc("invite",$requester,$invite_response);

	$timecode=time();
	$send_fn="/home/planwatc/pwusers/invite/sends/".files_encode_safe_name("$requester..$timecode..messageto");
	file_put_contents($send_fn,$invite_response);

	return $success;
}


// SEND_ADD()
//
// umbrella send handler
//------------------------------------------------------------------------------
function send_add($sender,$recipient,$message)
{
	$message=stripslashes($message);

//	if(!strstr($recipient,'jwdavidson') || plan_test_privacy($sender,$recipient))
//	{

	if(!strstr($recipient,'@')) $success=send_add_xmlrpc($sender,"$recipient@planwatch.org",$message);
	else $success=send_add_xmlrpc($sender,$recipient,$message);

	if (!is_dir("$_SERVER[USER_ROOT]/sends/"))
	{
		$oldumask=umask(0);
		mkdir("$_SERVER[USER_ROOT]/sends/",0755);
		umask($oldumask);
	}

	$timecode=time();
	$recipient=str_replace(array("@planwatch.org","@beta","@pwo"),'',$recipient);
	$recipient=str_replace(array("@note","@note.amherst.edu"),'@amherst.edu',$recipient);
	$recipient=str_replace("@pwn",'@planworld.net',$recipient);
	$send_fn="$_SERVER[USER_ROOT]/sends/".files_encode_safe_name("$recipient..$timecode..messageto");
	file_put_contents($send_fn,$message);


//		if ($recipient=='invite' || strstr($recipient,'invite@'))
//		{
//			$invite_success=send_invite_user($sender,$message);
//		}



		return $success;
//	}
//	else
//	{
//		if(plan_test_privacy($sender,"system@planwatch.org"))
//			send_add("system@planwatch.org",$sender,"Sending a message to $recipient failed");
//
//		return FALSE;
//
//	}
}


// SEND_ADD_XMLRPC()
//
// manages sends to other nodes
//------------------------------------------------------------------------------
function send_add_xmlrpc($sender,$recipient,$message,$debug=FALSE)
{
	list($recipient_username,$host)=explode("@",$recipient);

//	if(IS_JOSH || $_SERVER['USER']=='testuserb') $debug=TRUE;
	$success = planworld_xmlrpc_query(
		$host,
		'planworld.send.sendMessage',
		array("$sender@planwatch.org",$recipient_username,$message),
		$debug
		);

//	if ($success && (IS_JOSH || $_SERVER['USER']=='testuserb')) echo "send success $message";
//	elseif(IS_JOSH || $_SERVER['USER']=='testuserb') echo "send failed $recipient_username $host $recipient";

return $success;
}

// SEND_ADD_LOCAL()
//
// manages sends that stay on planwatch.org
//------------------------------------------------------------------------------
function send_add_local($sender,$recipient,$message,$debug=FALSE)
{
	list($recipient,$junk)=explode("@",$recipient);
	$send_dir="$_SERVER[PWUSERS_DIR]/$recipient/sends";

//	if(!plan_test_privacy($sender,$recipient))
//	{
//				if(plan_test_privacy($sender,"system@planwatch.org"))
//		send_add("system@planwatch.org",$sender,"Sending a message to $recipient failed. Message follows<br> ---------------<br> $message");

//		return FALSE;

//	}

	if (!is_dir($send_dir))
	{
		$oldumask=umask(0);
		mkdir($send_dir,0755);
		umask($oldumask);
	}

//	if($sender=="jwdavidson@planwatch.org" || $sender=="jwdavidson" || $sender=="system@planwatch.org" || $sender=="system" || empty($sender))
//		{ $_SERVER['XMLRPC_ERROR']="sender [$sender] recipient [$recipient] message [$message] noted. exiting."; }

	$recipient_info=user_read_info($recipient);
	$sender=str_replace("@planwatch.org",'',$sender);
	$send_fn="$send_dir/".files_encode_safe_name($sender."..".time()."..messagefrom");
	file_put_contents($send_fn,$message);
	touch("$send_dir/".files_encode_safe_name("$sender..new"));

	if (file_exists($send_fn) && !empty($recipient)) $success=TRUE;
	else $success=FALSE;

	if(file_exists("$_SERVER[PWUSERS_DIR]/$recipient/prowl.apikey") && $recipient_info['pushsend'])
	{
		include_once('class.php-prowl.php');
		$sid=user_set_sessionkey($recipient,time()+1800);
		$api_key=file_get_contents("$_SERVER[PWUSERS_DIR]/$recipient/prowl.apikey");
		$prowl = new Prowl();
		$prowl->setApiKey($api_key);
		$prowl->setProviderKey("c3f2f5f31955961126129685ac344111386147d4");
		$prowl->setDebug($debug);

		$application = "planwatch.org";
		$event = "Send from $sender";
		$description = "$message";
		$url = "https://planwatch.org/send/$sender/?sid=$sid";
		$priority = 0;

		$message = $prowl->add($application,$event,$priority,$description,$url);
	}

	if($recipient_info['mailsend'])
	{
		$emailsender=str_replace("@","-at-",$sender);
		email_send($recipient_info['email'],"Send from $sender",$message,FALSE,FALSE,"From: $recipient__$recipient_info[secretword]__$emailsender@planwatchsend.mailgun.org");
	}

	return $success;
}


// SEND_DISPLAY()
//
// outputs a send conversation
//------------------------------------------------------------------------------
function send_display($correspondent,$form=TRUE,$offset=0)
{
	$debuginfo='';
	if(!$offset) {$offset=0;}
	$correspondent=trim(str_replace(array("@planwatch.org",'/'),'',$correspondent));
	$send_dir="$_SERVER[USER_ROOT]/sends";

	if (!is_dir($send_dir))
	{
		umask(0);
		mkdir ($send_dir,0755);
	}
	$sendlist=files_list($send_dir,files_encode_safe_name($correspondent)."*message*");

	if (is_array($sendlist))
	{
		foreach($sendlist as $i=>$send)
		{
			$send_fn=basename($send);
			$send=str_replace("@planwatch.org",'',files_decode_safe_name($send_fn));
			list($recipient,$time,$direction)=explode("..",$send);
			if ($direction=='messagefrom')
			{
				$from=$correspondent;
				$style='  class="send_from"  ';
			}
			else
			{
				$from=$_SERVER['USER'];
				$style=' class="send_to" ';
			}

// 			echo "$send_dir/$send_fn<br>".file_get_contents("$send_dir/$send_fn")."<hr>";

			$sendarray[$time]="<div $style>".smart_nl2br(removeEvilTags(file_get_contents("$send_dir/$send_fn")))."<div style='text-align: right; font-weight: normal;'>&mdash; $from <span style='font-weight: normal;'>(".date("F jS Y g:ia",$time).")</span></div> </div>\n";
		}

		krsort($sendarray);
		$firstmessage='';
		foreach($sendarray as $time=>$sendtext)
		{
			if(strstr($sendtext,"_from"))
			{
				$firstmessage=$sendtext.$firstmessage;
				unset($sendarray[$time]);
			}
			else
			{
//				if(IS_JOSH) $firstmessage.="send_to appears first $sendtext";
				break;
			}

		}

		$sendarray=array_slice($sendarray,$offset,30);
		$latest_time=array_key_first($sendarray);
		$content=implode("\n",$sendarray);

		if (file_exists("$send_dir/".files_encode_safe_name("$correspondent..new")))
		{
			unlink("$send_dir/".files_encode_safe_name("$correspondent..new"));
		}
		if (file_exists("$send_dir/".files_encode_safe_name("$correspondent@planwatch.org..new")))
		{
			unlink("$send_dir/".files_encode_safe_name("$correspondent@planwatch.org..new"));
		}
	}
//	$content=hyperlink($content);

	if ($form)
	{
		if (browser_is_modern() && $_SERVER['OUTPUT_MODE']!='MOBILE')
			 $sendbutton="<input type='button' onclick='sendMessage();' value='Send' id='submit_button' />";
		else $sendbutton="<input type=\"submit\" value='Send' id='submit_button' href=\"#\" style='' />";

		if (!$offset)
		{
			if(OUTPUT_MODE=='HTML')
			{
				$content="
				$debuginfo
				$firstmessage<br clear='all' />
				<form action='$_SERVER[WEB_ROOT]/scripts/send.php' style='margin: 0px; display: block; ' method='post' class='panel'>
				<textarea id='sendmessage' name='sendmessage' class='textbox'></textarea>
				$sendbutton<br clear='all' />

				<hr />
				<h2>previously...</h2>
				<input type='hidden' name='action' value='send'/>
				<input type='hidden' id='recipient' name='recipient' value='$correspondent'/>
				<input type='hidden' id='sender'    name='sender' value='$_SERVER[USER]'/>
				</form>
				<div id='send_div'>
				$content
				</div>
				<br clear='all' />
				<!--<script type='text/javascript'>	setInterval(\"send_refresh();\",9757);</script>\n-->
				<a onclick='send_display_more(\"$correspondent\");'>more...</a>";

				return $content;
			}

			if (OUTPUT_MODE=='MOBILE')
			{
				$content="
				<style type='text/css'>.send_from { background-color: $GLOBALS[navbgcolor]; color: $GLOBALS[navtextcolor]; }</style>
				$firstmessage<br clear='all' />
				<form action='$_SERVER[WEB_ROOT]/scripts/send.php' style='margin: 0px;' method='post' class='panel'>
				<textarea id='textbox' name='sendmessage'></textarea>

				$sendbutton<br clear='all' />
				<input type='hidden' name='action' value='send'/>
				<input type='hidden' id='recipient' name='recipient' value='$correspondent'/>
				<input type='hidden' id='sender'    name='sender' value='$_SERVER[USER]'/>
				</form>
				<div id='send_div' style='width: 640px;'>
				<hr />
				<h2>previously...</h2>
				$content
				</div>
				<br clear='all' />
				<a onclick='send_display_more(\"$correspondent\");'>more...</a>";
			}
		}
		else output("send from $correspondent",$content); // starting at $offset
	}

return $content;
}


if(isset($_POST['action']))
{
	if ($_POST['action']=='send')
	{
		send_add($_SERVER['USER'],$_POST['recipient'],$_POST['sendmessage']);
		if ($_POST['ajax']) echo send_display($_POST['recipient'],FALSE);
		else redirect("/send/$_POST[recipient]");
	}

	if ($_POST['action']=='refresh')
	{
		if ($_POST['ajax'])
		{
			$_SERVER['OUTPUT_MODE']='AJAX';
			user_update_last_action();
			output("Send Refresh",send_display($_POST['recipient'],FALSE));
		}
		else redirect("/send/$_POST[recipient]");
	}
}
