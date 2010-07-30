<?php

/*
SEND.php

implements send - lightweight messaging between plan users
*/

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


	if ($recipient=='invite' || strstr($recipient,'invite@'))
	{
		$invite_success=send_invite_user($sender,$message);
	}



	return $success;
}


// SEND_ADD_XMLRPC()
//
// manages sends to other nodes
//------------------------------------------------------------------------------
function send_add_xmlrpc($sender,$recipient,$message)
{
	list($recipient_username,$host)=explode("@",$recipient);

	$debug=FALSE;
//	if(IS_JOSH) $debug=TRUE;
	$success = planworld_xmlrpc_query(
		$host,
		'planworld.send.sendMessage',
		array("$sender@planwatch.org",$recipient_username,$message)
		);

//	if ($success && IS_JOSH && $sender=='invite') echo "sender invite success $message"; 

return $success;
}

// SEND_ADD_LOCAL()
//
// manages sends that stay on planwatch.org
//------------------------------------------------------------------------------
function send_add_local($sender,$recipient,$message)
{
	list($recipient,$junk)=explode("@",$recipient);
	$send_dir="$_SERVER[PWUSERS_DIR]/$recipient/sends";

	if (!is_dir($send_dir))
	{
		$oldumask=umask(0);
		mkdir($send_dir,0755);
		umask($oldumask);
	}

	$sender=str_replace("@planwatch.org",'',$sender);
	$send_fn="$send_dir/".files_encode_safe_name($sender."..".time()."..messagefrom");
	file_put_contents($send_fn,$message);
	touch("$send_dir/".files_encode_safe_name("$sender..new"));

	if (file_exists($send_fn)) $success=TRUE;
	else $success=FALSE;
	
	return $success;
}


// SEND_DISPLAY()
//
// outputs a send conversation
//------------------------------------------------------------------------------
function send_display($correspondent,$form=TRUE,$offset=0)
{
	$correspondent=str_replace("@planwatch.org",'',$correspondent); 
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
				$style=' class="send_from" ';
			}
			else
			{
				$from=$_SERVER['USER'];
				$style=' class="send_to" ';
			}
		
			if(IS_JOSH) $sendarray[$time]="<div $style>".smart_nl2br(removeEvilTags(file_get_contents($send_dir."/$send_fn")))."<div style='text-align: right; font-size: 70%; font-weight: normal;'>&mdash; $from <span style='font-size: 70%; font-weight: normal;'>(".formattime($time).")</span></div> </div>\n";
			else $sendarray[$time]="<div $style> $from (".formattime($time)."): ".smart_nl2br(removeEvilTags(file_get_contents($send_dir."/$send_fn")))."</div>\n";
		}
		
		krsort($sendarray);
		if(IS_JOSH) { $firstmessage=reset($sendarray); unset($sendarray[key($sendarray)]); }
		if (OUTPUT_MODE=='MOBILE') { $sendarray=array_slice($sendarray,$offset,20); }
		else { $sendarray=array_slice($sendarray,$offset,100); }
		$latest_time=array_shift(array_keys($sendarray));
//		if ($latest_time < time() - 600 && $_SERVER['OUTPUT_MODE']=='AJAX') $content="IGNORE.NULL";
		//else
		$content=implode("\n",$sendarray);

		if (file_exists("$send_dir/".files_encode_safe_name("$correspondent..new")))
			unlink("$send_dir/".files_encode_safe_name("$correspondent..new"));
		if (file_exists("$send_dir/".files_encode_safe_name("$correspondent@planwatch.org..new")))
			unlink("$send_dir/".files_encode_safe_name("$correspondent@planwatch.org..new"));
	}
	$content=hyperlink($content);
	if ($form)
	{
		if (browser_is_modern() && $_SERVER['OUTPUT_MODE']!='IPHONE' && $_SERVER['OUTPUT_MODE']!='MOBILE' )
			 $sendbutton="<input type='button' onclick='sendMessage();' value='Send' style='font-size: 20px; color: white; background: $GLOBALS[linkcolor]; font-family: $GLOBALS[pfont],$GLOBALS[pfonts]; font-weight: bold; ' />";
		else $sendbutton="<input type=\"submit\" value='Send' class='whiteButton' href=\"#\" style='' />";

		if (!$offset)
		{
			if(IS_JOSH)
			{
				$content="
				$firstmessage<br clear='all' />
				<form action='$_SERVER[WEB_ROOT]/scripts/send.php' style='margin: 0px; display: block; ' method='post' class='panel'>
				<textarea id='textbox' name='sendmessage' style='width: 90%; font-size: 16px; height: 40px;' onfocus='this.style.height=\"200px;\"' onblur='this.style.height=\"40px;\"'></textarea>
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
				<script type='text/javascript'>	setInterval(\"send_refresh();\",9757);</script>\n
				";
				
				return $content;
			}

			if ($_SERVER['OUTPUT_MODE']=='HTML')
			{
				$content="
				<form action='$_SERVER[WEB_ROOT]/scripts/send.php' style='margin: 0px;' method='post' class='panel'>
				<h1>Send with <a href='/read/$correspondent'>$correspondent</a></h1>
				<div id='send_div' style='overflow: auto; height: 200px; margin-bottom: 30px; width: 80%;'>
				$content
				</div>
<!--				<script src='/resources/javascript/edit.js'></script>-->
				<textarea id='textbox' name='sendmessage'></textarea>
				$sendbutton
				<input type='hidden' name='action' value='send'/>
				<input type='hidden' id='recipient' name='recipient' value='$correspondent'/>
				<input type='hidden' id='sender'    name='sender' value='$_SERVER[USER]'/>
				</form>
<!--				<a href='/send/$correspondent/".($offset+100)."'>more...</a>-->
				<script type='text/javascript'>	setInterval(\"send_refresh();\",9757);</script>\n";
			}
			if ($_SERVER['OUTPUT_MODE']=='IPHONE' || $_SERVER['OUTPUT_MODE']=='MOBILE')
			{
				$content="
				<style type='text/css'>.send_from { background-color: #ffc; }</style>
				<form action='$_SERVER[WEB_ROOT]/scripts/send.php' style='margin: 0px;' method='post' class='panel'>
				<h3>Send with <a href='/read/$correspondent'>$correspondent</a></h3>
<!--				<script src='/resources/javascript/edit.js'></script>-->
				<textarea id='textbox' name='sendmessage' style='width: 300px; font-size: 16px;'></textarea>
<!--				<textarea id='sendmessage' style='width: 300px; height: 80px;' name='sendmessage'></textarea>-->
				$sendbutton<br clear='all' />
				<input type='hidden' name='action' value='send'/>
				<input type='hidden' id='recipient' name='recipient' value='$correspondent'/>
				<input type='hidden' id='sender'    name='sender' value='$_SERVER[USER]'/>
				</form>
				<div id='send_div'>
				$content
				</div>
				<a target='_replace' href='/send/$correspondent/".($offset+20)."'>more...</a>
				<script type='text/javascript'>	setInterval(\"send_refresh();\",9757);</script>\n";
			}
		}
		else output("send from $correspondent starting at $offset",$content);
	}

return $content;
}


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

?>
