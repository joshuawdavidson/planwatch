<?php
/*
OUTPUT.php

contains the output() function
*/


// OUTPUT()
//
// outputs provided content.
// calls output_html() or output_rss() as appropriate
//------------------------------------------------------------------------------
function output($title,$content,$disposition='browser')
{
	$_SERVER['PLANOWNER_DISPLAY_NAME']=str_replace("'","",$_SERVER['PLANOWNER_DISPLAY_NAME']);
	$_SERVER['STOPWATCH']['content_end']=array_sum(explode(' ',microtime()));
	$_SERVER['STOPWATCH']['output_begin']=array_sum(explode(' ',microtime()));

	// if there is no cookie, user is 'guest'
	if (!$_SERVER['USER']) $_SERVER['USER']='guest';


	if ($_SERVER['OUTPUT_MODE']=='HTML')
	{
		Header("Content-type: text/html; charset=UTF-8");
		$page=output_html($title,$content);
	}

	if ($_SERVER['OUTPUT_MODE']=='IPHONE')
	{
		Header("Content-type: text/html; charset=UTF-8");
		$page=output_iphone($title,$content);
	}

	if ($_SERVER['OUTPUT_MODE']=='RSS' || strstr($_SERVER['OUTPUT_MODE'],'ATOM'))
	{
		Header("Content-type: text/xml; charset=UTF-8");
		$page=output_feed($title,$content);
	}

	if ($_SERVER['OUTPUT_MODE']=='AJAX')
	{
		Header("Content-type: text/html; charset=UTF-8");
		$page=output_ajax($title,$content);
	}

	if ($disposition=='return') return $page;
	if (strstr($disposition,$_SERVER['DOCUMENT_ROOT']))
		{ file_write_contents($disposition,$page); return $page; }

	echo $page; exit;
}



