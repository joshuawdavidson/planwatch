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

function plan_process_oembed($plan,$urls)
{
	include_once('json.php');
	$totalprocessed=0;
	$json=new JSON;

	foreach($urls as $url)
	{
		$url="//".trim(strip_tags($url));
		$apiurl=urlencode($url);
		$data=file_get_contents("http://api.embed.ly/v1/api/oembed?url=$apiurl&maxwidth=600&maxheight=600&format=json");
		$embed=$json->unserialize($data);
		$html=$embed->html;
		if($embed->type=='video' || $embed->type=='rich')
			$plan=str_replace($url,$embed->html,$plan);
		if($embed->type=='photo')
			$plan=str_replace($url,"<img src='$embed->url' style='width: $embed->width"."px; height: $embed->height"."px;' />",$plan);
		if($embed->type=='link' && $embed->title)
			$plan=str_replace($url,"<a href='$theseurls[0]'>$embed->title</a>",$plan);
		if($embed->type=='link' && !$embed->title)
			$plan=str_replace($url,"<a href='$theseurls[0]'>$theseurls[0]</a>",$plan);
	}

	return $plan;

}

function plan_match_embed_urls($plan)
{
	$embedly_re = "/[^'\"=>]http:\/\/(.*youtube\.com\/watch.*|.*\.youtube\.com\/v\/.*|youtu\.be\/.*|.*\.youtube\.com\/user\/.*#.*|.*\.youtube\.com\/.*#.*\/.*|.*justin\.tv\/.*|.*justin\.tv\/.*\/b\/.*|www\.ustream\.tv\/recorded\/.*|www\.ustream\.tv\/channel\/.*|qik\.com\/video\/.*|qik\.com\/.*|.*revision3\.com\/.*|.*\.dailymotion\.com\/video\/.*|.*\.dailymotion\.com\/.*\/video\/.*|www\.collegehumor\.com\/video:.*|.*twitvid\.com\/.*|www\.break\.com\/.*\/.*|vids\.myspace\.com\/index\.cfm\?fuseaction=vids\.individual&videoid.*|www\.myspace\.com\/index\.cfm\?fuseaction=.*&videoid.*|www\.metacafe\.com\/watch\/.*|blip\.tv\/file\/.*|.*\.blip\.tv\/file\/.*|video\.google\.com\/videoplay\?.*|.*revver\.com\/video\/.*|video\.yahoo\.com\/watch\/.*\/.*|video\.yahoo\.com\/network\/.*|.*viddler\.com\/explore\/.*\/videos\/.*|liveleak\.com\/view\?.*|www\.liveleak\.com\/view\?.*|animoto\.com\/play\/.*|dotsub\.com\/view\/.*|www\.overstream\.net\/view\.php\?oid=.*|www\.livestream\.com\/.*|www\.worldstarhiphop\.com\/videos\/video.*\.php\?v=.*|worldstarhiphop\.com\/videos\/video.*\.php\?v=.*|teachertube\.com\/viewVideo\.php.*|teachertube\.com\/viewVideo\.php.*|bambuser\.com\/v\/.*|bambuser\.com\/channel\/.*|bambuser\.com\/channel\/.*\/broadcast\/.*|www\.schooltube\.com\/video\/.*\/.*|.*yfrog\..*\/.*|tweetphoto\.com\/.*|www\.flickr\.com\/photos\/.*|.*twitpic\.com\/.*|.*imgur\.com\/.*|.*\.posterous\.com\/.*|post\.ly\/.*|twitgoo\.com\/.*|i.*\.photobucket\.com\/albums\/.*|gi.*\.photobucket\.com\/groups\/.*|phodroid\.com\/.*\/.*\/.*|www\.mobypicture\.com\/user\/.*\/view\/.*|moby\.to\/.*|xkcd\.com\/.*|www\.xkcd\.com\/.*|www\.asofterworld\.com\/index\.php\?id=.*|www\.qwantz\.com\/index\.php\?comic=.*|23hq\.com\/.*\/photo\/.*|www\.23hq\.com\/.*\/photo\/.*|.*dribbble\.com\/shots\/.*|drbl\.in\/.*|.*\.smugmug\.com\/.*|.*\.smugmug\.com\/.*#.*|emberapp\.com\/.*\/images\/.*|emberapp\.com\/.*\/images\/.*\/sizes\/.*|emberapp\.com\/.*\/collections\/.*\/.*|emberapp\.com\/.*\/categories\/.*\/.*\/.*|embr\.it\/.*|picasaweb\.google\.com.*\/.*\/.*#.*|picasaweb\.google\.com.*\/lh\/photo\/.*|picasaweb\.google\.com.*\/.*\/.*|dailybooth\.com\/.*\/.*|brizzly\.com\/pic\/.*|pics\.brizzly\.com\/.*\.jpg|img\.ly\/.*|www\.facebook\.com\/photo\.php.*|www\.tinypic\.com\/view\.php.*|tinypic\.com\/view\.php.*|www\.tinypic\.com\/player\.php.*|tinypic\.com\/player\.php.*|www\.tinypic\.com\/r\/.*\/.*|tinypic\.com\/r\/.*\/.*|.*\.tinypic\.com\/.*\.jpg|.*\.tinypic\.com\/.*\.png|meadd\.com\/.*\/.*|meadd\.com\/.*|.*\.deviantart\.com\/art\/.*|.*\.deviantart\.com\/gallery\/.*|.*\.deviantart\.com\/#\/.*|fav\.me\/.*|.*\.deviantart\.com|.*\.deviantart\.com\/gallery|.*\.deviantart\.com\/.*\/.*\.jpg|.*\.deviantart\.com\/.*\/.*\.gif|.*\.deviantart\.net\/.*\/.*\.jpg|.*\.deviantart\.net\/.*\/.*\.gif|www\.whitehouse\.gov\/photos-and-video\/video\/.*|www\.whitehouse\.gov\/video\/.*|wh\.gov\/photos-and-video\/video\/.*|wh\.gov\/video\/.*|www\.hulu\.com\/watch.*|www\.hulu\.com\/w\/.*|hulu\.com\/watch.*|hulu\.com\/w\/.*|movieclips\.com\/watch\/.*\/.*\/|movieclips\.com\/watch\/.*\/.*\/.*\/.*|.*crackle\.com\/c\/.*|www\.fancast\.com\/.*\/videos|www\.funnyordie\.com\/videos\/.*|www\.vimeo\.com\/groups\/.*\/videos\/.*|www\.vimeo\.com\/.*|vimeo\.com\/groups\/.*\/videos\/.*|vimeo\.com\/.*|www\.ted\.com\/talks\/.*\.html.*|www\.ted\.com\/talks\/lang\/.*\/.*\.html.*|www\.ted\.com\/index\.php\/talks\/.*\.html.*|www\.ted\.com\/index\.php\/talks\/lang\/.*\/.*\.html.*|.*omnisio\.com\/.*|.*nfb\.ca\/film\/.*|www\.thedailyshow\.com\/watch\/.*|www\.thedailyshow\.com\/full-episodes\/.*|www\.thedailyshow\.com\/collection\/.*\/.*\/.*|movies\.yahoo\.com\/movie\/.*\/video\/.*|movies\.yahoo\.com\/movie\/.*\/info|movies\.yahoo\.com\/movie\/.*\/trailer|www\.colbertnation\.com\/the-colbert-report-collections\/.*|www\.colbertnation\.com\/full-episodes\/.*|www\.colbertnation\.com\/the-colbert-report-videos\/.*|www\.comedycentral\.com\/videos\/index\.jhtml\?.*|www\.theonion\.com\/video\/.*|theonion\.com\/video\/.*|wordpress\.tv\/.*\/.*\/.*\/.*\/|www\.traileraddict\.com\/trailer\/.*|www\.traileraddict\.com\/clip\/.*|www\.traileraddict\.com\/poster\/.*|www\.escapistmagazine\.com\/videos\/.*|www\.trailerspy\.com\/trailer\/.*\/.*|www\.trailerspy\.com\/trailer\/.*|www\.trailerspy\.com\/view_video\.php.*|www\.atom\.com\/.*\/.*\/|fora\.tv\/.*\/.*\/.*\/.*|www\.spike\.com\/video\/.*|www\.gametrailers\.com\/video\/.*|gametrailers\.com\/video\/.*|www\.koldcast\.tv\/video\/.*|www\.koldcast\.tv\/#video:.*|techcrunch\.tv\/watch.*|techcrunch\.tv\/.*\/watch.*|www\.godtube\.com\/featured\/video\/.*|www\.tangle\.com\/view_video.*|soundcloud\.com\/.*|soundcloud\.com\/.*\/.*|soundcloud\.com\/.*\/sets\/.*|soundcloud\.com\/groups\/.*|www\\.last\\.fm\/music\/.*|www\\.last\\.fm\/music\/+videos\/.*|www\\.last\\.fm\/music\/+images\/.*|www\\.last\\.fm\/music\/.*\/_\/.*|www\\.last\\.fm\/music\/.*\/.*|www\.mixcloud\.com\/.*\/.*\/|espn\.go\.com\/video\/clip.*|espn\.go\.com\/.*\/story.*|cnbc\.com\/id\/.*|cbsnews\.com\/video\/watch\/.*|www\.cnn\.com\/video\/.*|edition\.cnn\.com\/video\/.*|money\.cnn\.com\/video\/.*|today\.msnbc\.msn\.com\/id\/.*\/vp\/.*|www\.msnbc\.msn\.com\/id\/.*\/vp\/.*|www\.msnbc\.msn\.com\/id\/.*\/ns\/.*|today\.msnbc\.msn\.com\/id\/.*\/ns\/.*|multimedia\.foxsports\.com\/m\/video\/.*\/.*|msn\.foxsports\.com\/video.*|.*amazon\..*\/gp\/product\/.*|.*amazon\..*\/.*\/dp\/.*|.*amazon\..*\/dp\/.*|.*amazon\..*\/o\/ASIN\/.*|.*amazon\..*\/gp\/offer-listing\/.*|.*amazon\..*\/.*\/ASIN\/.*|.*amazon\..*\/gp\/product\/images\/.*|www\.amzn\.com\/.*|amzn\.com\/.*|www\.shopstyle\.com\/browse.*|www\.shopstyle\.com\/action\/apiVisitRetailer.*|www\.shopstyle\.com\/action\/viewLook.*|gist\.github\.com\/.*|twitter\.com\/.*\/status\/.*|twitter\.com\/.*\/statuses\/.*|www\.crunchbase\.com\/.*\/.*|crunchbase\.com\/.*\/.*|www\.slideshare\.net\/.*\/.*|.*\.scribd\.com\/doc\/.*|screenr\.com\/.*|polldaddy\.com\/community\/poll\/.*|polldaddy\.com\/poll\/.*|answers\.polldaddy\.com\/poll\/.*|www\.5min\.com\/Video\/.*|www\.howcast\.com\/videos\/.*|www\.screencast\.com\/.*\/media\/.*|screencast\.com\/.*\/media\/.*|www\.screencast\.com\/t\/.*|screencast\.com\/t\/.*|issuu\.com\/.*\/docs\/.*|www\.kickstarter\.com\/projects\/.*\/.*|www\.scrapblog\.com\/viewer\/viewer\.aspx.*|my\.opera\.com\/.*\/albums\/show\.dml\?id=.*|my\.opera\.com\/.*\/albums\/showpic\.dml\?album=.*&picture=.*|tumblr\.com\/.*|.*\.tumblr\.com\/post\/.*|www\.polleverywhere\.com\/polls\/.*|www\.polleverywhere\.com\/multiple_choice_polls\/.*|www\.polleverywhere\.com\/free_text_polls\/.*|www\.quantcast\.com\/wd:.*|www\.quantcast\.com\/.*|siteanalytics\.compete\.com\/.*|.*\.status\.net\/notice\/.*|identi\.ca\/notice\/.*|shitmydadsays\.com\/notice\/.*|wrttn\.me\/.*)[^<'\"]/i";
	preg_match_all($embedly_re, $plan, $matches, PREG_PATTERN_ORDER);
	$safeplan=$plan;
	$plan=plan_process_oembed($plan,$matches[1]);
	if(!trim($plan)) $plan=$safeplan;

	return $plan;
}

