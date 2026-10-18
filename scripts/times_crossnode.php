<?php

header("Content-type: text/plain");

date_default_timezone_set("America/New_York");
$_ENV['PWUSERS_DIR']="/var/www/planwatch.org/pwusers";
$_ENV['FILE_ROOT']="/var/www/planwatch.org/public_html";

if(!empty($_GET['debug'])) { $debug = TRUE; }
else $debug=FALSE;
global $debug;

include_once("$_ENV[FILE_ROOT]/backend/xmlrpc.inc");

echo "\n---------------------------\n\n##### times_crossnode #####\n";

$filestring="";

$crossnode_array = unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/plans_crossnode.dat"));

foreach($crossnode_array as $node=>$nodeplanusers)
{
	$nodeinfo=_planworld_node_getinfo($node);
	if($debug)
	{
		echo "$node<br>";
		print_r($nodeinfo);
		echo "<hr>";
	}
	$nodeplantimes=array();
	if(isset($nodeinfo['server']))
	{
		echo "\n---------------------------\n\n##### $node #####\n";

		$nodeplanusers=array_values($nodeplanusers);
		$sections=ceil(count($nodeplanusers)/200);
		if($debug) echo "in $sections sections\n201: $nodeplanusers[201]\n";
		for($j=0;$j<$sections;$j++)
		{
			$sectionmod=200*$j;
			if($debug) echo "ROUND $j\nmod $sectionmod\n";
			for($i=0; $i<=200; $i++)	//		foreach($nodeplanusers as $i=>$plan)
			{
				$modi=$i+$sectionmod;
				if($debug) echo "$modi\n";
				if(isset($nodeplanusers[$modi]))
				{
					$plan=$nodeplanusers[$modi];
					if($debug) echo "$modi: $plan added\n";
					if (strpos($plan,'@')) list($plan,$junk)=explode('@',$plan);
					if (strpos($plan,'.')) list($plan,$junk)=explode('.',$plan);
					$xmlarray[]=new xmlrpcval($plan,'string');
				}
			}
			$sendarray=new xmlrpcval($xmlarray,"struct");

//			unset($nodeplanusers);
			unset($xmlarray);
			unset($f,$r,$c);

			$f=new xmlrpcmsg('users.getLastUpdate');
			$f->addParam($sendarray);

			$c=new xmlrpc_client($nodeinfo['directory'],$nodeinfo['server'],$nodeinfo['port'],$nodeinfo['method']);

			$c->setDebug($debug);
			$r=$c->send($f);
			if (!$r) { echo "XML-RPC send failed trying to connect to $node\n"; }
			elseif (!$r->faultCode())
			{
				$nodeplantimes=array_merge($nodeplantimes,php_xmlrpc_decode($r->value()));
			}
		}

		if (is_array($nodeplantimes))
		{
			foreach($nodeplantimes as $i=>$time)
			{
				echo "$i@$node: $time (".date("F jS h:ia",$time).")\n";
				$filestring.="!!!$i@$node...$time";
				$crossnode_times_array["$i@$node"]=$time;
			}

			$file=fopen("$_ENV[FILE_ROOT]/stats/times_crossnode_$node.dat",'w');
			fwrite($file,serialize($nodeplantimes));
			fclose($file);
		}
	}
	else echo "no host for [$node]";

	unset($nodeplantimes);


}


$file=fopen("$_ENV[FILE_ROOT]/stats/times_crossnode.dat",'w');
fwrite($file,serialize($crossnode_times_array));
fclose($file);

function _planworld_node_getinfo($node='note')
{
	global $debug;

	if(empty($node)) { $node="@planwatch.org"; }
	if ($node[0]!='@') { $node='@'.$node; }
	$node=strtolower($node);

	if($debug) { echo "times_crossnode: _pw_node_getinfo: $node<br>\n"; }

	$pwo_aliases = array("@pwo","@chippu","@beta");
	$pwn_aliases = array("@pwn");
	$amherst_aliases = array("@note.amherst.edu","@amherst.edu","@krypton","@note","@s.amherst.edu");
	$node=str_replace($pwo_aliases,"@planwatch.org",$node);
	$node=str_replace($pwn_aliases,"@planworld.net",$node);
	$node=str_replace($amherst_aliases,"@planworld.amherst.edu",$node);

	if($debug) { echo "times_crossnode: _pw_node_getinfo: $node<br>\n"; }

	switch($node){
		case "@planworld.amherst.edu":
			return array("port"=>443,"server"=>"planworld.amherst.edu","directory"=>"/backend/index.php","method"=>"https");
			break;
		case "@planworld.net":
			return array("port"=>80,"server"=>"planworld.net","directory"=>"/backend/","method"=>"http");
			break;
		case "@planwatch.org":
			return array("port"=>443,"server"=>"planwatch.org","directory"=>"/backend/","method"=>"https");
			break;
		case '@flickr':
			return array("port"=>80,"server"=>"www.flickr.com","directory"=>"/services/xmlrpc/");
			break;

		default: return FALSE; break;
	}


}


?>