<?php

/*
FORMATTING_HTML.php

contains all the functions for formatting stuff as HTML

// TODO:(v4.1) abstract formatting functions to consider $_SERVER[OUTPUT_MODE]
*/

// HTML_SIZE_TO_CSS_SIZE()
//
// converts HTML 1-7 sizes to CSS point-specified sizes for more
// consistent font sizing cross-platform
//------------------------------------------------------------------------------
function html_size_to_css_size($number)
{
	switch($number):
	case "0": $number="0"; break;
	case "0px": $number="0"; break;
	case "1px": $number="0"; break;
	case "1": $number="9pt"; break;
	case "2": $number="10pt"; break;
	case "3": $number="12pt"; break;
	case "4": $number="16pt"; break;
	case "5": $number="20pt"; break;
	case "6": $number="24pt"; break;
	case "7": $number="36pt"; break;
	default: $number=str_replace(" pt","pt",$number); break;
	endswitch;
	return $number;
}


// LIST_FORMAT_HTML()
//
// formats an array of names with times
// calls plan_get_last_update()
//------------------------------------------------------------------------------
// TODO:(v4.5) consider in-situ watched list editing options
function list_format_html($list=FALSE,$sortby=FALSE)
{
	profile('list_format_html','begin');

	profile("prelist");
	if ($_SERVER['USER_ROOT'] && is_dir("$_SERVER[USER_ROOT]/sends/"))
	{
		$new_sends=files_list("$_SERVER[USER_ROOT]/sends/","*..new");
	}
	if ($new_sends)
	{
		$watchlist.="<li class='listheader'><a href='/send'>sends</a></li>";
		foreach($new_sends as $new_send)
		{
			$sender=files_decode_safe_name(str_replace("..new",'',$new_send));
			$sendtime=formattime(filectime("$_SERVER[USER_ROOT]/sends/$new_send"));
			$watchlist.="<li class='unread'><a href='/send/$sender'>&#x2709; $sender <span class='updatetime'>$sendtime</span></a></li>\n";
		}
	}


	if (!$list)
	{
		$list_fn="$_SERVER[USER_ROOT]/watchedlist.txt";	// reads in the user's watched list
		if (file_exists($list_fn))
		{
			$list=file($list_fn);
			if ($list[0]=="sort by time\n") $sortby='time';
			elseif ($list[0]=="sort by name\n") $sortby='name';
			elseif ($list[0]=="sort by none\n") $sortby='inorder';
			else $sortby='inorder';
			if (strpos($list[0],'sort by ')!==FALSE) unset($list[0]);
		}
		else $list=array("system","jwdavidson","jlodom00@amherst.edu");

		$sptime=plan_get_last_update("system");
		$slastview=plan_get_last_view("system");
		if($sptime > $slastview) $list=array_merge(array("system"),$list);

		if ($_SERVER['OUTPUT_MODE']!='IPHONE') $list=array_merge(array("#Watched Plans"),$list);
		else $list=array_merge(array("!$_SERVER[USER]:view your own plan!"),$list);
	}
	profile("prelist");

	profile('list_format_html_prep','begin');
	$ptime=plan_get_last_update($list);
	$lastview=plan_get_last_view($list);

	$ordinal=0;

	foreach($list as $z=>$plan)
	{
		$plan=urldecode($plan);
		if (strstr($plan,'!!!'))
		{
			$prune=TRUE;
			$plan='';
		}

		if (strstr($plan,'!prune'))
		{
// 			$prune=TRUE;
			$threshhold = str_replace('!prune','',$plan);
			$threshhold = time_calculate_threshhold($threshhold);
			$threshhold = time()-$threshhold;
			$plan='';
		}
		if (trim($plan)=='!onlynew') { $onlynew=TRUE; }
		if (trim($plan)=='!alwaysnew') { $alwaysnew=TRUE; }
		$plan=str_replace(array('!alwaysnew','!onlynew'),'',$plan);


		if (strpos($plan,'!')!==false && strpos($plan,':')!==false)
		{
			$plan=str_replace("!",'',$plan);
			$finalpos=strrpos($plan,':');
			$url=trim(substr($plan,0,$finalpos));
			$displayname=trim(substr($plan,$finalpos+1,strlen($plan)-$finalpos-1));
		}
		else { $displayname=$plan; $url=$plan; }


		if (!is_string($displayname) || $displayname==$url)
		{
			if (strstr($url,'@'))
			{
				list($username,$host)=explode("@",$url);
				$displayname="$username <span style='font-size: smaller;'>@$host</span>";
			}
			else $displayname=$url;

		}

		if($displayname[0]=='#')
		{
			$groupname=htmlentities(str_replace('#','',$displayname));
			$grouplist[]=$groupname;
		}
		if (trim($plan))
		{
			if ($plan[0]!='#')
			{
				if (file_exists("$_SERVER[USER_ROOT]/send/".files_encode_safe_name($url)."..new")) $send="<a href='/send/$url'>SEND</a>";
				else $send='';

				$biglist[$ordinal]=array(
					"group"=>$groupname,
					"url"=>trim($url),
					"name"=>$displayname,
					"updated"=>$ptime[$z],
					"viewed"=>$lastview[$z],
					"send"=>$send
					);

				$timelist[$ordinal]=$ptime[$z];
				$namelist[$ordinal]=$displayname;
				$ordinal++;
			}
		}

		if (!is_array($grouplist)) $grouplist=array('');

	}
	profile('list_format_html_prep','end');

	if (!$prune)
	{
		$onlynew = FALSE;
		$alwaysnew = FALSE;
		$threshhold = 0;
	}

	profile("buildlist");
	if ($sortby=='inorder')
	{
		foreach($biglist as $i=>$plan_details)
		{
			$outputlist[$biglist[$i]['group']].=format_watched_list_entry($biglist[$i],$threshhold,$prune,$alwaysnew);
		}
	}

	if ($sortby=='name')
	{
		asort($namelist);
		foreach($namelist as $i=>$name)
		{
			$outputlist[$biglist[$i]['group']].=format_watched_list_entry($biglist[$i],$threshhold,$prune,$alwaysnew);
		}
	}


	if ($sortby=='time')
	{
		arsort($timelist);
		foreach($timelist as $i=>$time)
		{
			$outputlist[$biglist[$i]['group']].=format_watched_list_entry($biglist[$i],$threshhold,$prune,$alwaysnew);
		}
	}

	foreach($grouplist as $groupname)
	{
		$jsgroupname=trim(str_replace(" ","_",$groupname));
		$groupname_url="/look/group/".urlencode(trim($groupname));
		if (strtolower($groupname)=='watched plans') $groupname_url='/look';
		if ($outputlist[$groupname] || strtolower($groupname)=='watched plans')
		{
			if (agent_is_lowfi())
			{
				$watchlist.="
				<br /><br /><div><b>$groupname</b> <span color='gray'>[<a href='$groupname_url'>look</a>]</span> </div>";
			}
			else
			{
				$watchlist.="
				<li class='listheader'>
				<a href='$groupname_url' target='_self'>$groupname</a>";
			}
		}
		if (strtolower($groupname)!='watched plans' && $_SERVER['OUTPUT_MODE']!='IPHONE')
			$watchlist.="<a id='hide_$jsgroupname' href='javascript:setStyleByClass(\"li\",\"$jsgroupname\",\"display\",\"none\"); element(\"show_$jsgroupname\").style.display=\"inline\"; element(\"hide_$jsgroupname\").style.display=\"none\";void(0);'>_</a>
			<a style='display: none;' id='show_$jsgroupname' href='javascript:setStyleByClass(\"li\",\"$jsgroupname\",\"display\",\"block\"); element(\"hide_$jsgroupname\").style.display=\"inline\"; element(\"show_$jsgroupname\").style.display=\"none\";void(0);'>+</a>";

		$watchlist.="\n$outputlist[$groupname]";
	}

	profile("buildlist");
	profile('list_format_html','end');

	return $watchlist;
}