function plan_strip_css($plan)
{
	$plan=preg_replace("/style=['\"][^'\"]*['\"]/","",$plan);
	$plan=preg_replace("|<style[^<]*</style>|ism","",$plan);
	return $plan;
}

function plan_strip_js($plan)
{
	$plan=preg_replace("/on\w*=['\"][^'\"]*['\"]/","",$plan);
	$plan=preg_replace("|<script[^<]*>|isU","",$plan);
	return $plan;
}

function plan_confine_css($plan)
{
	if(IS_JOSH)
	{
		$plan=preg_replace_callback("|<style[^<]*>([^<]*)</style>|ism",function($matches)
		{
			$match=$matches[1];
			$clean_styleblock=str_replace("body","#content",$match);
			$clean_styleblock=str_replace(".content","#content",$clean_styleblock);
			$clean_styleblock=str_replace(array(".navbar",'.planwatch','.header'),".confined",$clean_styleblock);
			$clean_styleblock=preg_replace("|\s*(.*)\s+\{(.*)\}|mU","#content \\1 { \\2 } \n",$clean_styleblock);
			$clean_styleblock=str_replace("#content #content","#content",$clean_styleblock);
			return "<style>$clean_styleblock</style>";
		},$plan);

		return $plan;
	}
	else
	{
		$stylebegin=strpos($plan,"<style");
		$stylebeginend=strpos($plan,">",$stylebegin);
		$styleend=strpos($plan,"</style>");
		$styletag=substr($plan,$stylebegin,$stylebeginend-$stylebegin);
		$styleblock=substr($plan,$stylebeginend,$styleend-$stylebegin+8);
		$clean_styleblock=str_replace("body","#content",$styleblock);
		$clean_styleblock=str_replace(".content","#content",$clean_styleblock);
		$clean_styleblock=str_replace(array(".navbar",'.planwatch','.header'),".confined",$clean_styleblock);
		$clean_styleblock=preg_replace("|\s*(.*)\s+\{(.*)\}|mU","#content \\1 { \\2 } \n",$clean_styleblock);
	//	$clean_styleblock=preg_replace("|\s+.*)\s+\{(.*)\}|mU","#content \\1 { \\2 } \n",$clean_styleblock);
		$clean_styleblock=str_replace("#content #content","#content",$clean_styleblock);

		return str_replace($styleblock,$clean_styleblock,$plan);
	}
}

