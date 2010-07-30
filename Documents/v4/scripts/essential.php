<?php

/*
ESSENTIAL.PHP -- part of the planwatch library

necessary functions for all pages that aren't somewhere
in the standard library. also includes authentication processes
and profiling.

--------------------------------------------------------------------------------

TODO:(v5) create installation scripts that run when you point at
	the dir we're in and create siteconfig.php, .htaccess, directories
	as appropriate.

--------------------------------------------------------------------------------

functions:
profile()
array_set_current()
redirect()
mail_look()
planworld_node_getinfo()
login() -- logs a user in, sets a cookie
logout() -- logs a user out, empties a cookie
profile_display()

*/

// CATCH BOTS
// stop them before anything else happens at all.
if (bot_blacklist())
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}



// LOAD PROFILING
//------------------------------------------------------------------------------
// profile measures loops in microseconds. the first time you call it is
// the beginning, the second time is the end.
profile('pageload');
profile('precontent');
profile('essential');
profile('includes','begin');
profile('includes_always','begin');

// ALWAYS INCLUDED FILES
//------------------------------------------------------------------------------
include_once('xmlrpc.inc');
include_once('output.php');
profile('includes_always','end');

profile('includes_lib','begin');
// STANDARD LIBRARY INCLUDES
//------------------------------------------------------------------------------
exec("ls $_SERVER[FILE_ROOT]/scripts/standard_library/*.php",$include_list);
foreach($include_list as $file)
	include_once($file);
profile('includes_lib','end');

profile('includes','end');



// GENERAL SETUP
//------------------------------------------------------------------------------

// TODO: shouldn't be reporting 200 OK here, rather when we output. 
// need to test this offline before changing it.
header("HTTP/1.1 200 OK\n");

// if you want standard php error reporting, uncomment this
//error_reporting(E_ALL & ~(E_NOTICE));

// the OUTPUT_MODE is used mostly to identify whether we're on
// mobile or desktop, but also for non-html output formats
// (Atom, RSS, soon JSON)
define("OUTPUT_MODE",output_set_mode());



// if we're in a subdirectory, strip it out to avoid complicating the
// logic in parseurl
// TODO: request cleanup should be moved to parseurl.php
$_SERVER['REQUEST_URI']=str_replace($_SERVER['WEB_ROOT'],'',$_SERVER['REQUEST_URI']);



//------------------------------------------------------------------------------
//
// AUTHENTICATION
//
//------------------------------------------------------------------------------
// get the session id / fingerprint if it's stored in the URL
// we always use the fingerprint for session ids, so we don't have to worry
// about people altering settings and not having them propagated to existing
// login states on other machines/browsers
//
// there are some fairly minor security considerations here, this could
// do with a rethink for client api
if (!$_POST['sid'] && !$_GET['sid']) list($trash,$sid)=explode("sid=",$_SERVER['REQUEST_URI']);
elseif(isset($_POST['sid'])) $sid=$_POST['sid'];
elseif(isset($_GET['sid'])) $sid=$_GET['sid'];



// URL Authentication
// used by people who are doing looks from different users than their
// logged in users (this includes basically josh and johnnie)
//-------------------------------------------------------------------
$urlarray=explode('/',$_SERVER['REQUEST_URI']);
$urlarray_count=count($urlarray);

// if we haven't found a fingerprint so far, we check for a fingerprint in the url
if (user_verify_fingerprint($urlarray[$urlarray_count-1]))
{
	// FINGERPRINT IS LAST ITEM IN URL ARRAY
}

// if no valid fingerprint is found, we test the last two parts
// of the url array to see if they're a valid username and password

elseif (user_verify_fingerprint(user_get_fingerprint($urlarray[$urlarray_count-2],$urlarray[$urlarray_count-1])))
{
	// USER AND PASS ARE LAST TWO ITEMS IN URL ARRAY
}


// If URL Authentication fails, we test for normal authentication.
//-------------------------------------------------------------------
// i know this seems weird, but it's really just a way to support
// temporary off-user look
else
{
	if (user_verify_fingerprint($_COOKIE[$_SERVER['AUTH_COOKIE']]))
	{
		// VALID USER COOKIE FOUND

		if (user_is_administrator() && user_verify_fingerprint($_COOKIE['mau']))
		{
			// MASQUERADING IS ON
			$_SERVER['MASQUERADE']=TRUE;
		}
		else $_SERVER['MASQUERADE']=FALSE;
	}
	elseif(user_verify_fingerprint($sid))
	{
		// VALID SESSION ID FOUND

		$_SERVER["SESSION_ID"]=$sid;
	}
	elseif(user_verify_fingerprint($_POST[$_SERVER['AUTH_COOKIE']]))
	{
		// VALID FINGERPRINT VIA POST
		// this is so the write form can't expire even if the user leaves it
		// up past cookie expiry.
	}
}


