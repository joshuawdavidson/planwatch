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
	if(isset($_SERVER['PLANOWNER_DISPLAY_NAME']))
	{
		$_SERVER['PLANOWNER_DISPLAY_NAME']=str_replace("'","",$_SERVER['PLANOWNER_DISPLAY_NAME']);
	}
	else
	{
		$_SERVER['PLANOWNER_DISPLAY_NAME']='';
	}
	$_SERVER['STOPWATCH']['content_end']=array_sum(explode(' ',microtime()));
	$_SERVER['STOPWATCH']['output_begin']=array_sum(explode(' ',microtime()));

	// if there is no cookie, user is 'guest'
	if (!$_SERVER['USER']) {$_SERVER['USER']='guest';}

	if($disposition!='echo')
	{

		if (OUTPUT_MODE=='HTML' || $_SERVER['OUTPUT_MODE']=='HTML')
		{
			header("Referrer-Policy: strict-origin-when-cross-origin");
			Header("Content-type: text/html; charset=UTF-8");
			$page=output_html($title,$content);
		}

		if (OUTPUT_MODE=='MOBILE' || $_SERVER['OUTPUT_MODE']=='MOBILE')
		{
			header("Referrer-Policy: strict-origin-when-cross-origin");
			Header("Content-type: text/html; charset=UTF-8");
			// $page="mobile page";
			$page=output_mobile($title,$content);
		}

		if (OUTPUT_MODE=='RSS' || OUTPUT_MODE=='ATOM' || OUTPUT_MODE=='ATOM_PRIVATE')
		{
			header("Referrer-Policy: strict-origin-when-cross-origin");
			Header("Content-type: text/xml; charset=UTF-8");
			$page=output_feed($title,$content);
		}

		if (OUTPUT_MODE=='AJAX' || $_SERVER['OUTPUT_MODE']=='AJAX')
		{
			header("Referrer-Policy: strict-origin-when-cross-origin");
			Header("Content-type: text/html; charset=UTF-8");
			$page=output_ajax($title,$content);
		}

		if ($disposition=='return') {return $page;}
		if (strpos($disposition,$_SERVER['DOCUMENT_ROOT'])!==FALSE)
		{
			file_write_contents($disposition,$page);
			return $page;
		}
	}
	else {$page="$content";}

	if($page) {header("http/1.0 200 ok");}

// buffer all upcoming output
ob_start();
echo $page;

// get the size of the output
$size = ob_get_length();

// send headers to tell the browser to close the connection
header("Content-Length: $size");
header('Connection: close');

// flush all output
ob_end_flush();
ob_flush();
flush();
	exit;
}



