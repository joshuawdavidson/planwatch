<?php

/*
PLAN_READ.php
plan
gets a plan based on what sort of plan it is and applies some text processing

important:
plan_read() -- the parent function to get a plan. calls subsids
	plan_read_finger
	plan_read_web
	plan_read_local
	plan_read_rss



*/

function plan_process_oembed($urls)
{
	include_once('json.php');
	foreach($urls as $url)
	{
		$url=strip_tags(trim($url));
		$data=file_get_contents("http://oohembed.com/oohembed/?url=$url");
		$json=new JSON;
		$embed=$json->unserialize($data);
		return $embed->html;
	}
//	else return $url;
}

function plan_match_embed_urls($plan)
{
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.5min\.com/Video/.*)[\s\W]|i",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.blip\.tv/.*)[\s\W]|i",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.youtube\.com/watch.*)[\s\W]|i",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.wikipedia\.org/wiki/.*)[^<'\"]*[\s\W]+|i",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.xkcd\.com/.*/)[\s\W]|iU",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://www\.vimeo\.com/groups/.*/videos/.*)[\s\W]|iU",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://www\.vimeo\.com/.*)[\s\W]|iU",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://www\.hulu\.com/watch/.*)[\s\W]|iU",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.flickr\.com/photos/.*)[\s\W]|iU",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.dailymotion\.com/.*)[\s\W]|iU",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://video\.google\.com/videoplay?.*)[\s\W]|iU",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.thedailyshow\.com/video/.*)[\s\W]|iU",'plan_process_oembed',$plan);
	$plan=preg_replace_callback("|[^'\"=/;>](http://.*\.scribd\.com/.*)[\s\W]|iU",'plan_process_oembed',$plan);

	return $plan;
}

function plan_strip_css($plan)
{
	$plan=preg_replace("/style=['\"][^'\"]*['\"]/","",$plan);
	$plan=preg_replace("|<style.*</style>|","",$plan);
	return $plan;
}

function plan_confine_css($plan)
{
	$stylebegin=strpos($plan,"<style");
	$styleend=strpos($plan,"</style>");
	$styleblock=substr($plan,$stylebegin,$styleend-$stylebegin+8);
	$clean_styleblock=str_replace("body","#content",$styleblock);
	$clean_styleblock=str_replace(".content","#content",$clean_styleblock);
	$clean_styleblock=str_replace(array(".navbar",'.planwatch','.header'),".confined",$clean_styleblock);
	$clean_styleblock=preg_replace("|\s*(.*)\s+\{(.*)\}|mU","#content \\1 { \\2 } \n",$clean_styleblock);
	$clean_styleblock=str_replace("#content #content","#content",$clean_styleblock);
	
	return str_replace($styleblock,$clean_styleblock,$plan);
}

function source_is_crossnode($source)
{
	if (!strstr($source,'@')) return FALSE;
	if (strstr($source,'@p') && !strstr($source,'@planwatch')) return "@pwn";
	if (strstr($source,'@n') || strstr($source,'@a')) return "@note";
}

function plan_read_sources($plan)
{
	$_SERVER['PLANOWNER_ROOT']="$_SERVER[FILE_ROOT]/../pwusers/$plan";

	// get the list of sources
	$sources=file("$_SERVER[PLANOWNER_ROOT]/sources.dat");


	// read in the local plan entries
	$threshhold=$_SERVER['PLANOWNER_INFO_ARRAY']['defaultdays'].'d';
	$threshhold = time_calculate_threshhold($threshhold);
	$begindate=time()-$threshhold; $enddate=time(); $default_view=TRUE;

	//sets dir for reading
	$plan_dir="$_SERVER[PLANOWNER_ROOT]/plan/";

	$nonjournaling_plan_filename=$plan_dir."plan.txt";
	if (file_exists($nonjournaling_plan_filename))
		$all_entries[filemtime($nonjournaling_plan_filename)]=$nonjournaling_plan_filename;

	// gets the entries indicated by $limiter
	// if $limiter is unset, fills the array with all available entries
	$plan_array=files_list($plan_dir,"plan.*.txt*");

	foreach($plan_array as $entry)
	{
		$timecode=str_replace(array("plan.",".txt",".p"),'',$entry);
		$all_entries["$timecode"]=$plan_dir.basename($entry);
	}

	foreach($sources as $source)
	{
		$source=trim($source);
		if ($source_node=source_is_crossnode($source))
		{
			$source_username=substr($source,0,strpos("@"));
			$timecode=plan_get_last_update($source);
			$all_entries["$timecode"]=plan_read_source_xmlrpc($source_username,$source_node);
		}

		if (strstr($source,'://'))
		{
			$source_entries=plan_read_sources_feed($source);
			foreach($source_entries as $timecode=>$source_entry)
			{
				$all_entries[$timecode]=$source_entry;
			}
		}
	}

	krsort($all_entries);

	reset($all_entries);
	$current_timecode=key($all_entries);
	while($current_timecode > $begindate)
	{
		if ($current_timecode<=$enddate)
		{
			$entry=current($all_entries);
			if (strstr($entry,$plan_dir))
			{
				$entry=stripslashes(file_get_contents($entry));
				if (strstr($entry,'<!--markdown-->')) { include_once('markdown.php'); $entry=Markdown($entry); }
				elseif (!strstr($entry,'nolinebreaks')) $entry=smart_nl2br($entry);
				$background_color='white';
			}
			else $background_color='#ddd';

			$entry="<div style='margin: 10px; padding-top: 10px; background-color: $background_color; border-top: 1px dotted #ccc;'>".formattime($current_timecode)."<br />\n$entry</div>\n";
			$content.=$entry;
		}
		next($all_entries);
		$current_timecode=key($all_entries);
	}

//exit;
	output("Multisource $plan",$content);
}

function plan_read_sources_feed($url)
{
	$url=str_replace("feed://","http://",$url);

	if (!strstr($url,'://'))	$url=plan_get_real_location($url);

	$feed = new SimplePie();
	$feed->feed_url($url);
	$feed->output_encoding('utf-8');
	$feed->init();
	$feed->handle_content_type();


	$title=$feed->get_feed_title();
	$items=$feed->get_items();

	foreach($items as $item)
	{
		$timecode=$item->get_date();
		$item_title=$item->get_title();
		$item_description=$item->get_description();
		if ($item_title==$item_description) $item_title='';

		if ($item_description!=$last_description)
		{
			if ($last_timecode-$timecode > 300 || !$last_timecode)
			{
				$entries["$timecode"]="<a href='/read/$url'>$title</a>: <h4>".$item_title."</h4>".$item_description;
				$last_timecode=$timecode;
			}

			else $entries["$last_timecode"].="<br />\n".$item_description;
		}

		$last_description=$item_description;
	}

	return $entries;
}

function plan_read_sources_xmlrpc($username,$server)
{
	if($nodeinfo=planworld_node_getinfo($remotenode))
	{
		if ($_SERVER['USERINFO_ARRAY']['snitchlevel']) $snitch=1;
		if ($_SERVER['USER']=='guest') { output('unauthorized',"you can't read offsite plans if you're not logged in."); exit; }


		$f=new xmlrpcmsg('planworld.plan.getContent');
		$f->addParam(new xmlrpcval($remoteuser, "string"));
		$f->addParam(new xmlrpcval($_SERVER['USER']."@planwatch.org", "string"));
		$f->addParam(new xmlrpcval($snitch, "boolean"));

		$c=new xmlrpc_client($nodeinfo["directory"], $nodeinfo["server"], $nodeinfo["port"]);
		$c->setDebug(0);
		$r=$c->send($f);

		if (!$r) { $plan="<div class='alert'>Could not retrieve $remoteuser's plan from $nodeinfo[server].</div>"; }
		else
		{
			$v=$r->value();
			if (!$r->faultCode()) {
				if ($v->kindOf()=='scalar') $plan=WrapWords($v->scalarval(),80);
			} else {
			$plan="<h1>Sorry! :(</h1>
			We are temporarily unable to get plans from $server. Please try again later.
			If this problem persists, check the <a href='/read/system'>system plan</a>
			or <a href='/read/jwdavidson'>jwdavidson</a> for updates.<hr />
			Here's the error message in case you need to <a href='/features'>file a bug</a>:";

			$plan.="Code: " . $r->faultCode() . '"' . $r->faultString() . '"';
			}
		}
	}
	else $plan=plan_read_finger($remoteuser.$remotenode);

	if (strstr($plan,'plan is not available')) $plan="$remoteuser@$remotenode does not allow plan reads outside $nodeinfo[server].<br />You can still <a href='/send/$remoteuser$remotenode'>send a message</a> if you want to establish contact.";

	return $plan;
}

// PLAN_READ_INVITE
//
// allows readers to get invitations
// for new accounts on pwo
function plan_read_invite($requester)
{
	if($_SERVER['USER'])
	{
		$invite_request=$requester.time();
		$requester=str_replace(array("@planwatch.org","@beta","@pwo"),'',$requester);
		$invite_code=md5($invite_request);
		$filename="$_SERVER[FILE_ROOT]/temp/invites/$invite_code.$requester.invite";
		$used_filename="$_SERVER[FILE_ROOT]/temp/invites/$invite_code.$requester.used.invite";

		$file=fopen($filename,'w');
		fwrite($file,serialize(array("email"=>"your@email.org","name"=>"Enter Your Name Here","inviter"=>"$requester")));
		fclose($file);

		if (!$invite_response)
			$invite_response="<h1>Invite a friend to planwatch.org</h1>Copy and paste this invitation address into an email, IM, or communication medium of your choice:<br /><br /> \n\n<strong><a href='http://planwatch.org/user/accept_invite/$requester/$invite_code'>http://planwatch.org/user/accept_invite/$requester/$invite_code </a></strong><br /><br />\n\nProblems? Ask <a href='mailto:help@planwatch.org'>help@planwatch.org</a>

			<div style='border: thin solid black; width: 300px; padding: 10px; margin-top: 20px; margin-bottom: 20px;' ><h3>Mail this invitation</h3>
			<form id='inviteform' action='http://planwatch.org/scripts/form_shim.php' method='post'>
			<input type='hidden' name='invite_url' value='http://planwatch.org/user/accept_invite/$requester/$invite_code' />
			<input type='hidden' name='requester' value='$requester' />
			<input type='text' name='recipient' value='email@address.com' />
			<br />Add a personal message (optional):<br /><textarea name='personal' style='width: 270px; height: 200px;'></textarea>
			<input type='submit' name='send email' />
			</form>
			</div>
			";
	}
	else $invite_response="You must be logged in to a planworld note to get an invite.";
	return $invite_response;
}

