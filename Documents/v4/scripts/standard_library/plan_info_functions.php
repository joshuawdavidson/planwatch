<?php

/*
PLAN_INFO_FUNCTIONS.php

contains functions to test properties of plans
*/



// plan_get_real_location()
//
// finds the correct planurl for a given plan name
//------------------------------------------------------------------------------
function plan_get_real_location($planowner,$debug=FALSE)
{
	profile("pgrl_$planowner",'begin');
	$planowner=urldecode($planowner);
	$planowner=trim($planowner);
//	if ($debug) $_SERVER['DEBUG_INFO'].="trimmed and decoded: $planowner<br/>\n";
	$planname=$planowner;
	$success=FALSE;
	$failure_fn="$_SERVER[FILE_ROOT]/stats/plan_failures.dat";
	$success_fn="$_SERVER[FILE_ROOT]/stats/plan_locations.dat";

	// check for incorrect case
	$planowner=plan_repair_local_name($planowner);
//	if ($debug) $_SERVER['DEBUG_INFO'].="repaired local name: $planowner<br/>\n";

	// if we already know it's a failure, give up now.
	if (is_array($_SERVER['PLAN_LOCATION_FAILED_ARRAY']) && in_array($planname,$_SERVER['PLAN_LOCATION_FAILED_ARRAY']))
	{
		if ($debug) $_SERVER['DEBUG_INFO'].="in failed array";
		profile("pgrl_$planowner",'end');
		return FALSE;
	}
	
	// check to see if we have it stored
	if (array_key_exists($planname,$_SERVER['PLAN_LOCATION_ARRAY']) && trim($_SERVER['PLAN_LOCATION_ARRAY'][$planname]))
	{
		$planowner=$_SERVER['PLAN_LOCATION_ARRAY'][$planname];
		profile("pgrl_$planowner",'end');
		if ($debug) $_SERVER['DEBUG_INFO'].="already had it: $planowner<br/>\n";
		if ($planowner) return $planowner;
	}

	// if it has the @local marker, assume it's legit
	// this is probably unwise as a policy.
	elseif(strpos($planowner,'@local'))
	{
		$planowner=str_replace('@local','',$planowner);
		if ($debug) $_SERVER['DEBUG_INFO'].="it's local: $planowner<br/>\n";
		$success=TRUE;
	}
	
	// if it has the @spiel marker, it's a spiel
	elseif(strpos($planowner,'@spiel'))
	{
		$planowner="http://www.planwatch.org/spiels/".str_replace('@spiel','',$planowner).".rss";
		$success=TRUE;
		if ($debug) $_SERVER['DEBUG_INFO'].="it's a spiel: $planowner<br/>\n";
	}
	
	// then see if it's an anonymous coward, which we should leave alone
 	elseif(strpos($planowner,'Anonymous Coward')!==FALSE)
 	{
 		$success=TRUE;
		if ($debug) $_SERVER['DEBUG_INFO'].="it's an anon coward: $planowner<br/>\n";
 	}

	// then see if it's an RSS Reader, which we should leave alone
	elseif(strpos($planowner,'RSS')!==FALSE)
	{
 		$success=TRUE;
		if ($debug) $_SERVER['DEBUG_INFO'].="it's an rss reader: $planowner<br/>\n";
	}

	// then see if it's a URL, which we should leave alone
	elseif(strpos($planowner,'://')!==FALSE)
	{
		$planowner=str_replace("feed://","http://",$planowner);
 		$success=TRUE;
		if ($debug) $_SERVER['DEBUG_INFO'].="it's a url: $planowner<br/>\n";
	}


// everything after this point gets recorded in the global array
// to prevent repeated lookups.


	// if not, check to see if it's a local user
	elseif (plan_is_local($planowner)) $success=TRUE;

// TODO:(v4.5) integrate rss_finder.php into plan_get_real_location() to make adding blogs easier.
	
	// next, check to see if it has a planurl
	elseif (file_exists("$_SERVER[PWUSERS_DIR]/$planowner/userinfo.dat"))
	{
		parse_str(user_read_info($planowner),$ownertest);
		$ownertest_url=$ownertest['planusername'].$ownertest['plantype'];
		$planowner=str_replace('RSS','',$ownertest_url); // account for plantype=RSS
		if (strpos($planowner,'://'))
		{
			$success=TRUE;
			if ($debug) $_SERVER['DEBUG_INFO'].="it's a url: $planowner<br/>\n";
		}
			// if it's a url, we're done. if it's @lj or something, we go ahead
	}
	// next, see if maybe it's a planworld offsite or lj plan (with an @ already)
	// dj, dl, and xanga also fall into this category. they may be separated by '.'
	// instead of '@' for cross-planworld reading.
	if(strpos($planowner,'@') || strpos($planowner,'.') && !$success)
	{
		$planowner=str_replace('@note','@amherst.edu',$planowner);
		if (strpos($planowner,'amherst'))
		{
			$planowner=str_replace('@amherst.edu.amherst.edu','@amherst.edu',$planowner);
			$planowner=str_replace('@vax.amherst.edu','@amherst.edu',$planowner);
			$planowner=str_replace('@unix.amherst.edu','@amherst.edu',$planowner);
		}

		$planowner=str_replace('@pwn','@planworld.net',$planowner);

		$planowner=str_replace('@deadjournal.com','@deadjournal',$planowner);
		$planowner=str_replace('.dj','@deadjournal',$planowner);
		$planowner=str_replace('@dj','@deadjournal',$planowner);
		if (strpos($planowner,'@deadjournal')) $planowner="http://www.deadjournal.com/users/".str_replace('@deadjournal','',$planowner)."/data/rss";

		$planowner=str_replace('@xanga.com','@xanga',$planowner);
		$planowner=str_replace('.xanga','@xanga',$planowner);
		if (strpos($planowner,'@xanga')) $planowner="http://www.xanga.com/rss.aspx?user=".str_replace('@xanga','',$planowner);

		$planowner=str_replace('@livejournal.com','@livejournal',$planowner);
		$planowner=str_replace('.lj','@livejournal',$planowner);
		$planowner=str_replace('@lj','@livejournal',$planowner);
		if (strpos($planowner,'@livejournal')) 
		{
			$planowner_array=explode("@",$planowner);
			if(count($planowner_array)==3)
			{
				$userinfo = $planowner_array[0]."@";
				$planowner=$planowner_array[1]."@".$planowner_array[2];
				$auth="?auth=digest";
			}
			$planowner="http://{$userinfo}www.livejournal.com/users/".str_replace('@livejournal','',$planowner)."/data/rss$auth";
		}

		$planowner=str_replace('.msn','@msn',$planowner);
		if (strpos($planowner,'@msn')) $planowner="http://spaces.msn.com/members/".str_replace('@msn','',$planowner)."/feed.rss";

//		$planowner=str_replace('.dl','@diaryland',$planowner);
		$planowner=str_replace('@dl','@diaryland',$planowner);
		$planowner=str_replace('@diaryland.com','@diaryland',$planowner);
		if (strpos($planowner,'@diaryland')) $planowner="http://".str_replace('@diaryland','',$planowner).".diaryland.com/";
		if (strpos($planowner,'@blogspot')) $planowner="http://".str_replace('@blogspot','',$planowner).".blogspot.com/atom.xml";
		if (strpos($planowner,'@blogger')) $planowner="http://".str_replace('@blogger','',$planowner).".blogger.com/atom.xml";
		if (strpos($planowner,'@myspace'))
		{
			if (!is_int($friendid=str_replace("@myspace",'',$planowner)))
			{
				$data=file_get_contents("http://myspace.com/".str_replace('@myspace','',$planowner))."<hr/>";
				preg_match("/friendID=(\d{1,})/",$data,$id);
				$friendid=$id[1];
			}
			$planowner="http://blog.myspace.com/blog/rss.cfm?friendID=$friendid";
		}

		$success=TRUE;
		
		if ($debug) $_SERVER['DEBUG_INFO'].="got it: $planowner<br/>\n";
	}

	// if we got what we wanted, and we didn't have it already, store it for future use.
	if (!in_array($planname,array_keys($_SERVER['PLAN_LOCATION_ARRAY'])) && $success==TRUE)
	{
		$_SERVER['PLAN_LOCATION_ARRAY'][$planname]=$planowner;
		file_put_contents($success_fn,serialize($_SERVER['PLAN_LOCATION_ARRAY']));
	}


	// return the good news or write the bad news quietly.
	if ($success===TRUE)
	{
		profile("pgrl_$planowner",'end');
		return $planowner;
	}
	else
	{
		$_SERVER['PLAN_LOCATION_FAILED_ARRAY'][$planowner]=$planowner;
		file_put_contents($failure_fn,serialize($_SERVER['PLAN_LOCATION_FAILED_ARRAY']));
		profile("pgrl_$planowner",'end');
		return FALSE;
	}
}