// OUTPUT_HTML()
//
// outputs provided content as an HTML page
// depends on formatting_html.php
//------------------------------------------------------------------------------
function output_html($title,$content)
{
	$alternate=$extrajs='';
	require_once("formatting_html.php");

// SKIN AND FONTS
//------------------------------------------------------------------------------
	if (isset($hatespictures) && $hatespictures)
	{
		$extracss = 'img { display: none; } #header img { display: inline; }';
	}


// WATCHED LIST
// we load via ajax for modern browsers, this is only for old browsers
// i should probably find a way to improve this for simply js-disabled readers
//------------------------------------------------------------------------------
	if (!browser_is_modern())
	{
		$planwatchlist=format_watched_list_html();
	}


// HTML <HEAD> TAGS
// AND TOP LINKS & MENUS
//------------------------------------------------------------------------------

	profile("meta");

	if(!isset($_SERVER['PLANOWNER_INFO']['css']))
	{
		$_SERVER['PLANOWNER_INFO']['css']='';
	}

	$title=strip_tags($title);
	$extracss="<style type='text/css'>{$_SERVER['PLANOWNER_INFO']['css']}</style>";
	$thisurl="https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

	// if watched position is set to bottom, we need javascript for that.
	if (isset($_SERVER['USERINFO_ARRAY']['wlpos']) && $_SERVER['USERINFO_ARRAY']['wlpos']==1)
	{
		$bottomjs.="\n<script type='text/javascript'>
		var pwnav=document.getElementById('pwnav');
		var pwbody=document.getElementById('pwnav').parentNode;
		var pwcontent=document.getElementById('content');
		pwbody.replaceChild(pwcontent, pwnav);
		pwbody.insertBefore(pwnav);
		</script>\n";
	}

	// if we're writing, set up draft autosaves
	if (strpos($_SERVER['REQUEST_URI'],'write')!==FALSE)
	{
		$_SERVER['PLAN_DRAFT_TIME']=time();
		$extrajs.="<script type='text/javascript'>setTimeout(\"saveDraft($_SERVER[PLAN_DRAFT_TIME]);\",61131);</script>";
	}

	if (isset($_SERVER['INURL_SID']))
		$js_sid="$_SERVER[SESSION_ID]";
	else $js_sid="";

	// if we're reading something besides an rss feed, set charset
	// to UTF-8. in the html5 template, everything's always set to UTF-8
	// and it doesn't seem to break anything. we can probably safely
	// remove this once we switch.
	if ($_SERVER['URL_ARRAY'][1]=='read' && strpos($_SERVER['URL_ARRAY'][2],'http')!==FALSE) $encoding="<meta http-equiv='Content-type' content='text/html; charset=UTF-8' />";

	// not that anyone will pay attention, but go ahead and put a copyright
	// notice in.
	if (isset($_SERVER['PLANOWNER']))
	{
		$subimage="_plan";
		$copyright="<meta http-equiv='copyright' content='This plan is copyright ".date("Y")." $_SERVER[PLANOWNER_DISPLAY_NAME], all rights reserved.' />";
		// if we're looking at a nonprivate local plan, provide an rss feed
		if (!plan_is_private($_SERVER['PLANOWNER']) && !plan_is_registered_only($_SERVER['PLANOWNER']) && plan_is_local($_SERVER['PLANOWNER']))
		{
			$alternate.="<link rel='alternate' type='application/atom+xml' title=\"$_SERVER[PLANOWNER_DISPLAY_NAME]'s Atom Feed\" href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]/atom' />\n";
		}
	}

	// provide a link to the watched list feed
	if (isset($_SERVER['SESSION_ID']) && user_verify_sessionkey($_SERVER['SESSION_ID']))
	{
		$alternate.="\n<link rel='alternate' type='application/rss+xml' title='Watched Plans' href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/watched/watched.rss' />\n";
	}

	profile('meta');
	profile('menus');

	// ____ LOGO AND SITE NAME  _______
	$logostring='';
	if (isset($GLOBALS['pwlogo']))
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
	if (isset($_SERVER['SESSION_ID']) && user_verify_sessionkey($_SERVER['SESSION_ID']))
	{
		$toplinks=buttons_populate($content);
	}
	profile('menus');


// LOGIN FORM
//------------------------------------------------------------------------------
// If the reader isn't logged in, present a login form.

	if (!isset($_SERVER['SESSION_ID']) || !$_SERVER['SESSION_ID'])
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

	if (isset($_SERVER['SESSION_ID']) && user_verify_sessionkey($_SERVER['SESSION_ID'])
		&& isset($_SERVER['PLANOWNER'])
		&& strpos($content,'<h1>Plan Read Failed</h1>')===FALSE
	)
	{
		profile('reader_toolbar','begin');
		$readertoolbar=output_build_reader_toolbar($content);
		profile('reader_toolbar','end');
	}



// MESSAGEBAR
//------------------------------------------------------------------------------
if (isset($_SERVER['USERINFO_ARRAY']['username']) && $_SERVER['USERINFO_ARRAY']['username']!='Guest')
{
	if (!strpos($_SERVER['USERINFO_ARRAY']['real_name'],' '))
	{
		if($_SERVER['USERINFO_ARRAY']['username']=='testuser99') { print_r(user_read_info("testuser99")); exit; }
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

	profile('slogans');

	if (!isset($_SERVER['USERINFO_ARRAY']['no_slogans']) || !$_SERVER['USERINFO_ARRAY']['no_slogans'])
	{
		include_once('slogan_functions.php');
		$slogan_a=slogans_get_one();

		$slogan="<span class='slogan' title='slogan #$slogan_a[1], submitted by $slogan_a[2], rated $slogan_a[3]' id='slogan_text'>$slogan_a[0]</span>";

		// only logged-in users can vote on slogans
		if (isset($_SERVER['USERINFO_ARRAY']['username']) && $_SERVER['USERINFO_ARRAY']['username']!='Guest')
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

	profile('slogans');




// TEMPLATES AND HEADERS
//------------------------------------------------------------------------------


	// header to make sure the pages aren't cached very long.
	// we send this right before the page load so it doesn't get in the way of other stuff.
	Header("Expires: 240");

// GENERATE THE STYLESHEET
	$skin_filetime   = file_exists("$_SERVER[USER_ROOT]/skin.txt")     ? filemtime("$_SERVER[USER_ROOT]/skin.txt")     : 0;
	$colors_filetime = file_exists("$_SERVER[USER_ROOT]/colors.txt")   ? filemtime("$_SERVER[USER_ROOT]/colors.txt")   : 0;
	$fonts_filetime  = file_exists("$_SERVER[USER_ROOT]/fonts.txt")    ? filemtime("$_SERVER[USER_ROOT]/fonts.txt")    : 0;
	$css_filetime    = file_exists("$_SERVER[USER_ROOT]/user_css.txt") ? filemtime("$_SERVER[USER_ROOT]/user_css.txt") : 0;
	// if the stylesheet cache is newer than the components, just send it again.
	if(!file_exists("$_SERVER[DOCUMENT_ROOT]/temp/".md5($_SERVER['USER']).".".OUTPUT_MODE.".styles.css")
		|| (filemtime("$_SERVER[DOCUMENT_ROOT]/temp/".md5($_SERVER['USER']).".".OUTPUT_MODE.".styles.css")
		< max(filemtime("$_SERVER[DOCUMENT_ROOT]/scripts/stylesheet.php"),$skin_filetime,$colors_filetime,$fonts_filetime,$css_filetime)
		))
	{
		$_SERVER['cache_stylesheet']=TRUE;
		include_once('stylesheet.php');
	}
	$time=filemtime("$_SERVER[DOCUMENT_ROOT]/temp/".md5($_SERVER['USER']).".".OUTPUT_MODE.".styles.css");
	$stylesheet_url="/temp/".md5($_SERVER['USER'])."$time.".OUTPUT_MODE.".styles.css";


// READ IN AND POPULATE THE TEMPLATE
	// everyone but modern browser users gets the lynx template starting with this version.
	if (browser_is_modern())
	{
		// load the HTML5 template
		$page=file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.html5.html");
		$doctype="<!doctype html>\n";

		$web_root     = '/';
		$http_host    = $_SERVER['HTTP_HOST'];
		$user         = $_SERVER['USER'];
		$wlpos        = isset($_SERVER['USERINFO_ARRAY']['wlpos']) ? $_SERVER['USERINFO_ARRAY']['wlpos'] : 1;
		$defined_vars = get_defined_vars();
		preg_match_all('/\[\[([\w.]+)\]\]/', $page, $page_vars);
		foreach($page_vars[0] as $i=>$page_var)
		{
			if(strpos($page_var[0],'.'))
			{
				$page_var_parts = explode('.',$page_vars[1][$i]);
				if(count($page_var_parts)==2)
				{
					$page_var_replacement = $$page_var_parts[0][$$page_var_parts[1]];
				}
				if(count($page_var_parts)==3)
				{
					$page_var_replacement = $$page_var_parts[0][$$page_var_parts[1]][$$page_var_parts[2]];
				}
				if(count($page_var_parts)==4)
				{
					$page_var_replacement = $$page_var_parts[0][$$page_var_parts[1]][$$page_var_parts[2]][$$page_var_parts[3]];
				}

				$page = str_replace($page_var,$page_var_replacement,$page);
			}
			else
			{
				if(!isset($defined_vars[$page_vars[1][$i]]))
				{
					$defined_vars[$page_vars[1][$i]]='';
				}
				$page = str_replace($page_var,$defined_vars[$page_vars[1][$i]],$page);
			}
		}

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

	if(strpos($page,'TIME-->'))
	{
		$timestring=profile_display();
		$page=str_replace('<!--LOADTIME-->',$timestring,$page);
		$page=str_replace('<!--TIME-->',round_sigdig($_SERVER['STOPWATCH']['pageload_end']-$_SERVER['STOPWATCH']['pageload_begin'],2),$page);
	}

	// display the "errors" panel
	if (isset($_SERVER['ERRORS']) && !empty($_SERVER['ERRORS']) && isset($_SERVER['USER']))
	{
		$errortime=time();
		$page=str_replace('<!--ERRORS-->',"<a id='error_link' href=\"javascript:document.getElementById('error_report').style.display='block';void(null);\">Errors (click me to report)</a> <div id='error_report'>Errors <a href='/report/$errortime'>report to josh</a> <a href=\"javascript:document.getElementById('error_report').style.display='none';void(null);\">hide</a><br/>$_SERVER[ERRORS]</div>",$page);
		file_put_contents("$_SERVER[DOCUMENT_ROOT]/temp/$errortime.error","<h2>Error Messages:</h2>$_SERVER[ERRORS]<hr/><h2>Error Details:</h2>$_SERVER[ERROR_DETAILS]<hr/><h2>Debug Info</h2>$_SERVER[DEBUG_INFO]");
	}

	// display the "debug" panel for administrators
	if (isset($_SERVER['DEBUG_INFO']) && !empty($_SERVER['DEBUG_INFO']) && user_is_administrator())
	{
		$page=str_replace('<!--DEBUG-->',"<a  id='debug_link' href=\"javascript:document.getElementById('debug_report').style.display='block';void(null);\">debug</a> <div id='debug_report'>Debug Info <a href=\"javascript:document.getElementById('debug_report').style.display='none';void(null);\">hide</a><br/>$_SERVER[DEBUG_INFO]</div>",$page);
	}

	return $page;
}


// OUTPUT_BUILD_READER_TOOLBAR($content)
// TODO: merge with output_build_reader_toolbar_phone()
function output_build_reader_toolbar($content)
{
	if(OUTPUT_MODE=='MOBILE')
	{
		return output_build_reader_toolbar_mobile($content);
	}
	else
	{
		return output_build_reader_toolbar_desktop($content);
	}
}

// OUTPUT_BUILD_READER_TOOLBAR_DESKTOP($content)
// TODO: merge with output_build_reader_toolbar_phone()
function output_build_reader_toolbar_desktop($content)
{
	$readertoolbar_html='';
		if ($_SERVER['USER_ROOT'] && file_exists("$_SERVER[USER_ROOT]/watchedlist.txt"))
		{
			$testwatchlist=file_get_contents("$_SERVER[USER_ROOT]/watchedlist.txt");
		}
		else
		{
			$testwatchlist = "sort by none\n!prune\nsystem";
		}

		if ($_SERVER['URL_ARRAY'][1]=='read'
			|| ($_SERVER['URL_ARRAY'][1]=='send' && $_SERVER['URL_ARRAY'][2]))
		{
			// build a bio link if appropriate (and the bio isn't currently the content)
			if ((file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[PLANOWNER]/bio.txt") ||
				(strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'diaryland') ||
				 strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'livejournal')))
				&& (!isset($_SERVER['URL_ARRAY'][3]) || $_SERVER['URL_ARRAY'][3]!='bio')
			)
			{
				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER_REAL_LOCATION]/bio'>bio</a>\n";
			}

			if ((strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'planworld.net') ||
				strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'amherst.edu') ||
				plan_is_local($_SERVER['PLANOWNER']))
				&& $_SERVER['PLANOWNER']!=$_SERVER['USER']
				&& $_SERVER['URL_ARRAY'][1]!='send')
			{
				exec("ls -t $_SERVER[USER_ROOT]/sends/".files_encode_safe_name("$_SERVER[PLANOWNER]")."*",$send_files);
				if(is_array($send_files))
				{
					$lastsend=formattime(filemtime("$_SERVER[USER_ROOT]/sends/".basename(reset($send_files))));
					$lastsend="$lastsend";
					if(strpos(reset($send_files),'.new')!==FALSE) $lastsend.=" <b>NEW</b>";
				}

				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/send/$_SERVER[PLANOWNER_REAL_LOCATION]/'>send <span style='opacity: 0.5'>$lastsend</span></a>";
			}

			// build a plan link instead if the reader is reading the bio or send
			if (isset($_SERVER['URL_ARRAY'][3]) && ($_SERVER['URL_ARRAY'][3]=='bio' || $_SERVER['URL_ARRAY'][1]=='send'))
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
			if (!stristr($testwatchlist,$_SERVER['PLANOWNER'])
				&& !stristr($testwatchlist,$_SERVER['PLANOWNER_REAL_LOCATION']))
			{
				$readertoolbar[]="<span id='watch_link'><a class='action' onclick=\"modify_watch_state('$_SERVER[PLANOWNER_REAL_LOCATION]','$_SERVER[PLANOWNER_DISPLAY_NAME]','add');\" title='add $_SERVER[PLANOWNER_DISPLAY_NAME] to your watched list' >watch</a></span>\n";
			}

			// if the reader *is* watching the writer, offer removal
			else
			{
				$readertoolbar[]="<span id='watch_link'><a class='action' onclick=\"modify_watch_state('$_SERVER[PLANOWNER_REAL_LOCATION]','$_SERVER[PLANOWNER_DISPLAY_NAME]','remove');\" title='add $_SERVER[PLANOWNER_DISPLAY_NAME] to your watched list' >unwatch</a></span>\n";
			}


			// if writer isn't a blog or the same as reader, offer the option of
			// blocking, unblocking, allowing, or disallowing access to reader's plan
			if ($_SERVER['PLANOWNER']!=$_SERVER['USER'] && !strpos($_SERVER['PLANOWNER'],'://'))
			{
				if (!user_is_blocked($_SERVER['USER'],$_SERVER['PLANOWNER']))
				{
					$readertoolbar[]="<a class='action' href='$_SERVER[WEB_ROOT]/lists/add/blocked/$_SERVER[PLANOWNER]' title='block $_SERVER[PLANOWNER_DISPLAY_NAME] from reading your plan altogether' >block</a>\n";
				}
				else
				{
					$readertoolbar[]="<a class='action' href='$_SERVER[WEB_ROOT]/lists/remove/blocked/$_SERVER[PLANOWNER]' title='unblock $_SERVER[PLANOWNER_DISPLAY_NAME] so they can read your public plan again' >unblock</a>\n";
				}

				if (!user_is_authorized($_SERVER['USER'],$_SERVER['PLANOWNER']))
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
				if(strpos($tool,'action')!==FALSE) $class=" class='action' ";
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

				if (isset($lastlogin) && trim($lastlogin) && $lastlogin>0) $readertoolbar.="<li class='action' style='font-size: 80%; float: right;'><script>getlastaction($_SERVER[PLANOWNER])</script></li>";
			}
			else
			{
				if ($lasttime=plan_get_last_update($_SERVER['PLANOWNER']))
				{
					$readertoolbar.="<li class='plan_data_block'>updated ".formattime($lasttime)."</li>";
				}

				if (trim($lastlogin) && $lastlogin>0) $readertoolbar.="<li class='plan_data_block' id='lastaction'><script>getlastaction($_SERVER[PLANOWNER])</script>&nbsp;</li>";
			}
		}
		return $readertoolbar;

}

