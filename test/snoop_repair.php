<?php

include_once('plan_read.php');
include_once('snoop.php');
exec("ls $_SERVER[FILE_ROOT]/pwusers/",$userlist);
foreach($userlist as $i=>$user)
{
	if ($i<1 && plan_is_local($user))
	{
		plan_get_owner_info($user);
		$content.=$plan_data=plan_read_quiet($user);
		if ($plan_data!='no plan entries found.' && !strstr($plan_data,"has no plan"))
		{
			$content.="<h2>$i: <a href='/read/$user'>$user</a></h2>\n";
			$snooplist=snoop_find($plan_data);
	
//			$content.=smart_trim($plan_data,1024)."<br/>\n";
//			$content.=$plan_data;
			$content.="<strong>".implode(" ... ",$snooplist)."</strong>";
		/*
			if ($snooplist)
			foreach($snooplist as $snoop_target)
			{
				$snoop_target=plan_get_real_location($snoop_target);
				list($snoop_target_username,$snoop_target_node)=explode("@",$snoop_target);
				if (!$snoop_target_node) $snoop_target_node="planwatch.org";
				if (!strstr($snoop_target_username,'http://'))
					$content.="snoop_add_xmlrpc($snoop_target_username,\"@$snoop_target_node\",$user);<br/>";
			}
		*/
			$content.="<hr/>";
		}
		unset($plan_data,$snooplist,$snoop_target,$snoop_target_username,$snoop_target_node);
	}
}
output("Snoop Repair",$content);