function source_is_crossnode($source)
{
	if (strpos($source,'@')===FALSE) return FALSE;
	if (strpos($source,'@p')!==FALSE && strpos($source,'@planwatch')===FALSE) return "@pwn";
	if (strpos($source,'@n')!==FALSE || strpos($source,'@a')!==FALSE) return "@note";
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

		if (strpos($source,'://')!==FALSE)
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
			if (strpos($entry,$plan_dir)!==FALSE)
			{
				$entry=stripslashes(file_get_contents($entry));
				if (strpos($entry,'<!--markdown-->')!==FALSE) { include_once('markdown.php'); $entry=Markdown($entry); }
				elseif (strpos($entry,'nolinebreaks')===FALSE) $entry=smart_nl2br($entry);
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

	if (strpos($url,'://')===FALSE)	$url=plan_get_real_location($url);

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
		if ($_SERVER['USER']=='guest') { output('unauthorized',"<h1>Unauthorized</h1>You can't read offsite plans if you're not logged in."); exit; }


		$f=new xmlrpcmsg('planworld.plan.getContent');
		$f->addParam(new xmlrpcval($remoteuser, "string"));
		$f->addParam(new xmlrpcval($_SERVER['USER']."@planwatch.org", "string"));
		$f->addParam(new xmlrpcval($snitch, "boolean"));

		if(!$nodeinfo['method']) $nodeinfo['method']='http';
		$c=new xmlrpc_client($nodeinfo["directory"], $nodeinfo["server"], $nodeinfo["port"], $nodeinfo['method']);
//		$c->setDebug(isjosh());
		$c->setDebug(FALSE);
		$r=$c->send($f,0.5,$nodeinfo['method']);

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
			for updates or email <a href='mailto:joshuawdavidson@gmail.com'>Josh</a> (who is no longer really the admin but sometimes fixes bugs).<hr />
			Here's the error message in case you need to <a href='/features'>file a bug</a>:";

			$plan.="Code: " . $r->faultCode() . '"' . $r->faultString() . '"';
			}
		}
	}
	else $plan=plan_read_finger($remoteuser.$remotenode);

	if (strpos($plan,'plan is not available')!==FALSE) $plan="$remoteuser@$remotenode does not allow plan reads outside $nodeinfo[server].<br />You can still <a href='/send/$remoteuser$remotenode'>send a message</a> if you want to establish contact.";

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

		if (!isset($invite_response))
		{
			$invite_response="<h1>Invite a friend to planwatch.org</h1>Copy and paste this invitation address into an email, IM, or communication medium of your choice:<br /><br /> \n\n<strong><a href='//planwatch.org/user/accept_invite/$requester/$invite_code'>https://planwatch.org/user/accept_invite/$requester/$invite_code </a></strong><br /><br />\n\nProblems? Ask <a href='mailto:help@planwatch.org'>help@planwatch.org</a>

			<div style='border: thin solid black; width: 300px; padding: 10px; margin-top: 20px; margin-bottom: 20px;' ><h3>Mail this invitation</h3>
			<form id='inviteform' action='//planwatch.org/scripts/form_shim.php' method='post'>
			<input type='hidden' name='invite_url' value='//planwatch.org/user/accept_invite/$requester/$invite_code' />
			<input type='hidden' name='requester' value='$requester' />
			<input type='text' name='recipient' value='email@address.com' />
			<br />Add a personal message (optional):<br /><textarea name='personal' style='width: 270px; height: 200px;'></textarea>
			<input type='submit' name='send email' />
			</form>
			</div>
			";
		}
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
		<iframe style='background: transparent; width: 100%; height: 500px; border: 0px;' src='//planwatch.org/xn_backup/setup/$_SERVER[USER]'></iframe>
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
		if (!isset($_SERVER['REMOTENODE']))
		{
			$planprefix='/read/';
			$remoteaddition='';
		}
		else
		{
			$planprefix='?id=';
			if (strpos($item,'@')===FALSE) $remoteaddition='@planwatch.org';
			else $remoteaddition='';
			$item=str_replace($_SERVER['REMOTENODE'],'',$item);
		}


		if ($alias && strpos($plan,$alias)!==FALSE)
		{
			if (((OUTPUT_MODE=='HTML' || OUTPUT_MODE=='MOBILE') && $_SERVER['USER'] && $_SERVER['USER']!='guest')
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

	$plan=str_replace('<!--','<!!--',$plan);

	if (strpos($plan,'feed:')!==FALSE)
		$plan=preg_replace("/(\W+)!(feed:\S+)[:]([^!]+)!(\W+)/","\\1<a target='_self' href='//planwatch.org/read/\\2' title='\\2'>\\3</a>\\4",$plan);
	if (strpos($plan,'http')!==FALSE)
		$plan=preg_replace("/(\W+)!(https?:\S+)[:]([^!]+)!(\W+)/","\\1<a target='_self' href='\\2' title='\\2'>\\3</a>\\4",$plan);
	if (strpos($plan,'!link:')!==FALSE)
		$plan=preg_replace("/(\W+)!link:(\S+)[:]([^!]+)!(\W+)/","\\1<a target='_self' href='\\2' title='\\2'>\\3</a>\\4",$plan);

	if (strpos($plan,'!email:')!==FALSE)
		$plan=preg_replace("/(\W+)!email:(\S+)[:]([^!]+)!(\W+)/","\\1<a href='mailto:\\2' title='email \\2'>\\3</a>\\4",$plan);

	profile("plan_filter_linked_users");
	if (strpos($plan,'!')!==FALSE)
	{
		$plan=preg_replace_callback("/!([\w@%.]+):([^!]+)!/",'plan_filter_linked_users',$plan);
		$plan=preg_replace_callback("/!([\w@%.]+)!/",'plan_filter_linked_users',$plan);
	}
	profile("plan_filter_linked_users");

	$plan=str_replace('<!!--','<!--',$plan);

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

	$planwriter = $_SERVER['PLANOWNER'];
	$remoteaddition='';
	if(strstr($_SERVER['PLANOWNER'],'@'))
	{
		list($planwriter,$remoteaddition)=explode("@",$_SERVER['PLANOWNER']);
		if ($remoteaddition)
		{
			$remoteaddition="@$remoteaddition";
		}
	}

	if (!isset($_SERVER['REMOTENODE']))  // if the reader is local
	{
		$planprefix='/read/';
		if (strpos($matches[1],'@')!==FALSE)
		{
			$remoteaddition=''; // if it's a link to a third node, don't add the node specifier from the plan owner
		}
		$matches[1]=str_replace('@planwatch.org','',$matches[1]);
	}
	else
	// if the reader is offnode, we ideally shouldn't be running this at all.
	// requires further study.
	//TODO: figure out linking for offnode readers.
	{
		$planprefix='?id=';
		if (strpos($matches[1],'@')!==FALSE) $remoteaddition='';
		else $remoteaddition='@planwatch.org';
	}

	$loopkey=rand();
	profile("pflu_privacytest_$loopkey!");
	if ((!plan_is_registered_only($matches[1]) && !plan_is_private($matches[1]))
		|| ((OUTPUT_MODE=='HTML' || OUTPUT_MODE=='MOBILE') && $_SERVER['USER'] && $_SERVER['USER']!='guest'))
	{
		if (isset($matches[2]))
		{
			return "<a target='_self' href='$_SERVER[WEB_ROOT]$planprefix$matches[1]$remoteaddition' title='$matches[1]'>$matches[2]</a>";
		}
		else
		{
			return "<a target='_self' href='$_SERVER[WEB_ROOT]$planprefix$matches[1]$remoteaddition' title='$matches[1]'>$matches[1]</a>";
		}
	}
	else
	{
		if ($matches[2]) return "$matches[2]";
		else return "";
	}
	profile("pflu_privacytest_$loopkey!");
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
	$opl=$planowner;
	plan_get_owner_info($planowner);


	$reader=$_SERVER['USER'];
	$utility=false;
	if ($reader=='cacheuser') {$utility=1;}


	if ($planowner=='invite' || $planowner=='invite@planwatch.org')
	{
		return plan_read_invite($reader);
	}

	if ($planowner=='backup' || $planowner=='backup@planwatch.org')
	{
		return plan_read_backup($reader);
	}

	if ($planowner=='backup_archives' || $planowner=='backup_archives@planwatch.org')
	{
		return plan_read_backup($reader,"archives");
	}

	$plan_read_rand=rand();

	if (!isset($_SERVER['PLANOWNER_INFO_ARRAY']) || $_SERVER['PLANOWNER']!=$planowner)
	{
		plan_get_owner_info($planowner);
	}

	profile("plan_read_$plan_read_rand");
	profile("plan_read_head_$plan_read_rand");
	$islocal=false;
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

	if (isset($_SERVER['REMOTENODE']))
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

	$localplan='';
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
		$plan='';
	    if ($islocal)
	    {
	        $plan=plan_read_local($planowner,$threshhold,$begindate,$unformatted);
	    }

		if (strpos($planowner,'@')!==FALSE && !strpos($planowner,'://') && empty($plan))
		{
			// if it comes from another planworld node (we used to fingertest in here too)
			list($xmlrpc_username,$xmlrpc_node)=explode('@',$planowner);

			$plan=plan_read_xmlrpc($xmlrpc_username,$xmlrpc_node);
		}

		if(strpos($planowner,"neon.note.amherst.edu/bugzilla/show") && empty($plan))
		{
			$plan=plan_read_bug($planowner);
		}

		if (strpos($planowner,'://') && !$plan)
		{
			// if it's a URL call plan_read_web()
			// plan_read_web passes it on to plan_read_rss_simplepie if necessary.
			$url=$planowner;
			$plan=plan_read_web($url);
		}

		// Valid snitches only get set here
		$default_snitch_length = isset($_SERVER['PLANOWNER_INFO_ARRAY']) ? $_SERVER['PLANOWNER_INFO_ARRAY']['defaultdays'] : 2;
		if ((!isset($threshhold) || $threshhold=='2d' || $threshhold==$default_snitch_length.'d') && !$begindate)
		{
			snitch_write($reader,$planowner);
		}
		else
		{
			snitch_write($reader,$planowner," archives ( $threshhold $begindate )");
		}

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
			if(plan_is_snobwalled($reader,$planowner))
			{
				$plan="<h1>Fault: Code: 800 No Such User</h1>No user found by that name. Please try a different spelling.";
			}
		}
		snitch_write($reader,$planowner,' failed');
		if (file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[USER]/userinfo.dat")) user_update_lastread($planowner);
	}


	profile("plan_format_$plan_read_rand");
	if(!$utility && !$unformatted)
	{
		profile("plan_read_tail_$plan_read_rand");

		profile("plan_add_links_$plan_read_rand");
		$plan=plan_add_alias_links($plan,$planowner);
		if (!isset($_SERVER['REMOTENODE']))
		{
			$plan=spiel_format($plan,$planowner);
			$plan=plan_add_user_links($plan);
		}
		else
		{
			preg_replace("|!sp[ie][ie]l:(.*):(.*)!|","<a href='//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/spiel/view/\\1'>\\2</a>",$plan);
			preg_replace("|!sp[ei][ie]l:(.*)!|","<a href='//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/spiel/view/\\1'>\\1</a>",$plan);
			$plan=plan_process_directives($plan,TRUE); // turn on remotepatch to avoid processing directives other nodes will handle
			if(strpos($plan,"aside")!==FALSE) $plan="<style>aside.sidebar { float: right; width: 200px; margin-left: 20px; }</style>$plan";
			$plan=plan_strip_js($plan);
		}
		profile("plan_add_links_$plan_read_rand");

//		if(strpos($plan,'http://')!==FALSE && plan_is_local($planowner)) { $plan=plan_match_embed_urls($plan); $plan="embeds on".$plan; }

		profile("plan_embeds_$plan_read_rand");
		if(OUTPUT_MODE=='MOBILE' || OUTPUT_MODE=='HTML')
		{
 			$plan=plan_embed_player($plan);
			$plan=str_replace(array("id=\"content\"","id='content'"),"id='content2'",$plan);
			$plan=str_replace("#content","#content2",$plan);
			$plan=str_replace("font-color:","color:",$plan);
			$plan=str_replace("position: absolute","position: relative;",$plan);
// 			$plan=hyperlink($plan);
		}
		profile("plan_embeds_$plan_read_rand");

		if(strpos($plan,"/read/$_SERVER[USER]"))
		{
			$plan=str_replace("<a target='_self' href='/read/$_SERVER[USER]'","<a target='_self' id='snoop' href='/read/$_SERVER[USER]'",$plan);
		}

		profile("plan_snoopid_$plan_read_rand");
		$plan=str_replace("@note","@amherst.edu",$plan);
		$plan=str_replace("@pwn","@planworld.net",$plan);

		$remoteuser=$_SERVER['USER'];
		if(isset($_SERVER['REMOTENODE']))
		{
			$remoteuser=str_replace($_SERVER['REMOTENODE'],"",$_SERVER['USER']);
		}

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
			if(OUTPUT_MODE=='HTML')
				$plan="<a onclick='document.getElementById(\"snoop\").scrollIntoView();'>Find My Snoop</a><br />\n$plan";
			if(OUTPUT_MODE=='MOBILE')
				$plan="<button onclick='document.getElementById(\"snoop\").scrollIntoView();'>Find My Snoop</button><br />\n$plan";
		}
		profile("plan_snoopid_$plan_read_rand");


		profile("plan_strip_css_$plan_read_rand");
		if($_SERVER['USERINFO_ARRAY']['strip_css']==1) $plan=plan_strip_css($plan);
		if($_SERVER['USERINFO_ARRAY']['strip_css']==2) $plan=plan_confine_css($plan);
		profile("plan_strip_css_$plan_read_rand");

	}



	profile('encoding');
	if (!isset($url))
	{
		$encoding=mb_detect_encoding($plan,'UTF-8, ISO-8859-1');
		if ($encoding!=='UTF-8') $plan=mb_convert_encoding($plan,'UTF-8',$encoding);
	}

	if(strpos($plan,'<article')===FALSE && (OUTPUT_MODE=='MOBILE' || OUTPUT_MODE=='HTML'))
	{
		$plan="<article id='plan_body_$_SERVER[PLANOWNER]'>$plan</article>";
	}

	profile('encoding');
	profile("plan_read_tail_$plan_read_rand");
	profile("plan_read_$plan_read_rand");

	profile("plan_format_$plan_read_rand");

	//fix 80-column plans for mobile
	if(stristr($plan,'<PRE>'))
	{
		$plan="<style>pre { white-space: pre-wrap; }</style><!-- pre wrap added by planwatch.org, ask josh if this bugs you -->\n\n".$plan;
	}

	return $plan;
}