// OUTPUT_BUILD_READER_TOOLBAR_MOBILE($content)
//------------------------------------------------------------------------------
// If the reader is logged in, and reading a plan, build the reader toolbar.
function output_build_reader_toolbar_mobile($content)
{
	$is_bio=$is_send=$is_archives=$is_plan=false;
	$readertoolbar=[];

	if ($_SERVER['USER_ROOT'] && file_exists("$_SERVER[USER_ROOT]/watchedlist.txt")) $testwatchlist=file_get_contents("$_SERVER[USER_ROOT]/watchedlist.txt");

	if (isset($_SERVER['URL_ARRAY'][3]) && $_SERVER['URL_ARRAY'][3]=='bio') {$is_bio=TRUE;}
	elseif (isset($_SERVER['URL_ARRAY'][1]) && $_SERVER['URL_ARRAY'][1]=='send') {$is_send=TRUE;}
	elseif (isset($_SERVER['URL_ARRAY']) && in_array('archives',$_SERVER['URL_ARRAY'])) {$is_archives=TRUE;}
	else {$is_plan=TRUE;}

	$planwatchlist=file_get_contents("$_SERVER[USER_ROOT]/watchedlist.txt");
	if ($is_send)
	{
		$_SERVER['PLANOWNER']=$_SERVER['URL_ARRAY'][2];
		plan_get_owner_info($_SERVER['PLANOWNER']);
	}

	if (user_verify_sessionkey($_SERVER['SESSION_ID'])
		&& $_SERVER['PLANOWNER'] && strpos($content,'<h1>Plan Read Failed</h1>')===FALSE)
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
				if(IS_JOSH)
				{
					$moremenu[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER_REAL_LOCATION]/bio'>bio</a>";
				}
				else
				{
					$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER_REAL_LOCATION]/bio'>bio</a>";
				}
			}

			// send
			if ((strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'planworld.net') ||
				strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'amherst.edu') ||
				plan_is_local($_SERVER['PLANOWNER']))
				&& $_SERVER['PLANOWNER']!=$_SERVER['USER']
				&& !$is_send)
			{
				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/send/$_SERVER[PLANOWNER_REAL_LOCATION]/'>send</a>";
			}

			// planread
			if ($is_send || $is_bio || $is_archives)
			{
				$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER_REAL_LOCATION]/'>plan</a>";
			}

			// archives
			if (plan_has_archives($_SERVER['PLANOWNER_REAL_LOCATION']))
			{
				if (!$is_archives)
					if(IS_JOSH)
					{
						$moremenu[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]/archives' >archives</a>";
					}
					else
					{
						$readertoolbar[]="<a href='$_SERVER[WEB_ROOT]/read/$_SERVER[PLANOWNER]/archives' >archives</a>";
					}

			}

			// If the reader isn't watching the writer, offer the option
			if (!stristr($testwatchlist,$_SERVER['PLANOWNER']) && !stristr($testwatchlist,$_SERVER['PLANOWNER_REAL_LOCATION']))
			{
				$readertoolbar[]="<span id='watch_link'><a class='action' onclick=\"modify_watch_state('$_SERVER[PLANOWNER_REAL_LOCATION]','$_SERVER[PLANOWNER_DISPLAY_NAME]','add');\" title='add $_SERVER[PLANOWNER_DISPLAY_NAME] to your watched list' >watch</a></span>";
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
					$moremenu[]="<a href='$_SERVER[WEB_ROOT]/masq/on/$_SERVER[PLANOWNER]'>masq</a>";
				}

				if ($is_plan) $readertoolbar[]="<a href='/lists/unread/$_SERVER[PLANOWNER]'>unread</a>";
				if ($is_send) $readertoolbar[]="<a href='/send/$_SERVER[PLANOWNER]/unread'>unread</a>";
			}

			// make the links into a string for output.
			if(is_countable($readertoolbar)) {$readertoolbar_st = "<li class='toolbutton'>".implode("</li><li class='toolbutton'>",$readertoolbar)."</li>\n";}
			$readertoolbar_st = str_replace("<li class='toolbutton'></li>","",$readertoolbar_st);
			if (($lasttime=plan_get_last_update($_SERVER['PLANOWNER'])) && $is_plan)
			{
				$readertoolbar_st="<li class='plan_data_block'>Updated:&nbsp;".formattime($lasttime)."</li>\n".$readertoolbar_st;
			}

			if ($lastlogin = plan_get_last_login($_SERVER['PLANOWNER']))
			{
				if ($lastlogin > 1) $readertoolbar_st="<li class='plan_data_block' id='lastaction'>Active:&nbsp;".formattime($lastlogin)."</li>\n".$readertoolbar_st;
			}
		}
		profile('reader_toolbar','end');
	}

	$moremenu_st='';
	if(isset($moremenu) && is_array($moremenu)) { $moremenu_st="<li>".implode("</li>\n<li>",$moremenu)."</li>"; }
	$readertoolbar_st="$readertoolbar_st <li style='border: none; background:transparent;'><a style='color: $GLOBALS[listsbgcolor]; background: transparent;' onclick='if(document.getElementById(\"moremenu\").style.display==\"none\") document.getElementById(\"moremenu\").style.display=\"block\"; else document.getElementById(\"moremenu\").style.display=\"none\";'>&#x25BC;</a> <ul id='moremenu' style='display: none; position: absolute; right: 0px; z-index: 1000;'>$moremenu_st</ul></li>\n";

	return $readertoolbar_st;
}