// OUTPUT_HTML()
//
// outputs provided content as an HTML page
// depends on formatting_html.php
//------------------------------------------------------------------------------
function output_html($title,$content)
{
	require_once("formatting_html.php");

// SKIN AND FONTS
//------------------------------------------------------------------------------
	if ($hatespictures) $extracss = 'img { display: none; } #header img { display: inline; }';


// WATCHED LIST
//------------------------------------------------------------------------------
	if (!browser_is_modern())
		$testwatchlist=$planwatchlist=format_watched_list_html();
	else
	{
		$planwatchlist="&nbsp;";
		if ($_SERVER['USER_ROOT'] && file_exists("$_SERVER[USER_ROOT]/watchedlist.txt")) $testwatchlist=file_get_contents("$_SERVER[USER_ROOT]/watchedlist.txt");
	}


// HTML <HEAD> TAGS
// AND TOP LINKS & MENUS
//------------------------------------------------------------------------------

	$_SERVER['STOPWATCH']['meta_begin']=array_sum(explode(' ',microtime()));

	$title=strip_tags($title);
	$extracss="<style type='text/css'>{$_SERVER['PLANOWNER_INFO']['css']}</style>";
	$thisurl="http://$_SERVER[HTTP_HOST]$web_root$_SERVER[REQUEST_URI]";

	// if we're writing or reading our own plan, load the editing javascript
	if (strstr($_SERVER[REQUEST_URI],'write') || $_SERVER['USER']==$_SERVER['PLANOWNER'])
		$extrajs.="\n<script type='text/javascript' src='/resources/javascript/setplan.js'></script>\n";

	// if we're writing, set up draft autosaves
	if (strstr($_SERVER[REQUEST_URI],'write'))
		$extrajs.="<script type='text/javascript'>setTimeout(\"saveDraft($_SERVER[PLAN_DRAFT_TIME]);\",61131);</script>";

	// if we're reading something besides an rss feed, set charset
	// to UTF-8. in the html5 template, everything's always set to UTF-8
	// and it doesn't seem to break anything. we can probably safely
	// remove this once we switch.
	if ($_SERVER['URL_ARRAY'][1]=='read' && strstr($urlarray[2],'http')) $encoding="<meta http-equiv='Content-type' content='text/html; charset=UTF-8' />";

	// not that anyone will pay attention, but go ahead and put a copyright
	// notice in. 
	if ($_SERVER['PLANOWNER']) $copyright="<meta http-equiv='copyright' content='This plan is copyright ".date("Y")." $_SERVER[PLANOWNER_DISPLAY_NAME], all rights reserved.' />";

	// if we're looking at a nonprivate local plan, provide an rss feed
	if ($_SERVER['PLANOWNER'] && !plan_is_private($_SERVER['PLANOWNER']) && !plan_is_registered_only($_SERVER['PLANOWNER']) && plan_is_local($_SERVER['PLANOWNER']))
	{
		$alternate.="<link rel='alternate' type='application/rss+xml' title=\"$_SERVER[PLANOWNER_DISPLAY_NAME]'s RSS Feed\" href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]/rss' />\n";
		$alternate.="<link rel='alternate' type='application/atom+xml' title=\"$_SERVER[PLANOWNER_DISPLAY_NAME]'s Atom Feed\" href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]/atom' />\n";
	}

	// provide a link to the watched list feed
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
		$alternate.="\n<link rel='alternate' type='application/rss+xml' title='Watched Plans' href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/watched/watched.rss' />\n";

	// use a different icon for plan pages
	if ($_SERVER['PLANOWNER']) $subimage="_plan";


	profile('meta');
	profile('menus');

	// ____ LOGO AND SITE NAME  _______
	if (strlen($GLOBALS['pwlogo'])>1)
	{
		if (!strpos($GLOBALS['pwlogo'],'ttp://'))
		{
			$logosize=@getimagesize("$_SERVER[FILE_ROOT]/$GLOBALS[pwlogo]");
			$logoroot=$_SERVER['WEB_ROOT'];
		}
		else
		{
			$logosize=getimagesize("$GLOBALS[pwlogo]");
			$logoroot='';
		}

		$logostring="<img src='$logoroot$GLOBALS[pwlogo]' border='0' $logosize[3] align='absmiddle' />";
	}

	$sitename="<a href='$_SERVER[WEB_ROOT]/' id='sitename'>$logostring $GLOBALS[sitename]</a>";
	$titlesitename=trim(strip_tags($GLOBALS['sitename']));
	if (!$titlesitename) { $titlesitename='planwatch'; }


	// populates the nav buttons along the top of the page, along
	// with their menus.
	// TODO: maybe give offsite users a 'write' button tuned to their plan home?
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
		$toplinks=buttons_populate($content);


	profile('menus');


	// load GA if the user allows it
	if($_SERVER['USERINFO_ARRAY']['allow_analytics'])
	$analytics="<script type=\"text/javascript\">
var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
</script>
<script type=\"text/javascript\">
try {
var pageTracker = _gat._getTracker(\"UA-12269975-1\");
pageTracker._trackPageview();
} catch(err) {}</script>";



// LOGIN FORM
//------------------------------------------------------------------------------
// If the reader isn't logged in, present a login form.

	if (!user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
	{
		$toplinks="

	<form action='$_SERVER[WEB_ROOT]/scripts/form_shim.php' method='post' name='loginForm'>
		user <input id='login_username' type='text' name='user' size='10'/>
		pass <input id='login_userpass' type='password'  name='pass' size='6'/>
		<input type='checkbox' name='remember' id='remember' value='1'/>
		<label for='remember'>remember me</label>
		<input type='hidden' name='action' value='login'/>
		<input type='submit' name='action' value='login' onclick='document.forms.loginForm.submit();' />
		<input type='hidden' name='prevpage' value='".str_replace('/','!!',$thisurl)."'/>
	</form>\n";
	}



// READER TOOLBAR
//------------------------------------------------------------------------------
// If the reader is logged in, and reading a plan, build the reader toolbar.
	if ($_SERVER['URL_ARRAY'][1]=='send')
	{
		$_SERVER['PLANOWNER']=$_SERVER['URL_ARRAY'][2];
		plan_get_owner_info($_SERVER['PLANOWNER']);
	}

	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass'])
		&& $_SERVER['PLANOWNER'] && !strstr($content,'<h1>Plan Read Failed</h1>'))
	{
		profile('reader_toolbar','begin');
		$readertoolbar=output_build_reader_toolbar($content);
		profile('reader_toolbar','end');
	}



// MESSAGEBAR
//------------------------------------------------------------------------------
if ($_SERVER['USERINFO_ARRAY']['username'])
{
	if (!strpos($_SERVER['USERINFO_ARRAY']['real_name'],' '))
	{
		$message .= "<img src='$GLOBALS[toolsicon]'> <a href='$_SERVER[WEB_ROOT]/prefs/userinfo'>Click here to enter a valid (full) real name and make this annoying box go away.</a><br/>\n";
	}

	if(file_exists("$_SERVER[FILE_ROOT]/temp/system_message.txt"))
	{
		$message .= file_get_contents("$_SERVER[FILE_ROOT]/temp/system_message.txt")."<br/>\n";
	}
}


// SLOGAN
//------------------------------------------------------------------------------
// If the user allows slogans to be presented, go ahead and pick one.
// (randomly, weighted by the popularity of the slogan)

	profile('slogans','begin');

	if (!$_SERVER['USERINFO_ARRAY']['no_slogans'])
	{
		include_once('slogan_functions.php');
		$slogan_a=slogans_get_one();

		$slogan="<span class='slogan' title='slogan #$slogan_a[1], submitted by $slogan_a[2], rated $slogan_a[3]' id='slogan_text'>$slogan_a[0]</span>";

		// only logged-in users can vote on slogans
		if ($_SERVER['USERINFO_ARRAY']['username'])
		{
			$slogan.="<span class='slogan' id='slogan_rating'>
			<a href=\"javascript:slogans_modify_one_rating('$slogan_a[1]','1');\" class='edit_links' title='mod this slogan up to ".($slogan_a[3]+1)."'>+</a>
			<a href=\"javascript:slogans_modify_one_rating('$slogan_a[1]','-1');\" class='edit_links' title='mod this slogan down to ".($slogan_a[3]-1)."'>-</a>\n
			</span>";
		}

		// only admins and slogan owners can edit slogans
		if ($_SERVER['USER']==$slogan_a[2] || user_is_administrator())
		{
			$slogan_js_edit=str_replace(array('"',"'"),array('~','`'),$slogan_a[0]);
			$slogan.=" [ <a class='edit_links' href=\"javascript:slogans_edit_one('$slogan_a[1]');\" title=\"edit this slogan. it is yours, after all.\">edit</a> ]";
		}
	}

	$_SERVER['STOPWATCH']['slogans_end']=array_sum(explode(' ',microtime()));




// TEMPLATES AND HEADERS
//------------------------------------------------------------------------------


	// header to make sure the pages aren't cached very long.
	// we send this right before the page load so it doesn't get in the way of other stuff.
	@Header("Expires: 240");

	// READ IN AND POPULATE THE TEMPLATE

	// everyone but modern browser users gets the lynx template starting with this version.
	if (browser_is_modern())
	{
//		load the HTML5 template if the user prefers
//		everyone will eventually get this automatically
//		once it's better tested
		if($_SERVER['USERINFO_ARRAY']['html5_template']==1)
		{
			$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.default.html"));
			$doctype="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		}
		else
		{
			$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.html5.html"));
			$doctype="<!doctype html>\n";
		}

		//TODO: find a better solution than eval() for page output
		eval ("\$page=\"$page\";");
		$page=$doctype.$page;
	}
	else //if browser isn't modern, hand out the textmode template.
	{
		$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.textmode.html"));
		eval ("\$page=\"$page\";");
		$page="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">".$page;
	}


	// planwatch_fixlinks adds session id to links that need it
	$page=planwatch_fixlinks($page);


	// close out the stopwatch
	profile('output','end'); profile('pageload','end');
	
	if(strstr($page,'TIME-->'))
	{
		$timestring=profile_display();
		$page=str_replace('<!--LOADTIME-->',$timestring,$page);
		$page=str_replace('<!--TIME-->',round_sigdig($_SERVER['STOPWATCH']['pageload_end']-$_SERVER['STOPWATCH']['pageload_begin'],2),$page);
	}

	// display the "errors" panel
	if ($_SERVER['ERRORS'] && $_SERVER['USER'])
	{
		$errortime=time();
		$page=str_replace('<!--ERRORS-->',"<a id='error_link' href=\"javascript:document.getElementById('error_report').style.display='block';void(null);\">Errors (click me to report)</a> <div id='error_report'>Errors <a href='/report/$errortime'>report to josh</a> <a href=\"javascript:document.getElementById('error_report').style.display='none';void(null);\">hide</a><br/>$_SERVER[ERRORS]</div>",$page);
		file_put_contents("$_SERVER[DOCUMENT_ROOT]/temp/$errortime.error","<h2>Error Messages:</h2>$_SERVER[ERRORS]<hr/><h2>Error Details:</h2>$_SERVER[ERROR_DETAILS]<hr/><h2>Debug Info</h2>$_SERVER[DEBUG_INFO]");
	}

	// display the "debug" panel for administrators
	if ($_SERVER['DEBUG_INFO'] && user_is_administrator())
		$page=str_replace('<!--DEBUG-->',"<a  id='debug_link' href=\"javascript:document.getElementById('debug_report').style.display='block';void(null);\">debug</a> <div id='debug_report'>Debug Info <a href=\"javascript:document.getElementById('debug_report').style.display='none';void(null);\">hide</a><br/>$_SERVER[DEBUG_INFO]</div>",$page);

	return $page;
}


// OUTPUT_BUILD_READER_TOOLBAR($content)
// TODO: merge with output_build_reader_toolbar_phone()
function output_build_reader_toolbar($content)
{
	if($_SERVER['OUTPUT_MODE']=='IPHONE' || $_SERVER['OUTPUT_MODE']=='MOBILE')
		return output_build_reader_toolbar_mobile($content);
	else return output_build_reader_toolbar_desktop($content);
}

// OUTPUT_BUILD_READER_TOOLBAR_DESKTOP($content)
// TODO: merge with output_build_reader_toolbar_phone()
function output_build_reader_toolbar_desktop($content)
{

		if ($_SERVER['URL_ARRAY'][1]=='read'
			|| ($_SERVER['URL_ARRAY'][1]=='send' && $_SERVER['URL_ARRAY'][2]))
		{
			// build a bio link if appropriate (and the bio isn't currently the content)
			if ((file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[PLANOWNER]/bio.txt") ||
				(strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'diaryland') ||
				 strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'livejournal')))
				&& !($_SERVER['URL_ARRAY'][3]=='bio'))
			{
				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER_REAL_LOCATION]/bio'>bio</a>\n";
			}

			if ((strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'planworld.net') ||
				strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'amherst.edu') ||
				plan_is_local($_SERVER['PLANOWNER']))
				&& $_SERVER['PLANOWNER']!=$_SERVER['USER']
				&& $_SERVER['URL_ARRAY'][1]!='send')
			{
				if(IS_JOSH)
				{
					$send_files=files_list("$_SERVER[USER_ROOT]/sends",files_encode_safe_name("$_SERVER[PLANOWNER]")."*");
					if(is_array($send_files))
					{
						$lastsend=formattime(filemtime("$_SERVER[USER_ROOT]/sends/".end($send_files)));
						if(strstr(end($send_files),'.new')) $lastsend.=" <b>NEW</b>";
					}

					$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/send/$_SERVER[PLANOWNER_REAL_LOCATION]/'>send <span style='opacity: 0.5'>$lastsend</span></a>";
				}
				else
						$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/send/$_SERVER[PLANOWNER_REAL_LOCATION]/'>send</a>\n";
			}

			// build a plan link instead if the reader is reading the bio or send
			if ($_SERVER['URL_ARRAY'][3]=='bio' || $_SERVER['URL_ARRAY'][1]=='send')
			{
				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER_REAL_LOCATION]' >plan</a>\n";
			}


			// build an archive link, if appropriate
			if (plan_has_archives($_SERVER['PLANOWNER_REAL_LOCATION']))
			{
				if (!in_array('archives',$_SERVER['URL_ARRAY']))
					$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]/archives' >archives</a>\n";
				else
					$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]' >plan</a>\n";

			}

			// If the reader isn't watching the writer, offer the option
			if (!stristr($testwatchlist,$_SERVER['PLANOWNER']) && !stristr($testwatchlist,$_SERVER['PLANOWNER_REAL_LOCATION']))
			{
				$readertoolbar[]="<span id='watch_link'><a class='action' href=\"javascript:loadXMLDoc('$_SERVER[WEB_ROOT]/lists/add_ajax/watched/!$_SERVER[PLANOWNER_REAL_LOCATION]:$_SERVER[PLANOWNER_DISPLAY_NAME]!',null,'planwatch');void(null);\" title='add $_SERVER[PLANOWNER_DISPLAY_NAME] to your watched list' >watch</a></span>\n";
			}

			// if the reader *is* watching the writer, offer removal
			else
			{
				$readertoolbar[]="<span id='watch_link'><a class='action' href=\"javascript:loadXMLDoc('$_SERVER[WEB_ROOT]/lists/remove_ajax/watched/$_SERVER[PLANOWNER_REAL_LOCATION]',null,'planwatch');void(null);\" title='remove $_SERVER[PLANOWNER_DISPLAY_NAME] from your watched list' >unwatch</a></span>\n";
			}


			// if writer isn't a blog or the same as reader, offer the option of
			// blocking, unblocking, allowing, or disallowing access to reader's plan
			if ($_SERVER['PLANOWNER']!=$_SERVER['USER'] && !strpos($_SERVER['PLANOWNER'],'://'))
			{
				if (!user_is_blocked($_SERVER['USER'],$_SERVER[PLANOWNER]))
				{
					$readertoolbar[]="<a class='action' href='$_SERVER[WEB_ROOT]/lists/add/blocked/$_SERVER[PLANOWNER]' title='block $_SERVER[PLANOWNER_DISPLAY_NAME] from reading your plan altogether' >block</a>\n";
				}
				else
				{
					$readertoolbar[]="<a class='action' href='$_SERVER[WEB_ROOT]/lists/remove/blocked/$_SERVER[PLANOWNER]' title='unblock $_SERVER[PLANOWNER_DISPLAY_NAME] so they can read your public plan again' >unblock</a>\n";
				}

				if (!user_is_authorized($_SERVER['USER'],$_SERVER[PLANOWNER]))
				{
					$readertoolbar[]="<a class='action' href='$_SERVER[WEB_ROOT]/lists/add/allowed/$_SERVER[PLANOWNER]' title='allow $_SERVER[PLANOWNER_DISPLAY_NAME] to read your private plan' >allow</a>\n";
				}
				else
				{
					$readertoolbar[]="<a class='action' href='$_SERVER[WEB_ROOT]/lists/remove/allowed/$_SERVER[PLANOWNER]' title='disallow $_SERVER[PLANOWNER_DISPLAY_NAME] from reading your private plan'>disallow</a>\n";
				}

				// offer administrators a link to masquerade as writer
				// this is so it's easy to follow up on plan-reported bugs
				if (user_is_administrator()
					&& file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[PLANOWNER]/userinfo.dat"))
				{
					$readertoolbar[]="<a class='action' href='$_SERVER[WEB_ROOT]/masq/on/$_SERVER[PLANOWNER]'>masq</a>";
				}

				$readertoolbar[]="<a class='action' href='/lists/unread/$_SERVER[PLANOWNER]'>unread</a>";
			}

			if ($_SERVER['PLANOWNER'] && $_SERVER['PLANOWNER']==$_SERVER['USER'] && !strpos($_SERVER['PLANOWNER'],'@') && browser_is_modern())
			{
				$readertoolbar[]="<a class='action' href='/write'>new entry</a>";
			}

			// make the links into a string for output.
			foreach($readertoolbar as $tool)
			{
				if(strstr($tool,'action')) $class=" class='action' ";
				else $class='';
				$readertoolbar_html .= "<li $class>$tool</li>";
			}
			$readertoolbar = "\n<li class='listheader'>$_SERVER[PLANOWNER_DISPLAY_NAME]</li>".$readertoolbar_html;

			if(IS_JOSH)
			{
				if ($lasttime=plan_get_last_update($_SERVER['PLANOWNER']))
				{
					$readertoolbar.="<li class='action'  style='font-size: 80%; float: right;'>updated ".formattime($lasttime)."</li>";
				}
	
				if ($lastlogin = plan_get_last_login($_SERVER['PLANOWNER']))
				{
					if (trim($lastlogin) && $lastlogin>0) $readertoolbar.="<li class='action' style='font-size: 80%; float: right;'>active ".formattime($lastlogin)."</li>";
				}
			}
			else
			{
				if ($lasttime=plan_get_last_update($_SERVER['PLANOWNER']))
				{
					$readertoolbar.="<li class='plan_data_block'>Last Update: ".formattime($lasttime)."</li>";
				}
	
				if ($lastlogin = plan_get_last_login($_SERVER['PLANOWNER']))
				{
					if (trim($lastlogin) && $lastlogin>0) $readertoolbar.="<li class='plan_data_block' id='lastaction'>Last Action: ".formattime($lastlogin)."</li>";
				}
			}
		}
		return $readertoolbar;

}