// PLAN_IS_CROSSNODE()
//
// tests planworld nodes to see if a user is valid.
//------------------------------------------------------------------------------
function plan_is_crossnode($username,$node)
{
	return	planworld_xmlrpc_query($node,"planworld.users.getId",array($note));
}



// plan_repair_local_name()
//
// makes plan names case-insensitive, but case-respecting
function plan_repair_local_name($planowner)
{
//	return $planowner;
	if (strpos($planowner,'@local'))
	{
		list($planowner,$crap)=explode('@',$planowner);
	}

	if (strpos($planowner,'local'))
	{
		$planowner=str_replace("local",'',$planowner);
	}

	// attempt to fix the case issue.
	if (!is_dir("$_SERVER[PWUSERS_DIR]/$planowner"))
	{
		if (!$userlist)
			$userlist=files_list("$_SERVER[PWUSERS_DIR]/",'*');

		if (!$lowercase_userlist)
			foreach($userlist as $i=>$user)
				$lowercase_userlist[$i]=strtolower($userlist[$i]);

		if ($key=array_search(strtolower($planowner),$lowercase_userlist))
			{ $planowner=$userlist[$key]; $success=TRUE; }
	
	}
	return $planowner;
}




// plan_repair_blog_name()
//
// substitutes pretty blog name for ugly url
//-----------------------------------------------------------------------------
function plan_repair_blog_name($urls)
{
	$multifeed=TRUE;
	if (is_string($urls))
		if (substr_count($urls,",http") > 0) { $urls=explode(",http",$urls); foreach($urls as $i=>$url) if (!strstr($url,'http')) $urls[$i]="http".$url; }
		else	{ $urls=array($urls); $multifeed=FALSE; }

//	if ($multifeed) return "Metafeed";

	foreach($urls as $i=>$url)
	{
		$cache=db_get_one_row("blogname_cache","url",$url);
		if ($cache['name'] && $cache['mtime']>(time()-(24*3600*30))) $name=$cache['name'];
		else
		{
			if (!strstr($url,'://')) $url=plan_get_real_location($url);
			if(IS_JOSH) include_once('simplepie.1.2.inc');
			else include_once('simplepie.inc');
			$feed = new SimplePie();
			$feed->set_feed_url($url);
			$feed->init();
			$feed->handle_content_type();
			$name=$feed->get_title();
			if (!$cache['name']) db_query("insert into `blogname_cache` (`url`,`name`,`mtime`) values('$url','$name','".time()."')");
			else db_query("update `blogname_cache` set `name`='$name', `mtime`='".time()."' where `url`='$url'");
		}
		$title.=$name;
		if ($urls[$i+1]) $title.=", ";
	}
	
	return $title;
}



