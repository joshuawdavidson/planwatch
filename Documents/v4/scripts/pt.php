<?php

/*
PT.php

runs regularly (2 minute intervals) from a cron job
to get plan times for all relevant plans
*/

header("Content-type: text/plain");

$_SERVER['PWUSERS_DIR']="/home/planwatc/pwusers";
$_SERVER['FILE_ROOT']="/home/planwatc/public_html";
// limited security -- other users on our host could get a reg. users list, but
// that's a minor risk. it would be nice to make this a bit better, though.
// TODO:(v4.1) adjust the server's cron job to use system's fingerprint
if ($_SERVER['REMOTE_ADDR']!=$_SERVER['SERVER_ADDR'] && !user_is_administrator()) redirect();

$debug=$_GET['debug'];
echo "DEBUG: $debug\n\n\n";

/*
// MAIL_SUBMIT =================================================================
turned off until it gets fixed
----
TODO:(v4.5) fix mail submission. maybe get a gmail POP account for this?
TODO:(v5) add SMS to mail gateway if there's any user interest http://
$lastmail_fn="$_SERVER[FILE_ROOT]/stats/lastmail";
if (!file_exists($lastmail_fn) || (@fileatime($lastmail_fn) < time() - 600))
{
echo "<b>checking for email plan posts...</b>\n";
//include_once('mail_submit.php');
touch($lastmail_fn);
}
*/

// MAIN ========================================================================
/* NEW PT PROCESS

1. get everyone's watched list
2. sort/clean the big list
3. iterate through calling the appropriate time update function

this would do away with the messy users_ and times_ files in stats and make
update times appear more quickly. we'd want to consider tracking the last time
we checked something's update time so we weren't constantly hitting NOTE or pwn
or someone's feed.
*/

$begintime=array_sum(explode(' ',microtime()));
//if (!$debug)
//{
	exec("cat -s $_SERVER[PWUSERS_DIR]/*/watchedlist.txt",$watched_array);
	
	echo "\n\n-----\n###### Counts ######\n";
	echo "Total watched entries (unpruned): ".count($watched_array)."\n";
	
	$watched_array=array_unique($watched_array);
	
	echo "Total watched entries (lightly pruned): ".count($watched_array)."\n\n\n";
//}

foreach($watched_array as $watched)
{
	$watched=trim($watched);
	// if it's a sort directive or a group header, ignore it.
	if (strpos($watched,'sort by')!==false) unset($watched);
	if (strpos($watched,'onlynew')!==false) unset($watched);
	if (strpos($watched,'alwaysnew')!==false) unset($watched);
	if (strpos($watched,'#')!==false) unset($watched);
	if (strpos($watched,'!prune')!==false) unset($watched);
	
	// if it's an alias, only run the important part
	if (strpos($watched,'!')!==false)
	{
		$watched=str_replace("!",'',$watched);
		$finalpos=strrpos($watched,':');
		$watched=substr($watched,0,$finalpos);
	}


	// echo the listed plan location and the real plan location for later debugging.
	if ($watched)
	{
		echo "$watched: ";
		$watched = _reduced_plan_get_real_location($watched);
		echo "$watched\n";
	}

	// if it's a url, see if it's a diaryland plan or a regular rss plan -- they have different procedures
	// because diaryland rss feeds don't have time info for some reason.
	if (strpos($watched,'ttp:'))
	{
		if (strpos($watched,'diaryland'))
		{
			$diaryland_array[] = $watched;
		}
		else
		{
			$feeds_array[] = $watched;
//			echo $watched."\n";
		}

		unset($watched);
	}

	// if it's a crossnode plan, put it in the appropriate list.
	if (strpos($watched,'@'))
	{
		list($username,$host) = explode('@',$watched);
		//echo "$watched: $username at $host\n";
		$crossnode_array[$host][] = $username;
		unset($watched);
	}

	if ($watched) $reject_array[]=$watched;
}
unset($watched_array);

// reduce the lists by eliminating duplicates
if (is_array($diaryland_array)) $diaryland_array = array_unique($diaryland_array);
if (is_array($feeds_array))		$feeds_array       = array_unique($feeds_array);
if (is_array($reject_array))	$reject_array    = array_unique($reject_array);

$file=fopen("$_SERVER[FILE_ROOT]/stats/feedlist.dat",'w');
fwrite($file,serialize($feeds_array));
fclose($file);

echo "\n-----------------\nHomeless Plans\n";
foreach ($reject_array as $reject)
{
	echo "$reject\n";
}

echo "-----------------\n";

echo "\n-----------------\n Crossnode Plans\n";
foreach($crossnode_array as $host=>$node_users_array)
{
	$crossnode_array[$host] = array_unique($node_users_array);
	echo "$host watched plans: ".count($crossnode_array[$host])."\n";
	foreach($crossnode_array[$host] as $user)
	{
		echo "$user\n";
	}
}
echo "-----------------\n";

