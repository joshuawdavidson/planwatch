<?php
include_once('xmlrpc.inc');
$nodeplanusers=array("jwdavidson","jlodom00");
$node='@planwatch.org';

function get_plantime_xmlrpc($nodeplanusers,$node)
{
          
    foreach($nodeplanusers as $i=>$plan)
    {
    	$nodeplanusers[$i]=str_replace('@note.amherst.edu','',trim($nodeplanusers[$i]));
    	$xmlarray[$i]=new xmlrpcval($nodeplanusers[$i],'string');
    }
    $sendarray=new xmlrpcval($xmlarray,"array");
    
    unset($nodeplanusers);
    unset($xmlarray);
    
    $f=new xmlrpcmsg('users.getLastUpdate');
    $f->addParam($sendarray);
    
    $nodeinfo=getnodeinfo($node);
    
    $c=new xmlrpc_client($nodeinfo['directory'],$nodeinfo['server'],$nodeinfo['port']);
    
    $c->setDebug(0);
    $r=$c->send($f);
    if (!$r) { echo "send failed"; }
    else
    	if (!$r->faultCode())
    	{
    		$nodeplantimes=xmlrpc_decode($r->value());
    	}
    
    if (!$nodeplantimes) $nodeplantimes=array();
    foreach($nodeplantimes as $i=>$time)	
    {
    	echo "$i$node....".$time."<br>";
    }
}

time_test($nodeplanusers,$node);
?>