// Cookie Updates
//-------------------------------------------------------------------
// if the user is not masquerading, we update the cookie expiry


if (!user_verify_fingerprint($_COOKIE['mau']))
{
	if ($_COOKIE['remember']=='remember=' && user_is_valid($user,$pass))	login($user,$pass,FALSE,FALSE);
	if ($_COOKIE['remember']=='remember=1' && user_is_valid($user,$pass))	login($user,$pass,1,FALSE);
}


// SMILEY_FUNCTIONS INCLUDE
//------------------------------------------------------------------------------
// Include the smileys functions if the user is reading or writing something
// that could involve smileys, and they don't have the smileys-destroyer
// turned on in their preferences.
if ((strpos($_SERVER['REQUEST_URI'],'read') || strpos($_SERVER['REQUEST_URI'],'write') || strpos($_SERVER['REQUEST_URI'],'help'))
	&& $_SERVER['USERINFO_ARRAY']['hatessmileys']==FALSE) { include_once('smiley_functions.php'); }


// GLOBALS
//------------------------------------------------------------------------------
if (file_exists("$_SERVER[FILE_ROOT]/stats/plan_locations.dat")) $_SERVER['PLAN_LOCATION_ARRAY']=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/plan_locations.dat"));
if (file_exists("$_SERVER[FILE_ROOT]/stats/plan_failures.dat")) $_SERVER['PLAN_LOCATION_FAILED_ARRAY']=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/plan_failures.dat"));



// ERROR REPORTING
//------------------------------------------------------------------------------
//if (user_is_administrator()) error_reporting(E_ALL & ~(E_NOTICE));
//else error_reporting(E_ERROR & E_PARSE);
//error_reporting(E_ALL & ~(E_NOTICE));

//if ($_SERVER['USER']=='jwdavidson') trigger_error("this is not a real error. do not be alarmed.");
//if ($_SERVER['USER']=='jwdavidson') trigger_error("this is not a real error. do not be alarmed.",E_USER_WARNING);


// UPDATE LAST ACTION
// also updates the time reported as "last login" since we don't track login times
// because most of our users are permanently logged in
//------------------------------------------------------------------------------
if ($_SERVER['OUTPUT_MODE']!='AJAX' && !strstr($_SERVER['REQUEST_URI'],'micro'))
	user_update_last_action();



// TESTING VARIABLES, ETC.
//------------------------------------------------------------------------------
// this space intentionally left blank (for now)





// STYLES
//------------------------------------------------------------------------------
// TODO:(v4.5) rewrite styles selection form (or at least offer advanced ver.)
if ($_SERVER['USER'])
{
	$styles_fn="$_SERVER[USER_ROOT]/styles.txt";		//reads user styles.
	$fonts_fn="$_SERVER[USER_ROOT]/fonts.txt";		//reads user fonts.
	$colors_fn="$_SERVER[USER_ROOT]/colors.txt";
	$skin_fn="$_SERVER[USER_ROOT]/skin.txt";
}

if($_SERVER['OUTPUT_MODE']=='IPHONE' || $_SERVER['OUTPUT_MODE']=='MOBILE')
	include('default.skin');				// reads default styles.

if ($_SERVER['OUTPUT_MODE']=='HTML' || $_SERVER['OUTPUT_MODE']=='IPHONE' || $_SERVER['OUTPUT_MODE']=='MOBILE')
{
	include_once('formatting_html.php');
	include('default.skin');				// reads default styles.

	if (file_exists($styles_fn) && !file_exists($skin_fn) && !file_exists($colors_fn))
	{
		parse_str(file_get_contents($styles_fn));
		if ($skin && file_exists("$_SERVER[FILE_ROOT]/resources/skins/$skin"))
		include($skin);
		if ($planlinkcolor) $navlinkcolor=$planlinkcolor;
	}

	if (file_exists($skin_fn))
	{
		parse_str(file_get_contents($skin_fn));
		if ($skin) include($skin);
	}

	if (file_exists($fonts_fn))
	{
		parse_str(file_get_contents($fonts_fn));
	}

	if (file_exists($colors_fn))
	{
		parse_str(file_get_contents($colors_fn));
	}

	$pfsize_css=html_size_to_css_size($pfsize);
	$nfsize_css=html_size_to_css_size($nfsize);
	$sfsize_css=html_size_to_css_size($sfsize);
	$hfsize_css=html_size_to_css_size($hfsize);
}