// output a status update
echo "Diaryland watched plans: ".count($diaryland_array)."\n";
echo "RSS watched plans: ".count($feeds_array)."\n";
echo "Other/invalid/local watched plans: ".count($reject_array)."\n";

$endtime=array_sum(explode(' ',microtime()));
echo round($endtime-$begintime,4);
$minutes=date('i');


echo "\n\n-----\n######GETTING UPDATE TIMES...######\n";
echo "//Minutes: $minutes// \n";

echo "\ndebug: $debug\n\n";

// don't let us go longer than 2 minutes
set_time_limit(60);
// || !file_exists("$_SERVER[FILE_ROOT]/stats/times_crossnode.dat") || $_GET['all']==1
if (($minutes % 4 == 0 || $debug) && is_array($crossnode_array))  include_once('times_crossnode.php');
if ((($minutes % 25 == 0)  || $debug) && is_array($diaryland_array)) include_once('times_diaryland.php');
//if ((($minutes % 4 == 0)  || $debug) && is_array($feeds_array))	include_once('times_rss.php');
$update_array=times_get_updates();


if (!$debug)
{
	$file=fopen("$_SERVER[FILE_ROOT]/stats/allplantimes.dat",'w');
	fwrite($file,serialize($update_array));
	fclose($file);
}//file_put_contents("$_SERVER[FILE_ROOT]/stats/allplantimes.dat",serialize($planref));


echo "\n\n-----\n#### STORED UPDATE TIMES ####\n";
foreach($update_array as $key=>$item) { if ($key) echo "$key $item\n"; }


// SNOOP QUEUE =================================================================
//$snoop_queue_list = files_list("$_SERVER[FILE_ROOT]/stats/","*snoop");
//exec("ls $_SERVER[FILE_ROOT]/stats/*snoop",$snoop_queue_list);

/*
if ($snoop_queue_list)
{
	include_once('snoop.php');
	echo "<hr/><h1>SNOOP QUEUE</h1>\n";
	foreach($snoop_queue_list as $snoop_file)
	{
		if (!strstr($snoop_file,$_SERVER['FILE_ROOT'])) $snoop_file="$_SERVER[FILE_ROOT]/stats/$snoop_file";
		
		$snoop_array=unserialize(file_get_contents($snoop_file));
		if (strpos($snoop_file,'remsnoop'))
		{
			$success = snoop_remove_xmlrpc($snoop_array['snoop_target'],$snoop_array['snoop_host'],$snoop_array['snoop_setter']);
		}
	
		if (strpos($snoop_file,'addsnoop'))
		{
			$success = snoop_add_xmlrpc($snoop_array['snoop_target'],$snoop_array['snoop_host'],$snoop_array['snoop_setter']);
		}
		
		if ($success===TRUE)
		{
			unlink($snoop_file);
		}
		
		echo basename($snoop_file).": $success ... file_exists: ".file_exists($snoop_file)."\n";
	}
}
*/


// WATCHED LIST MAILING ========================================================
//TODO:(v4.1) fix watched list mailing
//$lastmailed_fn="$_SERVER[FILE_ROOT]/stats/mailinglist.lastmailed";
//if ((time()-@file_get_contents($lastmailed_fn) >= 24*3600) || !file_exists($lastmailed_fn))
//{
//	$mailing_list=file("$_SERVER[FILE_ROOT]/resources/mailinglist.dat");
//	foreach($mailing_list as $person)
//	{
//		if (trim($person)) mail_look(trim($person));
//	}
//	file_put_contents("$_SERVER[FILE_ROOT]/stats/mailinglist.lastmailed",time());
//}


// FUNCTIONS ===================================================================

function times_get_updates()
{
	$now=time();
	$interval=3600;
	$basetime=$now-$interval;
	if (file_exists("$_SERVER[FILE_ROOT]/stats/times_diaryland.dat")) $diaryland_times=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/times_diaryland.dat"));
	if (file_exists("$_SERVER[FILE_ROOT]/stats/times_feeds.dat")) $feed_times=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/times_feeds.dat"));
	if (file_exists("$_SERVER[FILE_ROOT]/stats/times_crossnode.dat")) $crossnode_times=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/times_crossnode.dat"));
	$local_times=times_get_updates_local();
	$all_times=array_merge($local_times,$crossnode_times,$feed_times,$diaryland_times);

	echo "\nlocal times: ".count($local_times)."\n";
	echo "feeds times: ".count($feed_times)."\n";
	echo "crossnode times: ".count($crossnode_times)."\n";
	echo "diaryland times: ".count($diaryland_times)."\n";
	echo "ALL TIMES: ".count($all_times)."\n\n";
	

	foreach($all_times as $key=>$time)
	{
		if($all_times[$key] > $now) { $all_times[$key]=$basetime + (($time-$now) % $interval); echo "$now correction $key (".date("g:ia",$time).")=>(".date("g:ia",$all_times[$key]).")<br />";}
	}

return $all_times;
}