// OUTPUT_MOBILE()
//
// outputs iphone-formatted page. depends on iui library, template.iui3.html
//------------------------------------------------------------------------------
function output_mobile($title,$content)
{
	$current='toolstab';
	if (strpos($_SERVER['REQUEST_URI'],'/watched')!==FALSE || $_SERVER['REQUEST_URI']=='/') {$current='watchedtab';}
	if (strpos($_SERVER['REQUEST_URI'],'/send')!==FALSE)   {$current='sendtab';}
	if (strpos($_SERVER['REQUEST_URI'],'/tools')!==FALSE)  {$current='toolstab';}
	if (strpos($_SERVER['REQUEST_URI'],'/snitch')!==FALSE) {$current='snitchtab';}
	if (strpos($_SERVER['REQUEST_URI'],'/write')!==FALSE)  {$current='writetab';}
	if (strpos($_SERVER['REQUEST_URI'],'/read')!==FALSE)   {$current='NULL';}
	if (strpos($_SERVER['REQUEST_URI'],'/read')!==FALSE || strpos($_SERVER['REQUEST_URI'],'/send')!==FALSE || strpos($_SERVER['REQUEST_URI'],'/bio')!==FALSE)
	{
		$readertoolbar="<ul id='reader_toolbar'>".output_build_reader_toolbar($content)."</ul>";
	}

	// READ IN AND POPULATE THE TEMPLATE
		if(isset($_SERVER['PLANOWNER_INFO']['css']))
		{
			$extracss="<style type='text/css'>{$_SERVER['PLANOWNER_INFO']['css']}</style>";
		}

		if ($_SERVER['USER'] && $_SERVER['USER']!='guest') $tabbar="
				<ul class=\"tabbar\">
				<li id='writetab'><a id='write' onclick='setCurrent(\"writetab\");' href=\"/write/\">Write</a></li>
				<li id='snitchtab'><a id='snitch' onclick='setCurrent(\"snitchtab\");void(0);' href=\"/snitch/\">Snitch</a></li>
				<li id='sendtab'><a id='send' onclick='setCurrent(\"sendtab\");void(0);' href=\"/send/\">Send</a></li>
				<li id='watchedtab'><a id='watched' onclick='setCurrent(\"watchedtab\");void(0);' href=\"/\">Watched</a></li>
				<li id='xtab' ><a onclick='setCurrent(\"xtab\");void(0);' href='/logout' style='color: $GLOBALS[listsbgcolor]; font-weight: bold;'>&nbsp;X&nbsp;</a></li>
				<li id='toolstab' ><a id='tools' style='color: $GLOBALS[listsbgcolor]; font-weight: bold;' onclick='setCurrent(\"toolstab\");void(0);' href=\"/tools/\">&nbsp;&#x2699;&nbsp;</a></li>
			</ul>\n";

		else $tabbar="<!--
				<ul class=\"tabbar\">
				<li id='logintab'>Log In to Planwatch</li>
				</ul>-->\n";

		$cookie_host=$_SERVER['HTTP_HOST'];
		if (substr_count($cookie_host,'.') < 2) $cookie_host=".$cookie_host";
		$cookie_host=str_replace('www','',$cookie_host);

		setcookie("redirected","0",time()-1,$_SERVER['WEB_ROOT']."/",$cookie_host);

		// GENERATE THE STYLESHEET
		// if the stylesheet cache is newer than the components, just send it again.
		$iui_filetime = file_exists("$_SERVER[DOCUMENT_ROOT]/resources/templates/iui3.css") ? filemtime("$_SERVER[DOCUMENT_ROOT]/resources/templates/iui3.css") : 0;
		$css_filetime = file_exists("$_SERVER[DOCUMENT_ROOT]/scripts/stylesheet.php")       ? filemtime("$_SERVER[DOCUMENT_ROOT]/scripts/stylesheet.php")       : 0;
		$skn_filetime = file_exists("$_SERVER[USER_ROOT]/skin.txt")                         ? filemtime("$_SERVER[USER_ROOT]/skin.txt")                         : 0;
		$clr_filetime = file_exists("$_SERVER[USER_ROOT]/colors.txt")                       ? filemtime("$_SERVER[USER_ROOT]/colors.txt")                       : 0;
		$fnt_filetime = file_exists("$_SERVER[USER_ROOT]/fonts.txt")                        ? filemtime("$_SERVER[USER_ROOT]/fonts.txt")                        : 0;
		$ucs_filetime = file_exists("$_SERVER[USER_ROOT]/user_css.txt")                     ? filemtime("$_SERVER[USER_ROOT]/user_css.txt")                     : 0;
		if(!file_exists("$_SERVER[DOCUMENT_ROOT]/temp/".md5($_SERVER['USER']).".".OUTPUT_MODE.".styles.css")
			|| (filemtime("$_SERVER[DOCUMENT_ROOT]/temp/".md5($_SERVER['USER']).".".OUTPUT_MODE.".styles.css")
			< max($iui_filetime,$css_filetime,$skn_filetime,$clr_filetime,$fnt_filetime,$ucs_filetime)
			))
		{
			$_SERVER['cache_stylesheet']=TRUE;
			include_once('stylesheet.php');
		}
		$time=filemtime("$_SERVER[DOCUMENT_ROOT]/temp/".md5($_SERVER['USER']).".".OUTPUT_MODE.".styles.css");
		$stylesheet_url="/temp/".md5($_SERVER['USER'])."$time.".OUTPUT_MODE.".styles.css";

		$page=file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.iui3.html");
		$doctype="<!doctype html>\n";

		$web_root     = '/';
		$http_host    = $_SERVER['HTTP_HOST'];
		$user         = $_SERVER['USER'];
		$wlpos        = isset($_SERVER['USERINFO_ARRAY']['wlpos']) ? $_SERVER['USERINFO_ARRAY']['wlpos'] : 1;
		$defined_vars = get_defined_vars();
		preg_match_all('/\[\[([\w.]+)\]\]/', $page, $page_vars);
		foreach($page_vars[0] as $i=>$page_var)
		{
			if(strpos($page_var[0],'.'))
			{
				$page_var_parts = explode('.',$page_vars[1][$i]);
				if(count($page_var_parts)==2)
				{
					$page_var_replacement = $$page_var_parts[0][$$page_var_parts[1]];
				}
				if(count($page_var_parts)==3)
				{
					$page_var_replacement = $$page_var_parts[0][$$page_var_parts[1]][$$page_var_parts[2]];
				}
				if(count($page_var_parts)==4)
				{
					$page_var_replacement = $$page_var_parts[0][$$page_var_parts[1]][$$page_var_parts[2]][$$page_var_parts[3]];
				}

				$page = str_replace($page_var,$page_var_replacement,$page);
			}
			else
			{
				if(!isset($defined_vars[$page_vars[1][$i]]))
				{
					$defined_vars[$page_vars[1][$i]]='';
				}
				$page = str_replace($page_var,$defined_vars[$page_vars[1][$i]],$page);
			}
		}

		$page=$doctype.$page;


		// $page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.iui3.html"));
		if (strpos($page,'</body>') && $current)
		{
			$page=str_replace('</body>',"<script type='application/x-javascript'>if(document.getElementById('$current')) { setCurrent('$current'); }</script>\n</body>",$page);
		}
		else
		{
			$page.="<script type='application/x-javascript'>setCurrent('$current');</script>";
		}
		// eval ("\$page=\"$page\";");
		// $page="<!DOCTYPE html>".$page;

	if (!stristr($_SERVER['HTTP_USER_AGENT'],'blackberry')) //blackberry has more aggressive settings that happen earlier in the load.
	{
		@Header("Expires: 240");
		@Header("Cache-control: max-age=240, must-revalidate");
	}

	return $page;

}

