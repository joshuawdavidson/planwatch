<?php
include_once('getplan.php');

////////////////////////////////////////////////////////////////////////
// GETSNOOP()
//
// retrieves and formats snoop info
//----------------------------------------------------------------------
function getsnoop_test()
{
	global $rootdir,$user;
	if (file_exists("$rootdir/pwusers/$user/stats/snoop.txt"))
	{
		$snoop_a=file("$rootdir/pwusers/$user/stats/snoop.txt");
		foreach($snoop_a as $snoop)
		{
			$snoop=trim($snoop);
			$snoopline=explode(':',$snoop);
			if ($snoopline[0]!=$user &&
				trim($snoopline[0])
				)
			{
				if ($snoopline[1][0]=='1') $snooptime=$snoopline[1];
				elseif ($snoopline[2]) $snooptime=$snoopline[2];
				else $snooptime='';
				
				if ($snooptime > $snoop_times[$snoopline[0]]) $snoop_times[$snoopline[0]]=$snooptime;
			}
		}

		$snoopers=array_unique(array_keys($snoop_times));
		$lastview=lastview($snoopers);

		@arsort($snoop_times);
		if ($snoop_times)
		foreach($snoop_times as $snooper=>$snooptime)
		{
			$snooper_key=array_search($snooper,$snoopers);
			// for very old snoops, we should test them once a day to see if they're
			// still valid.
			// we don't, though.

				$snoop_tc=$snooptime;
				
				if ($snooptime)
					$snooptime=formattime($snooptime);

				$excerpt=snoop_get_excerpt($snooper);
	
//				$lastview[$snooper=lastview($snooper);
				if ($lastview[$snooper_key]<$snoop_tc) $content.="<b>";
				$content.="<a href='/read/$snooper'>$snooper</a> $snooptime\n";
				if ($lastview<$snoop_tc) $content.="</b>\n";
				$content.=" <span style='font-size: smaller;'>[ <a href='/snoop/ignore/$snooper'>x</a> ]</span>\n";
				$content.="<br/>\n\n";
				$content.="$excerpt<br/><br/>\n";
		}

	}

	if ($content) $content="<h2>snoop</h2><b>not an exact science</b><br>\n\n$content<hr>\n";
	else $content="\n\n<hr>";
return $content;
}

function snoop_get_excerpt($snooper)
{
	global $user,$rootdir;
	if (@filemtime("$rootdir/pwusers/$user/stats/$snooper.excerpt")<(time()-24*3600))
	{
		list($snoop_username,$server)=explode('@',get_real_plan_location($snooper));
		if (!$server) $server='planwatch.org';
		
		$queryitems=array("$snoop_username"=>"string","snoop_tester"=>"string",0=>"boolean");
		$plan=xmlrpc_query($server,'planworld.user.getPlan',$queryitems);
		preg_match_all("/.+$user.+/",$plan,$matches);
		$matches=implode(' ... ',$matches[0]);
		$matches=addlinks(addplanlinks(substr($matches,0,255)));
		file_write_contents("$rootdir/pwusers/$user/stats/$snooper.excerpt",$matches);
	}
	else $matches=stripslashes(stripslashes(file_read_contents("$rootdir/pwusers/$user/stats/$snooper.excerpt")));
	if (!trim($matches)) $matches='<i>this is probably not a valid snoop.</i>';

return $matches;
}

function xmlrpc_query($server='planwatch.org',$message,$parameters)
{
	$f=new xmlrpcmsg($message);
	foreach($parameters as $value=>$type)
	{
//		echo "$value, $type<br/>\n";
		$f->addParam(new xmlrpcval($value, $type));
	}

	$nodeinfo=getnodeinfo($server);
	$c=new xmlrpc_client($nodeinfo['directory'], $nodeinfo['server'], 80);
	$c->setDebug(0);
	$r=$c->send($f);

return xmlrpc_decode($r->value());
}

output("Snoop",getsnoop_test());

?>