// TIMEZONE
//-----------------------------------------------------------------------------------------
if (strlen($_SERVER['USERINFO_ARRAY']['timezone'])<=3) $_SERVER['USERINFO_ARRAY']['timezone']="America/New_York";
putenv("TZ={$_SERVER[USERINFO_ARRAY][timezone]}");

profile('essential','end');


//------------------------------------------------------------------------------
// FUNCTIONS BEGIN HERE
//==============================================================================


if (!function_exists("stripos")) {
	function stripos($str,$needle,$offset=0)
	{
		return strpos(strtolower($str),strtolower($needle),$offset);
	}
}

function cache_clear($planowner)
{
	if ($_SERVER['PLANOWNER_INFO_ARRAY']['username']!=$planowner)
		plan_get_owner_info($planowner);
	exec("rm -f $_SERVER[FILE_ROOT]/temp/*".base64_encode($planowner)."*.cache");
	exec("rm -f $_SERVER[FILE_ROOT]/../temp/*$planowner*.cache");
	exec("rm -f $_SERVER[FILE_ROOT]/temp/{$_SERVER['PLANOWNER_INFO_ARRAY']['salt']}*.cache");
}

// IS_MOBILE determines whether the user agent is a mobile device
// based on UA testing. this is not a good way to do it, but i 
// haven't found a better one. anyway it's only used for picking a default mode
// at the default url.
function is_mobile()
{
	$ua=strtolower($_SERVER['HTTP_USER_AGENT']);
	if(strstr($ua,"opera mobi")
		|| strstr($ua,"opera mini")
		|| strstr($ua,"iphone")
		|| strstr($ua,"ipod")
		|| strstr($ua,"android")
		|| (strstr($ua,"webos") && (strstr($ua,"pre") || strstr($ua,"pixi")))
		|| (strstr($ua,"msie") && strstr($ua,"ce"))
		|| (strstr($ua,"safari") && strstr($ua,"tear"))
		|| strstr($ua,"kindle")
		|| strstr($ua,"netfront")
		|| strstr($ua,"midp")
		|| strstr($ua,"ucweb")
		) return TRUE;
	
	else return FALSE;
}

function agent_is_lowfi()
{
	if (stristr($_SERVER['HTTP_USER_AGENT'],'blackberry')) return TRUE;
}

function isjosh() { if (IS_JOSH) return TRUE; else return FALSE; }

// prevents search engines and suspect sites from getting to plans
function bot_blacklist()
{
	$blacklist[]="irlbot";
//	$blacklist[]="intel mac";  // this is for testing.
	$blacklist[]="surveybot";
	$blacklist[]="googlebot";
	$blacklist[]="msnbot";
	$blacklist[]="yahoo! slurp";
	$blacklist[]="buildcms crawler";
	$blacklist[]="grub.org";
	$blacklist[]="65.222.176.124";
	$blacklist[]="66.249.85.130";

	$agent=strtolower($_SERVER['HTTP_USER_AGENT']);

	foreach($blacklist as $ban)
	{
		if (strpos($agent,$ban)) return TRUE;
		if (strpos($_SERVER["REMOTE_ADDR"],$ban)!==FALSE) return TRUE;
	}

	return FALSE;
}

// PROFILE()
//
// sets a point on the stopwatch so we can see how long each section takes
//------------------------------------------------------------------------------
function profile($key,$state=FALSE)
{
	if ($state==FALSE)
	{
		if ($_SERVER['STOPWATCH']["{$key}_begin"]) $state = 'end';
		else $state = 'begin';
	}

	$_SERVER['STOPWATCH']["{$key}_$state"]=array_sum(explode(' ',microtime()));
}


function test($message)
{
	if (strstr($_SERVER['USER'],'jwdavidson')) print_r($message."<br/>\n");
}

// ARRAY_SET_CURRENT
//
// this version by jari dot eskelinen at iki dot fi
// from comments on http://us4.php.net/current
//
// sets $array's pointer to point at the entry with $key.
// used in plan_read_local.
//------------------------------------------------------------------------------
function array_set_current(&$array, $key)
{
profile("array_set_current");
	reset($array);
	while (current($array)!==FALSE){
		if (key($array) == $key) {
			break;
		}
		next($array);
	}
profile("array_set_current");
	return current($array);
}