// plan_test_privacy
//
// checks for authorization to read
//-----------------------------------------------------------------------------
function plan_test_privacy($reader,$planwriter,$remotesnitch=FALSE)
{
	$valid=FALSE;

	// if the reader is blocked, give up now
	if (!user_is_blocked($planwriter,$reader))
	{
		$whitelist=file_get_contents("$_SERVER[FILE_ROOT]/resources/whitelist.txt");
		if (!strstr($reader,'@planworld.net') || user_is_authorized($planwriter,$reader) || strstr($whitelist,$reader))
		{
			$_SERVER['whitelist_passed']=TRUE;
		}
		else $_SERVER['whitelist_passed']=FALSE;

		// if the writer is registered only, there are a few considerations:
		// 1. the reader is registered here
		// 2. OR the reader is registered elsewhere in planworld
		// 3. if the reader is offsite, they must have snitch on
		// 4. if the reader is from planworld.net, they must be on the whitelist
		// 5. if the reader is registered here, they must have confirmed their email address
		// 6. EXCEPT the writer can personally allow any reader, regardless of snitch status
		if (plan_is_registered_only($planwriter)
			&& $reader!='guest' && $reader!='rss reader' && trim($reader)
			&& !plan_is_private($planwriter)
			&& !file_exists("$_SERVER[PWUSERS_DIR]/$reader/unconfirmed")
			&& ($_SERVER['USERINFO_ARRAY']['snitchlevel']>=1 || user_is_authorized($planwriter,$reader) || $reader=='cacheuser')
			&& $_SERVER['whitelist_passed']
			&& !(strstr(strtolower($reader),'anonymous') && $_SERVER['PLANOWNER_INFO_ARRAY']['snitchlevel']>2)
			)
		{
			$valid=1;
		}
	
		// if the plan is public or advertised, we're clear
		if(!plan_is_registered_only($planwriter) && !plan_is_private($planwriter))
		{
			$valid=1;
		}
		
		// if plan is private, only personally allowed users may read
		if (plan_is_private($planwriter) && user_is_authorized($planwriter,$reader))
		{
			$valid=1;
		}
	}

	// provides limited secret feeds for private plans. user must enable.
	if($_SERVER['OUTPUT_MODE']=="ATOM_PRIVATE") $valid=1;

	// if the writer isn't local, we let the other end handle privacy
	if(!file_exists("$_SERVER[PWUSERS_DIR]/$planwriter")) $valid=TRUE;
	
	return $valid;
}