// OUTPUT_BUILD_READER_TOOLBAR_MOBILE($content)
//------------------------------------------------------------------------------
// If the reader is logged in, and reading a plan, build the reader toolbar.
function output_build_reader_toolbar_mobile($content)
{
	if ($_SERVER['URL_ARRAY'][3]=='bio') $is_bio=TRUE;
	elseif ($_SERVER['URL_ARRAY'][1]=='send') $is_send=TRUE;
	elseif (in_array('archives',$_SERVER['URL_ARRAY'])) $is_archives=TRUE;
	else $is_plan=TRUE;

	$planwatchlist=file_get_contents("$_SERVER[USER_ROOT]/watchedlist.txt");
	if ($is_send)
	{
		$_SERVER['PLANOWNER']=$_SERVER['URL_ARRAY'][2];
		plan_get_owner_info($_SERVER['PLANOWNER']);
	}

	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass'])
		&& $_SERVER['PLANOWNER'] && !strstr($content,'<h1>Plan Read Failed</h1>'))
	{
		profile('reader_toolbar','begin');

		if ($is_plan || $is_bio || $is_archives || ($is_send && $_SERVER['URL_ARRAY'][2]))
		{
			// bio
			if ((file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[PLANOWNER]/bio.txt") ||
				(strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'diaryland') ||
				 strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'livejournal')))
				&& !$is_bio)
			{
				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER_REAL_LOCATION]/bio'>bio</a>";
			}

			// send
			if ((strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'planworld.net') ||
				strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'amherst.edu') ||
				plan_is_local($_SERVER['PLANOWNER']))
				&& $_SERVER['PLANOWNER']!=$_SERVER['USER']
				&& !$is_send)
			{
				$send_files=files_list("$_SERVER[USER_ROOT]/sends",files_encode_safe_name("$_SERVER[PLANOWNER]")."*");
				if(is_array($send_files))
				{
					$lastsend=formattime(filemtime("$_SERVER[USER_ROOT]/sends/".end($send_files)));
					if(strstr(end($send_files),'.new')) $lastsend.=" <b>NEW</b>";
					$lastsend="($lastsend)";
				}

				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/send/$_SERVER[PLANOWNER_REAL_LOCATION]/'>send</a>";
			}

			// planread
			if ($is_send || $is_bio)
			{
				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER_REAL_LOCATION]/'>plan</a>";
			}

			// archives
			if (plan_has_archives($_SERVER['PLANOWNER_REAL_LOCATION']))
			{
				if (!$is_archives)
					$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]/archives' >archives</a>";
				else
					$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]' >plan</a>";

			}

			// If the reader isn't watching the writer, offer the option
			if (!stristr($planwatchlist,$_SERVER['PLANOWNER']) && $is_plan)
			{
				$readertoolbar[]="<span id='watch_link'><a href=\"javascript:loadXMLDoc('$_SERVER[WEB_ROOT]/lists/add_ajax/watched/!$_SERVER[PLANOWNER_REAL_LOCATION]:$_SERVER[PLANOWNER_DISPLAY_NAME]!',null,'planwatch');void(null);\" title='add $_SERVER[PLANOWNER_DISPLAY_NAME] to your watched list' >watch</a></span>";
			}

			// if writer isn't a blog or the same as reader, offer the option of
			// blocking, unblocking, allowing, or disallowing access to reader's plan
			if ($_SERVER['PLANOWNER']!=$_SERVER['USER'] && !strpos($_SERVER['PLANOWNER'],'://'))
			{
				// offer administrators a link to masquerade as writer
				// this is so it's easy to follow up on plan-reported bugs
				if (user_is_administrator()
					&& file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[PLANOWNER]/userinfo.dat"))
				{
					$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/masq/on/$_SERVER[PLANOWNER]'>masq</a>";
				}

				if ($is_plan) $readertoolbar[]="<a href='/lists/unread/$_SERVER[PLANOWNER]'>unread</a>";
				if ($is_send) $readertoolbar[]="<a href='/send/$_SERVER[PLANOWNER]/unread'>unread</a>";
			}

			// make the links into a string for output.
			$readertoolbar = "<li class='toolbutton'>".implode("</li><li class='toolbutton'>",$readertoolbar)."</li>\n";
			$readertoolbar = str_replace("<li class='toolbutton'></li>","",$readertoolbar);
			if (($lasttime=plan_get_last_update($_SERVER['PLANOWNER'])) && $is_plan)
			{
				$readertoolbar="<li class='plan_data_block'>Last Update: ".formattime($lasttime)."</li>".$readertoolbar;
			}

			if ($lastlogin = plan_get_last_login($_SERVER['PLANOWNER']))
			{
				if ($lastlogin > 1) $readertoolbar="<li class='plan_data_block' id='lastaction'>Last Action: ".formattime($lastlogin)."</li>".$readertoolbar;
			}
		}
		profile('reader_toolbar','end');
	}
	return $readertoolbar;
}

