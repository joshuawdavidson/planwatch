<?php
/*
SNOOP.PHP
handles snoop functionality
(snoop is like trackback, but for internal planworld use)
*/

//include_once('xmlrpc.inc');



// SNOOP_SELF_CLEAN()
//
// retrieves and formats snoop info
//------------------------------------------------------------------------------
function snoop_self_clean($entry,$return=FALSE)
{
	$snoop_fn="$_SERVER[USER_ROOT]/stats/snoop.txt";
	if (file_exists($snoop_fn))
	{
		$snoop_a=file($snoop_fn);
		foreach($snoop_a as $i=>$snoop)
		{
			if (strstr($snoop,$entry))
				unset($snoop_a[$i]);
		}
	}

	file_put_contents($snoop_fn,implode('',$snoop_a));

	if($return) return TRUE;
	else redirect('/snoop');
}



// snoop_list()
//
// retrieves and formats snoop info
//------------------------------------------------------------------------------
function snoop_list($source=FALSE)
{
	profile('snoop_list');
	$snoop_filename="$_SERVER[USER_ROOT]/stats/snoop.txt";
	if(($source=='planwatch' || $source=='homesnoop') && $_SERVER['OUTPUT_MODE']!='MOBILE')
	{
		$ignore="ignore_ajax";
	}
	else
	{
		$ignore="ignore";
	}
	$content='';


	if (file_exists($snoop_filename))
	{
		$snoop_times=[];
		$snoop_a=file($snoop_filename);
		foreach($snoop_a as $i=>$snoop)
		{
			$snoop=trim($snoop);
			if(strpos($snoop,':')===false) { $snoop.=':'; }
			list($snooper,$snooptime)=explode(':',$snoop);
			if ($snooper != $_SERVER['USER'] && trim($snooper))
			{
				if (!isset($snoop_times[$snooper]) || $snooptime > $snoop_times[$snooper])
				{
					$snoop_times[$snooper] = $snooptime;
				}
			}
		}

		if (is_array($snoop_times))
		{
			$snoopers_lastview=plan_get_last_view(array_keys($snoop_times),TRUE);
			arsort($snoop_times);
			// for very old snoops, we test them once a day to make sure they're
			// still valid
			// TODO:(v4.5) improve and re-enable the snoop test
//			if (filemtime("$_SERVER[USER_ROOT]/stats/snoop_lastcheck") < (time()-24*3600))
//			{
//				$test_snoops=TRUE;
//				file_put_contents("$_SERVER[USER_ROOT]/stats/snoop_lastcheck",time());
//			}

			$alias_list=user_list_aliases($_SERVER['USER']);
			if(!$alias_list) $alias_list=array();
//			if($_SERVER['MASQUERADE']) { print_r($alias_list); exit; }

			foreach($snoop_times as $snooper=>$snooptime)
			{
				// the actual validity test gets called here
//				if (($snooptime < (time()-(72*3600))) && $test_snoops)
//				{
//					$valid_snoop=snoop_test($snoopers[$j]);
//				}
//				else $valid_snoop=TRUE;

				// if everything's OK, add it to the list
				if ($snooptime)// && $valid_snoop)
				{
					$snooptime_display=formattime($snooptime);

					$alias=array_search($snooper,$alias_list);
					if($alias!==FALSE)
					{
						$displayname=$alias;
					}
					elseif(strstr($snooper,'@'))
					{
						$displayname=str_replace("@","<wbr>@",$snooper);

					}
					else $displayname=$snooper;

					if ($snoopers_lastview[$snooper] < $snooptime) $read_status="unread $snoopers_lastview[$snooper] $snooptime";
					else $read_status='read';

					if($ignore=="ignore")
					$content.="<li class='snoop $read_status'>
						<a class='updatename' title='$snooper' href='$_SERVER[WEB_ROOT]/read/$snooper'>$displayname</a>
						<a title='$snooper' href='$_SERVER[WEB_ROOT]/read/$snooper' class='updatetime'>$snooptime_display</a>\n
						<a class='tool' href='$_SERVER[WEB_ROOT]/snoop/$ignore/$snooper/$source'>&#x2612;</a>"
						."</li>\n";

					else $content.="<li class='snoop $read_status'>
							<a class='updatename' title='$snooper' href='$_SERVER[WEB_ROOT]/read/$snooper'>$displayname</a>
							<a class='updatetime' title='$snooper' href='$_SERVER[WEB_ROOT]/read/$snooper'>$snooptime_display</a>\n
							<a class='tool' href='javascript:loadXMLDoc(\"$_SERVER[WEB_ROOT]/snoop/$ignore/$snooper/$source\",null,\"$source\");'>&#x2612;</a>"
						."</li>\n";
				}
			}

		}
		else $content.="<li class='unread'>no snoops found</li>\n";
	}

	profile('snoop_list');
return $content;
}