function plan_read_bug($url)
{
	$cachefn=md5($url).".bug.cache";

	// if the cache is less than 30m old, read that in. otherwise get the real thing.
	if(file_exists("$_SERVER[DOCUMENT_ROOT]/temp/$cachefn") && (filemtime("$_SERVER[DOCUMENT_ROOT]/temp/$cachefn")>(time()-3600)))
	{
		$cached=TRUE;
		$data=file_get_contents("$_SERVER[DOCUMENT_ROOT]/temp/$cachefn");
	}
	else
	{
		$data=file_get_contents($url);
	}

	$xml=simplexml_load_string($data);

	$title=$xml->bug->short_desc;
	$lastupdate=strtotime($xml->bug->delta_ts);
	$headerinfo=($xml->bug->bug_severity)." for ".$xml->bug->product." / ".$xml->bug->component;
	$author=$xml->bug->reporter;
	$id=$xml->bug->bug_id;
	$comments=$xml->bug->long_desc;
	foreach($comments as $comment)
	{
		$content.="<p>".$comment->who." at ".formattime(strtotime($comment->bug_when))."<br />".$comment->thetext."</p>";
	}
	$plan="
	<h1><a href='http://neon.note.amherst.edu/bugzilla/show_bug.cgi?id=$id'>$title</a></h1><h3>$headerinfo</h3>$author, updated ".formattime($lastupdate)."<br /><br />
	$content
	<a href='http://neon.note.amherst.edu/bugzilla/show_bug.cgi?id=$id'>see original</a>";

	if(!$cached) file_put_contents("$_SERVER[DOCUMENT_ROOT]/temp/$cachefn",$data);

//	header("content-type: text/plain");
//	print_r($xml->bug); exit;


	return $plan;

}


