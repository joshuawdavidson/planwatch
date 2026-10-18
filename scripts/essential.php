<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(0);
if(IS_JOSH)
{
	error_reporting(E_ALL);
}

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
include_once('/var/www/planwatch.org/siteconfig.php');
include_once('xmlrpc.inc');
include_once('output.php');
include_once('file_functions.php');
profile('includes_always','end');



profile('includes_lib','begin');
// STANDARD LIBRARY INCLUDES
//------------------------------------------------------------------------------
$include_list = glob("/var/www/planwatch.org/public_html/scripts/standard_library/*.php");
// $include_list = explode("\n",`ls /var/www/planwatch.org/public_html/scripts/standard_library/*.php`);
/* $include_list=files_list("/var/www/planwatch.org/public_html/scripts/standard_library/","*.php"); */
foreach($include_list as $file)
{	include_once($file); }
profile('includes_lib','end');


profile('includes','end');



// GENERAL SETUP
//------------------------------------------------------------------------------
// the OUTPUT_MODE is used mostly to identify whether we're on
// mobile or desktop, but also for non-html output formats
// (Atom, RSS, soon JSON)
//define("OUTPUT_MODE",);
$output_mode = output_set_mode();

if($output_mode=='MOBILE') {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

// $_SERVER["SESSION_ID"]=false;
$_SERVER['USERINFO_ARRAY']=[];
$_SERVER['PLANOWNER_INFO']=[];
$_SERVER['USERINFO_ARRAY']['timezone']="America/New_York";
$_SERVER['USER_ROOT']="/var/www/planwatch.org/pwusers/Guest";

//------------------------------------------------------------------------------
//
// AUTHENTICATION
//
//------------------------------------------------------------------------------
// get the session id
//
// there are some fairly minor security considerations here, this could
// do with a rethink for client api
if(empty($_POST['sid']) && empty($_GET['sid']) && !isset($_COOKIE[$_SERVER['SESSION_COOKIE']]))
{
	$sid='';
	$uriparts=explode("sid=",$_SERVER['REQUEST_URI']);
	if(isset($uriparts[1])) { $sid = $uriparts[1]; }
	if(strlen($sid)>4) $_SERVER['INURL_SID']=1;
}
elseif(isset($_POST['sid'])) $sid=$_POST['sid'];
elseif(isset($_GET['sid'])) $sid=$_GET['sid'];
elseif(isset($_COOKIE[$_SERVER['SESSION_COOKIE']])) $sid=$_COOKIE[$_SERVER['SESSION_COOKIE']];


// URL Authentication
// i know this seems weird, but it's really just a way to support
// temporary off-user look
//-------------------------------------------------------------------
$urlarray=explode('/',$_SERVER['REQUEST_URI']);
$urlarray_count=count($urlarray);

// if no valid fingerprint is found, we test the last two parts
// of the url array to see if they're a valid username and password
if(user_is_valid($urlarray[$urlarray_count-2],$urlarray[$urlarray_count-1]))
{
	$sid=user_set_sessionkey($urlarray[$urlarray_count-2]);
}
elseif(!$sid && user_verify_sessionkey($urlarray[$urlarray_count-1])) { $sid=$urlarray[$urlarray_count-1]; $_SERVER['INURL_SID']=TRUE; }

//echo $_COOKIE[$_SERVER['SESSION_COOKIE']]; exit;

// If URL Authentication fails, we test for normal authentication.
//-------------------------------------------------------------------
if(user_verify_sessionkey($sid))
{
//	echo "valid session"; exit;
//	if($_SERVER['USER']=='planworldbugs' || $_SERVER['USER']=='Guest') print_r($_SERVER['USER']);
	// VALID SESSION COOKIE FOUND
	user_read_info($_SERVER['USER'],TRUE);

	if (isset($_COOKIE['mau']) && user_is_administrator() && (user_verify_sessionkey($_COOKIE['mau'],FALSE,TRUE)) )
	{
		// MASQUERADING IS ON
		$_SERVER['MASQUERADE']=TRUE;
		user_read_info($_SERVER['USER'],TRUE);
		$sid=$_COOKIE['mau'];
	}
	else
	{
		$_SERVER['MASQUERADE']=FALSE;
	}

	$_SERVER["SESSION_ID"]=$sid;
}

// if(!isset($_SERVER['USER']) || $_SERVER['USER']=='Guest') {error_reporting(0);}

// enable beta features for adventurous users.
if(isset($_SERVER['USERINFO_ARRAY']['beta_tester'])) {define("IS_BETA",$_SERVER['USERINFO_ARRAY']['beta_tester']);}


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



// UPDATE LAST ACTION
// also updates the time reported as "last login" since we don't track login times
// because most of our users are permanently logged in
//------------------------------------------------------------------------------
if (OUTPUT_MODE!='AJAX' && strpos($_SERVER['REQUEST_URI'],'micro')===FALSE)
	{user_update_last_action();}



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
else
{
	$styles_fn = "/tmp/asdfasdfasdfasdfas";
	$fonts_fn  = "/tmp/asdfasdfasdfasdfas";
	$colors_fn = "/tmp/asdfasdfasdfasdfas";
	$skin_fn   = "/tmp/asdfasdfasdfasdfas";
}

if (OUTPUT_MODE=='HTML' || OUTPUT_MODE=='MOBILE')
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
		$result=array();
		parse_str(file_get_contents($skin_fn),$result);
		extract($result);
		if ($skin) {include($skin);}
	}

	if (file_exists($fonts_fn))
	{
		parse_str(file_get_contents($fonts_fn),$result);
		extract($result);
	}

	if (file_exists($colors_fn))
	{
		parse_str(file_get_contents($colors_fn),$result);
		extract($result);
	}

	$pfsize_css=html_size_to_css_size($pfsize);
	$nfsize_css=html_size_to_css_size($nfsize);
	$sfsize_css=html_size_to_css_size($sfsize);
	$hfsize_css=html_size_to_css_size($hfsize);

	if(!isset($GLOBALS['pfont']) && isset($GLOBALS['pfonts']))
	{
		$pfonts_a = explode(",",$GLOBALS['pfonts']);
		$GLOBALS['pfont']=$pfonts_a[0];
	}
	if(!isset($GLOBALS['nfont']) && isset($GLOBALS['nfonts']))
	{
		$nfonts_a = explode(",",$GLOBALS['nfonts']);
		$GLOBALS['nfont']=$nfonts_a[0];
	}
	if(!isset($GLOBALS['sfont']) && isset($GLOBALS['sfonts']))
	{
		$sfonts_a = explode(",",$GLOBALS['sfonts']);
		$GLOBALS['sfont']=$sfonts_a[0];
	}
	if(!isset($GLOBALS['hfont']) && isset($GLOBALS['hfonts']))
	{
		$hfonts_a = explode(",",$GLOBALS['hfonts']);
		$GLOBALS['hfont']=$pfonts_a[0];
	}
}


