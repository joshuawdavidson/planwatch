<?php

/*
PT.php

runs regularly (1 minute intervals) from a cron job
to get plan times for all relevant plans
*/
include_once('/var/www/planwatch.org/siteconfig.php');
include_once('essential.php');

// make sure it's either josh or a CLI user
if(isset($_ENV['HTTP_HOST']) && isset($_ENV['USER']) && $_ENV['USER']!=='jwdavidson') {die("unauthorized");}

header("Content-type: text/plain");

// SET ALL THE VARIABLES WE ASSUME HERE
date_default_timezone_set("America/New_York");
$_ENV['PWUSERS_DIR']="/var/www/planwatch.org/pwusers";
$_ENV['FILE_ROOT']="/var/www/planwatch.org/public_html";
if(isset($_GET['debug'])) {$debug=$_GET['debug'];} else {$debug=FALSE;}

//include some handy functions
include_once("$_ENV[FILE_ROOT]/scripts/standard_library/file_functions.php");
include_once("$_ENV[FILE_ROOT]/scripts/standard_library/plan_info_functions.php");
include_once("$_ENV[FILE_ROOT]/scripts/standard_library/user_info_functions.php");


// REASSURE ME THAT IT'S STILL HAPPENING
touch("$_ENV[FILE_ROOT]/stats/cron_fired.dat");


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

// GET ALL THE WATCHED LISTS AND PRUNE THEM A LITTLE
	exec("cat -s $_ENV[PWUSERS_DIR]/*/watchedlist.txt",$watched_array);
	exec("ls -d $_ENV[PWUSERS_DIR]/*/",$localusers_array);
	foreach($localusers_array as $i=>$username)
	{
		$localusers_array[$i]=trim(basename($username));
	}
	$watched_array=array_merge($watched_array,$localusers_array);
	foreach($watched_array as $i=>$username)
	{
		$watched_array[$i]=trim($username);
	}

	echo "\n\n-----\n###### Counts ######\n";
	echo "Total watched entries (unpruned): ".count($watched_array)."\n";

	$watched_array=array_unique($watched_array);

	echo "Total watched entries (lightly pruned): ".count($watched_array)."\n\n\n";


// WALK THROUGH THE AGGREGATE LIST
foreach($watched_array as $watched)
{
	$watched=trim($watched);

	//pull out the directives and un-alias the aliases
	if(!empty($watched))
	{
		// if it's a sort directive or a group header, ignore it.
		if (strpos($watched,'sort by')!==false) {$watched=FALSE;}
		if (strpos($watched,'onlynew')!==false) {$watched=FALSE;}
		if (strpos($watched,'alwaysnew')!==false) {$watched=FALSE;}
		if (strpos($watched,'#')!==false) {$watched=FALSE;}
		if (strpos($watched,'!prune')!==false) {$watched=FALSE;}

		// if it's an alias, only run the important part
		if (strpos($watched,'!')!==false)
		{
			$watched=str_replace("!",'',$watched);
			$finalpos=strrpos($watched,':');
			$watched=substr($watched,0,$finalpos);
		}
	}

	// echo the listed plan location and the real plan location for later debugging.
	if ($watched)
	{
		$watched_nominal=trim($watched);
		$watched=_reduced_plan_get_real_location($watched);
		if($debug)
		{
			if($watched!=$watched_nominal)
				{ echo "*** $watched (was $watched_nominal)\n"; }
			else echo "$watched was exact\n";
		}
	}

	// if it's a url, see if it's a diaryland plan or a regular rss plan -- they have different procedures
	// because diaryland rss feeds don't have time info for some reason.
	if (strpos($watched,'ttp:'))
	{
		if (strpos($watched,'diaryland'))
		{
			$diaryland_array[] = $watched;
		}
		elseif(strpos($watched,"show_bug"))
		{
			$bugs_array[] = $watched;
		}
		else
		{
			$feeds_array[] = $watched;
		}

		$watched=FALSE;
	}

	// if it's a crossnode plan, put it in the appropriate list.
	if (strpos($watched,'@'))
	{
		list($username,$host) = explode('@',$watched);
		//echo "$watched: $username at $host\n";
		$crossnode_array[$host][] = $username;
		if ($debug) echo "$username sorted into the $host hat\n";
		$watched=FALSE;
	}

	// it's probably a local plan if we got here
	if ($watched) { $local_array[]=$watched; }
}
unset($watched_array);

// reduce the lists by eliminating duplicates
if (isset($diaryland_array) && is_array($diaryland_array))
{
	$diaryland_array = array_unique($diaryland_array);

	$file=fopen("$_ENV[FILE_ROOT]/stats/plans_diaryland.dat",'w');
	fwrite($file,serialize($diaryland_array));
	fclose($file);
}

