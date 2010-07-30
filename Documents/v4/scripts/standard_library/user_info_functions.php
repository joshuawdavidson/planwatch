<?php
/*
USER_INFO_FUNCTIONS.php

contains all the functions used in conditionals with users, as well as a few
other authentication functions.

*/

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
	if (strstr($writers,"\n$user_name\n")) return TRUE;
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
// should probably be in usertestfunctions.php
//------------------------------------------------------------------------------
function user_read_info($user,$publish=FALSE)
{
	
	$_SERVER['STOPWATCH']['readuser_begin']=array_sum(explode(' ',microtime()));
	
	if ($user && $user!='guest')
	{
		$user=plan_repair_local_name($user);

		if (file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"))
			$readuser_info=unserialize(@file_get_contents("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"));
		
		if (file_exists("$_SERVER[PWUSERS_DIR]/$user/preferences.dat"))
			$readuser_prefs=unserialize(@file_get_contents("$_SERVER[PWUSERS_DIR]/$user/preferences.dat"));
		else
		{
			$readuser_prefs=unserialize(@file_get_contents("$_SERVER[FILE_ROOT]/resources/defaults/preferences.dat"));
		}


		$readuser_all=array_merge($readuser_info,$readuser_prefs);
		if($publish)
		{
			$_SERVER['USERINFO_ARRAY']=$readuser_all;
			$_SERVER['USER']=$user;
		}
		foreach($readuser_all as $key=>$value)
		{
			$userdata_string.="&$key=$value";
		}
		$userdata_string=substr($userdata_string,1);
	}
	$_SERVER['STOPWATCH']['readuser_end']=array_sum(explode(' ',microtime()));

	return $userdata_string;
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
			parse_str(user_read_info(basename($listuser)),$tempuser);
			if ($tempuser['rlpref']==1) $list[]=basename($listuser);
			if (is_dir("$_SERVER[PWUSERS_DIR]/".basename($listuser))) $list_all[]=basename($listuser);
		}
		file_put_contents("$_SERVER[FILE_ROOT]/stats/userlist.txt",implode("\n",$list));
		file_put_contents("$_SERVER[FILE_ROOT]/stats/userlist_all.txt",implode("\n",$list_all));
		if (user_is_administrator()) $list=$list_all;
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
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass'])
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
		parse_str(user_read_info($user));
		if ($snitchlevel >=1)	return $snitchlevel;
	}
	else return 4; // 4 is code for "inspect remotesnitch"
	
}





// user_get_fingerprint()
//
// returns a fingerprint based on the user
//-----------------------------------------------------------------------------
function user_get_fingerprint($user,$pass)
{
	return base64_encode($user).":::".md5("$pass");
}



// user_verify_fingerprint()
//
// tests a pwo fingerprint to determine the user
//-----------------------------------------------------------------------------
function user_verify_fingerprint($fingerprint='',$globalize=TRUE)
{
	$valid=FALSE;
	
	$fingerprint=urldecode($fingerprint);
	list($user,$pass)=explode(":::",$fingerprint);
	$user=base64_decode($user);
	if ($user && $user!='guest' && file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"))
	{
		$userinfo=@unserialize(@file_get_contents("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"));
					
		if (strtolower($user)==strtolower($userinfo['username']) && $pass==md5($userinfo['userpass']))
		{
			$valid=TRUE;	// return TRUE because the fingerprint checks out


			if ($globalize)
			{
				// read in prefs, too, since the user is valid. this creates a
				// reference array that can be easily used to query the logged in
				// user
				$prefs_fn="$_SERVER[PWUSERS_DIR]/$user/preferences.dat";
				if (file_exists($prefs_fn))
					$userinfo=array_merge($userinfo,unserialize(file_get_contents($prefs_fn)));

				// globalize the relevant info
				$_SERVER['USER']=$userinfo['username'];
				$_SERVER['USERINFO_ARRAY']=$userinfo;
				$_SERVER['USER_ROOT']="$_SERVER[PWUSERS_DIR]/$userinfo[username]";
				$_SERVER['FINGERPRINT']=$fingerprint;
			}
		}
		else
		{
			$valid=FALSE;
			if ($globalize)
			{
				$_SERVER['USER']='guest';
				$_SERVER['USERINFO_ARRAY']=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/resources/defaults/preferences.dat"));
				$_SERVER['USERINFO_ARRAY']['snitchlevel']=3;
			}
		}
	}
	else $valid=FALSE;

return $valid;
}



// user_is_valid()
//
// tests $user and $password against known values.
//
// stored in the user's dir inside userdata.txt
//-----------------------------------------------------------------------------
function user_is_valid($user='',$pass='')
{
	$valid=FALSE;
	
	if ($user && $user!='guest')
	{
		if (file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"))
		{
			extract(unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat")));
		}
		else parse_str(user_read_info($user));
					
		if (strtolower($user)==strtolower($username) && strtolower($pass)==strtolower($userpass))
		{
			$valid=TRUE;
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



// user_list_aliases()
//
// returns a list of aliases from $planowner's watched list 
//------------------------------------------------------------------------------
function user_list_aliases($planowner)
{
	profile('alias_list_pre');
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
					$item=plan_get_real_location($item);
					$alias_list[$alias]=$item;
	//	            if ($_SERVER['USER']=='jwdavidson') echo "ULA $alias: $item<br/>\n";
				}
			}
			profile('alias_list_loop');
		}
	}
	return $alias_list;
}


	
?>