// TIMEZONE
//-----------------------------------------------------------------------------------------
if (!isset($_SERVER['USERINFO_ARRAY']['timezone']) || strlen($_SERVER['USERINFO_ARRAY']['timezone'])<=3) {$_SERVER['USERINFO_ARRAY']['timezone']="America/New_York";}
date_default_timezone_set($_SERVER['USERINFO_ARRAY']['timezone']);
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


function urlinfo($url)
{
$ch = curl_init();
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($ch, CURLOPT_URL, $url);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 20);
curl_setopt ($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

// Only calling the head
curl_setopt($ch, CURLOPT_HEADER, true); // header will be at output
curl_setopt($ch, CURLOPT_NOBODY, true); // just get the headers and no content

$response = explode("\n",curl_exec ($ch));
curl_close ($ch);

foreach($response as $i=>$header)
{
	list($key,$value)=explode(": ",$header);
	$headers[$key]=$value;
}

return $headers;
}


// IS_MOBILE determines whether the user agent is a mobile device
// based on UA testing. this is not a good way to do it, but i
// haven't found a better one. anyway it's only used for picking a default mode
// at the default url.
function is_mobile()
{
	$ua=strtolower($_SERVER['HTTP_USER_AGENT']);
	if(strpos($ua,"opera mobi")!==FALSE
		|| strpos($ua,"opera mini")!==FALSE
		|| strpos($ua,"iphone")!==FALSE
		|| strpos($ua,"ipod")!==FALSE
		|| strpos($ua,"android")!==FALSE
		|| (strpos($ua,"webos")!==FALSE && (strpos($ua,"pre")!==FALSE || strpos($ua,"pixi")!==FALSE))
		|| (strpos($ua,"safari")!==FALSE && strpos($ua,"tear")!==FALSE)
		|| strpos($ua,"kindle")!==FALSE
		|| strpos($ua,"netfront")!==FALSE
		|| strpos($ua,"midp")!==FALSE
		|| strpos($ua,"ucweb")!==FALSE
		)
		{
			return TRUE;
		}

	else return FALSE;
}

