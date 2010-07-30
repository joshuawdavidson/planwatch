<?php

/*
SNITCH.php

contains the functions relating to snitch -- plan read logging
*/



// snitch_write()
//
// logs plan reads for each user (to userdir/stats/planlog.txt)
//------------------------------------------------------------------------------
function snitch_write($reader=FALSE,$writer,$extra='')
{
	$_SERVER['STOPWATCH']["snitch_write_begin"]=array_sum(explode(' ',microtime()));

	if (!($reader) || $reader=='guest')
	{
		$rh=@gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$reader="Anonymous Coward from $rh";
	  	if ($rh=='livejournal.com') $reader='LiveJournal';
	  	if (strstr($rh,'facebook.com')) $reader='Facebook';
	  	$remotesnitch=TRUE;
	}
	
	if ($reader=='rss reader') 
	{
		$rh=@gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$reader="RSS Reader from $rh";
	    $remotesnitch=TRUE;
	}
	
	if ($reader=='cacheuser')
	{
		$reader=FALSE;
	}

	$reader=str_replace('@planwatch.org','',$reader);
	$writer=str_replace('@planwatch.org','',$writer);

	$time=time();

	if ($reader
		&& $_SERVER['USERINFO_ARRAY']['snitchlevel'] > 1
		&& $_SERVER['PLANOWNER_INFO_ARRAY']['snitchlevel'] > 1
		&& $reader!=$writer)
	{
		if ((user_get_snitchlevel($reader) > 2
			&& user_get_snitchlevel($writer) > 2)
			|| $extra==' failed')
		{
			$snitch_string="$time,$reader,$extra\n";
		}
		else
		{
			$snitch_string="$time,$reader\n";
		}

//		echo "$_SERVER[PLANOWNER]($writer) $_SERVER[USER]($reader) $snitch_string";
		file_put_contents("$_SERVER[PLANOWNER_ROOT]/stats/planlog.txt",$snitch_string,FILE_APPEND);
		file_put_contents("$_SERVER[PLANOWNER_ROOT]/stats/planlog_".date("Y")."_".date("m")."_".date("d").".txt",'a');
	}
/*	else
	{
		if ($_SERVER['USER']=='jwdavidson' || $_SERVER['USER']=='testuser')
		{
		echo "no snitch written<br/>
		$_SERVER[PLANOWNER]($writer) $_SERVER[USER]($reader)<br/>
		$snitch_string<br/>
		{$_SERVER['USERINFO_ARRAY']['snitchlevel']}<br/>
		{$_SERVER['PLANOWNER_INFO_ARRAY']['snitchlevel']}<br/>\n";
		}
	}
*/
	$_SERVER['STOPWATCH']["snitch_write_end"]=array_sum(explode(' ',microtime()));
}




