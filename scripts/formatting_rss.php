<?php

/*
FORMATTING_RSS.php
*/

// FORMATWATCHEDLIST_RSS()
//
// formats an array of names with times
// calls plan_get_last_update()
//------------------------------------------------------------------------------
function list_format_rss($list,$sortby='time')
{
	$sep="\n";
	$subkey='#main';
	foreach($list as $z=>$plan)
	{
		if ($th=strstr($plan,'!prune'))
		{
			$prune=TRUE;
			$threshhold=str_replace('!prune','',$th);
			if (strstr($threshhold,'s')) $threshhold=str_replace('s','',$threshhold);
			if (strstr($threshhold,'m')) $threshhold=str_replace('m','',$threshhold)*60;
			if (strstr($threshhold,'b')) $threshhold=str_replace('b','',$threshhold)*86.4;
			if (strstr($threshhold,'h')) $threshhold=str_replace('h','',$threshhold)*3600;
			if (strstr($threshhold,'d')) $threshhold=str_replace('d','',$threshhold)*3600*24;
			if (strstr($threshhold,'w')) $threshhold=str_replace('w','',$threshhold)*3600*24*7;
			if (strstr($threshhold,'n')) $threshhold=str_replace('n','',$threshhold)*3600*24*30;
			if (strstr($threshhold,'y')) $threshhold=str_replace('y','',$threshhold)*3600*24*365;
			if (strstr($threshhold,'k')) $threshhold=str_replace('k','',$threshhold)*3600*24*365*10;
			$threshhold=time()-$threshhold;
		}
		if ($plan=='!onlynew') { $onlynew=TRUE; }

		unset($displayname);
		unset($url);
		$plan=str_replace('http://','http//',trim($plan));
		list($url,$displayname)=explode(':',str_replace('!','',$plan),2);
		if(!$displayname) $displayname=$url;
		$displayname=htmlspecialchars($displayname);

		if($plan[0]=='#') $subkey=$displayname;		if (trim($plan))
		{
		  $sublist[$subkey][]=str_replace('http//','http://',$url);
		  $sublist_display[$subkey][]=$displayname;
		}

	}

	$items.="\n<rdf:li rdf:resource='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/home'/>";
	$watchlist.="
		<item rdf:about='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/home'>
			<title>&gt;&gt; Planwatch Home</title>
			<link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/home</link>
			<description>Planwatch Home</description>
		</item>\n";

	foreach($sublist as $key=>$list)
	{
		if (count($list)>1)
		{
			$ptime=plan_get_last_update($list);
			$lastview=plan_get_last_view($list);
			$list_display=$sublist_display[$key];
		
			if ($sortby=='name') array_multisort($list,SORT_ASC,$ptime,$lastview,$list_display);
			if ($sortby=='time') array_multisort($ptime,SORT_DESC,$list,$lastview,$list_display);
		
			if ($key!='#main')
			{
				$items.="\n<rdf:li rdf:resource='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/$key'/>";
				$watchlist.="
					<item rdf:about='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/$key'>
						<title>__ ".str_replace('#','',$key)." __</title>
						<link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/</link>
						<description>category: ".str_replace('#','',$key)."</description>
					</item>\n";
			}

			foreach($list as $i=>$plan)
			{
				$plantime=str_replace('&nbsp;',' ',formattime($ptime[$i]));
				if ($plan[0]!='#' && $plan && ($ptime[$i]>$threshhold || !$threshhold))	//if it's not a category header
				{
					if ($lastview[$i] < $ptime[$i])
					{
						$items.="\n<rdf:li rdf:resource='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$plan'/>";
						$watchlist.="
					<item rdf:about='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$plan'>
						<link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$plan</link>
						<title>*** $list_display[$i] $plantime</title>
						<description>read $list_display[$i]'s plan</description>
					</item>\n";
					}
					
					elseif(!$onlynew)
					{
						$items.="\n<rdf:li rdf:resource='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$plan'/>";
						$watchlist.="
					<item rdf:about='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$plan'>
						<title>$list_display[$i] $plantime</title>
						<link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$plan</link>
						<description>read $list_display[$i]'s plan</description>
					</item>\n";
					}
				}
			}

		}
	}

	$items.="\n<rdf:li rdf:resource='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/look/$_SERVER[FINGERPRINT]'/>";
	$watchlist.="
		<item rdf:about='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/look/$_SERVER[FINGERPRINT]'/>
			<title>&gt;&gt; Planwatch Look</title>
			<link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/look/$_SERVER[FINGERPRINT]</link>
			<description>Planwatch Look</description>
		</item>\n";

	$watchlist = "$items\n<!-- FEED_DIVIDER -->\n$watchlist";


return $watchlist;
}

?>