function agent_is_lowfi()
{
	return false;
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

	if(isset($_SERVER['HTTP_USER_AGENT'])) $agent=strtolower($_SERVER['HTTP_USER_AGENT']); else $agent='';

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
		if (isset($_SERVER['STOPWATCH']["{$key}_begin"])) $state = 'end';
		else $state = 'begin';
	}

	$_SERVER['STOPWATCH']["{$key}_$state"]=array_sum(explode(' ',microtime()));
}


function test($message)
{
	if (strpos($_SERVER['USER'],'jwdavidson')!==FALSE) print_r($message."<br/>\n");
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
	if (!$url) $url="$_SERVER[WEB_ROOT]";
	if ($url[0]=='/') $url="https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]".$url;
	if (isset($_SERVER['INURL_SID']) && !strpos($_SERVER['REQUEST_URI'],'ookie-test')) $url.="/sid=$_SERVER[SESSION_ID]";

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

	email_send($userinfo['email'],"your watched list updates ".date("F jS Y"),$output,FALSE,FALSE,"watched@planwatch.org");
}



// planworld_node_getinfo()
//
// gets info for an XML-RPC planworld node
// an array is returned keyed with 'port', 'server', and 'directory'
//------------------------------------------------------------------------------
function planworld_node_getinfo($node='note')
{
	global $debug;

	if(empty($node)) { $node="@planwatch.org"; }
	if ($node[0]!='@') { $node='@'.$node; }
	$node=strtolower($node);

	if($debug) { echo "times_crossnode: _pw_node_getinfo: $node<br>\n"; }

	$pwo_aliases = array("@pwo","@chippu","@beta");
	$pwn_aliases = array("@pwn");
	$amherst_aliases = array("@note.amherst.edu","@amherst.edu","@krypton","@note","@s.amherst.edu");
	$node=str_replace($pwo_aliases,"@planwatch.org",$node);
	$node=str_replace($pwn_aliases,"@planworld.net",$node);
	$node=str_replace($amherst_aliases,"@planworld.amherst.edu",$node);

	if($debug) { echo "times_crossnode: _pw_node_getinfo: $node<br>\n"; }

	switch($node){
		case "@planworld.amherst.edu":
			return array("port"=>443,"server"=>"planworld.amherst.edu","directory"=>"/backend/index.php","method"=>"https");
			break;
		case "@planworld.net":
			return array("port"=>80,"server"=>"planworld.net","directory"=>"/backend/","method"=>"http");
			break;
		case "@planwatch.org":
			return array("port"=>443,"server"=>"planwatch.org","directory"=>"/backend/","method"=>"https");
			break;
		case '@flickr':
			return array("port"=>80,"server"=>"www.flickr.com","directory"=>"/services/xmlrpc/");
			break;

		default: return FALSE; break;
	}

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
//		if (is_bool($param)) $type="bool";
		if (is_array($param))
		{
			$type="array";
			if (!isset($param[count($param)-1])) $type="struct";
		}
		if (is_bool($param)) {$type=xmlrpcBoolean;}

		$f->addParam(new xmlrpcval($param, $type));
		//print_r($f);
		//echo "$param : $type<br/>";
		//echo "<hr>";
	}

	if(!$nodeinfo['method']) $nodeinfo['method']="http";

	$c=new xmlrpc_client($nodeinfo["directory"], $nodeinfo["server"], $nodeinfo["port"],$nodeinfo['method']);
	$c->setDebug($debug);
	// if(isjosh()) $c->setDebug(TRUE);
	$r=$c->send($f,10,$nodeinfo['method']);
	// print_r($f); exit;

	if (!$r) { $returnval="<div class='alert'>$message to $node failed on send().</div>"; }
	else
	{
		$v=$r->value();
		/* print_r($v); */
		if (!$r->faultCode()) {
			if ($v->kindOf()=='scalar') $returnval = $v->scalarval();
		} else {
		if ($debug) {
		$returnval="Fault: ";
		$returnval.="Code: " . $r->faultCode() .
			" Reason '" .$r->faultString()."'<br/>";
			$returnval.=" is updating" .rand();
		}

		$_SERVER['RPC_ERROR']="Fault: ";
		$_SERVER['RPC_ERROR'].="Code: " . $r->faultCode() .
			" Reason '" .$r->faultString()."'<br/>";
		}
	}
	return $returnval;
}