function plan_embed_player($plan_text)
{
	$object_block="
			<audio controls preload='metadata'>
				<source src='FILE_URL' />
			</audio>
";
	preg_match_all("|<a[\s]*href=['\"]([^<]*\.mp3)['\"][^<]*</a>|",$plan_text,$matches);
	foreach($matches[1] as $i=>$song)
	{
		$new_object_block=str_replace("FILE_URL","$song",$object_block)." {$matches[0][$i]}";
		$plan_text=str_replace($matches[0][$i],$new_object_block,$plan_text);
	}

	return $plan_text;
}

function plan_embed_enclosure($plan_text)
{
	if(OUTPUT_MODE=='ATOM')
	$object_block="
			<link rel=\"enclosure\" href=\"FILE_URL\"
                 length=\"FILE_LENGTH\"
                 type=\"FILE_MIME_TYPE\" />
";
	if(OUTPUT_MODE=='RSS')
	$object_block="
			<enclosure url=\"FILE_URL\"
                 length=\"FILE_LENGTH\"
                 type=\"FILE_MIME_TYPE\" />
";

	preg_match_all("|<a[\s]*href=['\"]([^<]*\.mp3)['\"][^<]*</a>|",$plan_text,$matches);
	foreach($matches[1] as $i=>$song)
	{
		$urlinfo=urlinfo(trim($song));
		$new_object_block=str_replace("FILE_URL","$song",$object_block);
		$new_object_block=str_replace("FILE_LENGTH",trim($urlinfo['Content-Length']),$new_object_block);
		$new_object_block=str_replace("FILE_MIME_TYPE",trim($urlinfo['Content-Type']),$new_object_block);
		$plan_text=str_replace($matches[0][$i],$new_object_block,$plan_text);
	}

	return $new_object_block;
}


