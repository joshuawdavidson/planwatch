<?php



///////////////////////////////////////////////////////////////////////
// GETSNITCH()
//
// reads and formats userdir/stats/planlog.txt for user reading
//----------------------------------------------------------------------
function getsnitch($owner,$threshhold='2dr')
{
	global $rootdir,$pwuserinfo,$listsbgcolor,$preferences,$stopwatch;
	parse_str($pwuserinfo);
	$writer=$user;

   	if ($user==$owner)
	{
		if($threshhold=='2dr') $mode='NORMAL';
//$stopwatch['snitch.lastplantime_begin']=array_sum(explode(' ',microtime()));
		$last_plan_update=getplantime($user);
//$stopwatch['snitch.lastplantime_end']=array_sum(explode(' ',microtime()));

//$stopwatch['snitch.setthresh_begin']=array_sum(explode(' ',microtime()));
		if (strpos($threshhold,'r')!==FALSE) $newest_first='SELECTED';
		else $oldest_first='SELECTED';
		
		if (strpos($threshhold,'w')!==FALSE) $weeks_selected='SELECTED';
		elseif (strpos($threshhold,'h')!==FALSE) $hours_selected='SELECTED';
		elseif (strpos($threshhold,'m')!==FALSE) $minutes_selected='SELECTED';
		else $days_selected='SELECTED';
		
		$threshvalue=str_replace(array('d','r','w','d','h','m'),'',$threshhold);
		if ($threshhold=='sidebar')  { $threshhold='1d'; $reverse='true'; $sidebar=1; }
		if (strstr($threshhold,'r')) { $reverse='true'; $threshhold=str_replace('r','',$threshhold); }
		if (strstr($threshhold,'w')) $threshhold=time()-(24*3600*7*str_replace('w','',$threshhold));
		if (strstr($threshhold,'d')) $threshhold=time()-(24*3600*str_replace('d','',$threshhold));
		if (strstr($threshhold,'h')) $threshhold=time()-(3600*str_replace('h','',$threshhold));
		if (strstr($threshhold,'m')) $threshhold=time()-(60*str_replace('m','',$threshhold));

//$stopwatch['snitch.setthresh_end']=array_sum(explode(' ',microtime()));

$stopwatch['snitch.read_begin']=array_sum(explode(' ',microtime()));
		$snitch_fn="$rootdir/pwusers/$writer/stats/planlog.txt";
//		if (file_exists($snitch_fn)) $snitch_array=file($snitch_fn);
		if ($mode) exec("tail -n 100 $snitch_fn",$snitch_array);
		else $snitch_array=explode("\n",file_read_contents($snitch_fn));
//		else { touch($snitch_fn); $snitch_array=array(); }
$stopwatch['snitch.read_end']=array_sum(explode(' ',microtime()));

$stopwatch['snitch.reverse_begin']=array_sum(explode(' ',microtime()));
		if ($reverse) $snitch_array=array_reverse($snitch_array);
$stopwatch['snitch.reverse_end']=array_sum(explode(' ',microtime()));

		foreach($snitch_array as $k=>$snitch_entry)
		{
			list($blah,$snitch_user,$blah)=explode(",",$snitch_entry);
			$snitch_users[]=$snitch_user;
		}

		$snitch_users=array_unique($snitch_users);
		$snitch_lastview=lastview($snitch_users);
		$snitch_planupdates=getplantime($snitch_users);

		foreach($snitch_array as $k=>$snitch_entry)
		{
			$small_sw_begin=array_sum(explode(' ',microtime()));
			list($snitch_time,$snitch_user,$snitch_extras)=explode(',',$snitch_entry);
			if ($snitch_time > $threshhold)
			{
				if ($snitch_time<$last_plan_update)
				{
					$content.="<br/><i>".str_replace('&nbsp;',' ',formattime($last_plan_update))." &nbsp; Last Plan Update<br/><br/></i>";
					$last_plan_update=0;
				}

				$snitchday=date('d',$snitch_time);
				if ($last_snitchday && $snitchday != $last_snitchday) $content.="<br/>";
				
//				$lastview=lastview($snitch_user);
//				$lastupdate=getplantime($snitch_user);
				
				$time_string=str_replace('&nbsp;',' ',formattime($snitch_time));
				$content.=$time_string." &nbsp; ";
				if (strlen(date('g',$snitch_time))==1) $content.="&nbsp;";

				$su_key=array_search($snitch_user,$snitch_users);
				
				
				if ($snitch_planupdates[$su_key]>$snitch_lastview[$su_key]) $content.="<b>";
				if(strpos($snitch_user,'Anon')!==FALSE)
                {
                 $snitch_ip=str_replace("Anonymous Coward from ",'',$snitch_user);
                 $snitch_user="Anonymous Coward <span style='font-size: smaller;'>from $snitch_ip [ <a href='/trace/$snitch_ip'>trace</a> ]</span>\n";
                }
				if(strpos($snitch_user,'RSS')!==FALSE)
                {
                 $snitch_ip=str_replace("RSS Reader from ",'',$snitch_user);
                 $snitch_user="RSS Reader <span style='font-size: smaller;'>from $snitch_ip [ <a href='/trace/$snitch_ip'>trace</a> ]</span>\n";
                }

				if (!strstr($snitch_user,'Anon') && !strstr($snitch_user,'RSS Reader')) $content.="<a href='/read/$snitch_user'>$snitch_user</a>";
				elseif ($sidebar!=1) $content.=$snitch_user;
				else $content.="AC";
				$content.=$snitch_extras;
				if ($snitch_planupdates[$su_key]>$snitch_lastview[$su_key]) $content.="</b>";
				$content.="<br/>\n";
				$last_snitchday=$snitchday;
			}
			else { $k+=10000; }
			$small_sw[$k]=array_sum(explode(' ',microtime()))-$small_sw_begin;
		}
		$stopwatch['snitch.format_begin']=0;
		$stopwatch['snitch.format_end']=array_sum($small_sw);
	}
	else $content="<div class='alert'>you are not the owner of $owner's plan. you are $user. please re-login if this is an error.</div>";
	
	if ($sidebar) $content="<span style='font-size: 9px; font-family: trebuchet ms;'>$content</span>";
	else $content="
				<h2>snitch</h2>
				<div style='text-align: center; width: 100%;'>
					<form action='/scripts/archive.php' method=POST>
						history: 
						<input type='text' name='threshhold' value='$threshvalue' size='3'>
						<select name='units'>
							<option value='w' $weeks_selected>weeks</option>
							<option value='d' $days_selected>days</option>
							<option value='h' $hours_selected>hours</option>
							<option value='m' $minutes_selected>minutes</option>
						</select>
						<select name='reverse'>
							<option value='r' $newest_first>newest first</option>
							<option value='' $oldest_first>oldest first</option>
						</select>
						<input type='submit' value='view'>
					</form>
				</div>
				<span style='font-size: smaller;'>
					[
					<a href='/help/snitch'>snitch help</a> |
					<a href='/help/snitch#coward'>what's an anonymous coward?</a> | 
					<a href='/help/snitch#rss_reader'>what's an RSS Reader?</a>
					]
				</span>
<br/><br/>
$content\n";

// I'm changing this, at least  until getsnoop is modified to handle 
// the sidebar, otherwise, font sizes are all wrong. 
// return getsnoop().$content;

if ($sidebar || $preferences['showsnoop']) { return $content; }
else { include_once('snoop.php'); return getsnoop().$content; } 

}

output("new snitch",getsnitch('jwdavidson'));

?>