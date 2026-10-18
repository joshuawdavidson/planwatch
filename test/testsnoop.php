<?php
/*
SNOOP.PHP
handles snoop functionality
(snoop is like trackback, but for internal planworld use)
*/

include_once('plan_read.php');
//plan_get_owner_info('jwdavidson');
echo $plantext=plan_read_local('jwdavidson','r3d',time()-(72*3600));
echo "\n<hr/>\n";
print_r(testsnoop_find($plantext));

// SNOOP_SELF_CLEAN()
//
// retrieves and formats snoop info
//------------------------------------------------------------------------------
function testsnoop_self_clean($entry)
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

redirect('/snitch');
}



// snoop_list()
//
// retrieves and formats snoop info
//------------------------------------------------------------------------------
function testsnoop_list()
{
	$_SERVER['STOPWATCH']['snoop_list_begin']=array_sum(explode(' ',microtime()));
	if (file_exists("$_SERVER[USER_ROOT]/stats/snoop.txt"))
	{
		$snoop_a=file("$_SERVER[USER_ROOT]/stats/snoop.txt");
//		if ($_SERVER['USER']=='jwdavidson') print_r($snoop_a);
		foreach($snoop_a as $i=>$snoop)
		{
			$snoop=trim($snoop);
			$snoopline=explode(':',$snoop);
			if ($snoopline[0] != $user &&
				$snooper = trim($snoopline[0])
				)
			{
				if ($snoopline[1][0]=='1') $snooptime = $snoopline[1];
				elseif ($snoopline[2]) $snooptime = $snoopline[2];
				else $snooptime = '';
				
				if ($snooptime > $snoop_times[$snooper])
				{
					$snoop_times[$snooper] = $snooptime;
					$snoopers[$snooper] = $snooper;
				}
			}
		}

		$snoopers_lastview=plan_get_last_view($snoopers);
		if ($snoop_times)
		{
			arsort($snoop_times);
			// for very old snoops, we test them once a day to make sure they're
			// still valid
			if (@fileatime("$_SERVER[USER_ROOT]/stats/snoop_lastcheck") < (time()-24*3600))
			{
				$test_snoops=TRUE;
				touch("$_SERVER[USER_ROOT]/stats/snoop_lastcheck");
			}

			foreach($snoop_times as $j=>$snooptime)
			{
				// the actual validity test gets called here
				if ($snooptime < (time()-72*3600) && $test_snoops)
				{
					$valid_snoop=snoop_test($snoopers[$j]);
				}
				else $valid_snoop=TRUE;

				// if everything's OK, add it to the list
				if ($snooptime && $valid_snoop)
				{
					$snooptime_display=formattime($snooptime);
	
					if ($snoopers_lastview[$i] < $snoop_times[$i]) $read_status='unread';
					else $read_status='read';

					$content.="<li><a class='$read_status' href='$_SERVER[WEB_ROOT]/read/$snoopers[$j]'>$snoopers[$j]</a> $snooptime_display\n"
						." <span class='edit_links'>[ <a href='$_SERVER[WEB_ROOT]/snoop/ignore/$snoopers[$j]'>x</a> ]</span>\n"
						."</li>\n";
				}
			}
		}
	}

	$_SERVER['STOPWATCH']['snoop_list_end']=array_sum(explode(' ',microtime()));
return $content;
}



// SNOOP_TEST()
//
// checks snoops to make sure they're still valid, removes them if not
//------------------------------------------------------------------------------
function testsnoop_test($plan)
{
	include_once('plan_read.php');
	$_SERVER['USER']='cacheuser';
	$snitchlevel=$_SERVER['USERINFO_ARRAY']['snitchlevel'];
	$_SERVER['USERINFO_ARRAY']['snitchlevel']=0;

	$plan_data=plan_read($plan);

	$_SERVER['USER']=$_SERVER['USERINFO_ARRAY']['username'];
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
function testsnoop_find($plan,$remote=FALSE)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	preg_match_all("/!([^!\/\\s]+):([^!]+)!/",$plan,$snooplist_one);
	preg_match_all("/!([^!:\/\\s]+)!/",$plan,$snooplist_two);
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
function testsnoop_clean($removelist,$remote=0)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;
	
	if (!$removelist) $removelist=array();	
	foreach($removelist as $i=>$oldsnoop)
	{
	    $oldsnoop=plan_get_real_location($oldsnoop);
		if ($oldsnoop=trim($oldsnoop))
		{
			if (plan_is_local($oldsnoop)) snoop_remove_local($oldsnoop,$snoop_setter);
			else
			{
				list($snoop_target,$node)=explode("@",$oldsnoop);
				snoop_remove_xmlrpc($snoop_target,'@'.$node,$snoop_setter);
			}
		}
	}

return TRUE;
}