// PLAN_READ_XMLRPC()
//
// uses xml-rpc to get plans from another planworld node
//------------------------------------------------------------------------------
function plan_read_xmlrpc($remoteuser='bob',$remotenode='@note',$reportsnitch=TRUE)
{
	if($nodeinfo=planworld_node_getinfo($remotenode))
	{
		// print_r($nodeinfo);
		// exit;
		if ($reportsnitch && $_SERVER['USERINFO_ARRAY']['snitchlevel']>0) $snitch=TRUE; else $snitch=FALSE;
		if (!$_SERVER['USER'] || $_SERVER['USER']=='guest' || $_SERVER['USER']=='Guest') { output('unauthorized',"you can't read offsite plans if you're not logged in."); exit; }


		$f=new xmlrpcmsg('planworld.plan.getContent');
		$f->addParam(new xmlrpcval($remoteuser, "string"));
		$f->addParam(new xmlrpcval($_SERVER['USER']."@planwatch.org", "string"));
		$f->addParam(new xmlrpcval($snitch, "boolean"));

		if(!isset($nodeinfo['method'])) { $nodeinfo['method']='http'; }
		$c=new xmlrpc_client($nodeinfo["directory"], $nodeinfo["server"], $nodeinfo["port"], $nodeinfo['method']);
		$c->setDebug(FALSE);
//		if($remotenode=="@planwatch.org") 		$c->setDebug(TRUE);
		$r=$c->send($f,1.5,$nodeinfo['method']);

		// print_r($r);

		// print_r($c);

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
			// $plan.=" is updating " .rand();
		}
		if(OUTPUT_MODE=='MOBILE' && strpos($plan,"</pre")!==FALSE)
		{
			$plan=str_replace(array("<pre>","</pre>"),array("<section class='pre'>","</section>"),$plan);
			$plan=smart_nl2br($plan,TRUE);
		}

	}
	else $plan=plan_read_finger($remoteuser.$remotenode);

	if (strpos($plan,'plan is not available]')!==FALSE) $plan="$remoteuser@$remotenode does not allow plan reads outside $nodeinfo[server]. <!-- $plan -->";

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

	exec("rg -i -C 2 '$keyword' $_SERVER[PWUSERS_DIR]/$writer/plan/",$resultlist);

	foreach($resultlist as $result)
	{
		if ((strpos($result,'hidden.')===FALSE && strpos($result,'rem.')===FALSE) || $writer==$reader)
		{
			if (!strpos($result,'txt.p') || $private)
			{
				$result_tc=false;
				list($result_fn,$result_text)=explode(':',stripslashes($result),2);
				$result_fn_parts = explode(".",basename($result_fn));
				foreach($result_fn_parts as $part) { if(is_numeric($part)) { $result_tc = $part; } }
				//list($junk,$result_tc,$morejunk)=explode('.',basename($result_fn));

				if (trim($result_text) && is_numeric($result_tc))
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

	$divider_filename="$_SERVER[PWUSERS_DIR]/$writer/plan/plandivider.txt";

	if (file_exists($divider_filename))
	{
		$divider=stripslashes(file_get_contents($divider_filename));
	}

	foreach($entries as $entry_time=>$entry_text)
	{
		$entry_header  = plan_prepare_divider($writer,$reader,$divider,$entry_time,$entries[$entry_time],$entry_text,$entry_nolinebreaks);

		$plan_content .= plan_prepare_entry($entry_time,$entry_header,$entry_text);

	}
	$content="\n<article id='plan_body'>\n$plan_content\n</article>";

return $content;
}





// PLAN_READ_LOCAL()
//
// gets a local plan
//------------------------------------------------------------------------------
function plan_read_local($planowner,$threshhold=FALSE,$begindate='',$source=FALSE)
{

	$prl_rand=rand();
	$planowner_info = plan_get_owner_info($planowner);
	profile("plan_read_local_$prl_rand");
	profile('plan_read_local_head');

	$reader = $_SERVER['USER'];

	if (!$threshhold) {$threshhold=$_SERVER['PLANOWNER_INFO_ARRAY']['defaultdays'].'d';}

	// this also appears in caching
	if (user_is_authorized($planowner,$reader))
	{
		$private="*"; $private_cache_fn='private';
	}

	// this also appears in caching
	$writer_cache_fn='';
	if (plan_user_may_write($planowner,$reader))
	{
		$writer_cache_fn='writer';
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
	$feed='';
	$remote='';
	if (OUTPUT_MODE=='RSS')
	{
		$feed='feed';
	}
	if (OUTPUT_MODE=='ATOM')
	{
		$feed='atomfeed';
	}
	if (OUTPUT_MODE=='ATOM_PRIVATE')
	{
		$feed=rand(100,999);
	}
	if (OUTPUT_MODE=='MOBILE')
	{
		$feed='phonefeed';
	}
	profile('plan_read_local_uia');
	$private_cache_fn='';
	$private='';
	if (user_is_authorized($planowner,$reader))
	{
		$private="*"; $private_cache_fn='private';
	}
	profile('plan_read_local_uia');
	profile('plan_read_local_utp');
	$blocked_cache_fn='';
	if (!plan_test_privacy($reader,$planowner))
	{
		$blocked_cache_fn='blocked';
	}
	profile('plan_read_local_utp');

	$fn_username=base64_encode($planowner);
	$self='';
	if ($planowner==$reader)
	{
		$self='self';
	}

	// if ($remoteuser=='rss reader')
	// {
	// 	$feed='feed';
	// }
	$mobile='';
	if (OUTPUT_MODE=='MOBILE')
	{
		$mobile='mobile';
	}
	// if (isset($source)) {$source="source";}

	$cache_fn="$_SERVER[FILE_ROOT]/temp/{$_SERVER['PLANOWNER_INFO_ARRAY']['salt']}.$self$private_cache_fn$writer_cache_fn$blocked_cache_fn$remote$feed.$threshhold.$begindate.$source.$mobile.cache";

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
		if (strpos($threshhold,'r')!==FALSE) { $threshhold=str_replace('r','',$threshhold); $reverse=1; }

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
		$result=array();
		if (file_exists($styles_fn) && !file_exists($skin_fn) && !file_exists($colors_fn))
		{
			parse_str(file_get_contents($styles_fn),$result);
			extract($result);
			if ($skin && file_exists("$_SERVER[FILE_ROOT]/resources/skins/$skin")) {include($skin);}
		}
		if (file_exists($skin_fn))
		{
			parse_str(file_get_contents($skin_fn),$result);
			extract($result);
			if ($skin) { include($skin); }
		}
		if (file_exists($colors_fn))
			{parse_str(file_get_contents($colors_fn),$result); extract($result); }
		if (file_exists($fonts_fn))
			{parse_str(file_get_contents($fonts_fn),$result); extract($result); }
		if (file_exists($css_fn))
			{eval(file_get_contents($css_fn));}
	}
	profile('plan_read_local_styles');



	// screen out blocked readers, readers that fall below
	// privacy levels set by planowner
	if (plan_test_privacy($reader,$planowner)<1)
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
		if (strpos($threshhold,',')!==FALSE)
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
			if ("$threshholdline"[0]=='.') {$limiter=$threshholdline;}
			else $limiter='.';


			//sets dir for reading
			if(!empty($_SERVER['PLANOWNER_ROOT'])) { $plan_dir="$_SERVER[PLANOWNER_ROOT]/plan/"; }
			else { $plan_dir="$_SERVER[USER_ROOT]/plan/"; }

			// gets the entries indicated by $limiter
			// if $limiter is unset, fills the array with all available entries
			$plan_array=array_merge($plan_array,files_list($plan_dir,"plan$limiter*.txt$private"));
			if (is_array($plan_array) && "$threshholdline"[0]=='.')
			{
				$begindate=1;
				$enddate=time()+1;
			}
			if(!is_array($plan_array)) { $plan_array = array(); }
			/* else { $plan_array = array(); } */
		}

		// get all the plan 'dressing' -- sidebars, header, footer, etc.
		$header_filename="$_SERVER[PLANOWNER_ROOT]/plan/planheader.txt";
		if (file_exists($header_filename))
		{
			$plan_dressing['header']=stripslashes(file_get_contents($header_filename));
			if(strpos($plan_dressing['header'],'nolinebreaks')!==FALSE)
			{
				$plan_dressing['header']=str_replace(array("<--nolinebreaks-->","\n"),"",$plan_dressing['header']);
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
			if(strpos($plan_dressing['footer'],'nolinebreaks')!==FALSE)
			{
				$plan_dressing['footer']=str_replace(array("<--nolinebreaks-->","\n"),"",$plan_dressing['footer']);
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

		if (isset($plan_dressing))
		{
			foreach($plan_dressing as $area=>$content)
			{
				$content=stripslashes($content);
				if (strpos($content,'nolinebreaks')!==FALSE)
				{
					$content=str_replace('#nolinebreaks#','',$content);
	//				$content=str_replace('nolinebreaks','',$content);
				}
				else
				{
					$content=smart_nl2br($content);
				}

				$plan_dressing[$area]=$content;
			}
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
				if(!empty($planfn))
				{
					$planfn_a=explode(".",$planfn);
					if(isset($plan_array[$z+1])) { $nextplanfn_a=explode(".",$plan_array[$z+1]); }
					else $nextplanfn_a = ['','',''];

					if($z>0) {$lastplanfn_a=explode(".",$plan_array[$z-1]);}
					else $lastplanfn_a = ['','',''];

					if ((($planfn_a[1] >= ($begindate)
						&& $planfn_a[1]<=($enddate))
						|| ($nextplanfn_a[1] >= ($begindate)
							&& $nextplanfn_a[1]<=($enddate))
						|| ($lastplanfn_a[1] >= ($begindate)
							&& $lastplanfn_a[1]<=($enddate)))
						&& strpos($planfn,'fake')===FALSE
					)
					{
						$plan_index_array[$planfn_a[1]]=$planfn;
					}
				}
			}

			// if we don't have any entries, give up now
			if (!is_array($plan_index_array)) return "no plan entries found.";


			// sort the plan list in order of update time newest to oldest.
			// we'll account for $reverse later, when we're
			// actually writing the entries to strings.
			krsort($plan_index_array);

			$plan_entries=[];
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
			if ((!isset($plan_entries) || !is_array($plan_entries)) && $enddate > (time()-15))
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
			if ((!isset($plan_entries) || !is_array($plan_entries)) && $enddate < (time()-15))
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

			if(is_array($plan_entries))
			{
				if (isset($reverse) && $reverse) { ksort($plan_entries); }
				else { krsort($plan_entries); }
			}

			$plan_content=$plan='';

			// if we're ouputting to HTML, put it all together in tasty DIVs.
			if (OUTPUT_MODE=='HTML' || OUTPUT_MODE=='MOBILE' || OUTPUT_MODE=='AJAX')
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

				$plan.=$plan_dressing['header']."\n<article class='plan_body' id='plan_body_$_SERVER[PLANOWNER]'>\n$plan_content\n</article>".$plan_dressing['footer'];
			}

			// if we're building a feed, we want to do valid XML goodness
			if (OUTPUT_MODE=='RSS')
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

					$rss_link="//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner";
					$entry_text=plan_add_user_links(plan_add_alias_links($entry_text,$planowner));
					list($entry,$li) = plan_encapsulate_rss($planowner,$entry_time,$entry_text);
					$plan.=$entry;
					$items.=$li;
				}
				$plan = "$items\n<!-- FEED_DIVIDER -->\n$plan";
			}

			if (OUTPUT_MODE=='ATOM' || OUTPUT_MODE=='ATOM_PRIVATE')
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

					$rss_link="//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner";
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

				$plan='';
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
						if(strpos($filename,'.gz')!==FALSE)
							$plan.=stripslashes(file_get_contents("compress.zlib://$_SERVER[PLANOWNER_ROOT]/plan/".basename($filename)));
						else
							$plan.=stripslashes(file_get_contents("$_SERVER[PLANOWNER_ROOT]/plan/".basename($filename)));
					}
				}
			}
			if (strlen(trim($plan))>10)
			{
				if (strpos($plan,'nolinebreaks')===FALSE) $plan=smart_nl2br($plan);
				$plan="$plan_dressing[header]<article class='plan_body' id='plan_body_$_SERVER[PLANOWNER]'>$plan</article>\n$plan_dressing[footer]";
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
	if (strpos($entry,'<!--no feed-->')===FALSE)
	{
		$entry = "
		 <item rdf:about=\"https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time\">
			 <title>".formattime($entry_time)." ".smart_trim(strip_tags($entry),32,FALSE)."</title>
			 <link>https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time</link>
			 <description>$entry</description>
			 <dc:creator>$planowner</dc:creator>
			 <dc:subject>entry</dc:subject>
			 <dc:date>".gmdate('Y-m-d\TH:i:s+00:00',$entry_time)."</dc:date>
		 </item>\n";

		$li = "<rdf:li rdf:resource=\"https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time\"/>\n";
	}

	return array($entry,$li);
}