// LOGIN()
//
// logs the user in by setting a cookie
// the cookie data is the user's planwatch fingerprint
//------------------------------------------------------------------------------
function login($user,$pass,$remember=0,$prevpage='/',$newuser=FALSE,$redirect=TRUE)
{
	// echo "got to login test $user $pass $remember"; exit;
	// only go further if the user exists and has given us a valid password
	if (file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat") && user_is_valid($user,$pass))
	{
		// echo "got through login test"; exit;
		$userinfo=user_read_info($user);

		// sets the expiry of the cookie to 3 hours from now, or 5 years
		if ($remember) $time=time()+(86400*365*5);
		else $time=0;


		$cookie_host = '.planwatch.org';
// 		$cookie_host=$_SERVER['HTTP_HOST'];
// 		if (substr_count($cookie_host,'.') < 2) $cookie_host=".$cookie_host";
// 		$cookie_host=str_replace('www','',$cookie_host);

		$sessionkey=user_set_sessionkey($user,$time);

		setcookie($_SERVER['SESSION_COOKIE'],$sessionkey,$time,$_SERVER['WEB_ROOT']."/",$cookie_host,TRUE,TRUE);
		setcookie('remember',"remember=$remember",time()+86400*365*5,$_SERVER['WEB_ROOT']."/",$cookie_host,TRUE,TRUE);

		//unset the fingerprint cookie if it's presently set.
// 		setcookie($_SERVER['AUTH_COOKIE'],"",time()-1,$_SERVER['WEB_ROOT']."/",$cookie_host);


		if($redirect)
		{
			if ($prevpage===FALSE) { $prevpage='/'; }
			Header("Location: https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/cookie-test.php?$_SERVER[SESSION_COOKIE]=$sessionkey&newuser=$newuser&redirect_page=$prevpage");
		}
		else return TRUE;
	}
	else
	{
		redirect("/failed");
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
	$cookie_names[]="deletekey";
	$cookie_names[]=$_SERVER['SESSION_COOKIE'];
	$cookie_data=FALSE;
	$time=time()-1;

	unlink("$_SERVER[SESSION_DIR]/".$_COOKIE[$_SERVER['SESSION_COOKIE']].".skey");

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
	$indent='';
	$stopwatch_content='';
	$loadtime=$_SERVER['STOPWATCH']['pageload_end']-$_SERVER['STOPWATCH']['pageload_begin'];
	foreach($_SERVER['STOPWATCH'] as $key=>$value)
	{
		if (strpos($key,'begin'))
		{
			for($i=0;$i<$indent_level;$i++)  $indent.="&nbsp;&nbsp;&nbsp;&nbsp;";
			$basekey=str_replace('_begin','',$key);
			$indent_level+=1;
			if (isset($_SERVER['STOPWATCH'][$basekey."_end"]) && is_numeric($_SERVER['STOPWATCH'][$basekey."_end"]))
			{
				$difference=round($_SERVER['STOPWATCH'][$basekey."_end"]-$_SERVER['STOPWATCH'][$basekey."_begin"],5);
				$outstring="$indent $basekey: $difference";
				if (strpos($key,'!')===FALSE)
				{
					if ($difference >= ($loadtime*.3)) $outstring="<strong>$outstring</strong><br/>";
					if ($difference <= ($loadtime*.1)) $outstring=""; //<span class='edit_links'>$outstring</span><br/>
					if ($difference <= ($loadtime*.05)) $outstring="";//$outstring="<span class='edit_links'>$outstring</span><br/>";
					if ($difference > ($loadtime*.1) && $difference < ($loadtime*.3))
						{ $outstring=$outstring."<br/>"; }
				}
				else { $outstring=$outstring."<br/>"; }
				$stopwatch_content.=$outstring;
				$indent='';
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
	if (stristr($_SERVER['HTTP_USER_AGENT'],'MSIE 7')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'MSIE 8')) $modern=TRUE;

	// This gets us Opera versions with non-awful CS and JS support
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/7')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/8')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/9')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/10')) $modern=TRUE;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'Opera/11')) $modern=TRUE;

	return $modern;
}

