<?php
include_once('/home/planwatc/public_html/backend/xmlrpc.inc');

echo "\n---------------------------\n\n##### times_crossnode #####\n";

//exec("ls $_SERVER[FILE_ROOT]/stats/*planusers.txt",$nodelist);
foreach($crossnode_array as $node=>$nodeplanusers)
{
	if ($node)
	{
		echo "\n---------------------------\n\n##### $node #####\n";

		foreach($nodeplanusers as $i=>$plan)
		{
			if (strpos($plan,'@')) list($plan,$junk)=explode('@',$plan);
			if (strpos($plan,'.')) list($plan,$junk)=explode('.',$plan);
			$xmlarray[$i]=new xmlrpcval($plan,'string');
		}
		$sendarray=new xmlrpcval($xmlarray,"array");

		unset($nodeplanusers);
		unset($xmlarray);

		$f=new xmlrpcmsg('users.getLastUpdate');
		$f->addParam($sendarray);

		$nodeinfo=_planworld_node_getinfo($node);

		$c=new xmlrpc_client($nodeinfo['directory'],$nodeinfo['server'],$nodeinfo['port']);
		
		$c->setDebug(0);
		$r=$c->send($f);
		if (!$r) { echo "XML-RPC send failed trying to connect to $node\n"; }
		else
			if (!$r->faultCode())
			{
				$nodeplantimes=xmlrpc_decode($r->value());
			}

		if ($nodeplantimes)
		foreach($nodeplantimes as $i=>$time)	
		{
			echo "$i@$node: $time (".date("F jS h:ia",$time).")\n";
			$filestring.="!!!$i@$node...$time";
			$crossnode_times_array["$i@$node"]=$time;
		}
	}
	unset($nodeplantimes);
}


$file=fopen("$_SERVER[FILE_ROOT]/stats/times_crossnode.txt",'w');
fwrite($file,$filestring);
fclose($file);

$file=fopen("$_SERVER[FILE_ROOT]/stats/times_crossnode.dat",'w');
fwrite($file,serialize($crossnode_times_array));
fclose($file);
?>