if (isset($feeds_array) && is_array($feeds_array))	{$feeds_array    = array_unique($feeds_array);}
if (isset($local_array) && is_array($local_array))	{$local_array    = array_unique($local_array);}

$file=fopen("$_ENV[FILE_ROOT]/stats/plans_feeds.dat",'w');
fwrite($file,serialize($feeds_array));
fclose($file);

if(isset($bugs_array) && is_array($bugs_array))
{
	$file=fopen("$_ENV[FILE_ROOT]/stats/plans_bugs.dat",'w');
	fwrite($file,serialize($bugs_array));
	fclose($file);
}

$file=fopen("$_ENV[FILE_ROOT]/stats/plans_local.dat",'w');
fwrite($file,serialize($local_array));
fclose($file);

if($debug)
{
	echo "\n-----------------\nProbably PWO plans\n";
	foreach ($local_array as $local)
	{
		echo "$local\n";
	}

	echo "-----------------\n";
}

if($debug) echo "\n-----------------\n Crossnode Plans\n";

foreach($crossnode_array as $host=>$node_users_array)
{
	$crossnode_array[$host] = array_unique($node_users_array);
	if($debug)
	{
		echo "\n\n$host watched plans: ".count($crossnode_array[$host])."\n";
		foreach($crossnode_array[$host] as $user)
		{
			if($debug) echo "$user\n";
		}
	}
}
echo "-----------------\n";

$file=fopen("$_ENV[FILE_ROOT]/stats/plans_crossnode.dat",'w');
fwrite($file,serialize($crossnode_array));
fclose($file);


// output a status update
if(isset($diaryland_array)) echo "Diaryland watched plans: ".count($diaryland_array)."\n";
echo "RSS watched plans: ".count($feeds_array)."\n";
echo "Other/invalid/local watched plans: ".count($local_array)."\n";
foreach($crossnode_array as $host=>$nua)
{
	echo "$host plans: ".count($nua)."\n";
}

$endtime=array_sum(explode(' ',microtime()));
echo round($endtime-$begintime,4);

$minute=date('i');
if($_GET['xn']==1 || (is_countable($crossnode_array) && count($crossnode_array))) { include_once('times_crossnode.php'); }
if($_GET['rss']==1 || (is_countable($feeds_array) && count($feeds_array) && $minute/10==0)) {include_once('times_feeds.php');}
if($_GET['dl']==1 || (is_countable($diaryland_array) && count($diaryland_array) && $minute/15==0)) {include_once('times_crossnode.php');}
if($_GET['bug']==1 || $minute/15==0) {include_once('times_bugs.php');}


$update_array=times_get_updates($debug);

$file=fopen("$_ENV[FILE_ROOT]/stats/allplantimes.dat",'w');
fwrite($file,serialize($update_array));
fclose($file);


if ($debug)
{
	echo "\n\n-----\n#### STORED UPDATE TIMES ####\n";
	foreach($update_array as $key=>$item) { if ($key) echo "$key $item\n"; }
}



// FUNCTIONS ===================================================================

function times_get_updates($debug)
{

	if (file_exists("$_ENV[FILE_ROOT]/stats/times_diaryland.dat")) $diaryland_times=unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/times_diaryland.dat"));
		else $diaryland_times=array();
	if (file_exists("$_ENV[FILE_ROOT]/stats/times_feeds.dat")) $feed_times=unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/times_feeds.dat"));
		else $feed_times=array();
	if (file_exists("$_ENV[FILE_ROOT]/stats/times_crossnode.dat")) $crossnode_times=unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/times_crossnode.dat"));
		else $crossnode_times=array();
	if (file_exists("$_ENV[FILE_ROOT]/stats/times_bugs.dat")) $bug_times=unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/times_crossnode.dat"));
		else $crossnode_times=array();
	$local_times=times_get_updates_local($debug);
	if(!is_array($local_times)) $local_times=array();

	$all_times=array_merge($local_times,$crossnode_times,$feed_times,$diaryland_times,$bug_times);

	echo "\nlocal times: ".count($local_times)."\n";
	echo "feeds times: ".count($feed_times)."\n";
	echo "crossnode times: ".count($crossnode_times)."\n";
	echo "diaryland times: ".count($diaryland_times)."\n";
	echo "bug times: ".count($bug_times)."\n";
	echo "ALL TIMES: ".count($all_times)."\n\n";

	// bring future update times into the present
	$now=time();
	$interval=3600;
	$basetime=$now-$interval;
	foreach($all_times as $key=>$time)
	{
		if($all_times[$key] > $now) { $all_times[$key]=$basetime + (($time-$now) % $interval); echo "$now time correction $key (".date("g:ia",$time).")=>(".date("g:ia",$all_times[$key]).")<br />";}
	}

return $all_times;
}