// plan_has_archives()
//
// checks if a user has archives
// by seing if their plan is local (which means
// they must have archives) or if they've provided
// an archiveurl in their userinfo
//-----------------------------------------------------------------------------
function plan_has_archives($planowner)
{
	$valid=0;

	if (plan_is_local($planowner) || $_SERVER['PLANINFO']['archiveurl']) $valid=1;

return $valid;
}



// plan_is_journaling()
//
// checks to see if user's plan is journaling or nonjournaling
//-----------------------------------------------------------------------------
function plan_is_journaling($user=FALSE)
{
	if (!$user) $user=$_SERVER['USER'];


	if (plan_is_local($user))
	{
		if (file_exists("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"))
			$testarray=unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$user/userinfo.dat"));
			return $testarray['journaling'];
	}
	else return FALSE;	
}




// plan_is_private()
//
// checks to see if user's plan is private by testing the privacy setting
// in userinfo.dat
// 1: advertised
// 2: public
// 3: registered only (the default when creating a user)
// 4: private						 
//
// returns a bool: 1 if pl is 4, 0 if less
//-----------------------------------------------------------------------------
function plan_is_private($username)
{
	
	if (plan_is_local($username))
	{
		$username=plan_repair_local_name($username);
		if (file_exists("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat"))
			$writer_info=unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat"));
	}

	if ($writer_info['privacy']==4) { return TRUE; }
	else return FALSE;
}




// plan_is_registered_only()
//
// bool test to see if a plan has 'registered only' status
//
// same as above
//-----------------------------------------------------------------------------
function plan_is_registered_only($username)
{
	
	if (file_exists("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat"))
	{
		$username=plan_repair_local_name($username);
		if (file_exists("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat"))
			$writer_info=unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat"));
	}
//	else	echo " -- plan isn't local -- ";

	if ($writer_info['privacy']==3 || strstr($username,'@')) { return TRUE; echo "<!-- reg only -->"; }
	else return FALSE;
}


