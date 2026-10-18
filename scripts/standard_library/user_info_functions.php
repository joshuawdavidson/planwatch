<?php
/*
USER_INFO_FUNCTIONS.php

contains all the functions used in conditionals with users, as well as a few
other authentication functions.

*/

include_once('/var/www/planwatch.org/siteconfig.php');
include_once('essential.php');

function cache_clear($planowner)
{
	if ($_SERVER['PLANOWNER_INFO_ARRAY']['username']!=$planowner)
		plan_get_owner_info($planowner);
	exec("rm -f $_SERVER[FILE_ROOT]/temp/*".base64_encode($planowner)."*.cache");
	exec("rm -f $_SERVER[FILE_ROOT]/../temp/*$planowner*.cache");
	exec("rm -f $_SERVER[FILE_ROOT]/temp/{$_SERVER['PLANOWNER_INFO_ARRAY']['salt']}*.cache");
}

// USER_IS_WRITER()
//
// TRUE if user can write for a plan, FALSE otherwise
//------------------------------------------------------------------------------
function user_is_writer($plan_name,$user_name)
{
	if ($plan_name==$user_name) return TRUE;
	$writers_list="$_SERVER[PWUSERS_DIR]/$plan_name/writerslist.txt";
	if (file_exists($writers_list))
		$writers=file_get_contents($writers_list);

	$writers=str_replace(array(",",";","/"),"\n",$writers)."\n";
	if (strpos($writers,"\n$user_name\n")!==FALSE) return TRUE;
	else return FALSE;
}


// USER_IS_LOCAL()
//
// TRUE if user exists locally, FALSE otherwise
//------------------------------------------------------------------------------
function user_is_local($username)
{
	if(file_exists("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat")) return TRUE;
	else return FALSE;
}

// USER_GET_LAST_ACTION()
//
// returns last action for local users
//------------------------------------------------------------------------------
function user_get_last_action($username)
{

	$lastact_fn="$_SERVER[FILE_ROOT]/stats/lastaction/$username";
	if (file_exists($lastact_fn))
	{
		return filemtime($lastact_fn);
	}
	else return 0;
}




// USER_UPDATE_LAST_ACTION()
//
// touches a file to indicate the last time the user viewed a page
//------------------------------------------------------------------------------
function user_update_last_action()
{
	if ($_SERVER['USER'])
	{
		// if the lastaction dir doesn't exist, make it.
		if (!is_dir("$_SERVER[FILE_ROOT]/stats/lastaction/"))
		{
			$oldu=umask(0);
			mkdir("$_SERVER[FILE_ROOT]/stats/lastaction/");
			umask($oldu);
		}

		file_put_contents("$_SERVER[FILE_ROOT]/stats/lastaction/$_SERVER[USER]",'a');
	}

}