//function output_iphone($title,$content) { output_mobile($title,$content); }

// OUTPUT_MOBILE()
//
// outputs iphone-formatted page. depends on iui library, template.iphone.html
//------------------------------------------------------------------------------
function output_iphone($title,$content)
{
	if (strstr($_SERVER['REQUEST_URI'],'/watched') || $_SERVER['REQUEST_URI']=='/') $current='watchedtab';
	if (strstr($_SERVER['REQUEST_URI'],'/send')) $current='sendtab';
	if (strstr($_SERVER['REQUEST_URI'],'/tools')) $current='toolstab';
	if (strstr($_SERVER['REQUEST_URI'],'/snitch')) $current='snitchtab';
	if (strstr($_SERVER['REQUEST_URI'],'/write')) $current='writetab';
	if (strstr($_SERVER['REQUEST_URI'],'/read')) $current='NULL';
	if (strstr($_SERVER['REQUEST_URI'],'/read') || strstr($_SERVER['REQUEST_URI'],'/send') || strstr($_SERVER['REQUEST_URI'],'/bio'))
	{
		$readertoolbar="<ul id='reader_toolbar'>".output_build_reader_toolbar($content)."</ul>";
	}

	$extracss="<style type='text/css'>{$_SERVER['PLANOWNER_INFO']['css']}</style>";

	if ($_SERVER['USER'] && $_SERVER['USER']!='guest') $tabbar="
			<ul class=\"tabbar\">
			<li id='writetab'><a id='write' onclick='setCurrent(\"writetab\");' href=\"/write/\">Write</a></li>
			<li id='snitchtab'><a id='snitch' onclick='setCurrent(\"snitchtab\");void(0);' href=\"/snitch/\">Snitch</a></li>
			<li id='sendtab'><a id='send' onclick='setCurrent(\"sendtab\");void(0);' href=\"/send/\">Send</a></li>
			<li id='watchedtab'><a id='watched' onclick='setCurrent(\"watchedtab\");void(0);' href=\"/\">Watched</a></li>
			<!--<li style='border: 0px; background: transparent;'>&nbsp;</li>-->
			<!--<li id='viewtab'><a id='view' onclick='setCurrent(\"viewtab\");void(0);' href=\"/read/$_SERVER[USER]\">View</a></li>-->
			<li id='xtab' ><a onclick='setCurrent(\"xtab\");void(0);' href='/logout' style='color: $GLOBALS[listsbgcolor]; font-weight: bold;'>&nbsp; X &nbsp;</a></li>
			<li id='toolstab' ><a id='tools' style='color: $GLOBALS[listsbgcolor]; font-weight: bold;' onclick='setCurrent(\"toolstab\");void(0);' href=\"/tools/\">&#x2699;</a></li>
		</ul>\n";

	else $tabbar="<!--
			<ul class=\"tabbar\">
			<li id='logintab'>Log In to Planwatch</li>
			</ul>-->\n";

	if (agent_is_lowfi())
	{
		$tabbar=str_replace(array("<ul","</ul>","<li","</li>"),array("<div","</div>"," [<span","</span>] "),$tabbar);
		$content=str_replace(array("<ul","</ul>","<li","</li>"),array("<div","</div>"," <div","</div> "),$content);
		$tabbar=str_replace(array("<ul","</ul>","<li","</li>"),array("<div","</div>","<span","</span>"),$tabbar);
		$tabbar=str_replace("> X <",">Logout<",$tabbar);
		$tabbar.="<hr />";
	}

	// READ IN AND POPULATE THE TEMPLATE
	if (((!strstr($_SERVER['HTTP_REFERER'],'iphone.planwatch') && !strstr($_SERVER['HTTP_REFERER'],'m.planwatch') && !strstr($_SERVER['HTTP_REFERER'],'m2.planwatch')) || $_COOKIE['redirected']=="1" || agent_is_lowfi()) && !strstr($_SERVER['REQUEST_URI'],'ajax'))
	{
		$cookie_host=$_SERVER['HTTP_HOST'];
		if (substr_count($cookie_host,'.') < 2) $cookie_host=".$cookie_host";
		$cookie_host=str_replace('www','',$cookie_host);

		setcookie("redirected","0",time()-1,$_SERVER['WEB_ROOT']."/",$cookie_host);

		$res_dir="$_SERVER[FILE_ROOT]/resources/";
		$version=max(
			filemtime("$res_dir/javascript/ajax.js"),
			filemtime("$res_dir/javascript/iphone_ajax.js"),
			filemtime("$res_dir/javascript/iui.js"),
			filemtime("$res_dir/templates/iui.css"),
			filemtime("$res_dir/templates/rss.css"),
			filemtime("$res_dir/templates/atom.css")
			);
		$page=str_replace("/includes/","/includes/$version/",$page);


		if($_SERVER['HTTP_HOST']=='m2.planwatch.org')
		{
			$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.iui3.html"));
			if (strstr($page,'</body>')) $page=str_replace('</body>',"<script type='application/x-javascript'>setCurrent('$current');</script>\n</body>",$page);
			else $page.="n<script type='application/x-javascript'>setCurrent('$current');</script>";
			eval ("\$page=\"$page\";");
			$page="<!DOCTYPE html>".$page;
		}
		else
		{
			$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.iphone.html"));
			if (strstr($page,'</body>')) $page=str_replace('</body>',"<script type='application/x-javascript'>setCurrent('$current');</script>\n</body>",$page);
			else $page.="n<script type='application/x-javascript'>setCurrent('$current');</script>";
			eval ("\$page=\"$page\";");
			$page=str_replace(array("& ","&& "),array("&amp;","&amp;&amp;"),$page);
			$page="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">".$page;
		}
	}
	else { $page="<div class='main_content' title='$title' id='$_SERVER[QUERY_STRING]'><style type='text/css'>{$_SERVER['PLANOWNER_INFO']['css']}</style>$readertoolbar $content</div>\n<script type='application/x-javascript'>setCurrent('$current');</script>"; }




	@Header("Expires: 240");
	@Header("Cache-control: max-age=240, must-revalidate");


	return $page;
}