// plan_get_owner_info()
//
// makes the info about $planowner available in a global array
//-----------------------------------------------------------------------------
function plan_get_owner_info($planowner)
{
	$planowner=trim($planowner);
	// if we're reading a feed, get the whole url
	if ($planowner=='http:' || $planowner=='feed:')
	{
//		$planowner_a=$_SERVER['URL_ARRAY'];
//		unset($planowner_a[0],$planowner_a[1]);
		$planowner=str_replace('/read/','',$_SERVER['REQUEST_URI']);
//		$planowner=implode('/',$planowner_a);
//		if ($planowner[strlen($planowner)-1]=='/')
//			$planowner=substr($planowner,0,strlen($planowner)-1);
	}

	// if the user isn't local, don't try to read their info in.
	if (user_is_local($planowner))
	{
		$planowner=plan_repair_local_name($planowner);
		if (file_exists("$_SERVER[PWUSERS_DIR]/$planowner/userinfo.dat")
			&& file_exists("$_SERVER[PWUSERS_DIR]/$planowner/preferences.dat"))
		{
			$_SERVER['PLANOWNER_INFO_ARRAY']=array_merge(
				unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$planowner/userinfo.dat")),
				unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$planowner/preferences.dat"))
				);
		}

		$_SERVER['PLANOWNER_ROOT']="$_SERVER[PWUSERS_DIR]/$planowner";
		$_SERVER['PLANOWNER_INFO_ARRAY']['salt']=plan_get_salt($planowner);
	}

	// let all the other functions know who the planowner is.
	$_SERVER['PLANOWNER']=$planowner;

	$_SERVER['PLANOWNER_REAL_LOCATION']=plan_get_real_location($planowner);
//	if ($_SERVER['USER']=='jwdavidson') echo $_SERVER['PLANOWNER'];
	
	// always present the case the planowner specified for their name
	$_SERVER['PLANOWNER_DISPLAY_NAME']=plan_repair_local_name($planowner);

	// if it's a blog, use the title as the display name instead of the url
	if (strpos($_SERVER['PLANOWNER_REAL_LOCATION'],'://'))
	{
//		if (substr_count($_SERVER['PLANOWNER_REAL_LOCATION'],"://"))
//		{
//			$_SERVER['PLANOWNER_DISPLAY_NAME']="Metafeed";
//		}
//		else
			$_SERVER['PLANOWNER_DISPLAY_NAME']=plan_repair_blog_name($planowner);
	}
}





// plan_is_local()
//
// checks if a user has a local plan
//------------------------------------------------------------------------------
function plan_is_local($planowner)
{
	$valid=0;
	$planowner=trim($planowner);
	parse_str(user_read_info(plan_repair_local_name($planowner)));

	if (strpos($plantype,'local')!==FALSE) $valid=1;
	if (strpos($plantype,'planwatch')!==FALSE) $valid=1;
	if ($plantype=='' && file_exists("$_SERVER[PWUSERS_DIR]/$planowner/userinfo.dat")) $valid=1;
	
return $valid;
}




// plan_get_last_view()
//
// takes an array of plan names, or a single plan 
// name and returns an array of strings (or a string)
// of the timecode of the user's last view of that
// plan
//------------------------------------------------------------------------------
function plan_get_last_view($list)
{
	profile("lastview.".count($list),"begin");

	// if we got a string, pretend it's an array for now
	if (is_string($list))
	{
		$list=array($list);
		$isstring=1;
	}

	// if the last read list isn't in memory, put it there
	if (!is_array($_SERVER['USER_LASTVIEW_ARRAY']) && $_SERVER['USER']!='guest' && file_exists("$_SERVER[USER_ROOT]/lastread.dat"))
		$_SERVER['USER_LASTVIEW_ARRAY'] = unserialize(file_get_contents("$_SERVER[USER_ROOT]/lastread.dat"));

	// if what we got still isn't an array, close up shop
	if (is_array($list))
	foreach($list as $i=>$item)
	{
		// if the item isn't a list header, get the last viewed time
		if ($item[0]!='#')
		{
			// handle aliases
			if (strpos($item,'!')!==false && strpos($item,':')!==false)
			{
				$item=str_replace("!",'',$item);
				$finalpos=strrpos($item,':');
				$url=trim(substr($item,0,$finalpos));
				$displayname=trim(substr($item,$finalpos+1,strlen($item)-$finalpos-1));
				$item=$url;
			}
			
			// get the real plan location
 			$item=plan_get_real_location($item);
	
			$last[$i]=$_SERVER['USER_LASTVIEW_ARRAY'][$item];				
			if (!$last[$i]) $last[$i]=$_SERVER['USER_LASTVIEW_ARRAY'][substr($item,0,strlen($item)-1)];
		}
		else $last[$i]=0;
	}

	// if we got a string, put the result in a string
	if($isstring) $last=$last[0];

	profile("lastview.".count($list),"end");
	
return $last;
}