function files_extract_timecode($array)
{
	if (is_string($array)) $array=array($array);

	foreach($array as $index=>$item)
	{
		if(strstr($item,"."))
		{
			list($junk,$timecode,$junk)=explode(".",$item);
			if(is_numeric($timecode))
				$result_tc[]=$timecode;
			else $result_tc[]=filectime($item);
		}
		else $result_tc[]=-1;
	}

	rsort($result_tc);

	if (count($array)==1) return $array[0];
	else return $result_tc;
}

function times_get_updates_problem_plans($debug)
{
	//TODO: try to resolve the fate of problem plans

	$problem_plans_list = unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/problem_plans.dat"));
	//check if there's a matching plan at NOTE
	//check if there's a matching plan at pwn
	//if not, alert the watching user(s) that they have a problem
}

function times_get_updates_local($debug)
{
	echo "\n##### Local Plans #####\n";

	$local_plans_list=files_list($_ENV['PWUSERS_DIR']);

	$local_pt_list=unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/plans_local.dat"));
	foreach($local_pt_list as $i=>$local_pt)
	{
		$local_pt_compare_list[$i]=trim(strtolower($local_pt));
	}
	foreach($local_plans_list as $j=>$local_plan)
	{
		$local_plans_compare_list[$j]=trim(strtolower($local_plan));
	}
	$problem_plans_list=array_diff(array_unique($local_pt_compare_list),array_unique($local_plans_compare_list));
	$local_plans_list=array_unique(array_merge($local_pt_list,$local_plans_list));

	file_put_contents("$_ENV[FILE_ROOT]/stats/problem_plans.dat",serialize($problem_plans_list));

	foreach($local_plans_list as $local_user)
	{
		// $local_user=basename($local_user);
		if($local_user[0]=='.' || !is_dir("$_ENV[PWUSERS_DIR]/$local_user")) { $local_user=FALSE; }
		else
		{
			if (!empty(trim($local_user)) && file_exists("$_ENV[PWUSERS_DIR]/$local_user/userinfo.dat"))
			{
				$local_user_info=unserialize(file_get_contents("$_ENV[PWUSERS_DIR]/$local_user/userinfo.dat"));

				// if the plan is local, get an update time
				if (isset($local_user_info['plantype']) && $local_user_info['plantype']=='@local' || $local_user_info['plantype']=='local')
				{
					$local='LOCAL';
					$planurl='';

					$lastupdate=0;
					if (file_exists($lu="$_ENV[PWUSERS_DIR]/$local_user/stats/lastupdate")) $lastupdate=filemtime($lu);
					elseif (file_exists("$_ENV[PWUSERS_DIR]/$local_user/plan/plan.txt"))	$lastupdate=filemtime("$_ENV[PWUSERS_DIR]/$local_user/plan/plan.txt");
					else
					{
	//					$filelist=files_list("$_ENV[PWUSERS_DIR]/$local_user/plan","plan*","-c");
						exec("ls -tc $_ENV[PWUSERS_DIR]/$local_user/plan/plan*",$lsoutput);
						$lsoutput=files_extract_timecode($lsoutput);
						$lastupdate=$lsoutput[0];
					}
					if(!is_numeric($lastupdate))
					{
						$lastupdate=-1;
						echo "PROBLEM: $local_user last update $lsoutput[0]\n";
					}

					$returnarray[$local_user]=$lastupdate;
				}

				// otherwise, just figure out where it's from for output and we'll sort it later.
				else
				{
					// TODO(v4.5): get a real update time for plans that end up here if possible.
					$planurl=$local_user_info['planusername'].$local_user_info['plantype'];
					echo "PROBLEM: $planurl ended up in pt.php:times_get_updates_local()\n";
					$local='';
				}
			}

			if($debug) {echo "$local_user $planurl $local $lastupdate (".date("F jS Y h:ia",$lastupdate).")\n";}
		}


	}
	// $local_pt_list = array_keys($returnarray);
	// file_put_contents("$_ENV[FILE_ROOT]/stats/plans_local.dat",serialize($local_pt_list));

	$file=fopen("$_ENV[FILE_ROOT]/stats/times_local.dat",'w');
	fwrite($file,serialize($returnarray));
	fclose($file);

return $returnarray;
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

		if(strpos($planowner,"bug_")!==FALSE && strpos($planowner,"query")===FALSE)
		{
			$id=str_replace("bug_","",$planowner);
			$planowner="http://neon.note.amherst.edu/bugzilla/show_bug.cgi?ctype=xml&id=$id";
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