function format_watched_list_entry($plan_details,$threshhold,$prune,$alwaysnew)
{
	$jsgroupname=trim(str_replace(" ","_",$plan_details['group']));
	if ($plan_details['updated']>$plan_details['viewed']) $class='unread';
	else $class='read';

	if ($plan_details['updated']>$threshhold || !$prune || ($alwaysnew && $class=='unread'))
	{
		$plan_details['updated']=formattime($plan_details['updated']);
		$id=md5($plan_details['url']);
		$entry="<li id='$id' class='$class $jsgroupname'><a onclick='document.getElementById(\"$id\").className=\"read\";' href='/read/$plan_details[url]' id='read_$plan_details[url]'>$plan_details[name] <span class='updatetime'>$plan_details[updated]</span></a> $plan_details[send]</li>\n";
		if (agent_is_lowfi())
		{
			if ($class=='unread') $b="b"; else $b="span";
			if ($class=='listheader') $plan_details['name']=strtoupper($plan_details['name']);
			$entry="<div><$b><a href='/read/$plan_details[url]' id='read_$plan_details[url]'>$plan_details[name] $plan_details[updated]</a></$b></div>\n";
		}
	}

	return $entry;
}


// MENUS_POPULATE()
//
// gets the links for the popup DHTML menus
//------------------------------------------------------------------------------
// TODO:(v4.1) test AJAX methods for populating menus
function menus_populate($button,$content=FALSE)
{
	switch($button):

	case "view":
		$links.="<li>\n<!--\nTHE VIEW MENUBOX\n-->\n
		<i>read your own plan. you know, in case you forgot.</i></li>\n\n
		<li><a href='$_SERVER[WEB_ROOT]/view'>view your plan</a></li>\n
		<li><a href='$_SERVER[WEB_ROOT]/read/$_SERVER[USER]/bio'>view your bio</a></li>\n";
		if (plan_has_archives($_SERVER['USER'])) $links.="<li><a href='$_SERVER[WEB_ROOT]/read/$_SERVER[USER]/archives'>view your archives</a></li>\n";
		if (plan_is_journaling($_SERVER['USER'])) $links.="<li><hr /><a href='$_SERVER[WEB_ROOT]/view/all_hidden'>view your hidden entries</a></li>\n
		<li><a href='$_SERVER[WEB_ROOT]/view/all_private'>view your private entries</a></li>\n";
		break;

	case "write":

		$links.="\n<!--\nTHE WRITE MENUBOX\n-->\n
		<li><i>write a plan update. you know you want to.</i></li>\n
		<li><a href='$_SERVER[WEB_ROOT]/write'>update your plan</a></li>\n
		<li><a href='$_SERVER[WEB_ROOT]/write/bio'>edit your bio</a></li>\n";
		if (user_is_administrator()) $links.="<li><a href='$_SERVER[WEB_ROOT]/write/system'>update the system plan</a></li>\n";
		$links.="<li><hr/>\n</li>
		<li><a href='$_SERVER[WEB_ROOT]/write/css'>change plan styles</a></li>\n
		<li><a href='$_SERVER[WEB_ROOT]/write/header'>change plan header</a></li>\n
		<li><a href='$_SERVER[WEB_ROOT]/write/footer'>change plan footer</a></li>\n";
		if (plan_is_journaling($_SERVER['USER'])) $links.="<li><a href='$_SERVER[WEB_ROOT]/write/divider'>change your divider</a></li>\n";
		$links.="<li><hr/></li>
		<li><a href='$_SERVER[WEB_ROOT]/send'>write a send</a></li>\n";
		if ($content && plan_is_journaling($_SERVER['USER']) && strstr($_SERVER['REQUEST_URI'],"/read/$_SERVER[USER]"))
		{
			preg_match_all('|entry_content_([0-9]+)|',$content,$matches);
			$matches=$matches[1];
			if (is_array($matches))
			{
				$links.="<li><hr/>edit recent entries...</li>\n";
				foreach($matches as $match)
				{
					$links.="<li><a href='$_SERVER[WEB_ROOT]/write/.$match'>".formattime($match)."</a></li>\n";
				}
			}
		}


		break;

	case "snitch":

		$links.="\n<!--\nTHE SNITCH MENUBOX\n-->\n
		<li><i>snitch and other s-words.</i></li>\n
		<li><a href='$_SERVER[WEB_ROOT]/snitch'>snitch</a></li>\n";
		$links.="<li><hr/>\n</li>
		<li><a href='$_SERVER[WEB_ROOT]/snoop'>snoop</a></li>\n
		<li><a href='$_SERVER[WEB_ROOT]/send'>send</a></li>\n";
		break;


	case "tools":
		$links.="\n<!--\nTHE TOOLS MENUBOX\n-->\n";
		$links.="<li><i>tools to maintain and customize your account</i></li>";
		$links.="
			<li><a href='$_SERVER[WEB_ROOT]/feature/' title='report a bug or request a feature'>report a bug</a><br/><hr/></li>\n"

			."<li><a href='$_SERVER[WEB_ROOT]/slogans/add' title='add your own pw.o slogan to the random selection'>add a new slogan</a><br/>
			<a href='$_SERVER[WEB_ROOT]/smileys/add' title='upload a new smiley for people to use on their plans'>add a new smiley</a><br/>
			<hr/></li>\n"

			."<li><a href='$_SERVER[WEB_ROOT]/lists/edit/allowed' title='change who can read your private entries'>edit your allowed list</a><br/>
			<a href='$_SERVER[WEB_ROOT]/lists/edit/blocked' title='change who is prevented from reading you'>edit your blocked list</a><br/>
			<hr/>\n</li>"

			."<li><a href='$_SERVER[WEB_ROOT]/prefs/styles'>customize colors</a></li>\n"
			."<li><a href='$_SERVER[WEB_ROOT]/prefs/fonts'>customize fonts</a></li>\n"
			."<li><a href='$_SERVER[WEB_ROOT]/prefs/custom_css' title='add custom css'>customize css</a></li>\n"
			."<li><a href='$_SERVER[WEB_ROOT]/prefs/skin' title='pick a skin'>pick a skin</a></li>\n"
			."<li><hr/></li>\n"
			."<li><a href='$_SERVER[WEB_ROOT]/prefs/userinfo'>user settings</a></li>\n"
			."<li><a href='$_SERVER[WEB_ROOT]/prefs/interface'>interface prefs</a></li>\n";
//			."<li>skin preview:<br/> <!--SKIN_SELECTOR--></li>\n"
		break;

	case "<!--TIME-->":
		$links.="<!--LOADTIME-->";
		break;

	case "watched":
		$links.="
			<li><a href='$_SERVER[WEB_ROOT]/lists/edit/watched' title='edit your watched list'>edit</a></li>
			<li class='listheader'>move
			<a href='$_SERVER[WEB_ROOT]/lists/move/watched/top'>top</a>
			<a href='$_SERVER[WEB_ROOT]/lists/move/watched/left'>left</a>
			<a href='$_SERVER[WEB_ROOT]/lists/move/watched/right'>right</a></li>
			<!--<b>move AJAX</b>
			<li><a href=\"javascript:list_move('top');void(null);\">top</a></li>
			<li><a href=\"javascript:list_move('left');void(null);\">left</a></li>
			<li><a href=\"javascript:list_move('right');void(null);\">right</a></li>
			-->
			<li class='listheader'>sort
			<a href=\"javascript:loadXMLDoc('$_SERVER[WEB_ROOT]/lists/resort/name/ajax','','planwatch');void(null);\" title='sort alphabetically by name'>abc</a>
			<a href=\"javascript:loadXMLDoc('$_SERVER[WEB_ROOT]/lists/resort/time/ajax','','planwatch');void(null);\" title='sort by date and time'>321</a>
			<a href=\"javascript:loadXMLDoc('$_SERVER[WEB_ROOT]/lists/resort/inorder/ajax','','planwatch');void(null);\" title='do not sort, use in the order listed'>zfq</a></li>
			<li class='listheader'>status
			<a href='$_SERVER[WEB_ROOT]/lists/planwatch_mark_all_read' title='mark all plans as read'>update</a>
			<a href='$_SERVER[WEB_ROOT]/lists/planwatch_mark_all_unread' title='mark all plans as unread'>reset</a></li>
			<!--<li><b>lists</b></li>
			<li><a href='$_SERVER[WEB_ROOT]/lists/advertised_users' title='advertised users'>advertised users</a> </li>
			<li><a href='$_SERVER[WEB_ROOT]/lists/registered_users' title='registered users'>registered users</a></li>-->\n";
		break;

	endswitch;


	return $links;
}