// plan_get_last_login()
//
// returns the last login time of a user
// only for one user at a time, called
// when viewing a plan.
//------------------------------------------------------------------------------
function plan_get_last_login($planowner)
{
	$planowner_pl=$_SERVER['PLAN_LOCATION_ARRAY'][$planowner];
	if (!$planowner_pl && !$_SERVER['PLAN_LOCATION_FAILED_ARRAY'][$planowner]) $planowner=plan_get_real_location($planowner);
	else $planowner=$planowner_pl;

	if (plan_is_local($planowner)) $lastlogin=user_get_last_action($planowner);
	else
	{
		list($user,$node)=explode('@',$planowner);
		if (!$node) $node='planwatch.org';
		
		$_SERVER['STOPWATCH']["lastlogin.rpc_begin"]=array_sum(explode(' ',microtime()));
		$f=new xmlrpcmsg('planworld.user.getLastLogin');
		$f->addParam(new xmlrpcval("$user", "string"));

		$nodeinfo=planworld_node_getinfo($node);
		
		$c=new xmlrpc_client($nodeinfo['directory'],$nodeinfo['server'],$nodeinfo['port']);
		
		$c->setDebug(0);
		//if (user_is_administrator()) $c->setDebug(1);
		$r=$c->send($f);
		if (!$r) { $lastlogin="error"; }
		elseif (!$r->faultCode())
		$lastlogin=xmlrpc_decode($r->value());

		$_SERVER['STOPWATCH']["lastlogin.rpc_end"]=array_sum(explode(' ',microtime()));
	}
	return $lastlogin;
}



// plan_get_last_update()
//
// returns the update timecode for a plan or list of plans		   
//
// returns just the timecode as a string for a single plan passed as 
// a string, and an array of timecodes in the same order as the input
// array for a list of plans.										
//
//------------------------------------------------------------------------------
function plan_get_last_update($list)
{
	profile("plan_get_last_update_$list[0]",'begin');

	if (is_string($list))
	{
//		$_SERVER['DEBUG_INFO'].="solo timing: $list<br/>\n ";
		$list=array($list);
		$isstring=1;
	}

	if (!isset($_SERVER['PLAN_TIMES_ARRAY']))
		$_SERVER['PLAN_TIMES_ARRAY']=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/allplantimes.dat"));

	if (is_array($list))
	foreach($list as $i=>$plan)
	{
		$rand=rand();
		$plan=trim($plan);
//		$_SERVER['DEBUG_INFO'].="trimmed: $plan<br/>\n ";
		profile("pglu_loop_$rand$plan",'begin');

		//alias handling
		if ($plan[0]=='!')
		{		
			if (strpos($plan,'!')!==false && strpos($plan,':')!==false)
			{
				$plan=str_replace("!",'',$plan);
				$finalpos=strrpos($plan,':');
				$url=trim(substr($plan,0,$finalpos));
				$displayname=trim(substr($plan,$finalpos+1,strlen($plan)-$finalpos-1));
				$plan=$url;
			}

			if (strstr($plan,'diaryland'))
			{
				$plan=str_replace(array('http://','.diaryland.com/'),'',$plan)."@DL";
			}
		}


		// if we know the real location, get it now
		if (array_key_exists($plan,$_SERVER['PLAN_LOCATION_ARRAY']))
		{
			if (trim($_SERVER['PLAN_LOCATION_ARRAY'][$plan])) $plan=$_SERVER['PLAN_LOCATION_ARRAY'][$plan];
//			$_SERVER['DEBUG_INFO'].="found: $plan<br/>\n ";
		}
		else
		{
			// if we know there isn't a real location, give up
			if (isset($_SERVER['PLAN_LOCATION_FAILED_ARRAY'][$plan])
					|| ($plan[0]=='#') || (strstr($plan,'/')) || !$plan || $plan=='onlynew' || $plan=='alwaysnew'
				)
			{
				$plan=FALSE;
				$pt[$i]=-1;
			}
			// if we don't know anything, investigate
			else
			{
				$plan=plan_get_real_location($plan,$_SERVER['USER']=='jwdavidson');
			}
		}

		if($_SERVER['PLAN_TIMES_ARRAY'][$plan])
		{
			$pt[$i]=$_SERVER['PLAN_TIMES_ARRAY'][$plan];
		}
		elseif(!isset($pt[$i]))
		{
//			$pt[$i]=-1;
/*
			if($_SERVER['USER']=='jwdavidson')
			{
				if(strstr($plan,'diaryland') || strstr($plan,'ttp:')) $pt[$i]=plan_find_time($plan);
				if(strstr($plan,'@amherst')) $amfindlist[$i]=$plan;
				if(strstr($plan,'@planworld.net')) $pwnfindlist[$i]=$plan;
			}
			else
*/
				$pt[$i]=plan_find_time($plan);
		}
		profile("pglu_loop_$plan",'end');
	}
	profile("plan_get_last_update_$list[0]",'end');

	if($_SERVER['USER']=='jwdavidson')
	{
		if($amfindlist)
			$amtimes=plan_find_xn_times($amfindlist);
		if($pwnfindlist)
			$pwntimes=plan_find_xn_times($pwnfindlist);

		if($amtimes || $pwntimes)
			$pt=array_merge($pt,$amtimes,$pwntimes);
	}

	if ($isstring) return $pt[0];
	else return $pt;
}