// REDIRECT
//
// handles HTTP redirection, adds session info as necessary
// subdir abstraction is now handled elsewhere thanks to $_SERVER[WEB_ROOT]
//------------------------------------------------------------------------------
function redirect($url='/')
{
	if (!$url) $url='/';
	if ($url[0]=='/') $url="http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]".$url;
	if (isset($_SERVER['SESSIONID'])) $url.="/sid=$_SERVER[SESSION_ID]";

	$cookie_host=$_SERVER['HTTP_HOST'];
	if (substr_count($cookie_host,'.') < 2) $cookie_host=".$cookie_host";
	$cookie_host=str_replace('www','',$cookie_host);

	//this way, we know we got redirected. it helps particularly on the iphone client.
	setcookie("redirected","1",time()+10,$_SERVER['WEB_ROOT']."/",$cookie_host);

	Header("Location: $url");
	return 1;
}




// MAIL_LOOK()
//
// emails a watched list for a registered user
//------------------------------------------------------------------------------
function mail_look($person)
{
	include_once('plan_read.php');

	$userinfo=unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$person/userinfo.dat"));
	$watchedlist=file("$_SERVER[PWUSERS_DIR]/$person/watchedlist.txt");

	$output.="[planwatch.org]: planwatch automailer\n\nHere's what's been updated:\n";

	$sortby=trim(str_replace("sort by ",'',$watchedlist[0]));
	if (strpos($watchedlist[0],'sort by ')!==FALSE) unset($watchedlist[0]);
	$output.=str_replace("&nbsp;",' ',strip_tags(list_format_html($watchedlist,$sortby,'',"\n")));

	$output.="\n\n\n\n";

	mail($userinfo['email'],"your watched list updates ".date("F jS Y"),$output,"From: watched@planwatch.org");
}



// planworld_node_getinfo()
//
// gets info for an XML-RPC planworld node
// an array is returned keyed with 'port', 'server', and 'directory'
//------------------------------------------------------------------------------
function planworld_node_getinfo($node='note')
{
	if (strpos($node,'@')===FALSE) $node='@'.$node;

	if	 ($node=='@note')	return array("port"=>80,"server"=>"neon.note.amherst.edu","directory"=>"/planworld/backend/");
	elseif ($node=='@note.amherst.edu')	return array("port"=>80,"server"=>"neon.note.amherst.edu","directory"=>"/planworld/backend/");
	elseif ($node=='@amherst.edu')	return array("port"=>80,"server"=>"neon.note.amherst.edu","directory"=>"/planworld/backend/");

	elseif($node=='@krypton') return array("port"=>80,"server"=>"neon.note.amherst.edu","directory"=>"/planworld/backend/");

	elseif ($node=='@pwn') return array("port"=>80,"server"=>"planworld.net","directory"=>"/backend/");
	elseif ($node=='@planworld.net') return array("port"=>80,"server"=>"planworld.net","directory"=>"/backend/");
	elseif ($node=='@planwatch.org') return array("port"=>80,"server"=>"planwatch.org","directory"=>"/backend/");
	elseif ($node=='@beta.planwatch.org') return array("port"=>80,"server"=>"beta.planwatch.org","directory"=>"/backend/");
	elseif ($node=='@beta') return array("port"=>80,"server"=>"beta.planwatch.org","directory"=>"/backend/");
	elseif ($node=='@flickr') return array("port"=>80,"server"=>"www.flickr.com","directory"=>"/services/xmlrpc/");
	else   return FALSE;
}



// PLANWORLD_XMLRPC_RESPONSE()
//
// generic xml-rpc response handler for the planworld api
// designed to simplify implementation of planworld api responses for server
// needs lots of testing and thinking work.
//------------------------------------------------------------------------------
function planworld_xmlrpc_response($method,$arguments) {
	global $xmlrpcerruser;
	global $signature;
	$err="";

	$parameter_count = $arguments->getNumParams();
	for($i=0;$i<$parameter_count;$i++)
	{
		$item[$i]=$arguments->getParam($i);
		$scalar_item[$i]=$item[$i]->scalarval();
	}

	$something = $signature[$method];

	// call the passed method here

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return new xmlrpcresp(new xmlrpcval($plan,'base64'));
	}
}