// PLAN_ENCAPSULATE_ATOM()
//
// take an entry and build an atom entry
//------------------------------------------------------------------------------
function plan_encapsulate_atom($planowner,$entry_time,$entry)
{
	if (strpos($entry,'<!--no feed-->')===FALSE)
	{
		if(strpos($entry,"<!--title")!==FALSE)
		{
			preg_match("/<!--title ([^<>]*)-->/",$entry,$matches);
			$title=$matches[1];
		}
		else $title=formattime($entry_time)." ".smart_trim(strip_tags($entry),32,FALSE);

		if(strpos($entry,"Posted By")!==FALSE)
		{
			preg_match("/Posted By .* <a href=['\"](.*)['\"]>(.*)<\/a>/",$entry,$matches);
			$link=$matches[1];
			$feedtitle=$matches[2];
//			$testentry.=var_dump_ret($matches);
//			$testentry.="linkback";
		}
		else $link="https://planwatch.org/read/$planowner/$entry_time";

		if(OUTPUT_MODE=='ATOM_PRIVATE') $entry="<a href='$link'>$link</a>";

		$enclosure=plan_embed_enclosure($entry);

		$entry = "
			<entry>
				<title type='html'>$title</title>
				<summary>".smart_trim(strip_tags($entry),255,FALSE)."</summary>
				<content type='html'>".htmlentities($entry)."</content>
				<link rel=\"alternate\" href=\"$link\" type=\"text/html\"/>
				<updated>".gmdate('Y-m-d\TH:i:s+00:00',$entry_time)."</updated>
				<id>https://planwatch.org/read/$planowner/$entry_time</id>
				$enclosure
				<author>
					<name>$planowner</name>
				</author>
			</entry>
		";
		$li = "<rdf:li rdf:resource=\"https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$planowner/.$entry_time\"/>\n";
	}

	return $entry;
}



