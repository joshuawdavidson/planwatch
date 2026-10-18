<?php

/*
FORMATTING_RSS.php
*/

// LIST_FORMAT_ATOM()
//
// formats an array of names with times
// calls plan_get_last_update()
//------------------------------------------------------------------------------
function list_format_atom($list)
{
	// get display names, prune headers
	// (the atom watched list is always sorted by time, and has no
	//  sections or read plans listed)
	foreach($list as $i=>$plan)
	{
		unset($displayname); unset($url);
		$plan=str_replace('http://','http//',trim($plan));
		list($url,$displayname)=explode(':',str_replace('!','',$plan),2);
		if(!$displayname) $displayname=$url;
		$displayname=htmlspecialchars($displayname);
		$plans_list[$i]=trim(str_replace('http//','http://',$url));
		$plans_list_display[$i]=$displayname;
	}

	$ptime=plan_get_last_update($plans_list);
	$lastview=plan_get_last_view($plans_list);

	array_multisort($ptime,SORT_DESC,$plans_list,$lastview,$plans_list_display);

	foreach($plans_list as $i=>$plan)
	{
			if ($lastview[$i] < $ptime[$i])
			{
				$plantime = gmdate('Y-m-d\TH:i:s+00:00',$ptime[$i]);
				$humantime = str_replace("&nbsp;",' ',formattime($ptime[$i]));
				$items.="\n<rdf:li rdf:resource='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$plan'/>";
				$watchlist.="
					<entry>
						<title type='html'>$plans_list_display[$i] updated $humantime</title>
						<summary> $humantime</summary>
						<link rel=\"alternate\" href=\"http://planwatch.org/read/$plan\" type=\"text/html\"/>
						<updated>$plantime</updated>
						<id>http://planwatch.org/read/$plan</id>
						<author>
							<name>$plans_list_display[$i]</name>
						</author>
					</entry>
				";
		}
	}

	$watchlist = "$watchlist";

return $watchlist;
}

?>