// PLANWORLD_XMLRPC_QUERY()
//
// generic xml-rpc query handler for the planworld api
// designed to simplify implementation of planworld api calls
// needs lots of testing and thinking work.
//------------------------------------------------------------------------------
// TODO:(v4.1) rework xmlrpc stuff using planworld_xmlrpc_query()
function planworld_xmlrpc_query($node,$message,$params,$debug=FALSE)
{
	$nodeinfo=planworld_node_getinfo($node);

	$f=new xmlrpcmsg($message);
//		echo "<hr>";
	foreach($params as $param)
	{
		if (is_int($param)) $type="int";
		if (is_string($param)) $type="string";
		if (is_array($param))
		{
			$type="array";
			if (!isset($param[count($param)-1])) $type="struct";
		}
		if (is_bool($param)) $type=xmlrpcBoolean;

		$f->addParam(new xmlrpcval($param, $type));
		//print_r($f);
		//echo "$param : $type<br/>";
		//echo "<hr>";
	}

	$c=new xmlrpc_client($nodeinfo["directory"], $nodeinfo["server"], $nodeinfo["port"]);
	$c->setDebug(FALSE);
	$r=$c->send($f);

	if (!$r) { $returnval="<div class='alert'>$message to $node failed on send().</div>"; }
	else
	{
		$v=$r->value();
		if (!$r->faultCode()) {
			if ($v->kindOf()=='scalar') $returnval = $v->scalarval();
		} else {
		if ($debug) {
		$returnval="Fault: ";
		$returnval.="Code: " . $r->faultCode() .
			" Reason '" .$r->faultString()."'<br/>";
		}
		}
	}
	return $returnval;
}



// LOGIN()
//
// logs the user in by setting a cookie
// the cookie data is the user's planwatch fingerprint
//------------------------------------------------------------------------------
function login($user,$pass,$remember=0,$prevpage='/',$newuser=FALSE)
{
	// only go further if the user exists and has given us a valid password
	if (file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat") && user_is_valid($user,$pass))
	{
		parse_str(user_read_info($user));

		// sets the expiry of the cookie to 3 hours from now, or 5 years
		if ($remember) $time=time()+(86400*365*5);
		else $time=0;

		$cookie_name=$_SERVER['AUTH_COOKIE'];
		$cookie_data=user_get_fingerprint($user,$pass);

		$cookie_host=$_SERVER['HTTP_HOST'];
		if (substr_count($cookie_host,'.') < 2) $cookie_host=".$cookie_host";
		$cookie_host=str_replace('www','',$cookie_host);


		setcookie($cookie_name,$cookie_data,$time,$_SERVER['WEB_ROOT']."/",$cookie_host);
		setcookie('remember',"remember=$remember",time()+86400*365*5,$_SERVER['WEB_ROOT']."/",$cookie_host);

		if ($prevpage===FALSE) { $prevpage='/'; }
		Header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/cookie-test.php?fingerprint_v4=$cookie_data&newuser=$newuser&redirect_page=$prevpage");
	}
	else
	{
		redirect("/failed");
/*		echo "<div class='alert'><h1>Login failed for $user.</h1> Double check the username and password you provided, and try again. If you keep getting this message and you shouldn't, email <a href='mailto:help@planwatch.org'>help@planwatch.org</a></div>
		<form action='/scripts/form_shim.php' method='post' name='loginForm'>
		user <input id='login_username' type='text' name='user' size='10' />
		pass <input id='login_userpass' type='password'  name='pass' size='6' />
		<input type='checkbox' name='remember' id='remember' value='1' />
		<label for='remember'>remember me</label>
		<input type='hidden' name='action' value='login'/>
		<input type='submit' name='action' value='login' onclick='document.forms.loginForm.submit();' />
		<input type='hidden' name='prevpage' value=''/>
	</form>"*/
	}
}


// LOGOUT()
//
// logs the user out by clearing cookie values
//------------------------------------------------------------------------------
function logout($message=FALSE)
{
	$cookie_names[]=$_SERVER['AUTH_COOKIE'];
	$cookie_names[]="mau";
	$cookie_names[]="remember";
	$cookie_data=FALSE;
	$time=time()-1;

	$cookie_host=$_SERVER['HTTP_HOST'];
	if (substr_count($cookie_host,'.') < 2) $cookie_host=".$cookie_host";
	$cookie_host_base=str_replace('www','',$cookie_host);

	foreach($cookie_names as $cookie_name)
	{
		setcookie ($cookie_name,$cookie_data,$time,$_SERVER['WEB_ROOT']."/",$cookie_host);
		setcookie ($cookie_name,$cookie_data,$time,$_SERVER['WEB_ROOT']."/",$cookie_host_base);
	}

	if (!$message) redirect('/');
	else redirect("/alert/".urlencode($message));
}




// profile_display()
//
// builds a profile of how much time various functions take
//------------------------------------------------------------------------------
function profile_display()
{
	$indent_level=0;
	$loadtime=$_SERVER['STOPWATCH']['pageload_end']-$_SERVER['STOPWATCH']['pageload_begin'];
	foreach($_SERVER['STOPWATCH'] as $key=>$value)
	{
		if (strpos($key,'begin'))
		{
			for($i=0;$i<$indent_level;$i++)  $indent.="&nbsp;&nbsp;&nbsp;&nbsp;";
			$basekey=str_replace('_begin','',$key);
			$indent_level+=1;
			if (is_numeric($_SERVER['STOPWATCH'][$basekey."_end"]))
			{
				$difference=round($_SERVER['STOPWATCH'][$basekey."_end"]-$_SERVER['STOPWATCH'][$basekey."_begin"],5);
				$outstring="$indent $basekey: $difference";
				if (strpos($key,'!')===FALSE)
				{
					if ($difference >= ($loadtime*.3)) $outstring="<strong>$outstring</strong><br/>";
					if ($difference <= ($loadtime*.1)) $outstring="<span class='edit_links'>$outstring</span><br/>";
					if ($difference <= ($loadtime*.05)) $outstring="<span class='edit_links'>$outstring</span><br/>";//$outstring="";
					if ($difference > ($loadtime*.1) && $difference < ($loadtime*.3))
						{ $outstring=$outstring."<br/>"; }
				}
				else { $outstring=$outstring."<br/>"; }
				$stopwatch_content.=$outstring;
				$indent="";
			}
		}
		if (strpos($key,'end')) $indent_level-=1;
	}
	return $stopwatch_content;
}

// browser_is_modern()
//
// makes sure the browser can take the good template
//------------------------------------------------------------------------------
function browser_is_modern()
{
	$modern=FALSE;

	// This gets us Konqeror, Safari, Mozilla & Firefox, Camino
	if (stristr($_SERVER['HTTP_USER_AGENT'],'gecko')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'webkit')) $modern=TRUE;

	// This gets us MSIE versions with non-awful CSS and JS support
	if (stristr($_SERVER['HTTP_USER_AGENT'],'MSIE 5')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'MSIE 6')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'MSIE 7')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'MSIE 8')) $modern=TRUE;

	// This gets us Opera versions with non-awful CS and JS support
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/7')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/8')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/9')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/10')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/11')) $modern=TRUE;