// PLAN_READ_BACKUP
//
// provides a backup interface for crossnode users
function plan_read_backup($requester)
{
	if($_SERVER['USER'])
	{
		$plan="
		<iframe style='background: transparent; width: 100%; height: 500px; border: 0px;' src='http://planwatch.org/xn_backup/setup/$_SERVER[USER]'></iframe>
		";
	}
	else $plan="Sorry, you must be logged in and reading from a planworld note to use pwo backup services.";
	return $plan;
}


// PLAN_ADD_ALIAS_LINKS()
//
// turns watched list aliases embedded in plans into links
//------------------------------------------------------------------------------
function plan_add_alias_links($plan,$planwriter,$remote=FALSE)
{
	$alias_array=user_list_aliases($planwriter);

	// don't bother if there aren't aliases to link
	if (is_array($alias_array))
	foreach($alias_array as $alias=>$item)
	{
		if (!$_SERVER['REMOTENODE'])
		{
			$planprefix='/read/';
			$remoteaddition='';
		}
		else
		{
			$planprefix='?id=';
			if (!strstr($item,'@')) $remoteaddition='@planwatch.org';
			else $remoteaddition='';
		}

		$item=str_replace($_SERVER['REMOTENODE'],'',$item);

		if ($alias && strpos($plan,$alias)!==FALSE)
		{
			if ((($_SERVER['OUTPUT_MODE']=='HTML' || $_SERVER['OUTPUT_MODE']=='IPHONE') && $_SERVER['USER'] && $_SERVER['USER']!='guest')
				|| ($planprefix=='/read/' && !plan_is_private($item) && !plan_is_registered_only($item)))
			{
				$plan=str_replace("!$alias!","<a target='_self' href='$planprefix$item$remoteaddition'>$alias</a>",$plan);
				$plan=preg_replace("/!$alias:([^!]+)!/","<a target='_self' href='$planprefix$item$remoteaddition'>\\1</a>",$plan);
			}
			else
			{
				$plan=str_replace("!$alias!","$alias",$plan);
				$plan=preg_replace("/!$alias:([^!]+)!/","\\1",$plan);
			}
		}
	}

	profile('alias');

	return $plan;
}




// PLAN_PROCESS_DIRECTIVES()
//
// turns %keyword% references in the plan into the values those keywords
// represent.
//------------------------------------------------------------------------------
function plan_process_directives($plan,$remotepatch=FALSE)
{
	profile('plan_process_directives');

	// these directives will happen on the other nodes
	// we only want to patch the ones they won't deal with
	// (presently %bio% and %toc%)
	if(!$remotepatch)
	{
		if (strpos($plan,'%version%')!==FALSE)
			$plan=str_replace("%version%","planwatch.org v$_SERVER[PLANWATCH_VERSION]",$plan);
	
		if (strpos($plan,'%time%')!==FALSE)
			$plan=str_replace("%time%",date("h:iA",time()),$plan);
	
		if (strpos($plan,'%date%')!==FALSE)
			$plan=str_replace("%date%",date("F jS, Y",time()),$plan);
	}

	if (strpos($plan,'%bio%')!==FALSE)
	{
		include_once('bios.php');
		$plan=str_replace("%bio%",bio_read($_SERVER['PLANOWNER']),$plan);
	}

	if (strpos($plan,'%toc%')!==FALSE)
	{
/*		if(preg_match_all("|id='plan_entry_([1-9]*)'.*<!--title (.*) -->|miU",str_replace("\n","",$plan),$matches,PREG_SET_ORDER))
		{
			foreach($matches as $matchline)
			{
				$titles.="<li><a href='#plan_entry_$matchline[1]'>$matchline[2]</a></li>";
			}
		}
	*/	
		if(preg_match_all("|<header id='(.*)' .*>(.*)<|iU",$plan,$matches,PREG_SET_ORDER))
		{
			foreach($matches as $i=>$matchline)
			{
				if($i==0) $first=" class='first' ";
				else $first='';
				$titles.="<li $first><a href='#$matchline[1]'>$matchline[2]</a></li>";
			}
		}

		$plan=str_replace("%toc%","<ul id='plan_$_SERVER[PLANOWNER]_toc'>$titles</ul>",$plan);
	}

	profile('plan_process_directives');

return $plan;
}





// PLAN_ADD_USER_LINKS()
//
// turns plan references in the plan into links to the referenced plans
//------------------------------------------------------------------------------
function plan_add_user_links($plan)
{
	profile('plan_add_user_links','begin');

	// moved from process_directives
	if (strpos($plan,'%user%')!==FALSE)
		if ($_SERVER['USER']) $plan=str_replace("%user%",$_SERVER['USER']."@planwatch.org",$plan);
	    else $plan=str_replace("%user%","Anonymous Coward",$plan);

	$plan=str_replace('<!--','<--',$plan);

	$plan=preg_replace("/(\W*)!(feed:\S+)[:]([^!]+)!(\W+)/","\\1<a target='_self' href='http://planwatch.org/read/\\2' title='\\2'>\\3</a>\\4",$plan);
	$plan=preg_replace("/(\W*)!(http:\S+)[:]([^!]+)!(\W+)/","\\1<a target='_self' href='\\2' title='\\2'>\\3</a>\\4",$plan);
	$plan=preg_replace("/(\W*)!link:(\S+)[:]([^!]+)!(\W+)/","\\1<a target='_self' href='\\2' title='\\2'>\\3</a>\\4",$plan);
	$plan=preg_replace("/(\W*)!email:(\S+)[:]([^!]+)!(\W+)/","\\1<a href='mailto:\\2' title='email \\2'>\\3</a>\\4",$plan);
	$plan=preg_replace("|(\W*)!([^<]+tp\S+)[:]([^!]+)!(\W+)|","\\1<a target='_self' href='\\2' title='\\2'>\\3</a>\\4",$plan);

	$plan=preg_replace_callback("/!([\w@%.]+):([^!]+)!/",'plan_filter_linked_users',$plan);
	$plan=preg_replace_callback("/!([\w@%.]+)!/",'plan_filter_linked_users',$plan);

//	$plan=hyperlink(" $plan");

	$plan=str_replace('<--','<!--',$plan);

	profile('plan_add_user_links','end');

return $plan."\n";
}



// PLAN_FILTER_LINKED_USERS()
//
// protects non-public users from being externally linked
//------------------------------------------------------------------------------
function plan_filter_linked_users($matches)
{
//	if($_SERVER['USER']=='jwdavidson')	print_r($matches);

	list($planwriter,$remoteaddition)=explode("@",$_SERVER['PLANOWNER']);
	if ($remoteaddition) $remoteaddition="@$remoteaddition";

	if (!$_SERVER['REMOTENODE'])  // if the reader is local
	{
		$planprefix='/read/';
		if (strstr($matches[1],'@')) $remoteaddition=''; // if it's a link to a third node, don't add the node specifier from the plan owner
		$matches[1]=str_replace('@planwatch.org','',$matches[1]);
	}
	else
	// if the reader is offnode, we ideally shouldn't be running this at all.
	// requires further study.
	//TODO: figure out linking for offnode readers.
	{
		$planprefix='?id=';
		if (strstr($matches[1],'@')) $remoteaddition='';
		else $remoteaddition='@planwatch.org';
	}

	if ((!plan_is_registered_only($matches[1]) && !plan_is_private($matches[1]))
		|| (($_SERVER['OUTPUT_MODE']=='HTML' || $_SERVER['OUTPUT_MODE']=='IPHONE') && $_SERVER['USER'] && $_SERVER['USER']!='guest'))
	{
		if ($matches[2]) return "<a target='_self' href='$_SERVER[WEB_ROOT]$planprefix$matches[1]$remoteaddition' title='$matches[1]'>$matches[2]</a>";
		else return "<a target='_self' href='$_SERVER[WEB_ROOT]$planprefix$matches[1]$remoteaddition' title='$matches[1]'>$matches[1]</a>";
	}
	else
	{
		if ($matches[2]) return "$matches[2]";
		else return "$matches[1]";
	}
}



// PLAN_PROCESS_SMILEYS()
//
// converts text smileys to images, for mckelley
//------------------------------------------------------------------------------
function plan_process_smileys($plan,$hate=FALSE)
{
	profile('smileys');

	if ($_SERVER['USERINFO_ARRAY']['hatessmileys']==FALSE)
	{
		include_once('smiley_functions.php');
		$smileyarray=smiley_listall();
		foreach($smileyarray as $graphic=>$keylist)
		{
			$graphic_size=getimagesize("$_SERVER[FILE_ROOT]/resources/smileys/$graphic");
			foreach($keylist as $i=>$key)
			{
				if (strpos($key,':')===FALSE && strpos($key,'>')===FALSE && strpos($key,'8')===FALSE && strpos($key,')')===FALSE)
				   $key=":$key:";
				else $key=" $key";
				if (!$hate) $plan=str_replace($key,"<img src='$_SERVER[WEB_ROOT]/resources/smileys/$graphic' $graphic_size[3] />",$plan);
				elseif (substr_count($key,':')>1) $plan=str_replace($key,"",$plan);
				if ($key==" $keylist[$i]" && !$hate) $plan=str_replace("$keylist[$i] ","<img src='$_SERVER[WEB_ROOT]/resources/smileys/$graphic' $graphic_size[3] />",$plan);
				if ($key==" $keylist[$i]" && $hate && substr_count($key,':')>1) $plan=str_replace("$keylist[$i] ","",$plan);
			}
		}
	}
	profile('smileys');

	return $plan;
}