// OUTPUT_FEED()
//
// outputs provided content as an rss feed
// depends on formatting_rss.php
//------------------------------------------------------------------------------
function output_feed($title,$content)
{
	if (strpos($content,'FEED_DIVIDER'))
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
	if (OUTPUT_MODE=='RSS')
	{
		$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.rss_1.0.xml"));
	}
	if (OUTPUT_MODE=='ATOM')
	{
		$page=str_replace('"','\"',file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/template.atom.xml"));
	}
	if (OUTPUT_MODE=='ATOM_PRIVATE')
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
	$content=planwatch_fixlinks($content);

	// prepend the title in a comment to facilitate testing.
	return "<!-- ".strip_tags($title)."--> $content ";
}



// OUTPUT_SET_MODE
// defines the output mode.
// called near the beginning of the load process
function output_set_mode()
{
	// assume we're outputting HTML for the moment.
	$_SERVER['OUTPUT_MODE']='HTML';
	$output_mode_const="HTML";


	// these tests determine if the output mode should be something else
	if ((strpos($_SERVER['REQUEST_URI'],'/rss')
			|| strpos($_SERVER['REQUEST_URI'],'/rdf'))
		&& !strpos($_SERVER['REQUEST_URI'],'http'))

		{ $_SERVER['OUTPUT_MODE']='RSS'; 	$output_mode_const="RSS"; }


	if ((strpos($_SERVER['REQUEST_URI'],'/atom')
			|| strpos($_SERVER['REQUEST_URI'],'/xml'))
		&& !strpos($_SERVER['REQUEST_URI'],'http'))
		{ $_SERVER['OUTPUT_MODE']='ATOM1.0'; 	$output_mode_const="ATOM"; }

	if (strpos($_SERVER['REQUEST_URI'],'/privatefeed'))
		{ $_SERVER['OUTPUT_MODE']='ATOM_PRIVATE'; 	$output_mode_const="ATOM_PRIVATE"; }

	// sets up for mobile output.
	if (stristr($_SERVER['HTTP_HOST'],'m')
		|| (is_mobile() && (!isset($_COOKIE['forceview']) || $_COOKIE['forceview']!="desktop"))
		|| (isset($_COOKIE['forceview']) && $_COOKIE['forceview']=="mobile"))

		{
			$_SERVER['OUTPUT_MODE']='MOBILE';
			$output_mode_const='MOBILE';
		}

	// sets up for desktop view on mobile device
	if (stristr($_SERVER['HTTP_HOST'],'d.pl')
		|| (isset($_COOKIE['forceview']) && $_COOKIE['forceview']=="desktop"))

		{
			$_SERVER['OUTPUT_MODE']='HTML'; 	$output_mode_const="HTML";
		}


	// most ajax returns have their own code path, but some of them
	// just use the same path as the regular request with "/ajax" at the end.
	// this takes care of those.
	if (strpos($_SERVER['REQUEST_URI'],'/ajax') || strpos($_SERVER['REQUEST_URI'],'_ajax')
		&& !strpos($_SERVER['REQUEST_URI'],'http')    // reads of other sites that happen to have ajax in the name
		&& !strpos($_SERVER['REQUEST_URI'],'.js'))   // javascript includes

		{ $_SERVER['OUTPUT_MODE']='AJAX'; 	$output_mode_const="AJAX"; }

	define("OUTPUT_MODE",$output_mode_const);
	return $_SERVER['OUTPUT_MODE'];
}

?>