function plan_find_xn_times($plans)
{
	foreach($plans as $plan)
	{
		list($planuser,$node)=explode('@',$plan);
		$xmlarray[]=new xmlrpcval($planuser,'string');
	}	

	$f=new xmlrpcmsg('users.getLastUpdate');
	$f->addParam(new xmlrpcval($xmlarray,'array'));

	$nodeinfo=planworld_node_getinfo($node);
	$c=new xmlrpc_client($nodeinfo['directory'],$nodeinfo['server'],$nodeinfo['port']);
	
	$c->setDebug(0);
	$r=$c->send($f);
	if (!$r) { echo "XML-RPC failed trying to connect to $node\n"; }
	else
		if (!$r->faultCode())
		{
			$nodeplantimes=xmlrpc_decode($r->value());
		}

	return $nodeplantimes;
}

function plan_find_time($plans)
{
//	if(is_string($plans)) { $plans[0]=$plans; $isstring=TRUE; } else $isstring=FALSE;

//	foreach($plans as $plan)
//	{
		if (strstr($plan,'diaryland.'))
		{
			$dluser=str_replace("@diaryland.com","",$plan);
			$url  = "http://members.diaryland.com/edit/profile.phtml?user=$dluser";
			$result = file_get_contents($url);
			preg_match("|last updated:.*(\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d)|",$result,$match);
			$time=strtotime($match[1]);
		}
	
		if (strstr($plan,'http') && !strstr($plan,'diaryland'))
		{
			if(IS_JOSH) include_once('simplepie.1.2.inc');
			else include_once('simplepie.inc');
			$feed_o = new SimplePie($plan);
			$items=$feed_o->get_items();
			$items=array_slice($items,0,1);
			if ($items[0])
			{
				$time=$items[0]->get_date('U');
			}
		}
//	}


	if (strstr($plan,'@') && !strstr($plan,'http'))
	{
		list($planuser,$node)=explode('@',$plan);
		$xmlarray[]=new xmlrpcval($planuser,'string');

		$f=new xmlrpcmsg('users.getLastUpdate');
		$f->addParam(new xmlrpcval($xmlarray,'array'));

		$nodeinfo=planworld_node_getinfo($node);
		$c=new xmlrpc_client($nodeinfo['directory'],$nodeinfo['server'],$nodeinfo['port']);
		
		$c->setDebug(0);
		$r=$c->send($f);
		if (!$r) { echo "XML-RPC failed trying to connect to $node\n"; }
		else
			if (!$r->faultCode())
			{
				$nodeplantimes=xmlrpc_decode($r->value());
			}

		if ($nodeplantimes)
		$time=end($nodeplantimes);
	}

	if ($time) plan_set_last_update($plan,$time);
	return $time;
}