function hyperlink($string) {
//	$string = preg_replace("#\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>#i","<a href='\\1'>\\1</a>",$string);
	$string = preg_replace("#([^'\"!-][\s\(\)])+(https?|ftp|news){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^\"\s\(\)<\[]*)?)#ie", 'handle_url_tag', $string);
//	$string = preg_replace("#([\s\(\)])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^\"\s\(\)<\[]*)?)#ie", '\'$1\'.handle_url_tag(\'$2.$3\', \'$2.$3\')', $string);
  return $string;
}

function handle_url_tag($url, $link='') {
		$pretext=$matches[1];
		$url=$matches[2];
		$link=$matches[3];

		$full_url = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $url);

		if (strpos($url, 'www.') === 0) { $full_url = 'http://'.$full_url; }
		else if (strpos($url, 'ftp.') === 0) { $full_url = 'ftp://'.$full_url; }
		else if (!preg_match('#^([a-z0-9]{3,6})://#', $url, $bah))  { $full_url = 'http://'.$full_url; }

		$link = ($link == '' || $link == $url) ? ((strlen($url) > 55) ? substr($url, 0 , 39).' &hellip; '.substr($url, -10) : $url) : stripslashes($link);

		return '$pretext <a href="'.$full_url.'">'.$link.'</a>';
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