// OUTPUT_FEED()
//
// outputs provided content as an rss feed
// depends on formatting_rss.php
//------------------------------------------------------------------------------
function output_feed($title,$content)
{
	if (strstr($content,'FEED_DIVIDER'))
	{
		$content=explode("<!-- FEED_DIVIDER -->",$content);
		$items=$content[0];
		$plan=$content[1];
	}
	else $plan=$content;

	if ($_SERVER['PLANOWNER'])
	{
		$rss_link = "http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]";
	}
	else
	{
		$rss_link = "http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/home";
	}
	$thisDate = gmdate('Y-m-d\TH:i:s+00:00');
	$thisYear = gmdate('Y');
	$fullcount=substr_count($plan,"<entry");

	// select a template based on feed type
	if ($_SERVER['OUTPUT_MODE']=='RSS')
	{
		$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.rss_1.0.xml"));
	}
	if ($_SERVER['OUTPUT_MODE']=='ATOM0.3')
	{
		$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.atom0.3.xml"));
	}
	if ($_SERVER['OUTPUT_MODE']=='ATOM1.0')
	{
		$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.atom.xml"));
	}
	if ($_SERVER['OUTPUT_MODE']=='ATOM_PRIVATE')
	{
		$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.atom.xml"));
	}
	eval ("\$page=\"$page\";");

	return $page;
}