// PLAN_PREPARE_ENTRY()
//
// creates a uniform entry look
//------------------------------------------------------------------------------
function plan_prepare_entry($entry_time,$entry_header,$entry_text)
{
	$entry="\n<section class='plan_entry' selected='true' id='plan_entry_$entry_time'>\n$entry_header\n<section class='entry_content' id='entry_content_$entry_time'>\n$entry_text\n</section></section>\n";
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

	if(!is_numeric($entry_time)) { return ""; }

	$entry_header="<header class='entry_header' id='entry_header_$entry_time'><!--EDIT_LINKS-->\n$divider\n</header>";

	$publicize_link=$privatize_link='';
	if (strpos($entry_filename,'.p'))
	{
		$entry_is_private = 1;
		if ($reader == $planowner && browser_is_modern())
		{
			$publicize_link=" <span  id='privacy_$entry_time'><a href=\"javascript:loadXMLDoc('//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/entry/publicize/$planowner/.$entry_time/ajax',null,'privacy_$entry_time');document.getElementById('privacy_marker_$entry_time').innerHTML='';void(null);\">publicize</a></span> ";
		}
		if ($reader == $planowner && OUTPUT_MODE=='MOBILE')
		{
			$publicize_link=" <span  id='privacy_$entry_time'><a href=\"/entry/publicize/$planowner/.$entry_time\">publicize</a></span> ";
		}
		$entry_header=str_replace('PRIVATE'," <span class='private_marker' id='privacy_marker_$entry_time'>private entry</span> ",$entry_header);
	}
	else
	{
		$entry_is_private = '';
		if ($reader == $planowner && browser_is_modern())
		{
			$privatize_link=" <span  id='privacy_$entry_time'><a href=\"javascript:loadXMLDoc('//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/entry/privatize/$planowner/.$entry_time/ajax',null,'privacy_$entry_time');document.getElementById('privacy_marker_$entry_time').innerHTML='private entry';void(null);\">privatize</a></span> ";
		}
		if ($reader == $planowner && OUTPUT_MODE=='MOBILE')
		{
			$privatize_link=" <span  id='privacy_$entry_time'><a href=\"/entry/privatize/$planowner/.$entry_time\">privatize</a></span> ";
		}
		$entry_header=str_replace('PRIVATE'," <span class='private_marker' id='privacy_marker_$entry_time'></span> ",$entry_header);
	}

	if (strpos($entry_text,'<!--no feed-->')!==FALSE)
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
	if (($reader == $planowner || plan_user_may_write($planowner,$reader)) && browser_is_modern())
	{
		$edit_links='';

		if(OUTPUT_MODE=='HTML')
		{
			$edit_links.="<a href=\"javascript:layerToggle('edit_links_$entry_time');\" class='tool'><span class='hidden'>editing tools &nbsp; </span> &#x2699;</a>"
			." <div id='edit_links_$entry_time' class='edit_links' style='display: none;'>\n"
			." <a href=\"/write/$planowner/.$entry_time\">edit</a>\n";

		}

		if($reader == $planowner)
		{
			$edit_links.=" <a href=\"javascript:displayEditLayer('$entry_time');\">tweak</a>\n";
		}

		$edit_links.=" <a href=\"javascript:loadXMLDoc('//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/entry/delete/$planowner/.$entry_time/ajax',null,'plan_entry_$entry_time');\">delete</a> \n"
		." <a href=\"javascript:loadXMLDoc('//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/entry/hide/$planowner/.$entry_time/ajax',null,'plan_entry_$entry_time');\">hide</a> \n"
		."$privatize_link $publicize_link\n</div>";

		if(OUTPUT_MODE=='MOBILE')
		{
			$edit_links="<a href=\"javascript:document.getElementById('edit_links_$entry_time').style.display='block';void(0);\" class='tool'>&#x2699;</a>"
			." <div id='edit_links_$entry_time' class='edit_links' style='display: none;'>\n"
			." <a href=\"/write/$planowner/.$entry_time\">edit</a>\n"
			." <a href=\"/entry/delete/$planowner/.$entry_time\">delete</a> \n"
			." <a href=\"/entry/hide/$planowner/.$entry_time/\">hide</a> \n"
			."$privatize_link $publicize_link\n</div>";
		}

		$entry_header = str_replace("<!--EDIT_LINKS-->","\n$edit_links\n",$entry_header);

		$entry_header = str_replace(
			"<header class='entry_header' id='entry_header_$entry_time'>",
			"<header class='entry_header' id='entry_header_$entry_time' ondblclick=\"displayEditLayer('$entry_time');\">",
			$entry_header);
	}

	if(preg_match("<!--title (.*) -->",$entry_text,$titlematches))
	{
		$entry_header.="<header id='$entry_time' style='visibility: hidden;'>$titlematches[1]</header>";

	}


	return $entry_header;
}


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
	include_once('db_functions.php');
	include_once('simplepie.1.2.inc');
// 	include_once('simplepie-1.5/autoloader.php');

	profile('rss_read');
	$lastview=plan_get_last_view($urls);
	$multifeed=TRUE;
	if (is_string($urls))
		if (substr_count($urls,",http") > 0) { $urls=explode(",http",$urls); foreach($urls as $i=>$url) if (strpos($url,'http')===FALSE) $urls[$i]="http".$url; }
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
	$items=array_slice($items,$offset*count($urls),$item_count);
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
					$content.="<li><a href='https://planwatch.org/read/$link'>$link</a></li>\n";
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
	if (strpos($url,'://')===FALSE)	$url=plan_get_real_location($url);

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


function plan_read_archives($planowner)
{
	if(!file_exists("$_SERVER[PWUSERS_DIR]/$planowner/plan/.arcprivate") || user_is_authorized($planowner,$_SERVER['USER']))
	{
		if (plan_is_local($planowner) && plan_test_privacy($_SERVER['USER'],$planowner))
		{
			if (plan_is_journaling($planowner))
			{
				$m=array('','','','','','','','','','','','','');
				$m[date('n',time()-(24*3600*10))]='SELECTED';
				$d=date('j',time()-(24*3600*10));
				$y=date('Y',time()-(24*3600*10));

				$content="
		<div align='center'>
			<form action='//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/form_shim.php' method='post'>
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

				if (strpos($_SERVER['USER'],'@')!==FALSE)
				{
					$authid=base64_encode("$_SERVER[USER]".time());
					file_put_contents("$_SERVER[FILE_ROOT]/temp/$authid.calauth",$planowner);
				}

				$content.="
				</select>
				<input type='submit' value='go'/>
				</form>

				<b>search</b>: <form action='//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/form_shim.php'><input type='text' name='keyword' value=''/><input type='hidden' name='writer' value='$planowner'/><input type='submit' name='submit' value='search'/><input type='hidden' name='action' value='archive_search'/></form>

				<iframe id='archives_calendar' align='bottom' standby='waiting for cal' src='/cal/$planowner/".date("Y-m-01")."/".date("Y-m-t")."' type='text/html' />
				</div>\n";

			}
			else
			{
				$content="<b>Pick an archived plan to view or check several and click 'view checked':</b>

				<b>search</b>: <form action='//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/form_shim.php'><input type='text' name='keyword' value=''/><input type='hidden' name='writer' value='$planowner'/><input type='submit' name='submit' value='search'/><input type='hidden' name='action' value='archive_search'/></form>
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