function email_send_mg($parameters)
{
	global $debug;

	if($parameters['html']=='')
	{
		$parameters['html']=nl2br($parameters['text']);
	}

	if(!is_array($parameters['attachments']) && is_array(unserialize(base64_decode($parameters['attachments']))))
	{
		$parameters['attachments']=unserialize(base64_decode($parameters['attachments']));
	}

	if(is_array($parameters['attachments']))
	{
		$parameters['text'].="\n\nAttachments:\n------------\n";
		$parameters['html'].="<br><h4>Attachments:</h4>";
		foreach($parameters['attachments'] as $i=>$attachment)
		{
			if(is_file($attachment))
			{
			$attachment_url="https://planwatch.org/".str_replace($_SERVER['DOCUMENT_ROOT'],'',$attachment);
			$attachment_name=basename($attachment);
			$parameters['text'].="\n\n[$attachment_name]($attachment_url)";
			$parameters['html'].="<br><a href='$attachment_url'>$attachment_name</a>";
			$parameters["attachment[$i]"]=curl_file_create($attachment,mime_content_type($attachment),basename($attachment));
			}
		}
	}
	else
	{
		$parameters['html'].="<br><br>no attachments";
	}

	if(strlen($parameters['from'])<3) $parameters['from']="info@mg.planwatch.org";



	// initiate cURL w/ protocol & URL of remote host
	$ch=curl_init($_SERVER['mailgun_base_url']."/messages");

	// normal POST request
	curl_setopt($ch,CURLOPT_POST,TRUE);

	// normal POST request
	curl_setopt($ch,CURLOPT_SAFE_UPLOAD,TRUE);

	//authenticate
	curl_setopt($ch,CURLOPT_USERPWD,"api:$_SERVER[mailgun_skey]");

	//verify
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);

	//options
	curl_setopt($ch,CURLOPT_POSTFIELDS,$parameters);

	// set response to return as variable
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

	// trap response into $response var
	$response=curl_exec($ch);
	$response_a=json_decode($response);

	if(!strstr($response,"Queued"))
	{
		$caller=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
		$stack=print_r(debug_backtrace(),TRUE);
		foreach($parameters as $key=>$parameter)	$parameters_for_error="$key: $parameter\n";
		mail("joshuawdavidson@gmail.com","mail error from $caller - $response message_functions.php:email_send_mg ","$parameters_for_error\n\n\n$stack","error@chippu.org");
	}
	else
	{
		if(isset($parameters['customer']))		db_query("insert into `customer_contacts` (`customer`,`message`,`gordernum`,`type`,`notes`) values(\"$parameters[customer]\",\"".base64_encode($parameters['text'])."\",\"$parameters[gordernum]\",\"$parameters[type]\",\"$response\");");
		if(isset($response_a->id))
		{
/*
			echo "<br>insert into `communications_log` (`medium`,`to`,`message`,`mediaurls`,`status`,`origin`,`sid`,`ref_table`,`ref_rowid`)
				values(\"email\",\"$parameters[to]\",\"$parameters[html]\",\"$parameters[attachment]\",\"".$response_a->message."\",\"email_queue $parameters[id]\",\"".$response_a->id."\",\"$parameters[tablename]\",\"$parameters[rowid]\"<br>";
			db_query("insert into `communications_log` (`medium`,`to`,`message`,`mediaurls`,`status`,`origin`,`sid`,`ref_table`,`ref_rowid`)
				values(\"email\",\"$parameters[to]\",\"$parameters[html]\",\"$parameters[attachment]\",\"".$response_a->message."\",\"email_queue $parameters[id]\",\"".$response_a->id."\",\"$parameters[tablename]\",\"$parameters[rowid]\");
			");
			db_query("update email_queue set sid=\"".$response_a->id."\" where `id`=\"$parameters[id]\"");
*/
		}
	}

	// close cURL transfer
	curl_close($ch);

	return $response;

}



function email_send($to,$subject,$message,$htmlmessage=FALSE,$attachments=FALSE,$from=FALSE)
{
	foreach($attachments as $attachment)
	{
		$attachment_url="https://planwatch.org/".str_replace($_SERVER['DOCUMENT_ROOT'],'',$attachment);
		$attachment_name=basename($attachment);
		$message.="\n\n[$attachment_name]($attachment_url)";
		if($htmlmessage) $htmlmessage.="<br><a href='$attachment_url'>$attachment_name</a>";
	}
	email_send_mg(array("to"=>$to,"subject"=>$subject,"text"=>$message,"html"=>$htmlmessage));
}

?>