// USER_READ_INFO()
//
// reads in and returns the whole userdata array for a given user
//------------------------------------------------------------------------------
function user_read_info($user,$publish=FALSE)
{
	$profilekey=rand();
	profile('readuser'.$profilekey);
	$defaults=user_get_defaults();
	$readuser_all = [];


	$valid=FALSE;

	if ($user && $user!='guest')
	{
		$user=plan_repair_local_name($user);
//		if($user=='testuser99') { echo file_get_contents("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"); exit; }

		if (file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"))
		{
			$readuser_info=unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"));
//			if($_SERVER['REMOTE_ADDR']=='70.36.144.243') { print_r($readuser_info); exit; }
		}

		if (file_exists("$_SERVER[PWUSERS_DIR]/$user/preferences.dat"))
			$readuser_prefs=unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$user/preferences.dat"));
		else $readuser_prefs=array();

		if(isset($readuser_info) && is_array($readuser_info)) // && is_array($readuser_prefs)
		{
			$valid=TRUE;
			$readuser_all=array_merge($defaults,$readuser_prefs,$readuser_info);
			if($publish)
			{
				$_SERVER['USERINFO_ARRAY']=$readuser_all;
				$_SERVER['USER']          = $user;
				$_SERVER['USER_ROOT']     = "$_SERVER[PWUSERS_DIR]/$readuser_all[username]";
				$_SERVER['FINGERPRINT']   = isset($readuser_all['userpass']) ? user_get_fingerprint($readuser_all['username'],$readuser_all['userpass']) : '';
			}
		}
		elseif($publish)
		{
			$readuser_all=array("error"=>"User is invalid");
			$valid=FALSE;
			$_SERVER['USER']='Guest';
			$_SERVER['USERINFO_ARRAY']=array();
			$_SERVER['USER_ROOT']="$_SERVER[DOCUMENT_ROOT]/tmp";
			$_SERVER['USERINFO_ARRAY']['snitchlevel']=3;
			$_SERVER['SESSION_ID']=FALSE;
		}
	}
	profile('readuser'.$profilekey);

	return $readuser_all;
}


// USERS_GET_LIST()
//
// returns a list of users with "list me" pref, or all users if caller is admin
//------------------------------------------------------------------------------
function users_get_list()
{
	$list=array();
	if (user_is_administrator()) $list_fn="$_SERVER[FILE_ROOT]/stats/userlist_all.txt";
	else $list_fn="$_SERVER[FILE_ROOT]/stats/userlist_public.txt";

	if (file_exists($list_fn) && @filemtime($list_fn)>(time()-3600*12))
	{
		$list=@file($list_fn);
	}
	else
	{
		exec("ls -d $_SERVER[PWUSERS_DIR]/"."*"."/",$ulist);
		foreach($ulist as $listuser)
		{
			$tempuser=user_read_info(basename($listuser));
			if (isset($tempuser) && isset($tempuser['rlpref']) && $tempuser['rlpref']==1)
			{
				$list[]=basename($listuser);
			}
			if (is_dir("$_SERVER[PWUSERS_DIR]/".basename($listuser)))
			{
				$list_all[]=basename($listuser);
			}
		}
		file_put_contents("$_SERVER[FILE_ROOT]/stats/userlist.txt",implode("\n",$list));
		file_put_contents("$_SERVER[FILE_ROOT]/stats/userlist_all.txt",implode("\n",$list_all));
		if (user_is_administrator())
		{
			$list=$list_all;
		}
	}

return $list;
}






// user_is_administrator()
//
// tests the $user to see if it is an administrator
// and a valid user
//-----------------------------------------------------------------------------
function user_is_administrator()
{
	if (user_verify_sessionkey()
		&& (
			$_SERVER['USERINFO_ARRAY']['username']=='jwdavidson'
			|| $_SERVER['USERINFO_ARRAY']['username']=='madvani'
			|| $_SERVER['USERINFO_ARRAY']['username']=='system'
			)
		) return TRUE;

	else return FALSE;
}




// user_get_snitchlevel()
//
// determines the level of snitch a user has
//-----------------------------------------------------------------------------
function user_get_snitchlevel($user)
{
		if (file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"))
	{
		$userinfo=user_read_info($user);
		if ($userinfo['snitchlevel'] >=1)	return $userinfo['snitchlevel'];
	}
	else return 4; // 4 is code for "inspect remotesnitch"

}


// user_verify_sessionkey()
//
// tests a pwo fingerprint to determine the user
//-----------------------------------------------------------------------------
function user_verify_sessionkey($sessionkey=FALSE,$testuser=FALSE,$globalize=TRUE,$debug=FALSE)
{
	$valid=FALSE;
//	if($testuser===FALSE && isset($_SERVER['USER'])) $testuser=$_SERVER['USER'];
	if(!$sessionkey && isset($_SERVER['SESSION_ID'])) {$sessionkey=$_SERVER['SESSION_ID'];}
	if(!$sessionkey && isset($_COOKIE[$_SERVER['SESSION_COOKIE']])) {$sessionkey=$_COOKIE[$_SERVER['SESSION_COOKIE']];}
	if(file_exists("$_SERVER[SESSION_DIR]/$sessionkey.skey"))
	{
//		echo "session file exists!"; exit;
		$keydata=unserialize(file_get_contents("$_SERVER[SESSION_DIR]/$sessionkey.skey"));

		if($debug) { print_r($keydata); echo "<hr>".file_get_contents("$_SERVER[SESSION_DIR]/$sessionkey.skey"); exit; }

//		if($_SERVER['USER']=='jwdavidson' && $sessionkey==$_COOKIE['mau']) $debug=TRUE;

		if($sessionkey==$keydata['sessionkey'] && $keydata['expires']>time() && $keydata['expires']>-1)
		{
			if($globalize===TRUE)
			{
				$_SERVER['SESSIONKEY_VERIFIED']=TRUE;
				$_SERVER['SESSION_ID']=$keydata['sessionkey'];
				$_SERVER['USER']=$keydata['user'];
			}
			if(($testuser && $keydata['user']==$testuser) || $testuser===FALSE)
				$valid=TRUE;

		}
	}
//	else 		{ echo "session file doesnt exist!"; exit; }


	return $valid;
}

function user_set_sessionkey($user,$expires=0)
{
	// wipe sessions in two weeks.
	if($expires==0) $expires=time()+24*3600*14;

	$sessionkey=uniqid();
	file_put_contents("$_SERVER[SESSION_DIR]/$sessionkey.skey",serialize(array("user"=>$user,"sessionkey"=>$sessionkey,"expires"=>$expires)));
	if(!file_exists("$_SERVER[SESSION_DIR]/$sessionkey.skey")) { echo "failed to write session $sessionkey to $_SERVER[SESSION_DIR]!"; exit; }
	$_SERVER['SESSION_ID']=$sessionkey;
	return $sessionkey;
}

function user_clear_sessionkey($key="1")
{
	// wipe sessions in two weeks.
	if(file_exists("$_SERVER[SESSION_DIR]/$key.skey"))
		unlink("$_SERVER[SESSION_DIR]/$key.skey");
	if(file_exists("$_SERVER[SESSION_DIR]/$key.skey")) $success=TRUE;
	else $success=FALSE;

	return $success;
}

function user_get_postable_plans($username)
{
	exec("grep -l $username $_SERVER[PWUSERS_DIR]/*/writerslist.txt",$lists);
	if(plan_is_local($username)) $plans[]=$username;
	foreach($lists as $list)
	{
		$plans[]=str_replace(array($_SERVER['PWUSERS_DIR'],"writerslist.txt","/"),"",$list);
	}
	if(!is_array($plans)) $plans=array();
	return $plans;
}

// user_get_fingerprint()
//
// returns a fingerprint based on the user
//-----------------------------------------------------------------------------
function user_get_fingerprint($user,$pass)
{
		return user_set_sessionkey($user,0);
//		return base64_encode($user).":::".md5("$pass");
}


// user_verify_fingerprint()
//
// tests a pwo fingerprint to determine the user
//-----------------------------------------------------------------------------
function user_verify_fingerprint($fingerprint='',$globalize=TRUE)
{
	$valid=FALSE;

	if(user_verify_sessionkey($fingerprint,FALSE,$globalize))
	{
		$valid=FALSE;
	}
	else
	{
		$fingerprint=urldecode($fingerprint);

		list($user,$pass)=explode(":::",$fingerprint);
		$user=base64_decode($user);


		if ($user && $user!='guest' && file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"))
		{
			$userinfo=unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"));

			if (strtolower($user)==strtolower($userinfo['username']) && $pass==md5($userinfo['userpass']))
			{
				$valid=TRUE;	// return TRUE because the fingerprint checks out
			}
		}
		else $valid=FALSE;
	}

	return $valid;
}


// user_is_valid()
//
// tests $user and $password against known values.
//
// stored in the user's dir inside userinfo.dat
//-----------------------------------------------------------------------------
function user_is_valid($user='',$pass='')
{
	$valid=FALSE;
	$storedhash="*FALSE*";

	$username=$userpass='';

	if ($user && $user!='guest')
	{

// 		if(file_exists("$_SERVER[PWUSERS_DIR]/$user/hash.bc"))
// 		{
// 			exec("mv \"$_SERVER[PWUSERS_DIR]/$user/hash.bc\" \"$_SERVER[PWUSERS_DIR]/$user/.hash.bc\"");
// 		}

		if(file_exists("$_SERVER[PWUSERS_DIR]/$user/.hash.bc"))
		{
			$storedhash=file_get_contents("$_SERVER[PWUSERS_DIR]/$user/.hash.bc");
			$hasher = new PasswordHash(8, false);
			if (strlen($pass) > 72) { die("Password must be 72 characters or fewer"); }
			$valid = $hasher->CheckPassword($pass, $storedhash);
		}
		elseif($user!='jwdavidson')
		{
			if (file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"))
			{
				extract(unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat")));
			}

			if (strtolower($user)==strtolower($username) && strtolower($pass)==strtolower($userpass))
			{
				$valid=TRUE;
			}
		}
		else $valid=FALSE;
	}
return $valid;
}



// user_update_lastread()
//
// updates the last read time of planwriter's plan for user
//------------------------------------------------------------------------------
// TODO:(v4.1) rename user_update_lastread to plan_update_lastread and move to plan_info_functions.php
function user_update_lastread($planwriter,$reset=FALSE)
{
	$lastfn_dat="$_SERVER[USER_ROOT]/lastread.dat";
	if (file_exists($lastfn_dat))
		$lastread=unserialize(file_get_contents($lastfn_dat));
	else $lastread=array();

//	if ($_SERVER['USER']=='jwdavidson') echo "$planwriter $lastread[$planwriter]<br/>";

	if ($reset) $lastread[$planwriter]=0;
	else $lastread[$planwriter]=time();

//	if ($_SERVER['USER']=='jwdavidson') echo "$planwriter $lastread[$planwriter]<br/>";

	file_put_contents($lastfn_dat,serialize($lastread));

return TRUE;
}




// user_is_authorized()
//
// checks to see if user is authorized to view private plan updates
// by testing their username against the planowner's allowedlist.txt
// in the planowner's directory
//
// returns a bool
//-----------------------------------------------------------------------------
function user_is_authorized($planowner,$remoteuser)
{
	$authorized=FALSE;

	if (!$remoteuser) { $remoteuser=$_SERVER['USER']; }
	if (!$remoteuser) { $remoteuser='guest'; }

	if ($remoteuser==$planowner) return TRUE;

	$auth_fn="$_SERVER[PWUSERS_DIR]/$planowner/allowedlist.txt";

	if (file_exists($auth_fn))
	{
		$auth_data=file_get_contents($auth_fn);

		$auth_data  = strtolower($auth_data);
		$remoteuser = strtolower($remoteuser);

		if (strpos($auth_data,$remoteuser)!==FALSE) return TRUE;
		else return FALSE;
	}
	else return FALSE;
}



// user_is_blocked()
//
// checks to see if user is blocked from viewing a plan
// by testing their username against the blockedlist.txt file
// in the planowner's directory
//
// returns a bool
//-----------------------------------------------------------------------------
function user_is_blocked($planowner,$remoteuser)
{
	$blocked=FALSE;
	if (!$remoteuser) { $remoteuser=$_SERVER['USER']; }
	if (!$remoteuser) { $remoteuser='guest'; }

	$block_fn="$_SERVER[PWUSERS_DIR]/$planowner/blockedlist.txt";

	if (file_exists($block_fn))
	{
		$block_data=file_get_contents($block_fn);

		$block_data=strtolower($block_data);
		$remoteuser=strtolower($remoteuser);

		if (strpos($block_data,$remoteuser)!==FALSE) return TRUE;
		else
		{
			if (strpos($block_data,$_SERVER['REMOTE_ADDR'])!==FALSE) return TRUE;
			else return FALSE;
		}
	}
	else return FALSE;
}


function user_get_defaults($which=FALSE)
{
	if(empty($_SERVER['USER_DEFAULTS']))
	{
		include('prefs_form.php');

		if($which===FALSE || $which=='prefs')
		foreach($prefs_form_definitions as $key=>$definfo)
		{
			if($key!='username' && $key!='userpass' && $key!='form_disposition')
			{
				$defaults[$key]=$definfo['default'];
			}
		}

		if($which===FALSE || $which=='info')
		foreach($user_form_definitions as $key=>$definfo)
		{
			if($key!='username' && $key!='userpass' && $key!='form_disposition')
			{
				$defaults[$key]=$definfo['default'];
			}
		}

		$_SERVER['USER_DEFAULTS']=$defaults;
		return $defaults;
	}
	else return $_SERVER['USER_DEFAULTS'];
}


// user_list_aliases()
//
// returns a list of aliases from $planowner's watched list
//------------------------------------------------------------------------------
function user_list_aliases($planowner)
{
	profile('alias_list_pre');
	$alias_list=array();
	if($planowner==$_SERVER['USER']
		&& isset($_SERVER['USERINFO_ARRAY']['alias_list'])
	)
	{
		return $_SERVER['USERINFO_ARRAY']['alias_list'];
	}

	if (file_exists("$_SERVER[PWUSERS_DIR]/$planowner/watchedlist.txt"))
	{
		$watchedlist=@file("$_SERVER[PWUSERS_DIR]/$planowner/watchedlist.txt");
		if (!$watchedlist) $watchedlist=array();

		// don't bother if there aren't aliases to link
		$watchstring=implode(' ',$watchedlist);
		profile('alias_list_pre');
		if (strpos($watchstring,'!') && strpos($watchstring,':'))
		{
			unset($watchstring);
			profile('alias_list_loop');
			foreach($watchedlist as $i=>$item)
			{
				$item=trim($item);
				if (strpos($item,'!')!==FALSE && strpos($item,':')!==FALSE)
				{
					$item=str_replace('://','//',$item);
					list($item,$alias)=explode(':',str_replace('!','',$item),2);
					$item=str_replace('//','://',$item);
					//$item=plan_get_real_location($item);
					$alias_list[$alias]=$item;
	//	            if ($_SERVER['USER']=='jwdavidson') echo "ULA $alias: $item<br/>\n";
				}
			}
			profile('alias_list_loop');
		}
	}
	if($planowner==$_SERVER['USER'])	$_SERVER['USERINFO_ARRAY']['alias_list']=$alias_list;
	return $alias_list;
}



?>