// SNOOP_TEST()
//
// checks snoops to make sure they're still valid, removes them if not
//------------------------------------------------------------------------------
function snoop_test($plan)
{
	include_once('plan_read.php');
	$plan_data=plan_read_quiet($plan);

	if (strpos($plan_data,$_SERVER['USER'])) return TRUE;
	else
	{
		snoop_self_clean($plan);
		return FALSE;
	}
}




// SNOOP_FIND()
//
// pulls snoops out of entries and sets them
//------------------------------------------------------------------------------
function snoop_find($plan,$remote=FALSE)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;


	preg_match_all("/!([\w@\.\-]+):[^!]+!/",$plan,$snooplist_one);
	preg_match_all("/!([\w@\.\-]+)!/",$plan,$snooplist_two);
	$real_snoop_array=array_merge($snooplist_one[1],$snooplist_two[1]);

	$alias_list=user_list_aliases($snoop_setter);

	foreach($real_snoop_array as $i=>$snoop_item)
	{
		if ($snoop_item=='http' || $snoop_item=='link' || $snoop_item=='--ut') unset($real_snoop_array[$i]);
		if (isset($alias_list[$snoop_item])) $real_snoop_array[$i]=$alias_list[$snoop_item];
	}


return $real_snoop_array;
}



// SNOOP_CLEAN()
//
// remove old and outdated snoops
//------------------------------------------------------------------------------
function snoop_clean($removelist,$remote=0)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	if (is_string($removelist)) $removelist=array($removelist);
	if (!$removelist) $removelist=array();
	foreach($removelist as $i=>$oldsnoop)
	{
	    $oldsnoop=plan_get_real_location($oldsnoop);
		if ($oldsnoop=trim($oldsnoop))
		{
			if (plan_is_local($oldsnoop))
			{
				$success=snoop_remove_xmlrpc($oldsnoop,"@planwatch.org",$snoop_setter);
			}
			else
			{
				list($snoop_target,$node)=explode("@",$oldsnoop);
				$success=snoop_remove_xmlrpc($snoop_target,'@'.$node,$snoop_setter);
			}

			$success_list[$snoop_target]=$success;
		}
	}

return $success_list;
}



// SNOOP_ADD()
//
// update and add new snoops
//------------------------------------------------------------------------------
function snoop_add($addlist,$remote=0)
{
//	if(IS_JOSH) { echo "<hr>"; print_r($addlist); echo "<hr>"; }

	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	if (is_string($addlist)) $addlist=array($addlist);
	if (!$addlist) $addlist=array();
	foreach($addlist as $i=>$newsnoop)
	{
	    $newsnoop=plan_get_real_location($newsnoop);
		if (!strstr($newsnoop,'ttp:') && $newsnoop=trim($newsnoop))
		{
//		    if(IS_JOSH) { echo "$newsnoop<br>"; }
			if (plan_is_local($newsnoop))
			{
				$success=snoop_add_xmlrpc($newsnoop,"@planwatch.org",$snoop_setter);
				if (!$success) $_SERVER['ERRORS'].="$newsnoop snoop failed<br />\n";
			}
			else
			{
				list($snoop_target,$node)=explode("@",$newsnoop);
				$success=snoop_add_xmlrpc($snoop_target,'@'.$node,$snoop_setter);
				if (!$success) $_SERVER['ERRORS'].="$newsnoop snoop failed<br />\n";
			}
			$success_list[$snoop_target]=$success;
		}
	}

return $success_list;
}



// snoop_add_LOCAL()
//
// sets a local snoop
//------------------------------------------------------------------------------
function snoop_add_local($snoop,$remote=0)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	if (strstr($snoop_setter,"@planwatch.org"))
	{
		$snoop_setter=str_replace('@planwatch.org','',$snoop_setter);
		$snoop_setter=plan_repair_local_name($snoop_setter);
	}
	if (strstr($snoop,"@planwatch.org"))
	{
		$snoop=str_replace('@planwatch.org','',$snoop);
		$snoop=plan_repair_local_name($snoop);
	}

	if ($snoop!=$snoop_setter)
	{
		if (file_exists("$_SERVER[PWUSERS_DIR]/$snoop/userinfo.dat")) $success=TRUE;
		else $success=FALSE;

		file_put_contents("$_SERVER[PWUSERS_DIR]/$snoop/stats/snoop.txt","\n$snoop_setter:".time(),FILE_APPEND);
	}
	else $success=FALSE;