// OUTPUT_AJAX()
//
// outputs provided content as plain data for AJAX
//------------------------------------------------------------------------------
function output_ajax($title,$content)
{
	// i am really serious about not caching ajax content. any of these directives
	// should be sufficient, but i am going ahead and issuing all of them just
	// to be on the safe side
	header("Cache-control: no-cache");
	header("Cache-control: no-store");
	header("Pragma: no-cache");
	header("Expires: 0");

	// the title attribute has no purpose ajax output, so we just send back the content.
	return $content;
}



// OUTPUT_SET_MODE
// defines the output mode.
// called near the beginning of the load process
function output_set_mode()
{
	// assume we're outputting HTML for the moment.
	// TODO: transition from polluting $_SERVER to using the constant
	// TODO: add a JSON output mode
	$_SERVER['OUTPUT_MODE']='HTML';
	
	
	// these tests determine if the output mode should be something else
	if ((strpos($_SERVER['REQUEST_URI'],'/rss') 
			|| strpos($_SERVER['REQUEST_URI'],'/rdf'))
		&& !strpos($_SERVER['REQUEST_URI'],'http'))
		
		$_SERVER['OUTPUT_MODE']='RSS';
	

	if ((strpos($_SERVER['REQUEST_URI'],'/atom')
			|| strpos($_SERVER['REQUEST_URI'],'/xml'))
		&& !strpos($_SERVER['REQUEST_URI'],'http'))

		$_SERVER['OUTPUT_MODE']='ATOM1.0';

	
	// TODO: we can probably safely dispense with atom0.3
	if ((strpos($_SERVER['REQUEST_URI'],'/atom0.3')
		&& !strpos($_SERVER['REQUEST_URI'],'http')))

		$_SERVER['OUTPUT_MODE']='ATOM0.3';
	

	// most ajax returns have their own code path, but some of them
	// just use the same path as the regular request with "/ajax" at the end.
	// this takes care of those.
	if ((strpos($_SERVER['REQUEST_URI'],'/ajax')
		&& !strpos($_SERVER['REQUEST_URI'],'http')
		&& !strpos($_SERVER['REQUEST_URI'],'ajax.js')))

		$_SERVER['OUTPUT_MODE']='AJAX';
	

	// sets up for mobile output.
	if (stristr($_SERVER['HTTP_HOST'],'iphone')
		|| stristr($_SERVER['HTTP_HOST'],'m.')
		|| ($_SERVER['HTTP_HOST']=="planwatch.org" && is_mobile() && $_COOKIE['forceview']!="desktop")
		|| ($_SERVER['HTTP_HOST']=="www.planwatch.org" && is_mobile() && $_COOKIE['forceview']!="desktop")
		|| $_COOKIE['forceview']=="mobile")
		
		$_SERVER['OUTPUT_MODE']='IPHONE';
		// yes, i know non-iphones are being used.
		// this will eventually change to "MOBILE" once
		// i get around to changing it everywhere.

	
	// sets up for beta mobile output
	if (stristr($_SERVER['HTTP_HOST'],'m2.'))

		$_SERVER['OUTPUT_MODE']='IPHONE';
	

	// sets up for desktop view on mobile device
	if (stristr($_SERVER['HTTP_HOST'],'d.pl')
		|| $_COOKIE['forceview']=="desktop")

		$_SERVER['OUTPUT_MODE']='HTML';



	// if we're being read from a blackberry, be very agressive
	// about cache-clearing
	// this may belong somewhere else
	if (stristr($_SERVER['HTTP_USER_AGENT'],'blackberry'))
	{
		header('Cache-Control: max-age=0'); // must-revalidate
		header('Expires: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
		header('ETag: ' . md5(rand(0,10000)));
	}


	return $_SERVER['OUTPUT_MODE'];
}

?>