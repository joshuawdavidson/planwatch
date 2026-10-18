<?php

/*
SNITCH.php

contains the functions relating to snitch -- plan read logging
*/



// snitch_write()
//
// logs plan reads for each user (to userdir/stats/planlog_[Y]_[m]_[d].txt)
//------------------------------------------------------------------------------
function snitch_write($reader=FALSE,$writer=FALSE,$extra='')
{
	$_SERVER['STOPWATCH']["snitch_write_begin"]=array_sum(explode(' ',microtime()));

	if (!($reader) || $reader=='guest' || $reader=='Guest')
	{
		$rh=@gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$reader="Anon $rh";
	  	if ($rh=='livejournal.com') $reader='LiveJournal';
	  	if (strstr($rh,'facebook.com')) $reader='Facebook';
	  	$remotesnitch=TRUE;
	}

	if ($reader=='rss reader')
	{
		$rh=@gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$reader="RSS $rh";
	    $remotesnitch=TRUE;
	}

	if ($reader=='cacheuser')
	{
		$reader=FALSE;
	}

	$reader=str_replace('@planwatch.org','',$reader);
	$writer=str_replace('@planwatch.org','',$writer);

	$time=time();

	$snitchlevel = isset($_SERVER['PLANOWNER_INFO_ARRAY']['snitchlevel']) ? $_SERVER['PLANOWNER_INFO_ARRAY']['snitchlevel'] : 1;
	if ($reader
		&& ($_SERVER['USERINFO_ARRAY']['snitchlevel'] > 1	&& $snitchlevel > 1	&& $reader!=$writer)
		|| strstr($reader,'Anon')
		|| strstr($reader,'RSS')
		)
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
		file_put_contents("$_SERVER[PLANOWNER_ROOT]/stats/planlog_".date("Y_m_d").".txt",$snitch_string,FILE_APPEND);
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
	if (!($threshhold)) {$threshhold='2dr';}
	$mode='NORMAL';
	$writer=$_SERVER['USER'];
	$content=$sidebar=$style=$m_time_string='';

   	if ($_SERVER['USER']==$owner)
	{
		if($threshhold=='r'.$_SERVER['USERINFO_ARRAY']['snitch_default_days'].'d')
		{
			$mode='NORMAL';
		}
		if($threshhold=='r2h')
		{
			$mode='BRIEF';
		}
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
			$snitch_array=[];
			if ($mode=='NORMAL')
			{
				if(file_exists("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d").".txt"))
				{
					$snitch_array=file("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d").".txt");
				}

				if(file_exists("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d",time()-24*3600).".txt"))
				{
					$snitch_array=array_merge($snitch_array,file("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d",time()-24*3600).".txt"));
				}

				if(file_exists("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d",time()-48*3600).".txt"))
				{
					$snitch_array=array_merge($snitch_array,file("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d",time()-48*3600).".txt"));
				}

				if(!isset($snitch_array) || !is_array($snitch_array))
				{
					$snitch_array=[];
				}
			}
			elseif ($mode=='BRIEF') //exec("tail -n 30 $snitch_fn",$snitch_array);
			{
				$snitch_array=[];
				if(file_exists("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d").".txt"))
				{
					$snitch_array=file("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d").".txt");
				}
				if(file_exists("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d",time()-24*3600).".txt"))
				{
					$snitch_array=array_merge($snitch_array,file("$_SERVER[USER_ROOT]/stats/planlog_".date("Y_m_d",time()-24*3600).".txt"));
				}
				if(!isset($snitch_array) || !is_array($snitch_array))
				{
					$snitch_array=[];
				}
			}

			//TODO: make historical snitch reading faster by reading file chunks instead of the whole thing.
			else $snitch_array=explode("\n",file_get_contents($snitch_fn));
		}
		else $snitch_array=array();

		profile('snitch_read');

		$snitch_array[]="$last_plan_update,Last Update,header";
		$snitch_array[]=strtotime(date("Y-m-d",time()-24*3600)." 11:59pm").",".date("l",time()-24*3600).",headerD";
		$snitch_array[]=strtotime(date("Y-m-d",time()-48*3600)." 11:59pm").",".date("l",time()-48*3600).",headerD";

		if($_SERVER['OUTPUT_MODE']=='MOBILE')
		{
			$content.="<li class='listheader'><img src='/resources/graphics/pixel.gif' width='1' height='1' /></li>";
		}

		foreach($snitch_array as $k=>$snitch_entry)
		{
			list($snitch_times[$k],$snitch_users[$k],$snitch_details[$k])=explode(',',$snitch_entry);
		}

		if($reverse) arsort($snitch_times); else asort($snitch_times);

		if (is_array($snitch_users)) $snitch_users_unique=array_unique($snitch_users);
		else $snitch_users='No snitch';


		if ($mode) $lastview=plan_get_last_view($snitch_users);
		if ($mode) $lastupdate=plan_get_last_update($snitch_users);

		profile('snitch_format_begin');
		foreach($snitch_times as $k=>$snitch_timecode)
		{
			$style='';
			$class='';
			if ($snitch_timecode > $threshhold)
			{
				$su_key=array_search($snitch_users[$k],$snitch_users_unique);

				$time_string=str_replace('&nbsp;',' ',formattime($snitch_timecode));
				if (strlen(date('g',$snitch_timecode))==1 && $_SERVER['OUTPUT_MODE']!='MOBILE') $time_string.="&nbsp;";

				if ($lastupdate[$su_key]> 0 && $lastupdate[$su_key]>$lastview[$su_key]) $class="unread";
				else $class='read';

				if(strpos($snitch_users[$k],'Anon')!==FALSE)
	            {
	            	$snitch_ip=str_replace("Anon ",'',$snitch_users[$k]);
	            	if (strstr($snitch_ip,'livejournal'))
	            	{
	            		$snitch_users[$k]="LiveJournal";
	            	}
	            	elseif (strstr($snitch_ip,'facebook.com'))
	            	{
	            		$snitch_users[$k]="Facebook";
	            	}
	            	else
	            	{
						$ac="Anon ";
						if ((strpos($_SERVER['REQUEST_URI'],'/snitch')===0) && $_SERVER['OUTPUT_MODE']!='MOBILE')
						{
							$numerical_ip=gethostbyname($snitch_ip);
							$snitch_users[$k].=" <a href='$_SERVER[WEB_ROOT]/trace/$numerical_ip'>trace</a> | <a href='$_SERVER[WEB_ROOT]/lists/add/blocked/$numerical_ip'>block</a>\n";
						}

						$snitch_users[$k]=$snitch_ip;
					}
	            }

	            $snitch_users_display[$k]=str_replace("@","<wbr>@",$snitch_users[$k]);
				$snitch_users_display[$k]=str_replace(".","<wbr>.",$snitch_users_display[$k]);
				$snitch_users_display[$k]=str_replace('archives ( )','',$snitch_users_display[$k]);
				// $snitch_users_display[$k]=str_replace("( <wbr>.","(.",$snitch_users_display[$k]);

	            if (strpos($snitch_details[$k],'archives')!==FALSE)
	            {
					// echo $snitch_details[$k]."<br>";
					//preg_match('/archives \( (\.\d+)  \)/', $input_line, $output_array);
	            	preg_match('/archives \( (\.\d+)  \)/',$snitch_details[$k],$matches);
					if(!empty($matches))
					{
						$timecode=str_replace(".","",$matches[1]);
						$entrytime=formattime($timecode);
						$snitch_details[$k]=str_replace($matches[0],"<a href='/read/$_SERVER[USER]/.$timecode'>$entrytime</a>",$snitch_details[$k]);
					}
	            }
				if($snitch_details[$k]=='archives (   )')
				{
					$snitch_details[$k]='';
				}

				if(strpos($snitch_users[$k],'RSS')!==FALSE)
	            {
					$snitch_ip=str_replace("RSS Reader from ",'',$snitch_users[$k]);
	            	if (strstr($_SERVER['REQUEST_URI'],'snitch')) $rr="RSS Reader ";
	            	else $rr="RSS ";
					$snitch_users[$k]="$rr <span class='edit_links'>from $snitch_ip";
					if ($rr!='RSS ') $snitch_users[$k].="[ <a href='$_SERVER[WEB_ROOT]/trace/$snitch_ip'>trace</a> | <a href='$_SERVER[WEB_ROOT]/lists/add/blocked/$ip'>block</a> ]</span>\n";
	            }

				if ($_SERVER['OUTPUT_MODE']=='MOBILE')
				{
					$m_time_string="<a class='updatetime' href='$_SERVER[WEB_ROOT]/read/$snitch_users[$k]'>".trim($time_string)."</a>";
					$time_string='';
				}

				if (!strstr($snitch_users[$k],'AC ')
					&& !strstr($snitch_users[$k],'Anon')
					&& !strstr($snitch_users[$k],'RSS')
					&& !strstr($snitch_users[$k],'LiveJournal')
					&& !strstr($snitch_users[$k],'Facebook')
					&& !strstr($snitch_details[$k],'header')
				)
				{
					$content.="<li class='$class' style='$style'>$time_string <a href='$_SERVER[WEB_ROOT]/read/$snitch_users[$k]' class='updatename'>$snitch_users_display[$k]</a> $m_time_string $snitch_details[$k] </li>";
				}
				elseif(strstr($snitch_details[$k],'header'))
				{
					if(strstr($snitch_details[$k],'headerD'))
					{
						$time_string=$m_time_string='';
					}
					$content.="<li class='listheader'>$time_string <a href='#'>$snitch_users_display[$k]</a> $m_time_string</li>\n";
				}
				elseif ($sidebar!=1)
				{
					$content.="<li class='$class' style='$style font-size: 1rem; font-weight: normal;'>$time_string $snitch_users_display[$k] $snitch_details[$k] $m_time_string</li>\n";
				}
				else
				{
					$content.="<li class='$class' style='$style'>$time_string Anon $snitch_details[$k] $m_time_string </li>\n";
				}
			}
			else $k+=10000;
		}
		profile('snitch_format_end');
	}
	else
	{
		$content="<li class='alert'>you are not the owner of $owner's plan. you are $user. please re-login if this is an error.</li>";
	}

	return $content;
}

?>