<?php

function get_plantime_xmlrpc_test($plans,$host)
{
	global $rootdir;
	include_once('xmlrpc.inc');
	$hostinfo=getnodeinfo($host);

    foreach($plans as $plan)
    {
        echo $plan;
    	$f=new xmlrpcmsg('users.getLastUpdate');
        $f->addParam(new xmlrpcval($plan,'string'));
    	$c=new xmlrpc_client($hostinfo['directory'], $hostinfo['server'], $hostinfo['port']);
    	$c->setDebug(1);
    	$r=$c->send($f);
    	$v=$r->value();
    
    	if (!$r->faultCode()) {
    		if ($v->kindOf()=='int')
    		{
    			$time=$v->value();
    			echo "<br>$plan: $time";
    		}
    	}
    }
	
}

get_plantime_xmlrpc_test(array("jwdavidson@planwatch.org","jlodom00"),"@planwatch.org");

?>