// BUTTONS_POPULATE()
//
// this function prepares the toolbar icons that run across the top of the page
//------------------------------------------------------------------------------
function buttons_populate($content)
{
	//TOP LINK ICONS
	if (strlen($GLOBALS['viewicon'])>1) $vistring="<img src='$_SERVER[WEB_ROOT]$GLOBALS[viewicon]' border='0' align='bottom' vspace='2' id='viewicon' alt='icon' />";
	if (strlen($GLOBALS['writeicon'])>1) $wistring="<img src='$_SERVER[WEB_ROOT]$GLOBALS[writeicon]' border='0' align='bottom' vspace='2' id='writeicon' alt='icon' />";
	if (strlen($GLOBALS['toolsicon'])>1) $tistring="<img src='$_SERVER[WEB_ROOT]$GLOBALS[toolsicon]' border='0' align='bottom' vspace='2' id='toolsicon' alt='icon' />";
	if (strlen($GLOBALS['snitchicon'])>1) $sistring="<img src='$_SERVER[WEB_ROOT]$GLOBALS[snitchicon]' border='0' align='bottom' vspace='2' id='snitchicon' alt='icon' />";
	if (strlen($GLOBALS['helpicon'])>1) $histring="<img src='$_SERVER[WEB_ROOT]$GLOBALS[helpicon]' border='0' align='bottom' vspace='2' id='helpicon' alt='icon' />";
	if (strlen($GLOBALS['logouticon'])>1) $listring="<img src='$_SERVER[WEB_ROOT]$GLOBALS[logouticon]' border='0' align='bottom' vspace='2' id='logouticon' alt='icon' />";

	if ($GLOBALS['hfsize']>0 && $GLOBALS['hfsize']!='1px') { $viewtext=' view'; $toolstext=' tools'; $writetext=' write'; $snitchtext=' snitch'; $helptext=' help'; $logouttext=' logout'; $masqtext=' masq off'; }
	$view="<li class='menuicon'><a style='z-index: 21;' href='$_SERVER[WEB_ROOT]/view'>$vistring$viewtext</a>\n<ul>".menus_populate('view')."</ul></li>";
	if (user_is_local($_SERVER['USER'])) $write="<li class='menuicon'><a style='z-index: 23;' href='$_SERVER[WEB_ROOT]/write'>$wistring$writetext</a>\n<ul>".menus_populate('write',$content)."</ul></li>";
	$tools="<li class='menuicon'><a style='z-index: 30;' href='$_SERVER[WEB_ROOT]/tools'>$tistring$toolstext</a>\n<ul>".menus_populate('tools')."</ul></li>";
	$snitch="<li class='menuicon'><a href='$_SERVER[WEB_ROOT]/snitch' title='see who reads you, and read them back'>$sistring$snitchtext</a>\n<ul>".menus_populate('snitch')."</ul></li>\n";
	$help="<li class='menuicon'><a href='$_SERVER[WEB_ROOT]/help'>$histring$helptext</a></li>\n";
	if ($_SERVER['MASQUERADE']) $logout="<li class='menuicon'><a href='$_SERVER[WEB_ROOT]/masq/off' title='end the masquerade!'>$listring$masqtext</a></li>\n";
	else $logout="<li class='menuicon'><a href='$_SERVER[WEB_ROOT]/logout' title='log out $_SERVER[USER]'>$listring$logouttext</a></li>\n";
	if ($_SERVER['USERINFO_ARRAY']['displayloadtime'])
	{
		$time_icon_string="<img src='$_SERVER[WEB_ROOT]/resources/graphics/clock.png' id='timeicon' alt='time icon'/>";
		$time="<li class='menuicon'>$time_icon_string <span style='font-size: 8pt;'><!--TIME--></span><ul>".menus_populate('<!--TIME-->')."</ul></li>";
	}

	return $view.$write.$snitch.$tools.$help.$logout.$time;
}