// PLAN_READ()
//
// the parent plan_read function calls subsid functions to do the work
//------------------------------------------------------------------------------
function plan_read($planowner,$threshhold=FALSE,$begindate=FALSE,$unformatted=FALSE,$remotesnitch=FALSE)
{
	if ($planowner=='invite' || $planowner=='invite@planwatch.org')
		return plan_read_invite($reader);

	if ($planowner=='backup' || $planowner=='backup@planwatch.org')
		return plan_read_backup($reader);

	if ($planowner=='backup_archives' || $planowner=='backup_archives@planwatch.org')
		return plan_read_backup($reader,"archives");

	$reader=$_SERVER['USER'];
	if ($reader=='cacheuser') $utility=1;

	$plan_read_rand=rand();

	if (!isset($_SERVER['PLANOWNER_INFO_ARRAY']) || $_SERVER['PLANOWNER']!=$planowner)
	{
		plan_get_owner_info($planowner);
	}

	profile("plan_read_$plan_read_rand");
	profile("plan_read_head_$plan_read_rand");
	if (plan_is_local($planowner))
	{
		$islocal=TRUE;
		$planowner=plan_repair_local_name($planowner);
	}
	else if (user_is_local($planowner))
	{
		$localusernotplan=TRUE;
		$localusername=$planowner;
	}


	include_once('snitch.php');
	include_once('spiel.php');

	if ($_SERVER['REMOTENODE'])
	{
	    if (isset($_SERVER['PLANOWNER_INFO_ARRAY']['fingerpref'])
	    	&& $_SERVER['PLANOWNER_INFO_ARRAY']['fingerpref']==0)
		{
			snitch_write($reader,$planowner,' failed');
			return "$planowner's plan is not available for reading outside planwatch.org.";
		}
	}

	// tests the user against the planowner's privacy settings,
	// blocked list, and allowed list
	$plan_test_privacy=plan_test_privacy($reader,$planowner,$remotesnitch);

	$authorized=user_is_authorized($planowner,$reader);
	if (!$_SERVER['PLANOWNER_REAL_LOCATION'])
	{
		plan_get_owner_info($planowner);
	}

	if (user_is_local($planowner) && !plan_is_local($planowner))
	{
		$localplan=plan_read_local($planowner,$threshhold,$begindate,$unformatted);
	}

	if ($_SERVER['PLANOWNER_REAL_LOCATION'])
		$planowner=$_SERVER['PLANOWNER_REAL_LOCATION'];
	profile("plan_read_head_$plan_read_rand");

	// on with the show
	if ($plan_test_privacy)
	{
	    if ($islocal)
	    {
	        $plan=plan_read_local($planowner,$threshhold,$begindate,$unformatted);
	    }
//		elseif($localusernotplan) $localplan=plan_read_local($localusername,$threshhold,$begindate,$unformatted);

		if (strpos($planowner,'@')!==FALSE && !strpos($planowner,'://') && !$plan)
		{
			// if it comes from another planworld node (we used to fingertest in here too)
			list($xmlrpc_username,$xmlrpc_node)=explode('@',$planowner);

			$plan=plan_read_xmlrpc($xmlrpc_username,$xmlrpc_node);
		}

		if (strpos($planowner,'://') && !$plan)
		{
			// if it's a URL call plan_read_web()
			// plan_read_web passes it on to plan_read_rss_simplepie if necessary.
			$url=$planowner;
			$plan=plan_read_web($url);
		}

		// Valid snitches only get set here
		if ((!$threshhold || $threshhold=='2d' || $threshhold==$_SERVER['PLANOWNER_INFO_ARRAY']['defaultdays']."d") && !$begindate)
			snitch_write($reader,$planowner);
		else
			snitch_write($reader,$planowner," archives ( $threshhold {$_SERVER['PLANOWNER_INFO_ARRAY']['defaultdays']}$begindate )");

		if (file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[USER]/userinfo.dat")) user_update_lastread($planowner);
		$plan=trim($localplan).$plan;
	}
	else
	{
		// if the privacy test didn't check out, display the blocked message
		$blocked_fn="$_SERVER[PWUSERS_DIR]/$planowner/blockedmessage.txt";
		if (file_exists($blocked_fn))
		{
			$plan=stripslashes(stripslashes(file_get_contents($blocked_fn)));
			$plan=str_replace('MYUSER',$planowner,$plan);
			$plan=str_replace('READER',$reader,$plan);
		}
		// this is the generic blocked message
		else
		{
			if ($_SERVER['whitelist_passed']===FALSE)
			{
				$plan="<h1>Security Error</h1>Unverified readers from planworld.net are not allowed to read protected planwatch.org plans without
				specific permission from the author. Please send the owner of this plan, or email <a href='mailto:help@planwatch.org'>help@planwatch.org</a>
				to be added to the list of verified readers. ";
			}
			else $plan="<h1>Error: No Such User</h1>No user found by that name. Please try again, $reader.";
		}
		snitch_write($reader,$planowner,' failed');
		if (file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[USER]/userinfo.dat")) user_update_lastread($planowner);
	}



	profile("plan_format_$plan_read_rand");
	if( !$utility && !$unformatted)
	{
		profile("plan_read_tail_$plan_read_rand");

		$plan=plan_add_alias_links($plan,$planowner);
		if (!$_SERVER['REMOTENODE'])
		{
			$plan=spiel_format($plan,$planowner);
			$plan=plan_add_user_links($plan);
		}
		else
		{
			preg_replace("|!sp[ie][ie]l:(.*):(.*)!|","<a href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/spiel/view/\\1'>\\2</a>",$plan);
			preg_replace("|!sp[ei][ie]l:(.*)!|","<a href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/spiel/view/\\1'>\\1</a>",$plan);
			$plan=plan_process_directives($plan,TRUE); // turn on remotepatch to avoid processing directives other nodes will handle
			if(strstr($plan,"aside")) $plan="<style>aside.sidebar { float: right; width: 200px; margin-left: 20px; }</style>$plan";
		}

		if(strstr($plan,'http://')) $plan=plan_match_embed_urls($plan);

		$plan=plan_embed_player($plan);
		$plan=str_replace(array("id=\"content\"","id='content'"),"id='content2'",$plan);
		$plan=str_replace("#content","#content2",$plan);
		$plan=str_replace("font-color:","color:",$plan);
		$plan=str_replace("position: absolute","position: relative;",$plan);
		$plan=hyperlink($plan);

		if(strpos($plan,"/read/$_SERVER[USER]"))
		{
			$plan=str_replace("<a target='_self' href='/read/$_SERVER[USER]'","<a target='_self' id='snoop' href='/read/$_SERVER[USER]'",$plan);
		}

		$plan=str_replace("@note","@amherst.edu",$plan);
		$plan=str_replace("@pwn","@planworld.net",$plan);

		$remoteuser=str_replace($_SERVER['REMOTENODE'],"",$_SERVER['USER']);
		if(strpos($plan,"?id=$remoteuser"))
		{
			$plan=str_replace("<a href='?id=$remoteuser","<a id='snoop' href='?id=$remoteuser",$plan);
		}

		if(strpos($plan,"!$_SERVER[USER]"))
		{
			$plan=preg_replace("/!$_SERVER[USER]:([^!]+)!/","<a id='snoop' href='?id=$remoteuser'>\\1</a>",$plan);
			$plan=preg_replace("/!$_SERVER[USER]!/","<a id='snoop' href='?id=$remoteuser'>$_SERVER[USER]</a>",$plan);
		}

		if(strpos($plan,"id='snoop'"))
		{
			if($_SERVER['OUTPUT_MODE']=='HTML')
				$plan="<a href='#snoop'>Find My Snoop</a><br />\n$plan";
			if($_SERVER['OUTPUT_MODE']=='IPHONE' || $_SERVER['OUTPUT_MODE']=='MOBILE')
				$plan="<a href='javascript:alert(getRealTop(\"snoop\"));'>Find My Snoop</a><br />\n$plan";
		}


		if($_SERVER['USERINFO_ARRAY']['strip_css']==1) $plan=plan_strip_css($plan);
		if($_SERVER['USERINFO_ARRAY']['strip_css']==2) $plan=plan_confine_css($plan);

	}

	profile('encoding');
	if (!$url)
	{
		$encoding=mb_detect_encoding($plan,'UTF-8, ISO-8859-1');
		if ($encoding!=='UTF-8') $plan=mb_convert_encoding($plan,'UTF-8',$encoding);
	}

	profile('encoding');
	profile("plan_read_tail_$plan_read_rand");
	profile("plan_read_$plan_read_rand");

	profile("plan_format_$plan_read_rand");

	return $plan;
}


function plan_embed_player($plan_text)
{
	$object_block="
<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='25' height='20'
    codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab'>
    <param name='movie' value='http://planwatch.org/resources/singlemp3player.swf?file=sound.mp3&backColor=990000&frontColor=ddddff&repeatPlay=false&songVolume=30' />

    <param name='wmode' value='transparent' />
    <embed wmode='transparent' width='25' height='20' src='http://planwatch.org/resources/singlemp3player.swf?file=sound.mp3&backColor=990000&frontColor=ddddff&repeatPlay=false&songVolume=30'

    type='application/x-shockwave-flash' pluginspage='http://www.macromedia.com/go/getflashplayer' />
</object>
";
	preg_match_all("|<a[\s]*href=['\"]([^<]*)\.mp3['\"][^<]*</a>|",$plan_text,$matches);
	foreach($matches[1] as $i=>$song)
	{
		$new_object_block=str_replace("sound.","$song.",$object_block)." {$matches[0][$i]}";
		$plan_text=str_replace($matches[0][$i],$new_object_block,$plan_text);
	}
	return $plan_text;
}



// PLAN_READ_XMLRPC()
//
// uses xml-rpc to get plans from another planworld node
//------------------------------------------------------------------------------
function plan_read_xmlrpc($remoteuser='bob',$remotenode='@note')
{
	if($nodeinfo=planworld_node_getinfo($remotenode))
	{
		if ($_SERVER['USERINFO_ARRAY']['snitchlevel']) $snitch=1;
		if (!$_SERVER['USER'] || $_SERVER['USER']=='guest') { output('unauthorized',"you can't read offsite plans if you're not logged in."); exit; }


		$f=new xmlrpcmsg('planworld.plan.getContent');
		$f->addParam(new xmlrpcval($remoteuser, "string"));
		$f->addParam(new xmlrpcval($_SERVER['USER']."@planwatch.org", "string"));
		$f->addParam(new xmlrpcval($snitch, "boolean"));

		$c=new xmlrpc_client($nodeinfo["directory"], $nodeinfo["server"], $nodeinfo["port"]);
		$c->setDebug(0);
		$r=$c->send($f);

		if (!$r) { $plan="<div class='alert'>Could not retrieve $remoteuser's plan from $nodeinfo[server].</div>"; }
		else
		{
			$v=$r->value();
			if (!$r->faultCode()) {
				if ($v->kindOf()=='scalar') $plan=WrapWords($v->scalarval(),80);
			} else {
			$plan="Fault: ";
			$plan.="Code: " . $r->faultCode() .
				" Reason '" .$r->faultString()."'<br/>";
			}
		}
	}
	else $plan=plan_read_finger($remoteuser.$remotenode);

	if (strstr($plan,'plan is not available')) $plan="$remoteuser@$remotenode does not allow plan reads outside $nodeinfo[server].<br />You can still <a href='/send/$remoteuser$remotenode'>send a message</a> if you want to establish contact.";

	return $plan;
}




// PLAN_READ_FINGER()
//
// gets a plan via finger, caches it on server
// caches plan locally until updatetime is newer than filemodtime
//------------------------------------------------------------------------------
function plan_read_finger($url)
{

	if (!stristr($url,'amherst.edu'))
	{
		$url=str_replace('@vax','@amherst.edu',$url);
		$url=str_replace('@unix','@unix.amherst.edu',$url);
	}

	$url=str_replace('@amherst.edu','@alpha3.amherst.edu',$url);

	if (strstr($url,'@')==$url)
	{
		$uri=explode('/',getenv('REQUEST_URI'));
		$username=$uri[sizeof($uri)-1];
		$url=$username.$url;
	}

	$plan=nl2br(shell_exec("finger -l $url"));

	return $plan;
}




// PLAN_READ_WEB()
//
// gets a web plan
//------------------------------------------------------------------------------
function plan_read_web($url)
{
		$plan=plan_read_simplepie_rss($url);
/*
	if (stripos($url,'rss') || stripos($url,'rdf') || stripos($url,'xml') || stripos($url,'feed') || stripos($url,'atom') || stripos($url,'eed://') || stripos($url,'makedatamakesense'))
	{
		$plan=plan_read_simplepie_rss($url);
	}
	else
	{
		$plan=@file_get_contents($url);
		$feeds=plan_feed_search($url);
		
		if($feeds)
		{
			print_r($feeds); exit;
		}

		if (!$plan)$plan="<div class='alert'>Sorry, plan_read_web($url) failed. planwatch could not read the page $url.
		<br/><br/>
		See if you can read it by clicking <a href='$url'>here</a>, then
		email <a href='mailto:help@planwatch.org'>help@planwatch.org</a>
		with the details of this error.</div>";
	}
*/
	$content=$plan;
	preg_replace("/{src|SRC}=('|\")([^hf][^t])/","\\1=\\2$url/\\3",$content);
return $content;
}




// PLAN_SEARCH_LOCAL()
//
// basic archive search for local plans.
//------------------------------------------------------------------------------
function plan_search_local($writer,$reader,$keyword)
{
	$keyword=urldecode($keyword);
	$writer=urldecode($writer);

	if (user_is_authorized($writer,$reader)) { $private=TRUE; }

	exec("grep -ri -A 2 -B 2 '$keyword' $_SERVER[PWUSERS_DIR]/$writer/plan/",$resultlist);

	foreach($resultlist as $result)
	{
		if (!strpos($result,'hidden.') || $writer==$reader)
		{
			if (!strpos($result,'txt.p') || $private)
			{
				$result_parts=explode(':',$result,2);
				$result_text=stripslashes($result_parts[1]);
				$result_tc_a=explode('.',$result_parts[0]);
				$result_tc=$result_tc_a[1];

				if (trim($result_text))
				{
					$entries[$result_tc].=plan_add_user_links(plan_add_alias_links($result_text,$writer));
					$filenames[$result_tc].=$result_parts[0];
				}
			}
		}
	}

	if (!$entries)
	{
		$entries[time()]="Nothing found.";
	}

	krsort($entries);

	$divider_filename="$_SERVER[PLANOWNER_ROOT]/plan/plandivider.txt";

	if (file_exists($divider_filename))
	{
		$divider=stripslashes(file_get_contents($divider_filename));
	}

	foreach($entries as $entry_time=>$entry_text)
	{
		$entry_header  = plan_prepare_divider($writer,$reader,$divider,$entry_time,$entries[$entry_time],$entry_text,$entry_nolinebreaks);

		$plan_content .= plan_prepare_entry($entry_time,$entry_header,$entry_text);

	}
	$content="\n$new_entry_link\n<div id='plan_body'>\n$plan_content\n</div>";

return $content;
}





// PLAN_READ_LOCAL()
//
// gets a local plan
//------------------------------------------------------------------------------
function plan_read_local($planowner,$threshhold=FALSE,$begindate='',$source=FALSE)
{

	$prl_rand=rand();
	profile("plan_read_local_$prl_rand");
	profile('plan_read_local_head');

	$reader = $_SERVER['USER'];

	if (!$threshhold) $threshhold=$_SERVER['PLANOWNER_INFO_ARRAY']['defaultdays'].'d';

	// this also appears in caching
	if (user_is_authorized($planowner,$reader))
	{
		$private="*"; $private_cache_fn='private';
	}

	// css has to be here so it can be placed properly even when the plan is cached.
	// this is especially important for offnode readers for some reason.
	$css_filename="$_SERVER[PLANOWNER_ROOT]/plan/plancss.txt";
	if (file_exists($css_filename))
	{
		$plan_dressing['css']=stripslashes(file_get_contents($css_filename));
		$plan_dressing['css']=str_replace(array("\r","\n")," ",$plan_dressing['css']);
		$plan_dressing['css']=str_replace(array("\r","\n")," ",$plan_dressing['css']);
		$plan_dressing['css']=str_replace(array("\r","\n")," ",$plan_dressing['css']);
		$_SERVER['PLANOWNER_INFO']['css']=strip_tags(trim($plan_dressing['css']));
	}
//		if ($_SERVER['USER']=='jwdavidson') return "got css $css_filename $plan_dressing[css]";

	// CACHING
	// this tests to see if there's a cached version. if not, it will write
	// one when it's done getting the plan together.
	//----------------------------------------------
	// first figure out the appropriate cache filename
	if ($_SERVER['OUTPUT_MODE']=='RSS')
	{
		$feed='feed';
	}
	if ($_SERVER['OUTPUT_MODE']=='ATOM')
	{
		$feed='atomfeed';
	}
	if ($_SERVER['OUTPUT_MODE']=='ATOM_PRIVATE')
	{
		$feed=rand(100,999);
	}
	if ($_SERVER['OUTPUT_MODE']=='IPHONE')
	{
		$feed='phonefeed';
	}
	profile('plan_read_local_uia');
	if (user_is_authorized($planowner,$reader))
	{
		$private="*"; $private_cache_fn='private';
	}
	profile('plan_read_local_uia');
	profile('plan_read_local_utp');
	if (!plan_test_privacy($reader,$planowner))
	{
		$blocked_cache_fn='blocked';
	}
	profile('plan_read_local_utp');

	$fn_username=base64_encode($planowner);
	if ($planowner==$reader)
	{
		$self='self';
	}
	if ($remoteuser=='rss reader')
	{
		$feed='feed';
	}
	if ($source) $source="source";

	$cache_fn="$_SERVER[FILE_ROOT]/temp/{$_SERVER['PLANOWNER_INFO_ARRAY']['salt']}.$self$private_cache_fn$blocked_cache_fn$remote$feed.$threshhold.$begindate.$source.cache";

	// then, if the file exists, read it
	if (file_exists($cache_fn) && (plan_get_last_update($planowner) < filemtime($cache_fn))
		&& filesize($cache_fn)>256)//&& !$_SERVER['USER']=='jwdavidson'
	{
		profile('plan_read_local_pis');
		if (plan_is_local($reader)) user_update_lastread($planowner);
		profile('plan_read_local_pis');
		return file_get_contents($cache_fn);
	}

	// END CACHING

	// if no valid cache is found, on with the show
	if ($threshhold[0]!='.')
	{
		if (strstr($threshhold,'r')) { $threshhold=str_replace('r','',$threshhold); $reverse=1; }

		$threshhold = time_calculate_threshhold($threshhold);
		$threshhold = time() - $threshhold;

		if (!$begindate || $begindate<100) { $begindate=$threshhold; $enddate=time(); $default_view=TRUE;}
		else $enddate=$begindate+(time()-$threshhold);
	}
	profile('plan_read_local_head');

	profile('plan_read_local_styles');
	// if the user has local pref stuff, read it in
	if ($_SERVER['USER_ROOT'])
	{
		$styles_fn="$_SERVER[USER_ROOT]/styles.txt";
		$skin_fn="$_SERVER[USER_ROOT]/skin.txt";
		$colors_fn="$_SERVER[USER_ROOT]/colors.txt";
		$fonts_fn="$_SERVER[USER_ROOT]/fonts.txt";
		$css_fn="$_SERVER[USER_ROOT]/user_css.txt";
		if (file_exists($styles_fn) && !file_exists($skin_fn) && !file_exists($colors_fn))
		{
			parse_str(file_get_contents($styles_fn));
			if ($skin && file_exists("$_SERVER[FILE_ROOT]/resources/skins/$skin")) @include($skin);
		}
		if (file_exists($skin_fn))
		{
			parse_str(file_get_contents($skin_fn));
			if ($skin) { include($skin); }
		}
		if (file_exists($colors_fn))
			parse_str(file_get_contents($colors_fn));
		if (file_exists($fonts_fn))
			parse_str(file_get_contents($fonts_fn));
		if (file_exists($css_fn))
			eval(file_get_contents($css_fn));
	}
	profile('plan_read_local_styles');


	// screen out blocked readers, readers that fall below
	// privacy levels set by planowner
	if (!plan_test_privacy($reader,$planowner))
	{
	    $blocked_fn="$_SERVER[PLANOWNER_ROOT]/blockedmessage.txt";
	    if (file_exists($blocked_fn))
		{
			$plan=stripslashes(file_get_contents($blocked_fn));
			$plan=str_replace('MYUSER',$planowner,$plan);
			$plan=str_replace('READER',$reader,$plan);
	        $plan.=" <!--planowner: $planowner | reader: $reader-->";
		}
		else $plan="$planowner has no plan... <!-- reader: $reader --- planowner: $planowner -->";
	}
	else
	{
		// if there's a series of entries listed, break out that list
		if (strstr($threshhold,','))
		{
			$threshhold_array=explode(',',$threshhold);
		}

		// otherwise treat the threshhold normally
		else
		{
			$threshhold_array=array($threshhold);
		}

		$plan_array=array();

		// this either reads in all the files from the list of entries passed
		// OR it just gets a list of all the entries that pass privacy muster
		foreach($threshhold_array as $threshholdline)
		{
			// if the first character of the threshhold is a '.' we
			// want to show the entry indicated by the remainder of the line
			if ($threshholdline[0]=='.') $limiter=$threshholdline;
			else $limiter='.';


			//sets dir for reading
			$plan_dir="$_SERVER[PLANOWNER_ROOT]/plan/";

			// gets the entries indicated by $limiter
			// if $limiter is unset, fills the array with all available entries
			$plan_array=array_merge($plan_array,files_list($plan_dir,"plan$limiter*.txt$private"));
			if (is_array($plan_array) && $threshholdline[0]=='.')
			{
				$begindate=1;
				$enddate=time()+1;
			}
		}

		// get all the plan 'dressing' -- sidebars, header, footer, etc.
/* CSS taken care of outside of caching.
		$css_filename="$_SERVER[PLANOWNER_ROOT]/plan/plancss.txt";
		if (file_exists($css_filename))
		{
			$plan_dressing['css']=stripslashes(file_get_contents($css_filename));
			$plan_dressing['css']=str_replace(array("\r","\n")," ",$plan_dressing['css']);
			$plan_dressing['css']=str_replace(array("\r","\n")," ",$plan_dressing['css']);
			$plan_dressing['css']=str_replace(array("\r","\n")," ",$plan_dressing['css']);
			$_SERVER['PLANOWNER_INFO']['css']=strip_tags(trim($plan_dressing['css']));
		}
*/
		$header_filename="$_SERVER[PLANOWNER_ROOT]/plan/planheader.txt";
		if (file_exists($header_filename))
		{
			$plan_dressing['header']=stripslashes(file_get_contents($header_filename));
			if(strstr($plan_dressing['header'],'nolinebreaks'))
			{
				$plan_dressing['header']=str_replace(array("\r","\n"),"",$plan_dressing['header']);
				$plan_dressing['header']=str_replace(array("\r","\n"),"",$plan_dressing['header']);
				$plan_dressing['header']=str_replace(array("\r","\n"),"",$plan_dressing['header']);
			}
			if (strpos($plan_dressing['header'],'<!--markdown-->'))
			{
				include_once('markdown.php');
				$plan_dressing['header']=Markdown(str_replace('<!--markdown-->','',$plan_dressing['header']));
			}
			$plan_dressing['header']="<div id='plan_header_$_SERVER[PLANOWNER]' class='plan_header'>$plan_dressing[header]</div>";
		}

		$footer_filename="$_SERVER[PLANOWNER_ROOT]/plan/planfooter.txt";
		if (file_exists($footer_filename))
		{
			$plan_dressing['footer']=stripslashes(file_get_contents($footer_filename));
			if(strstr($plan_dressing['footer'],'nolinebreaks'))
			{
				$plan_dressing['footer']=str_replace(array("\r","\n"),"",$plan_dressing['footer']);
				$plan_dressing['footer']=str_replace(array("\r","\n"),"",$plan_dressing['footer']);
				$plan_dressing['footer']=str_replace(array("\r","\n"),"",$plan_dressing['footer']);
			}
			if (strpos($plan_dressing['footer'],'<!--markdown-->'))
			{
				include_once('markdown.php');
				$plan_dressing['footer']=Markdown($plan_dressing['footer']);
			}
			$plan_dressing['footer']="<div id='plan_footer_$_SERVER[PLANOWNER]' class='plan_footer'>$plan_dressing[footer]</div>";
		}

		$divider_filename="$_SERVER[PLANOWNER_ROOT]/plan/plandivider.txt";
		if (file_exists($divider_filename))
		{
			$plan_dressing['divider']=stripslashes(file_get_contents($divider_filename));
		}

		if ($plan_dressing)
		foreach($plan_dressing as $area=>$content)
		{
			$content=stripslashes($content);
			if (strpos($content,'nolinebreaks')!==FALSE)
			{
				$content=str_replace('#nolinebreaks#','',$content);
			}
			else
			{
				$content=smart_nl2br($content);
			}

			$plan_dressing[$area]=$content;
		}

		if (plan_is_journaling($planowner))
		{
			$plan_array[]="plan.$begindate.fake";
			$plan_array[]="plan.$enddate.fake";
			sort($plan_array);
			$plan_array=array_values($plan_array);
			// extract the update time for all the plan entries
			foreach($plan_array as $z=>$planfn)
			{
				$planfn_a=explode(".",$planfn);
				$nextplanfn_a=explode(".",$plan_array[$z+1]);
				$lastplanfn_a=explode(".",$plan_array[$z-1]);
				if ((($planfn_a[1] >= ($begindate)
					&& $planfn_a[1]<=($enddate))
					||
					($nextplanfn_a[1] >= ($begindate)
						&& $nextplanfn_a[1]<=($enddate))
					||
					($lastplanfn_a[1] >= ($begindate)
						&& $lastplanfn_a[1]<=($enddate)))
					&&
					!strstr($planfn,'fake')
					)
					{
						$plan_index_array[$planfn_a[1]]=$planfn;
					}
			}

			// if we don't have any entries, give up now
			if (!is_array($plan_index_array)) return "no plan entries found.";


			// sort the plan list in order of update time newest to oldest.
			// we'll account for $reverse later, when we're
			// actually writing the entries to strings.
			krsort($plan_index_array);

			foreach($plan_index_array as $entry_timecode=>$entry_filename)
			{
				if($entry_timecode >= $begindate && $entry_timecode <=$enddate)
				{
					$entry_filename="$_SERVER[PLANOWNER_ROOT]/plan/$entry_filename";
					if (file_exists($entry_filename))
					{
						$plan_entries[$entry_timecode]=file_get_contents($entry_filename);
					}
				}
			}


			// if there were no entries within the threshhold
			// and the threshhold was near now, show the most recent entry
			if (!is_array($plan_entries) && $enddate > (time()-15))
			{
				// the most recent entry will be just after the beginning of the array
				// because the first two entries will be the $enddate test entry
				reset($plan_index_array);
				while (strpos(current($plan_index_array),'FAKE'))
				{
					next($plan_index_array);
				}

				$entry_filename="$_SERVER[PLANOWNER_ROOT]/plan/".current($plan_index_array);
				$entry_timecode=key($plan_index_array);
				if (file_exists($entry_filename))
				{
					$plan_entries[$entry_timecode]=file_get_contents($entry_filename);
				}
			}

			// if there's still no plan, and we're considering an archival view
			// find the nearest-neighbor plan entry
			if (!is_array($plan_entries) && $enddate < (time()-15))
			{
				// get the distance to the prev entry
				array_set_current($plan_index_array,$enddate);
				$previous_entry_filename="$_SERVER[PLANOWNER_ROOT]/plan/".prev($plan_index_array);
				$previous_entry_timecode=key($plan_index_array);

				if (file_exists($previous_entry_filename))
				{
					$plan_entries[$previous_entry_timecode]=file_get_contents($previous_entry_filename);
				}

				// get the distance to the next entry
				array_set_current($plan_index_array,$begindate);
				$next_entry_filename="$_SERVER[PLANOWNER_ROOT]/plan/".next($plan_index_array);
				$next_entry_timecode=key($plan_index_array);

				if (file_exists($next_entry_filename))
				{
					$plan_entries[$next_entry_timecode]=file_get_contents($next_entry_filename);
				}

				$plan_entries[1000000000000000]="Nothing In Range";

			}


			if ($reverse) ksort($plan_entries);
			else krsort($plan_entries);


			// if we're ouputting to HTML, put it all together in tasty DIVs.
			if ($_SERVER['OUTPUT_MODE']=='HTML' || $_SERVER['OUTPUT_MODE']=='IPHONE'  || $_SERVER['OUTPUT_MODE']=='MOBILE' || $_SERVER['OUTPUT_MODE']=='AJAX')
			{
				foreach($plan_entries as $entry_time=>$entry_text)
				{
					$entry_text=trim(stripslashes($entry_text));
					if (strpos($entry_text,'nolinebreaks') || strpos($entry_text,'<!--markdown-->'))
					{
						$entry_nolinebreaks=1;
						$entry_text=str_replace('#nolinebreaks#','',$entry_text);
					}
					else
					{
						$entry_nolinebreaks='';
						$entry_text=smart_nl2br($entry_text);
					}
					if (strpos($entry_text,'<!--markdown-->'))
					{
						include_once('markdown.php');
						$entry_text=Markdown(str_replace('<!--markdown-->','',$entry_text));
					}

					// pretty up the divider (erm... entry header)
					$entry_header = plan_prepare_divider($planowner,$reader,$plan_dressing['divider'],$entry_time,$plan_index_array[$entry_time],$entry_text,$entry_nolinebreaks);
					$plan_content.=plan_prepare_entry($entry_time,$entry_header,$entry_text);
				}

				$plan.=$plan_dressing['header']."\n$new_entry_link\n<div class='plan_body' id='plan_body_$_SERVER[PLANOWNER]'>\n$plan_content\n</div>".$plan_dressing['footer'];
			}

			// if we're building a feed, we want to do valid XML goodness
			if ($_SERVER['OUTPUT_MODE']=='RSS')
			{
				foreach($plan_entries as $entry_time=>$entry_text)
				{
					$entry_text=trim(stripslashes($entry_text));
					if (strpos($entry_text,'nolinebreaks'))
					{
						$entry_text=str_replace('#nolinebreaks#','',$entry_text);
					}
					else
					{
						$entry_text=smart_nl2br($entry_text);
					}

					$rss_link="http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner";
					$entry_text=plan_add_user_links(plan_add_alias_links($entry_text,$planowner));
					list($entry,$li) = plan_encapsulate_rss($planowner,$entry_time,$entry_text);
					$plan.=$entry;
					$items.=$li;
				}
				$plan = "$items\n<!-- FEED_DIVIDER -->\n$plan";
			}

			if (strstr($_SERVER['OUTPUT_MODE'],'ATOM'))
			{
				foreach($plan_entries as $entry_time=>$entry_text)
				{
					$entry_text=trim(stripslashes($entry_text));
					if (strpos($entry_text,'nolinebreaks'))
					{
						$entry_text=str_replace('#nolinebreaks#','',$entry_text);
					}
					else
					{
						$entry_text=smart_nl2br($entry_text);
					}

					$rss_link="http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner";
					$entry_text=plan_add_user_links(plan_add_alias_links($entry_text,$planowner));
					$entry = plan_encapsulate_atom($planowner,$entry_time,$entry_text);
					$plan.=$entry;
				}
				$plan = "$items\n<!-- FEED_DIVIDER -->\n$plan";
			}

		}
		else //NOT JOURNALING -- TRADITIONAL PLAN BEGINS HERE
		{
			// TODO:(v4.5) RSS for traditional plans
			//if ($_SERVER['USER']=='dskatz04') echo "$threshhold ".(time()-21*24*3600)." this is a traditional plan\n<br />";
			if (strlen($threshhold) < 9) $threshhold='';
			elseif (strpos($threshhold,',')) $threshhold_array=explode(',',$threshhold);
			else $threshhold_array=array($threshhold);

			if (is_array($threshhold_array)) $threshhold_array=array_reverse($threshhold_array);
			else $threshhold_array=array("1"=>"");

			foreach($threshhold_array as $z=>$threshhold)
			{
				if ($threshhold!=time()-($_SERVER['PLANOWNER_INFO_ARRAY']['defaultdays']*24*3600))
				{
					$plan_filenames  = files_list("$_SERVER[PLANOWNER_ROOT]/plan","*$threshhold*");
				}
				else $plan_filenames = array('plan.txt');
				$entry_time=$threshhold;

				if (count($threshhold_array)>1)
				{
					$plan.="<hr/>".date('F jS Y, g:ia',$threshhold_array[$z])."<hr/>";
					$entry_header = plan_prepare_divider($planowner,$reader,$plan_dressing['divider'],$entry_time,$plan_filenames[0],$entry_text,$entry_nolinebreaks);
				}

				if (is_array($plan_filenames))
				foreach($plan_filenames as $filename)
				{
					if (file_exists("$_SERVER[PLANOWNER_ROOT]/plan/$filename"))
					{
						if(strstr($filename,'.gz'))
							$plan.=stripslashes(file_get_contents("compress.zlib://$_SERVER[PLANOWNER_ROOT]/plan/".basename($filename)));
						else
							$plan.=stripslashes(file_get_contents("$_SERVER[PLANOWNER_ROOT]/plan/".basename($filename)));
					}
				}
			}
			if (strlen(trim($plan))>10)
			{
				if (!strstr($plan,'nolinebreaks')) $plan=smart_nl2br($plan);
				$plan="$plan_dressing[header]<div class='plan_body' id='plan_body_$_SERVER[PLANOWNER]'>$plan</div>\n$plan_dressing[footer]";
			}
		}
	}

// MORE CACHING
	if (!file_exists($cache_fn) || (plan_get_last_update($planowner) > filemtime($cache_fn)) && $remoteuser!='rss reader')
		file_put_contents($cache_fn,$plan);

	profile("plan_read_local_$prl_rand");

return "<!-- since ".formattime($threshhold)." ".formattime($begindate)." -->\n".$plan;
}


// PLAN_ENCAPSULATE_RSS()
//
// take an entry and build an rss item
//------------------------------------------------------------------------------
function plan_encapsulate_rss($planowner,$entry_time,$entry)
{
	if (!strstr($entry,'<!--no feed-->'))
	{
		$entry = "
		 <item rdf:about=\"http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time\">
			 <title>".formattime($entry_time)." ".smart_trim(strip_tags($entry),32,FALSE)."</title>
			 <link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time</link>
			 <description>$entry</description>
			 <dc:creator>$planowner</dc:creator>
			 <dc:subject>entry</dc:subject>
			 <dc:date>".gmdate('Y-m-d\TH:i:s+00:00',$entry_time)."</dc:date>
		 </item>\n";

		$li = "<rdf:li rdf:resource=\"http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time\"/>\n";
	}

	return array($entry,$li);
}


// PLAN_ENCAPSULATE_ATOM()
//
// take an entry and build an atom entry
//------------------------------------------------------------------------------
function plan_encapsulate_atom($planowner,$entry_time,$entry)
{
	if (!strstr($entry,'<!--no feed-->'))
	{
		if(strstr($entry,"<!--TITLE"))
		{
			preg_match("/<!--TITLE ([^<>]*)-->/",$entry,$matches);
			$title=$matches[1];
		}
		else $title=formattime($entry_time)." ".smart_trim(strip_tags($entry),32,FALSE);

		if(strstr($entry,"Posted By"))
		{
			preg_match("/Posted By .* <a href=['\"](.*)['\"]>(.*)<\/a>/",$entry,$matches);
			$link=$matches[1];
			$feedtitle=$matches[2];
//			$testentry.=var_dump_ret($matches);
//			$testentry.="linkback";
		}
		else $link="http://planwatch.org/read/$planowner/$entry_time";

		if($_SERVER['OUTPUT_MODE']=='ATOM_PRIVATE') $entry="<a href='$link'>$link</a>";


		$entry = "
			<entry>
				<title type='html'>$title</title>
				<summary>".smart_trim(strip_tags($entry),255,FALSE)."</summary>
				<content type='html'>".htmlentities($entry)."</content>
				<link rel=\"alternate\" href=\"$link\" type=\"text/html\"/>
				<updated>".gmdate('Y-m-d\TH:i:s+00:00',$entry_time)."</updated>
				<id>http://planwatch.org/read/$planowner/$entry_time</id>
				<author>
					<name>$planowner</name>
				</author>
			</entry>
		";
		$li = "<rdf:li rdf:resource=\"http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time\"/>\n";
	}

	return $entry;
}



// PLAN_PREPARE_ENTRY()
//
// creates a uniform entry look
//------------------------------------------------------------------------------
function plan_prepare_entry($entry_time,$entry_header,$entry_text)
{
	$entry="\n<article class='plan_entry' selected='true' id='plan_entry_$entry_time' $click_to_edit>\n$entry_header\n<section class='entry_content' id='entry_content_$entry_time'>\n$entry_text\n</section></article>\n";
	return $entry;
}


// PLAN_READ_QUIET()
//
// reads the public version of a plan with no trace - used for various utility
// functions, not to be used for actual plan reads.
//------------------------------------------------------------------------------
function plan_read_quiet($plan)
{
	// become cacheuser temporarily
	$_SERVER['USER']='cacheuser';
	$snitchlevel=$_SERVER['USERINFO_ARRAY']['snitchlevel'];
	$_SERVER['USERINFO_ARRAY']['snitchlevel']=0;

	$plan_data=plan_read($plan);

	// put things back in order
	$_SERVER['USER']=$_SERVER['USERINFO_ARRAY']['username'];
	$_SERVER['USERINFO_ARRAY']['snitchlevel']=$snitchlevel;

	return $plan_data;
}


// PLAN_PREPARE_DIVIDER()
//
// creates a pretty and context-relevant divider for local plans
//------------------------------------------------------------------------------
function plan_prepare_divider($planowner,$reader,$divider,$entry_time,$entry_filename,$entry_text,$entry_nolinebreaks)
{


	$entry_header="<header class='entry_header' id='entry_header_$entry_time'><!--EDIT_LINKS-->\n$divider\n</header>";

	if (strpos($entry_filename,'.p'))
	{
		$entry_is_private = 1;
		if ($reader == $planowner && browser_is_modern()) $publicize_link=" <span  id='privacy_$entry_time'><a href=\"javascript:loadXMLDoc('http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/entry/publicize/.$entry_time/ajax',null,'privacy_$entry_time');document.getElementById('privacy_marker_$entry_time').innerHTML='';void(null);\">publicize</a></span> ";
		$entry_header=str_replace('PRIVATE'," <span class='private_marker' id='privacy_marker_$entry_time'>private entry</span> ",$entry_header);
	}
	else
	{
		$entry_is_private = '';
		if ($reader == $planowner && browser_is_modern()) $privatize_link=" <span  id='privacy_$entry_time'><a href=\"javascript:loadXMLDoc('http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/entry/privatize/.$entry_time/ajax',null,'privacy_$entry_time');document.getElementById('privacy_marker_$entry_time').innerHTML='private entry';void(null);\">privatize</a></span> ";
		$entry_header=str_replace('PRIVATE'," <span class='private_marker' id='privacy_marker_$entry_time'></span> ",$entry_header);
	}

	if (strstr($entry_text,'<!--no feed-->'))
	{
		$entry_is_nonfeed = 1;
		$entry_header=str_replace('NOFEED'," <span class='feed_marker' id='feed_marker_$entry_time'>not in feed</span> ",$entry_header);
	}
	else
	{
		$entry_is_nonfeed = '';
		$entry_header=str_replace('NOFEED',"",$entry_header);
	}

	$entry_header=preg_replace('|LINK\[(.*)\]|',"<a href='$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time' title='permanent link to this entry'>\\1</a>",$entry_header);
	preg_match_all('|DATE\[(.*)\]|',$entry_header,$matches);
	foreach($matches[0] as $z=>$ds)
	{
		$entry_header=str_replace($matches[0][$z],date($matches[1][$z],$entry_time),$entry_header);
	}

	// create the editing links
	if ($reader == $planowner && browser_is_modern())
	{
		$edit_links.="<a href=\"javascript:layerToggle('edit_links_$entry_time');\" class='tool'><span class='hidden'>editing tools &nbsp; </span> &#x2699;</a>"
			." <div id='edit_links_$entry_time' class='edit_links' style='display: none;'>\n"
			." <a href=\"/write/.$entry_time\">edit</a>\n"
			." <a href=\"javascript:displayEditLayer('$entry_time');\">tweak</a>\n"
			." <a href=\"javascript:loadXMLDoc('http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/entry/delete/.$entry_time/ajax',null,'plan_entry_$entry_time');\">delete</a> \n"
			." <a href=\"javascript:loadXMLDoc('http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/entry/hide/.$entry_time/ajax',null,'plan_entry_$entry_time');\">hide</a> \n"
			."$privatize_link $publicize_link\n</div>";

		$entry_header = str_replace("<!--EDIT_LINKS-->","\n$edit_links\n",$entry_header);

		$entry_header = str_replace(
			"<header class='entry_header' id='entry_header_$entry_time'>",
			"<header class='entry_header' id='entry_header_$entry_time' ondblclick=\"displayEditLayer('$entry_time');\">",
			$entry_header);

//		$entry_header.="
//		<div class='editLayer' id='editLayer_$entry_time'>
//		</div>";
	}

	if(preg_match("<!--title (.*) -->",$entry_text,$titlematches))
	{
		$entry_header.="<header id='$entry_time' style='visibility: hidden;'>$titlematches[1]</header>";
	
	}


	return $entry_header;
}


/*

// plan_read_RSS()
//
// retrieves rss feeds and formats them as plans
// relies on magpie_rss
//----------------------------------------------------------------------

function plan_read_rss($url)
{
	$url=str_replace("feed://","http://",$url);

	if (!strstr($url,'://'))	$url=plan_get_real_location($url);

	$result=@fetch_rss($url);

	if ($result->image)
	{
		$image_info=$result->image;
		$image="<a href='$image_info[link]' title='$image_info[title]'><img src='$image_info[url]' border='0' /></a>";
	}

	if ($result->channel)
	{
		$channel_info=$result->channel;
		$content.= "<h1>$image<a href='$channel_info[link]'>$channel_info[title]</a></h1>
		<i>$channel_info[description]$tagline</i><br /><br />\n\n";
	}

	if (!($result->items))
	{
		$result->items[0]['title']="Error";
		$result->items[0]['date_timestamp']=time();
		$result->items[0]['content']['encoded']="Reading $url failed. If this persists, please write <a href='mailto:help@planwatch.org'>help@planwatch.org</a>";
	}

	foreach($result->items as $item)
	{
		$entry_header='';
		if ($item['title'])
			$entry_header.="<a href='$item[link]' class='entry_title'>$item[title]</a>\n";

		$dc=$item['dc'];

		if ($item['date_timestamp']) $timecodes=formattime($item['date_timestamp']);
		else if ($item['issued']) $timecodes=formattime(parse_w3cdtf($item['issued']));
		else if ($item['published']) $timecodes=formattime(parse_w3cdtf($item['published']));
		else if ($item['pubDate']) $timecodes=formattime(parse_w3cdtf($item['pubDate']));

		if ($item['modified']!=$item['issued']) $modified=formattime(parse_w3cdtf($item['modified']));
		if ($item['updated']!=$item['published']) $modified=formattime(parse_w3cdtf($item['updated']));

		$entry_header.= "<span class='edit_links'>";
		if ($dc['subject']) $entry_header.=" $dc[subject], ";
		if ($timecodes) $entry_header.=" posted $timecodes ";
		if ($dc['creator']) $entry_header.="by $dc[creator] ";
		elseif ($item['author_name']) $entry_header.="by ".($item['author_name'])." ";
		elseif ($item['author']) $entry_header.="by $item[author] ";
		if ($modified) $entry_header.="(modified $modified)";
		$entry_header.="</span><br />\n";


		// if there are multiple options for content, pick the longest one.
		$atom_length=strlen($item['atom_content']);
		$ce_length=strlen($item['content']['encoded']);
		$description_length=strlen($item['description']);

		if (max($atom_length,$ce_length,$description_length)==$atom_length) $entry_text="<div class='plan_entry'>$item[atom_content]</div>\n\n";
		if (max($atom_length,$ce_length,$description_length)==$ce_length) $entry_text="<div class='plan_entry'>{$item[content][encoded]}</div>\n\n";
		if (max($atom_length,$ce_length,$description_length)==$description_length) $entry_text="<div class='plan_entry'>$item[description]</div>\n\n";

//		$entry_text=removeEvilTags($entry_text);

		$content.="
			<div class='plan_entry' id='plan_entry_".md5($timecodes)."'>
			$entry_header
			<div class='entry_content' id='entry_content_".md5($timecodes)."'>
			$entry_text
			</div>
			</div>
			";
//		if ($_SEVER['USER']=='jwdavidson') $content.=var_dump_ret($item);

//		$content.="testing...";

	}

	if ($result->textinput)
	{
		$form_info=$result->textinput;
		$content.= "
		<form action='$form_info[link]'>
		$form_info[description]
		<input type='text' name='$form_info[name]'/>
		<input type='submit' value='$form_info[title]'/>
		</form>
		";
	}

	$content=str_replace("src='/","src='$channel_info[link]/",$content);
	$content=str_replace("src=\"/","src=\"$channel_info[link]/",$content);
	$content=str_replace("SRC='/","src='$channel_info[link]/",$content);
	$content=str_replace("SRC=\"/","src=\"$channel_info[link]/",$content);

return $content;
}
*/

function plan_feed_search($url)
{
	require_once('find_feeds.php');
	$find_links= new Find_RSS_Links($url);
	$links = $find_links->getLinks();
	return $links;
}

function plan_multifeed_sort_items($a, $b)
{
	profile('rss_sort');
	$array=SimplePie::sort_items($a, $b);
	profile('rss_sort');

	return $array;
}


// THIS IS THE REAL RSS READING FUNCTION
function plan_read_simplepie_rss($urls,$offset=0,$item_count=30)
{
	if(IS_JOSH) include_once('simplepie.1.2.inc');
	else include_once('simplepie.inc');
	profile('rss_read');
	$lastview=plan_get_last_view($urls);
	$multifeed=TRUE;
	if (is_string($urls))
		if (substr_count($urls,",http") > 0) { $urls=explode(",http",$urls); foreach($urls as $i=>$url) if (!strstr($url,'http')) $urls[$i]="http".$url; }
		else	{  $urls=array($urls); $multifeed=FALSE;}

	preg_match("|\/\/(.*)@|",$urls[0],$match);
	if ($match[1])
	{
		$_SERVER['DIGEST_USERINFO']=$match[1];
		$_SERVER['DIGEST_AUTH']=TRUE;
	}

	profile("load feeds");
	$feed = new SimplePie($urls);
	profile("load feeds");

	$items=$feed->get_items();
	usort($items, "plan_multifeed_sort_items");
	$items=array_slice($items,$offset,$item_count);

	if (!is_object($items[0]))
	{
//		if(IS_JOSH) print_r($items);
		// do stuff to fix the url and try again
		preg_match("|.*://([^/]+)/|",$urls[0],$base_url_array);
		$base_link="<a href='$base_url_array[0]'>$base_url_array[1]</a>";

		if($offset!="RECURSION") $content=plan_read_simplepie_rss($base_url_array[0],"RECURSION");

		if(!$content)
		{
			$title="Failed to read feed $urls[0]";
			$content="<h1>Plan Read Failed</h1> We tried <tt>$urls[0]</tt> and did not get a valid feed.<br />
			You may want to try the site directly: $base_link<br />
			If the feed address is incorrect, you can <a href='/lists/edit/watched'>edit your watched list</a> to correct it.<br /><br />
			Still have questions? Ask for <a href='mailto:help@planwatch.org'>help</a>.";
	
			$alternate_links=array_unique(array_merge(plan_feed_search(str_replace("http://","http://www.",$base_url_array[0])),plan_feed_search($base_url_array[0]),plan_feed_search($urls[0])));
			if ($alternate_links)
			{
				$content.="<hr /><h2>Alternate Links</h2>Here are some possible alternate feed links:<ul>";
				foreach($alternate_links as $link)
				{
					$content.="<li><a href='http://planwatch.org/read/$link'>$link</a></li>\n";
				}
			}
		}

	}
	else
	foreach($items as $item)
	{
		unset($item_categories);
		$item_authors_string='';
		$entry_header='';
		$item_title=$item->get_title();
		$item_link=$item->get_permalink();
		$item_date=formattime($item->get_date('U'));
		$item_data=$item->get_content();
		if ($item_category_list=$item->get_categories())
			foreach($item_category_list as $category)
				$item_categories.=" ".$category->get_label();
		$item_authors=$item->get_authors();
		$item_id=$item->get_id();

		if ($item_authors)
		foreach($item_authors as $item_author)
		{
			if ($item_author->get_name())
				$item_authors_string.="<a href='".$item_author->get_link()."'>".$item_author->get_name()."</a> ";
			elseif ($item_author->get_email())
				$item_authors_string.=$item_author->get_email()." ";
		}

		if ($item_title)
		{
			$feed=$item->get_feed();
			$feed_title=$feed->get_title();
			$feed_link=$feed->get_link();
			$feed_url=$feed->feed_url;
			if ($multifeed)
				$entry_header="<a target='_self' href='/read/$feed_url'>$feed_title</a><br/><a target='_self' href='$item_link' class='entry_title'>$item_title</a>\n";
			else $entry_header.="<a target='_self' href='$item_link' class='entry_title'>$item_title</a>\n";
		}

		$entry_header.= "<span class='edit_links'>";
		if ($item_categories) $entry_header.=" $item_categories, ";
		if ($item_date) $entry_header.=" posted $item_date ";
		if ($item_authors_string) $entry_header.="by $item_authors_string ";
		$item_id_hash=md5($item_id);
		$entry_header.="</span><br />\n";

		//$entry_text=removeEvilTags($item_data);
		$entry_text=$item_data;

		$content.="
			<div class='plan_entry' id='plan_entry_$item_id_hash'>
			$entry_header
			<div class='entry_content' id='entry_content_$item_id_hash'>";
		$content.=str_replace("<a ","<a target='_self' ",$entry_text);
		$content.="
			</div>
			</div>
			";
	}

	if (!$multifeed && is_object($item))
	{
		$feed=$item->get_feed();
		$feed_title=$feed->get_title();
		$feed_link=$feed->get_link();
		$feed_description=$feed->get_description();

		$content="<h1><a href='$feed_link'>$feed_title</a></h1>$feed_description<br /><br />$content";

//		if($urls[0]!=$feed_link) $content="<div class='message'><b>This feed is autodiscovered</b> based on the link you provided. It may keep working, but discovery is unpredictable. If you want to ".$content;
	}



	profile('rss_read');
return $content;
}

function plan_read_simplepie_single($url,$id)
{
	$url=str_replace("feed://","http://",$url);
	if (!strstr($url,'://'))	$url=plan_get_real_location($url);

	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->set_output_encoding('utf-8');
	$feed->init();
	$feed->handle_content_type();


	$these_items=$feed->get_items();
	$items=array_merge($items,$these_items);

	foreach($items as $item)
	{
		if (md5($item->get_id())==$id)
		{
			$item_data=$item->get_content();
			//$entry_text=removeEvilTags($item_data);
			$entry_text=$item_data;
			$content.="$entry_text";
		}
	}

return $content;
}

/*
function plan_read_metafeed($urls,$offset=0,$length=30)
{
	profile('rss_read_meta');
	foreach($urls as $url)
	{
		$url=str_replace("feed://","http://",$url);

		if (!strstr($url,'://'))	$url=plan_get_real_location($url);

		if (strstr($url,'://'))
		{
			$feed = new SimplePie();
			$feed->feed_url($url);
			$feed->output_encoding('utf-8');
			$feed->init();
			$feed->handle_content_type();

			$title=$feed->get_feed_title();
			$items=$feed->get_items();
			$image=$feed->get_image_url();
			$link=$feed->get_feed_link();

			foreach($items as $item)
			{
				$all_items[$item->get_date()]=$item;
			}
		}
	}

	$all_items=array_slice($all_items,$offset,$length,TRUE);
	krsort($all_items);

	foreach($all_items as $item)
	{
		$item_authors_string='';
		$entry_header='';
		$item_title=$item->get_title();
		$item_link=$item->get_permalink();
		$item_date=formattime($item->get_date());
		$item_data=$item->get_description();
		if ($item->get_categories()) $item_categories=implode(" ",$item->get_categories());
		$item_authors=$item->get_authors();

		if ($item_authors)
		foreach($item_authors as $item_author)
		{
			if ($item_author->get_name())
				$item_authors_string.="<a href='".$item_author->get_link()."'>".$item_author->get_name()."</a> ";
			elseif ($item_author->get_email())
				$item_authors_string.=$item_author->get_email()." ";
		}

		if ($item_title)
			$entry_header.="<a href='$item_link' class='entry_title'>$item_title</a>\n";

		$entry_header.= "<span class='edit_links'>";
		if ($item_categories) $entry_header.=" $item_categories, ";
		if ($item_date) $entry_header.=" posted $item_date ";
		if ($item_authors_string) $entry_header.="by $item_authors_string ";
		$entry_header.="</span><br />\n";


//		$entry_text=removeEvilTags($item_data);

		$entry_text=str_replace("src='/","src='$link/",$entry_text);
		$entry_text=str_replace("src=\"/","src=\"$link/",$entry_text);
		$entry_text=str_replace("SRC='/","src='$link/",$entry_text);
		$entry_text=str_replace("SRC=\"/","src=\"$link/",$entry_text);

		$id_extension=md5($item_date);
		$all_items[$item->get_date()]="
			<div class='plan_entry'>
			<a href='$link'>$title</a>: $entry_header
			<div class='entry_content' id='entry_content_$id_extension'>
			$entry_text
			</div>
			</div>
			";
	}

	$content=implode("\n",$all_items);
	profile('rss_read_meta');

return $content;
}
*/

function plan_read_archives($planowner)
{
	if(!file_exists("$_SERVER[PWUSERS_DIR]/$planowner/plan/.arcprivate") || user_is_authorized($planowner,$_SERVER['USER']))
	{
		if (plan_is_local($planowner) && plan_test_privacy($_SERVER['USER'],$planowner))
		{
			if (plan_is_journaling($planowner))
			{
				$m=array('','','','','','','','','','','','');
				$m[date('n',time()-(24*3600*10))]='SELECTED';
				$d=date('j',time()-(24*3600*10));
				$y=date('Y',time()-(24*3600*10));

				$content="
		<div align='center'>
			<form action='$_SERVER[WEB_ROOT]/scripts/form_shim.php' method='post'>
				view
				<input type='text' name='threshhold' value='10' size='5'/>
				<select name='units'>
					<option value='w'>weeks</option>
					<option value='d' SELECTED>days</option>
					<option value='h'>hours</option>
					<option value='m'>minutes</option>
				</select>
				<input type='hidden' value='$planowner' name='username'/>
				of $planowner's archives
				<select name='reverse'>
					<option value=''>newest first</option>
					<option value='r' SELECTED>oldest first</option>
				</select><br />
				starting
				<select name='startmonth'>
					<option value='1' $m[1]>January</option>
					<option value='2' $m[2]>February</option>
					<option value='3' $m[3]>March</option>
					<option value='4' $m[4]>April</option>
					<option value='5' $m[5]>May</option>
					<option value='6' $m[6]>June</option>
					<option value='7' $m[7]>July</option>
					<option value='8' $m[8]>August</option>
					<option value='9' $m[9]>September</option>
					<option value='10' $m[10]>October</option>
					<option value='11' $m[11]>November</option>
					<option value='12' $m[12]>December</option>
				</select>
				<input type='text' name='startdom' value='$d' size='5'/>
				<input type='text' name='startyear' value='$y' size='5'/>
				<select name='starttime'>\n";

				for ($i=0;$i<24;$i++)
				{
					$hour=($i % 12);
					if ($hour==0) $hour=12;
					$pm=floor($i / 12);
					if ($pm) $pm='pm';
					else $pm='am';
					$content.="\n\t\t\t\t\t\t<option value='$i'>$hour $pm</option>\n";
				}

				if (strstr($_SERVER['USER'],'@'))
				{
					$authid=base64_encode("$_SERVER[USER]".time());
					file_put_contents("$_SERVER[FILE_ROOT]/temp/$authid.calauth",$planowner);
				}

				$content.="
				</select>
				<input type='submit' value='go'/>
				</form>

				<b>search</b>: <form action='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/form_shim.php'><input type='text' name='keyword' value=''/><input type='hidden' name='writer' value='$planowner'/><input type='submit' name='submit' value='search'/><input type='hidden' name='action' value='archive_search'/></form>

				<object id='archives_calendar' align='bottom' standby='waiting for cal' data='http://planwatch.org/cal/$planowner/".date("Y-m-01")."/".date("Y-m-t")."' type='text/html' />
				</div>\n";

			}
			else
			{
				$content="<b>Pick an archived plan to view or check several and click 'view checked':</b>

				<b>search</b>: <form action='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/form_shim.php'><input type='text' name='keyword' value=''/><input type='hidden' name='writer' value='$planowner'/><input type='submit' name='submit' value='search'/><input type='hidden' name='action' value='archive_search'/></form>
				<object id='archives_calendar' align='bottom' standby='waiting for cal' data='/cal/$planowner/".date("Y-m-01")."/".date("Y-m-t")."' type='text/html'></object>

				<form action='$_SERVER[WEB_ROOT]/scripts/form_shim.php' method='post'>
				<input type='hidden' name='username' value='$planowner'/>
				<input type='submit' value='view checked'/>
				<ul>";
				exec("ls $_SERVER[PWUSERS_DIR]/$planowner/plan/plan.*",$planlist);
				$planlist=array_reverse($planlist);

				foreach($planlist as $i=>$planentry)
				{
					$pesize=filesize($planentry);
					if (!$oldpesize) $oldpesize=.00001;
					if ((($pesize - $oldpesize) > 100) || (($pesize/$oldpesize) >= 1.25) || (($pesize/$oldpesize) <= .75)) $content.="<br />\n";
					$content.="<br />";
					$planentry=basename($planentry);
					$planentry=str_replace('txt','',$planentry);
					$planentry=str_replace('plan','',$planentry);
					$planentry=str_replace('gz','',$planentry);
					$planentry=str_replace('.','',$planentry);
					$planentrydate=date('F jS Y, h:ia',$planentry);
					$content.="<li><input type='checkbox' name='archivelist[]' value='$planentry'/><a href='$_SERVER[WEB_ROOT]/read/$planowner/.$planentry'>$planentrydate</a>\n";
					$content.="<font size=-1>(".files_format_size($pesize).")</font>\n";
					if ($planowner==$_SERVER['USER']) $content.=" &nbsp;&nbsp;<a href='$_SERVER[WEB_ROOT]/deleteentry/.$planentry'>delete</a>\n";
					$oldpesize=$pesize;
				}
				$content.="</ul><input type='submit' value='view checked'/></form>";
			}
		}
		elseif(!plan_is_local($planowner))
		{
			redirect($_SERVER['PLANOWNER_INFO_ARRAY']['archiveurl']);
			exit;
		}
		else
		{
			$content="you are not allowed to view $planowner's archives.";
		}
	}
	else $content="$planowner's archives are private, and you do not have sufficient permission to view them.";



	return $content;
}
?>