//	if ($_SERVER['USER']=='jwdavidson') echo $_SERVER['HTTP_USER_AGENT'];

	return $modern;
}

function hyperlink($string) {
//	$string = preg_replace("#\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>#i","<a href='\\1'>\\1</a>",$string);
	$string = preg_replace("#([\s\(\)])[^'\"]*(https?|ftp|news){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^\"\s\(\)<\[]*)?)#ie", '\'$1\'.handle_url_tag(\'$2://$3\')', $string);
	$string = preg_replace("#([\s\(\)])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^\"\s\(\)<\[]*)?)#ie", '\'$1\'.handle_url_tag(\'$2.$3\', \'$2.$3\')', $string);
  return $string;
}

function handle_url_tag($url, $link='') {
		$full_url = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $url);

		if (strpos($url, 'www.') === 0) { $full_url = 'http://'.$full_url; }
		else if (strpos($url, 'ftp.') === 0) { $full_url = 'ftp://'.$full_url; }
		else if (!preg_match('#^([a-z0-9]{3,6})://#', $url, $bah))  { $full_url = 'http://'.$full_url; }

		$link = ($link == '' || $link == $url) ? ((strlen($url) > 55) ? substr($url, 0 , 39).' &hellip; '.substr($url, -10) : $url) : stripslashes($link);

		return '<a href="'.$full_url.'">'.$link.'</a>';
}

function strip_whitespace($page)
{
	$page=str_replace(array(' =','= '),'=',$page);
	$page=str_replace(array(" !",", "),array("!",","),$page);

	$page=str_replace('{ ','{',$page);
	$page=str_replace(' }','}',$page);

	$page=preg_replace('|[\s\r]//.*|','',$page);

	$page=preg_replace('/[ \r\n\t]/',' ',$page);
	$page=preg_replace('/\s\s+/',' ',$page);
	$page=preg_replace('/\<![�\r\n\t]*(--([^\-]|[\r\n]|-[^\-])*--[�\r\n\t]*)\>/','',$page);
	return $page;
}



?>