// snitch_read()
//
// reads and formats userdir/stats/planlog.txt for user reading
//------------------------------------------------------------------------------
function snitch_read($owner,$threshhold)
{
	if (!($threshhold)) $threshhold='2dr';
	$writer=$_SERVER['USER'];

   	if ($_SERVER['USER']==$owner)
	{
		if($threshhold=='r'.$_SERVER['USERINFO_ARRAY']['snitch_default_days'].'d') $mode='NORMAL';
		if($threshhold=='r2h') $mode='BRIEF';
		$last_plan_update=plan_get_last_update($_SERVER['USER']);

		if (strpos($threshhold,'r')!==FALSE) $newest_first='SELECTED';
		else $oldest_first='SELECTED';
		
		if (strpos($threshhold,'w')!==FALSE) $weeks_selected='SELECTED';
		elseif (strpos($threshhold,'h')!==FALSE) $hours_selected='SELECTED';
		elseif (strpos($threshhold,'m')!==FALSE) $minutes_selected='SELECTED';
		else $days_selected='SELECTED';
		
		$threshvalue=str_replace(array('d','r','w','d','h','m'),'',$threshhold);
		if ($threshhold=='sidebar')  { $threshhold='1d'; $reverse='true'; $sidebar=1; }
		if (strstr($threshhold,'r')) { $reverse='true'; $threshhold=str_replace('r','',$threshhold); }
		$threshhold = time_calculate_threshhold($threshhold);
		$threshhold = time() - $threshhold;


profile('snitch_read');
		$snitch_fn="$_SERVER[USER_ROOT]/stats/planlog.txt";
		if (file_exists($snitch_fn))
		{
		if ($mode=='NORMAL') exec("tail -n 200 $snitch_fn",$snitch_array);
		if ($mode=='BRIEF') exec("tail -n 30 $snitch_fn",$snitch_array);
		//TODO: make historical snitch reading faster by reading file chunks instead of the whole thing.
		else $snitch_array=explode("\n",file_get_contents($snitch_fn));
		}
		else $snitch_array=array();
profile('snitch_read');

		if ($reverse) $snitch_array=array_reverse($snitch_array);

		foreach($snitch_array as $k=>$snitch_entry)
		{
			list($blah,$snitch_user,$blah)=explode(',',$snitch_entry);
			$snitch_users[]=$snitch_user;
		}

		if (is_array($snitch_users)) $snitch_users=array_unique($snitch_users);
		else $snitch_users='No snitch';

		if ($mode) $lastview=plan_get_last_view($snitch_users);
		if ($mode) $lastupdate=plan_get_last_update($snitch_users);

		profile('snitch_format_begin');
		foreach($snitch_array as $k=>$snitch_entry)
		{
			unset($style); unset($class);
			$snitch_entry=explode(',',$snitch_entry);
			if ($snitch_entry[0] > $threshhold)
			{
				if ($snitch_entry[0]<$last_plan_update)
				{
					$content.="<li style='margin: 15px;'>".str_replace('&nbsp;',' ',formattime($last_plan_update))." &nbsp;&nbsp; Last Plan Update</li>";
					$last_plan_update=0;
				}

				$su_key=array_search($snitch_entry[1],$snitch_users);

				$snitchday=date('d',$snitch_entry[0]);
				if ($last_snitchday && $snitchday != $last_snitchday) $style="margin-top: 15px;";
				
				$time_string=str_replace('&nbsp;',' ',formattime($snitch_entry[0]));
				if (strlen(date('g',$snitch_entry[0]))==1) $time_string.="&nbsp;";

				if ($lastupdate[$su_key]> 0 && $lastupdate[$su_key]>$lastview[$su_key]) $class="unread";
				else $class='read';

				if(strpos($snitch_entry[1],'Anon')!==FALSE)
	            {
	            	$snitch_ip=str_replace("Anonymous Coward from ",'',$snitch_entry[1]);
	            	if ($snitch_ip=='livejournal.com')
	            	{
	            		$snitch_entry[1]="LiveJournal";
	            	}
	            	elseif (strstr($snitch_ip,'facebook.com'))
	            	{
	            		$snitch_entry[1]="Facebook";
	            	}
	            	else
	            	{
						if ((strpos($_SERVER['REQUEST_URI'],'/snitch')===0) && $_SERVER['OUTPUT_MODE']!='IPHONE') $ac="Anonymous Coward ";
						else $ac="AC ";
						$numerical_ip=gethostbyname($snitch_ip);
						$snitch_entry[1]="$ac from $snitch_ip";
						if ($ac!='AC ') $snitch_entry[1].=" <a href='$_SERVER[WEB_ROOT]/trace/$snitch_ip'>trace</a> | <a href='$_SERVER[WEB_ROOT]/lists/add/blocked/$numerical_ip'>block</a>\n";
					}
	            }
				
	            if (strpos($snitch_entry[2],'archives')!==FALSE)
	            {
	            	preg_match("|archives \\( (r.*d) 3(.*) \\)|",$snitch_entry[2],$matches);
	            	$length=$matches[1];
	            	$timecode=$matches[2];
	            	$url=date("Y/m/d/h:i",$timecode);
	            	$snitch_entry[2]=str_replace($matches[0],"<a href='/read/$_SERVER[USER]/$length/$url'>archives ($length from ".date("F jS Y g:ia",$timecode).")</a>",$snitch_entry[2]);
	            }
	            
				if(strpos($snitch_entry[1],'RSS')!==FALSE)
	            {
					$snitch_ip=str_replace("RSS Reader from ",'',$snitch_entry[1]);
	            	if (strstr($_SERVER['REQUEST_URI'],'snitch')) $rr="RSS Reader ";
	            	else $rr="RSS ";
					$snitch_entry[1]="$rr <span class='edit_links'>from $snitch_ip";
					if ($rr!='RSS ') $snitch_entry[1].="[ <a href='$_SERVER[WEB_ROOT]/trace/$snitch_ip'>trace</a> | <a href='$_SERVER[WEB_ROOT]/lists/add/blocked/$ip'>block</a> ]</span>\n";
	            }

				if (!strstr($snitch_entry[1],'AC ')
					&& !strstr($snitch_entry[1],'Anon')
					&& !strstr($snitch_entry[1],'RSS')
					&& !strstr($snitch_entry[1],'LiveJournal')
					&& !strstr($snitch_entry[1],'Facebook'))
					$content.="<li class='$class' style='$style'><a href='$_SERVER[WEB_ROOT]/read/$snitch_entry[1]'>$time_string $snitch_entry[1]</a> $snitch_entry[2]</li>";
				elseif ($sidebar!=1) $content.="<li class='$class' style='$style'>$time_string $snitch_entry[1] $snitch_entry[2]</li>\n";
				else $content.="<li class='$class' style='$style'>$time_string AC $snitch_entry[2]</li>\n";

				$last_snitchday=$snitchday;
			}
			else $k+=10000;
		}
		profile('snitch_format_end');
	}
	else $content="<li class='alert'>you are not the owner of $owner's plan. you are $user. please re-login if this is an error.</li>";

	return $content;
}

?>