function format_watched_list_html($controls=TRUE)
{

// WATCHED LIST
//------------------------------------------------------------------------------
	if(!user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
	{
//		$planwatchlist = "\n<li class='listheader'>Advertised Plans</li>\n";

		$list_fn = "$_SERVER[FILE_ROOT]/resources/advertised.txt";

		// read in the advertised plans list, break it into an array,
		// and run it through the list formatter
		if (file_exists($list_fn))
		{
			$list = file($list_fn);
			$list=array_merge(array("!!!","!prune31d","#Advertised Plans"),$list);
			$planwatchlist.=list_format_html($list,'time');
		}

	}
	else
	{
		$_SERVER['STOPWATCH']['watched_begin']=array_sum(explode(' ',microtime()));
		$loadtime=formattime(time());

		if ($controls)
		$planwatchlist="
		<li style='font-size: 120%; float: right;'>
			<a class='tool' id='menuwatched_icon' href='javascript:var editlinks=document.getElementById(\"watched_list_edit\");if (editlinks.style.display==\"none\") editlinks.style.display=\"block\"; else editlinks.style.display=\"none\"; void(null);' ><span class='hidden'>edit</span> &#x2699;</a>
			<a class='tool' href='javascript:watched_list_refresh();void(null);' title='$loadtime'><span class='hidden'>$loadtime</span> &#x21bb;
			</a>
		</li>
		<li><ul style='list-style: none; display: none; padding-left: 0px; margin-left: 0px; line-height: 100%; border-bottom: 1px dashed rgba(255,255,255,0.8); background: rgba(255,255,255,0.6); border-radius: 10px;' id='watched_list_edit'>".menus_populate('watched')."</ul></li>\n";
//		if ($_SERVER['USER']!='jwdavidson' && !strstr($_SERVER['REQUEST_URI'],'watched'))
			$planwatchlist.=list_format_html();
		$key=md5($planwatchlist);
		if (!stristr($_SERVER['HTTP_USER_AGENT'],'blackberry')) $planwatchlist.="<li style='display: none;' id='key'>$key</li>";
		$_SERVER['WATCHED_KEY']=$key;
		$_SERVER['STOPWATCH']['watched_end']=array_sum(explode(' ',microtime()));

// SNOOP LIST
//------------------------------------------------------------------------------

		include_once('snoop.php');
		$_SERVER['STOPWATCH']['snoop_begin']=array_sum(explode(' ',microtime()));
		if ($_SERVER['USERINFO_ARRAY']['showsnoop'])
		{
			$snooplinks=snoop_list("planwatch");

			if (trim(strip_tags($snooplinks)))
			{
				if (agent_is_lowfi()) $planwatchlist.="\n<br /><br /><div><b>Snoops</b></div>\n$snooplinks\n\n";
				else $planwatchlist.="\n<li class='listheader'>Snoops</li>\n$snooplinks\n\n";
			}
		}

		$_SERVER['STOPWATCH']['snoop_end']=array_sum(explode(' ',microtime()));

	}

// SPIEL LIST
//------------------------------------------------------------------------------

	$_SERVER['STOPWATCH']['spiel_begin']=array_sum(explode(' ',microtime()));

	include_once('spiel.php');
	$planwatchlist.=spiel_format_list_html();

	$_SERVER['STOPWATCH']['spiel_end']=array_sum(explode(' ',microtime()));



return $planwatchlist;
}

function format_watched_list_iphone()
{

// WATCHED LIST
//------------------------------------------------------------------------------
	if(!user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
	{
		return "";
	}
	else
	{
		$_SERVER['STOPWATCH']['watched_begin']=array_sum(explode(' ',microtime()));

		$planwatchlist.="<option value='/watched'>watched</option>";
		$planwatchlist.=list_format_iphone();
	}
return $planwatchlist;
}

function list_format_iphone($list=FALSE,$sortby=FALSE)
{
	profile('list_format_html','begin');

	profile("prelist");
	if ($_SERVER['USER_ROOT'] && is_dir("$_SERVER[USER_ROOT]/sends/"))
	{
//		echo $_SERVER['USER_ROOT'];
		$new_sends=files_list("$_SERVER[USER_ROOT]/sends/","*..new");
	}
	if ($new_sends)
	{
		$watchlist.="<option value='/send'>sends</option>";
		foreach($new_sends as $new_send)
		{
			$sender=files_decode_safe_name(str_replace("..new",'',$new_send));
			$watchlist.="<option value='/send/$sender'>$sender</option>\n";
		}
	}

	if (!$list)
	{
		$list_fn="$_SERVER[USER_ROOT]/watchedlist.txt";	// reads in the user's watched list
		if (file_exists($list_fn))
		{
			$list=file($list_fn);
			if ($list[0]=="sort by time\n") $sortby='time';
			elseif ($list[0]=="sort by name\n") $sortby='name';
			elseif ($list[0]=="sort by none\n") $sortby='inorder';
			else $sortby='inorder';
			if (strpos($list[0],'sort by ')!==FALSE) unset($list[0]);
		}
		else $list=array();
		$list=array_merge(array("#Watched Plans"),$list);
	}
	profile("prelist");

	profile('list_format_html_prep','begin');
	$ptime=plan_get_last_update($list);
	$lastview=plan_get_last_view($list);

	$ordinal=0;

	foreach($list as $z=>$plan)
	{
		$plan=urldecode($plan);
		if (strstr($plan,'!!!'))
		{
			$prune=TRUE;
			$plan='';
		}

		if (strstr($plan,'!prune'))
		{
// 			$prune=TRUE;
			$threshhold = str_replace('!prune','',$plan);
			$threshhold = time_calculate_threshhold($threshhold);
			$threshhold = time()-$threshhold;
			$plan='';
		}
		if (trim($plan)=='!onlynew') { $onlynew=TRUE; }
		if (trim($plan)=='!alwaysnew') { $alwaysnew=TRUE; }
		$plan=str_replace(array('!alwaysnew','!onlynew'),'',$plan);


		$alias_array=explode(':',str_replace('!','',$plan));
		$displayname=end($alias_array);
		$url = $alias_array[0];
		if ($alias_array[1][0] == '/') $url.=":$alias_array[1]";
		if (!is_string($displayname) || $displayname==$url)
		{
			if (strstr($url,'@'))
			{
				list($username,$host)=explode("@",$url);
				$displayname="$username <span style='font-size: smaller;'>@$host</span>";
			}
			else $displayname=$url;

		}
//		else echo $displayname;

		if($displayname[0]=='#')
		{
			$groupname=htmlentities(str_replace('#','',$displayname));
			$grouplist[]=$groupname;
		}
		if (trim($plan))
		{
			if ($plan[0]!='#')
			{
				if (file_exists("$_SERVER[USER_ROOT]/send/".files_encode_safe_name($url)."..new")) $send="<a href='/send/$url'>SEND</a>";
				else $send='';

				$biglist[$ordinal]=array(
					"group"=>$groupname,
					"url"=>trim($url),
					"name"=>$displayname,
					"updated"=>$ptime[$z],
					"viewed"=>$lastview[$z],
					"send"=>$send
					);

				$timelist[$ordinal]=$ptime[$z];
				$namelist[$ordinal]=$displayname;
				$ordinal++;
			}
		}

		if (!is_array($grouplist)) $grouplist=array('');

	}
	profile('list_format_html_prep','end');

	if (!$prune)
	{
		$onlynew = FALSE;
		$alwaysnew = FALSE;
		$threshhold = 0;
	}

	profile("buildlist");
	if ($sortby=='inorder')
	{

		foreach($biglist as $i=>$plan_details)
		{
			$plan_details=$biglist[$i];
			if ($plan_details['updated']>$plan_details['viewed']) $class='* ';
			else $class='';

			if ($plan_details['updated']>$threshhold || !$prune || ($alwaysnew && $class=='unread'))
			{
				$plan_details['updated']=formattime($plan_details['updated']);
				$outputlist[$plan_details['group']].="<option value='/read/$plan_details[url]'>$class$plan_details[name]: $plan_details[updated]</option>\n";
			}
		}
	}

	if ($sortby=='name')
	{
		asort($namelist);
		foreach($namelist as $i=>$name)
		{
			$plan_details=$biglist[$i];
			if ($plan_details['updated']>$plan_details['viewed']) $class='* ';
			else $class='';

			if ($plan_details['updated']>$threshhold || !$prune || ($alwaysnew && $class=='unread'))
			{
				$plan_details['updated']=formattime($plan_details['updated']);
				$outputlist[$plan_details['group']].="<option value='/read/$plan_details[url]'>$class$plan_details[name]: $plan_details[updated]</option>\n";
			}
		}
	}


	if ($sortby=='time')
	{
		arsort($timelist);
		foreach($timelist as $i=>$time)
		{
			$plan_details=$biglist[$i];
			if ($plan_details['updated']>$plan_details['viewed']) $class='* ';
			else $class='';

			if ($plan_details['updated']>$threshhold || !$prune || ($alwaysnew && $class=='unread'))
			{
				$plan_details['updated']=formattime($plan_details['updated']);
				$outputlist[$plan_details['group']].="<option value='/read/$plan_details[url]'>$class$plan_details[name]: $plan_details[updated]</option>\n";
			}
		}
	}

	foreach($grouplist as $groupname)
	{
		$jsgroupname=trim(str_replace(" ","_",$groupname));
		if ($outputlist[$groupname] || strtolower($groupname)=='watched plans') $watchlist.="
		<option value=''>---</option><option value='/look/group/".urlencode(trim($groupname))."'>[$groupname]</option>\n$outputlist[$groupname]";
	}

	profile("buildlist");
	profile('list_format_html','end');

	return $watchlist;
}

?>