// records update time for plan that isn't yet cached.
function plan_set_last_update($plan,$time)
{
	if (strstr($plan,'diaryland.'))
	{
		if (file_exists("$_SERVER[FILE_ROOT]/stats/times_diaryland.dat")) $diaryland_times=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/times_diaryland.dat"));
		$diaryland_times[$plan]=$time;
		file_put_contents("$_SERVER[FILE_ROOT]/stats/times_diaryland.dat",serialize($diaryland_times));
	}

	if (strstr($plan,'http') && !strstr($plan,'diaryland'))
	{
		if (file_exists("$_SERVER[FILE_ROOT]/stats/times_feeds.dat")) $feed_times=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/times_feeds.dat"));
		$feed_times[$plan]=$time;
		file_put_contents("$_SERVER[FILE_ROOT]/stats/times_feeds.dat",serialize($feed_times));
	}

	if (strstr($plan,'@') && !strstr($plan,'http'))
	{
		if (file_exists("$_SERVER[FILE_ROOT]/stats/times_crossnode.dat")) $crossnode_times=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/times_crossnode.dat"));
		$crossnode_times[$plan]=$time;
		file_put_contents("$_SERVER[FILE_ROOT]/stats/times_crossnode.dat",serialize($crossnode_times));
	}

	return TRUE;
}


// plan_get_last_update_crossnode()
//
// gets the update time for an xml_rpc plan
//------------------------------------------------------------------------------
function plan_get_last_update_crossnode($nodeplanusers,$node)
{
		  
	foreach($nodeplanusers as $i=>$plan)
	{
		$nodeplanusers[$i]=str_replace('@neon.note.amherst.edu','',trim($nodeplanusers[$i]));
		$nodeplanusers[$i]=str_replace('@note.amherst.edu','',trim($nodeplanusers[$i]));
		$xmlarray[$i]=new xmlrpcval($nodeplanusers[$i],'string');
	}
	$sendarray=new xmlrpcval($xmlarray,"array");
	
	unset($nodeplanusers);
	unset($xmlarray);
	
	$f=new xmlrpcmsg('users.getLastUpdate');
	$f->addParam($sendarray);
	
	$nodeinfo=planworld_node_getinfo($node);
	
	$c=new xmlrpc_client($nodeinfo['directory'],$nodeinfo['server'],$nodeinfo['port']);
	
	$c->setDebug(0);
	$r=$c->send($f,0.1);
	if (!$r)
	 {
	 	foreach($nodeplanusers as $i=>$plan) $outlist_times["$plan$node"]=-1;
	 }
	else
		if (!$r->faultCode())
		{
			$nodeplantimes=xmlrpc_decode($r->value());
		}
	
	if (!$nodeplantimes) $nodeplantimes=array();
	foreach($nodeplantimes as $i=>$time)	
	{
		$outlist_times["$i$node"]=$time;
	}
	return $outlist_times;
}


function plan_get_salt($username)
{
	$files=files_list($_SERVER['PLANOWNER_ROOT'],"salt.*");
	$salt=str_replace('salt.','',$files[0]);
	if (!$salt) $salt=plan_generate_salt($username);
	if (file_exists("$_SERVER[PLANOWNER_ROOT]/salt.")) unlink("$_SERVER[PLANOWNER_ROOT]/salt.");
//	if ($_SERVER['USER']=='dskatz04') echo $salt;
	return $salt;
}

function plan_generate_salt($username)
{
	$salt_number=rand(10000,99999);
	$salt_string=base64_encode($salt_number);
	file_put_contents("$_SERVER[PLANOWNER_ROOT]/salt.$salt_string",$salt_string);
//	if ($_SERVER['USER']=='dskatz04') echo $salt_string;
	return $salt_string;
}


?>