// SNOOP_ADD()
//
// update and add new snoops
//------------------------------------------------------------------------------
function testsnoop_add($addlist,$remote=0)
{

	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;
	
	if (!$addlist) $addlist=array();	
	foreach($addlist as $i=>$newsnoop)
	{
	    $newsnoop=plan_get_real_location($newsnoop);
		if ($newsnoop=trim($newsnoop))
		{
			if (plan_is_local($newsnoop)) snoop_add_local($newsnoop,$snoop_setter);
			else
			{
				list($snoop_target,$node)=explode("@",$newsnoop);
				snoop_add_xmlrpc($snoop_target,'@'.$node,$snoop_setter);
			}
		}
	}

return TRUE;
}



// snoop_add_LOCAL()
//
// sets a local snoop
//------------------------------------------------------------------------------
function testsnoop_add_local($snoop,$remote=0)
{
	// snoop_add_local doesn't get the queue system because we don't fail on
	// local snoop sets
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;
	
	$snoop=str_replace('@planwatch.org','',$snoop);
	$snoop=plan_repair_local_name($snoop);
	
	if (file_exists("$_SERVER[FILE_ROOT]/pwusers/$snoop/userinfo.dat")) $success=TRUE;
	else $success=FALSE;

//	@touch("$_SERVER[FILE_ROOT]/pwusers/$snoop/stats/snoop.txt");
	file_put_contents("$_SERVER[FILE_ROOT]/pwusers/$snoop/stats/snoop.txt","\n$snoop_setter:".time(),FILE_APPEND);

return $success;
}




// snoop_remove_LOCAL()
//
// clears snoops from a given user
//------------------------------------------------------------------------------
function testsnoop_remove_local($oldsnoop,$remote=0)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;
	
	$oldsnoop=str_replace('@planwatch.org','',$oldsnoop);
	$oldsnoop=plan_repair_local_name($oldsnoop);
	$oldsnoop_fn="$_SERVER[FILE_ROOT]/pwusers/$oldsnoop/stats/snoop.txt";

	if (file_exists("$_SERVER[FILE_ROOT]/pwusers/$oldsnoop/userinfo.dat")) $success=TRUE;
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
function testsnoop_add_xmlrpc($rpcuser,$host,$remote=0)
{
	// $remote in this function is just a shim for admins updating system,
	// but we're going to leverage it for the queue system just the same by
	// using it to pass in snoop_setter
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	$rpcuser=str_replace(strstr($rpcuser,'@'),'',$rpcuser);
	if (!strstr($host,'@')) $host="@".$host;

	$hostinfo=planworld_node_getinfo($host);
	$f=new xmlrpcmsg('snoop.addReference');
	$f->addParam(new xmlrpcval($rpcuser, "string"));
	$f->addParam(new xmlrpcval($snoop_setter."@planwatch.org", "string"));


	$c=new xmlrpc_client($hostinfo["directory"], $hostinfo["server"], $hostinfo["port"]);
	$c->setDebug(0);
	$r=$c->send($f);
	
	if (!$r) { $success = FALSE; }
	else
	{
		$v=$r->value();
		if (!$r->faultCode())
		{
			if ($v->kindOf()=='array')
			{
				$success = TRUE;
			}
		}
		else $success = FALSE;
	}
	
	// If the snoop send failed for any reason, add it to the queue
	// for later sending. This is done by creating a TIMECODE.addsnoop file in
	// FILE_ROOT/stats which gets picked up by the queue processor in pt.php
	if ($success == FALSE)
	{
		$queue_array=array("snoop_setter"=>$snoop_setter,"snoop_target"=>$rpcuser,"snoop_host"=>$host);
		file_put_contents("$_SERVER[FILE_ROOT]/stats/".array_sum(microtime()).".addsnoop",serialize($queue_array));
	}
	
	return $success;
}




// snoop_remove_XMLRPC()
//
// clears snoops from another planworld node
//------------------------------------------------------------------------------
function testsnoop_remove_xmlrpc($rpcuser,$host,$remote=0)
{
	if ($remote==FALSE) { $snoop_setter=$_SERVER['USER']; }
	else $snoop_setter=$remote;

	$hostinfo=planworld_node_getinfo($host);
	$f=new xmlrpcmsg('snoop.removeReference');
	$f->addParam(new xmlrpcval($rpcuser, "string"));
	$f->addParam(new xmlrpcval($snoop_setter."@planwatch.org", "string"));


	$c=new xmlrpc_client($hostinfo["directory"], $hostinfo["server"], $hostinfo["port"]);
	$c->setDebug(0);
	$r=$c->send($f);
	
	if (!$r) { $success = FALSE; }
	else
	{
		$v=$r->value();
		if (!$r->faultCode()) {
			if ($v->kindOf()=='array')
			{
				$success = TRUE;
			}
		}
		else $success = FALSE;
	}

	// If the snoop remove failed for any reason, add it to the queue
	// for later sending. This is done by creating a TIMECODE.remsnoop file in
	// FILE_ROOT/stats which gets picked up by the queue processor in pt.php
	if ($success == FALSE)
	{
		$queue_array=array("snoop_setter"=>$snoop_setter,"snoop_target"=>$rpcuser,"snoop_host"=>$host);
		file_put_contents("$_SERVER[FILE_ROOT]/stats/".array_sum(microtime()).".remsnoop",serialize($queue_array));
	}
	
	return $success;
}
?>