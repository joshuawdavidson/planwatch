<?php

function addsnoop_xmlrpc($rpcuser,$host)
{
	$hostinfo=array("port"=>80,"server"=>"planwatch.org","directory"=>"/backend/");
	$f=new xmlrpcmsg('snoop.addReference');
	$f->addParam(new xmlrpcval($rpcuser, "string"));
	$f->addParam(new xmlrpcval("fakeuser2@note", "string"));


	$c=new xmlrpc_client($hostinfo["directory"], $hostinfo["server"], $hostinfo["port"]);
	$c->setDebug(1);
	$r=$c->send($f);
	
	if (!$r) { die("send failed"); }
	$v=$r->value();
	if (!$r->faultCode()) {
		if ($v->kindOf()=='scalar') print $v->scalarval() . "<BR>";
		if ($v->kindOf()=='array')
		{
			$arr=xmlrpc_decode($v);
			foreach($arr as $val) echo "<br>$val";
		}
	} else {
	print "Fault: ";
	print "Code: " . $r->faultCode() . 
		" Reason '" .$r->faultString()."'<BR>";
	}
}

function removesnoop_xmlrpc($rpcuser,$host)
{
	$hostinfo=array("port"=>80,"server"=>"planwatch.org","directory"=>"/backend/");
	$f=new xmlrpcmsg('snoop.removeReference');
	$f->addParam(new xmlrpcval($rpcuser, "string"));
	$f->addParam(new xmlrpcval("fakeuser2@note", "string"));


	$c=new xmlrpc_client($hostinfo["directory"], $hostinfo["server"], $hostinfo["port"]);
	$c->setDebug(1);
	$r=$c->send($f);
	
	if (!$r) { die("send failed"); }
	$v=$r->value();
	if (!$r->faultCode()) {
		if ($v->kindOf()=='scalar') print $v->scalarval() . "<BR>";
		if ($v->kindOf()=='array')
		{
			$arr=xmlrpc_decode($v);
			foreach($arr as $val) echo "<br>$val";
		}
	} else {
	print "Fault: ";
	print "Code: " . $r->faultCode() . 
		" Reason '" .$r->faultString()."'<BR>";
	}
}

removesnoop_xmlrpc("testuser","planwatch.org");
?>