return $success;
}




// snoop_remove_LOCAL()
//
// clears snoops from a given user
//------------------------------------------------------------------------------
function snoop_remove_local($oldsnoop,$remote=0)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	$oldsnoop=str_replace('@planwatch.org','',$oldsnoop);
	$oldsnoop=plan_repair_local_name($oldsnoop);
	$oldsnoop_fn="$_SERVER[PWUSERS_DIR]/$oldsnoop/stats/snoop.txt";

	if (file_exists("$_SERVER[PWUSERS_DIR]/$oldsnoop/userinfo.dat")) $success=TRUE;
	else $success=FALSE;

	if (file_exists($oldsnoop_fn))
	{
		$oldsnoop_a=file($oldsnoop_fn);
		foreach($oldsnoop_a as $i=>$snoopitem)
		{
			if (strpos($snoopitem,$snoop_setter)!==FALSE) $oldsnoop_a[$i]='';
		}
		$oldsnoop_a=array_unique(array_values($oldsnoop_a));
		sort ($oldsnoop_a);
		file_put_contents($oldsnoop_fn,str_replace("\n\n","\n",implode("\n",$oldsnoop_a)));
	}

return $success;
}




// snoop_add_XMLRPC()
//
// sets snoops on another planworld node
//------------------------------------------------------------------------------
function snoop_add_xmlrpc($rpcuser,$host,$remote=FALSE,$debug=FALSE)
{
//	if(IS_JOSH)
//		echo "host: $host * rpcuser: $rpcuser * remote: $remote<hr>";

	// $remote in this function is just a shim for admins updating system,
	// but we're going to leverage it for the queue system just the same by
	// using it to pass in snoop_setter
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	if (!strstr($snoop_setter,'@planwatch.org')) $snoop_setter.="@planwatch.org";

	$passedrpcuser=$rpcuser;
	$rpcuser=str_replace(strstr($rpcuser,'@'),'',$rpcuser);
	if (!strstr($host,'@')) $host="@".$host;

	$success=planworld_xmlrpc_query($host,"planworld.snoop.add",array($rpcuser,$snoop_setter),$debug);
	if (!$success)
	{
		// email_send("failedsnoop@planwatch.org","Failed Snoop: $rpcuser $host $snoop_setter","Failed Snoop: $rpcuser $host $snoop_setter\n\n$_SERVER[RPC_ERROR]\n\nplanworld_xmlrpc_query($host,\"planworld.snoop.add\",array($rpcuser,$snoop_setter),FALSE)\n\npassed: $passedrpcuser;");

	// If the snoop send failed for any reason, add it to the queue
	// for later sending. This is done by creating a snoopadd.TIMECODE.txt file in
	// FILE_ROOT/stats which gets picked up by the queue processor in pt.php
		$queue_array=array("snoop_setter"=>$snoop_setter,"snoop_target"=>$rpcuser,"snoop_host"=>$host);
		file_put_contents("$_SERVER[FILE_ROOT]/stats/snoopadd.".array_sum(explode(" ",microtime())).".txt",serialize($queue_array));
	}

	return $success;
}




// snoop_remove_XMLRPC()
//
// clears snoops from another planworld node
//------------------------------------------------------------------------------
function snoop_remove_xmlrpc($rpcuser,$host,$remote=0)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	$success=planworld_xmlrpc_query($host,"planworld.snoop.remove",array($rpcuser,"$snoop_setter@planwatch.org"));

	// If the snoop remove failed for any reason, add it to the queue
	// for later sending. This is done by creating a snooprem.TIMECODE.txt file in
	// FILE_ROOT/stats which gets picked up by the queue processor in pt.php
	if ($success == FALSE)
	{
		// email_send("failedsnoop@planwatch.org","Failed RemSnoop: $rpcuser $host $snoop_setter","Failed RemSnoop: $rpcuser $host $snoop_setter\n\nplanworld_xmlrpc_query($host,\"planworld.snoop.remove\",array($rpcuser,$snoop_setter),FALSE)\n\npassed: $passedrpcuser;");
		$queue_array=array("snoop_setter"=>$snoop_setter,"snoop_target"=>$rpcuser,"snoop_host"=>$host);
		file_put_contents("$_SERVER[FILE_ROOT]/stats/snooprem.".array_sum(explode(" ",microtime())).".txt",serialize($queue_array));
	}

	return $success;
}
?>