function files_extract_timecode($array)
{
	if (is_string($array)) $array=array($array);

	foreach($array as $index=>$item)
	{
		list($junk,$timecode,$junk)=explode(".",$item);
		$result_tc[]=$timecode;
	}

	rsort($result_tc);

	if (count($array)==1) return $array[0];
	else return $result_tc;
}

function times_get_updates_local()
{
	echo "\n##### Local Plans #####\n";
	exec("ls -d $_SERVER[PWUSERS_DIR]/*",$local_plans_list);
	foreach($local_plans_list as $local_user)
	{
		$local_user=basename($local_user);
		if (file_exists("$_SERVER[PWUSERS_DIR]/$local_user/userinfo.dat"))
		{
			$local_user_info=unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$local_user/userinfo.dat"));

			// if the plan is local, get an update time
			if ($local_user_info['plantype']=='@local' || $local_user_info['plantype']=='local')
			{
				$local='LOCAL';
				$planurl='';

				$lastupdate=0;
				if (file_exists($lu="$_SERVER[PWUSERS_DIR]/$local_user/stats/lastupdate")) $lastupdate=filemtime($lu);
				elseif (!file_exists("$_SERVER[PWUSERS_DIR]/$local_user/plan/plan.txt"))
				{
					exec("ls $_SERVER[PWUSERS_DIR]/$local_user/plan/plan*",$lsoutput);
//					@exec("ls $_SERVER[PWUSERS_DIR]/$local_user/plan/plan*.p",$privatelsoutput);
					$lsoutput=files_extract_timecode($lsoutput);
					$lastupdate=$lsoutput[0];
				}
				else $lastupdate=filemtime("$_SERVER[PWUSERS_DIR]/$local_user/plan/plan.txt");
		
				$returnarray[$local_user]=$lastupdate;
			}

			// otherwise, just figure out where it's from for output and we'll sort it later.
			else
			{
				// TODO(v4.5): get a real update time for plans that end up here if possible.
				$planurl=$local_user_info['planusername'].$local_user_info['plantype'];
				$local='';
			}
		}

		echo "$local_user $planurl $local $lastupdate (".date("F jS h:ia",$lastupdate).")\n";
	}

return $returnarray;
}

function _planworld_node_getinfo($node='note')
{
	if (strpos($node,'@')===FALSE) $node='@'.$node;

	if	 ($node=='@note')	return array("port"=>80,"server"=>"neon.note.amherst.edu","directory"=>"/planworld/backend/");
	elseif ($node=='@note.amherst.edu')	return array("port"=>80,"server"=>"neon.note.amherst.edu","directory"=>"/planworld/backend/");
	elseif ($node=='@amherst.edu')	return array("port"=>80,"server"=>"neon.note.amherst.edu","directory"=>"/planworld/backend/");

	elseif ($node=='@krypton')	return array("port"=>80,"server"=>"krypton.note.amherst.edu","directory"=>"/planworld/backend/");


	elseif ($node=='@planworld.net') return array("port"=>80,"server"=>"planworld.net","directory"=>"/backend/");
	elseif ($node=='@planwatch.org') return array("port"=>80,"server"=>"planwatch.org","directory"=>"/backend/");
	elseif ($node=='@beta.planwatch.org') return array("port"=>80,"server"=>"beta.planwatch.org","directory"=>"/backend/");
	elseif ($node=='@beta') return array("port"=>80,"server"=>"beta.planwatch.org","directory"=>"/backend/");
	elseif ($node=='@flickr') return array("port"=>80,"server"=>"www.flickr.com","directory"=>"/services/xmlrpc/");
	else   return FALSE;
}

function _reduced_plan_get_real_location($planowner)
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
		if (strpos($planowner,'@livejournal')) $planowner="http://www.livejournal.com/users/".str_replace('@livejournal','',$planowner)."/data/rss";

		$planowner=str_replace('.msn','@msn',$planowner);
		if (strpos($planowner,'@msn')) $planowner="http://spaces.msn.com/members/".str_replace('@msn','',$planowner)."/feed.rss";

//		$planowner=str_replace('.dl','@diaryland',$planowner);
		$planowner=str_replace('@dl','@diaryland',$planowner);
		$planowner=str_replace('@diaryland.com','@diaryland',$planowner);
		if (strpos($planowner,'@diaryland')) $planowner="http://".str_replace('@diaryland','',$planowner).".diaryland.com/";
		if (strpos($planowner,'@blogspot')) $planowner="http://".str_replace('@blogspot','',$planowner).".blogspot.com/atom.xml";
		if (strpos($planowner,'@blogger')) $planowner="http://".str_replace('@blogger','',$planowner).".blogger.com/atom.xml";

return $planowner;
}

?>