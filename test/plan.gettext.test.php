<html>
<head><title>xml-rpc test suite</title></head>
<body>
<?php
include_once("xmlrpc.inc");

/*
//exec("rm -f $rootdir/pwusers/stats/snoop*.txt");
//echo "deleted";
	$nodeinfo=getnodeinfo('@amherst.edu');
	$f=new xmlrpcmsg('planworld.snoop.clear');
    $f->addParam(new xmlrpcval("cacheuser@planwatch.org",'string'));
//    $f->addParam(new xmlrpcval("josh(testing xmlrpc)",'string'));
//    $f->addParam(new xmlrpcval(0,'boolean'));
	$c=new xmlrpc_client($nodeinfo['directory'], $nodeinfo['server'], $nodeinfo['port']);
	$c->setDebug(1);
	$r=$c->send($f);

	if (!$r) { die("send failed"); }
	$v=$r->value();
	if (!$r->faultCode()) {
		if ($v->kindOf()=='scalar') print $v->scalarval() . "<BR>";
		if ($v->kindOf()=='array' || $v->kindOf()=='struct')
		{
			$arr=xmlrpc_decode($v);
//			foreach($arr as $val) echo "<br>$val";
			echo "<pre>";
			var_dump($arr);
			echo "</pre>";
		}
	} else {
	print "Fault: ";
	print "Code: " . $r->faultCode() . 
		" Reason '" .$r->faultString()."'<BR>";
	}
*/
/*
$localuser='jwdavidson';
$remoteuser='Josh - API Testing';
$nodeinfo=planworld_node_getinfo('@beta.planwatch.org');


	$f=new xmlrpcmsg('plan.getText');
	$f->addParam(new xmlrpcval($localuser, "string"));
	$f->addParam(new xmlrpcval($remoteuser, "string"));
	$f->addParam(new xmlrpcval(TRUE, "boolean"));

	$c=new xmlrpc_client($nodeinfo['directory'], $nodeinfo['server'], $nodeinfo['port']);
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


*/
$nodeinfo=planworld_node_getinfo('@flickr');

	$f=new xmlrpcmsg('flickr.people.findByUsername');
	$f->addParam(new xmlrpcval("4038142368f7aee684284406e0db6eda", $XMLRPCString));
	$f->addParam(new xmlrpcval("jwdavidson", "string"));
//	$f->addParam(new xmlrpcval(TRUE, "boolean"));

	$c=new xmlrpc_client($nodeinfo['directory'], $nodeinfo['server'], $nodeinfo['port']);
	$c->setDebug(1);

	$r=$c->send($f);

	echo "<pre>";
	print_r($r);
	echo "</pre>";

	
	if (!$r) { die("send failed"); }
	$v=$r->value();
	if (!$r->faultCode()) {
		if ($v->kindOf()=='scalar') print $v->scalarval() . "<br/>";
		if ($v->kindOf()=='array')
		{
			$arr=xmlrpc_decode($v);
			foreach($arr as $val) echo "<br/>$val";
		}
	} else {
	print "Fault: ";
	print "Code: " . $r->faultCode() . 
		" Reason '" .$r->faultString()."'<br/>";
	}


/*    
$xml_user='testuser31';
$xml_pass='test';
$xml_id='asdfadf';
$nodeinfo=getnodeinfo('@planwatch.org');
$content='testing the response of blogger.newPost';

	$f=new xmlrpcmsg('blogger.newPost');
	$f->addParam(new xmlrpcval($xml_id, "string"));
	$f->addParam(new xmlrpcval($xml_user, "string"));
	$f->addParam(new xmlrpcval($xml_user, "string"));
	$f->addParam(new xmlrpcval($xml_pass, "string"));
	$f->addParam(new xmlrpcval($content, "string"));
    $f->addParam(new xmlrpcval(TRUE,"boolean"));
    
	$c=new xmlrpc_client($nodeinfo['directory'], $nodeinfo['server'], $nodeinfo['port']);
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
*/
/*

echo "<br><hr><br>";

//----------------------------------------------------------------------------------------

	echo "planworld.send.sendMessage";
	$f=new xmlrpcmsg('planworld.send.sendMessage');
	$f->addParam(new xmlrpcval("jwdavidson@planwatch.org", "string"));
	$f->addParam(new xmlrpcval("jwdavidson", "string"));
	$f->addParam(new xmlrpcval("this is the thing that is", "string"));

	$c=new xmlrpc_client("/backend/index.php", "planworld.net", 80);
	$c->setDebug(0);
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

echo "<br><hr><br>";
*/

//----------------------------------------------------------------------------------------
/*
//----------------------------------------------------------------------------------------

	echo "system.listMethods";
	$f=new xmlrpcmsg('system.listMethods');
	$f->addParam(new xmlrpcval("planworld.archive.get", "string"));

	$c=new xmlrpc_client("/backend/", "planworld.net", 80);
	$c->setDebug(0);
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

echo "<br><hr><br>";

//----------------------------------------------------------------------------------------
*/
/*
	echo "stats.getnumusers";
	$f=new xmlrpcmsg('stats.getNumUsers');

	$c=new xmlrpc_client("/backend/", "planwatch.org", 80);
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
echo "<br><hr><br>";
//----------------------------------------------------------------------------------------

	echo "stats.getNumPlans";
	$f=new xmlrpcmsg('stats.getNumPlans');

	$c=new xmlrpc_client("/backend/", "planwatch.org", 80);
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

	echo "users.getLastUpdate<br>";
	$f=new xmlrpcmsg('users.getLastUpdate');
	$ar=array(0=>new xmlrpcval("lebaer00",'string'));
	$arf=new xmlrpcval($ar,'struct');
//	$arf=new xmlrpcval('jlodom','string');
	$f->addParam($arf);

	$c=new xmlrpc_client("/planworld/backend/", "note.amherst.edu", 80);
	
	echo $c;
	
	$c->setDebug(1);
	$r=$c->send($f);
	
	if (!$r) { die("send failed"); }
	$v=$r->value();
	if (!$r->faultCode()) {
		if ($v->kindOf()=='scalar') print $v->scalarval() . "<BR>";
		if ($v->kindOf()=='array' || $v->kindOf=='struct')
		{
			$arr=xmlrpc_decode($v);
			foreach($arr as $val) echo "<br>$val";
		}
	} else {
	print "Fault: ";
	print "Code: " . $r->faultCode() . 
		" Reason '" .$r->faultString()."'<BR>";
	}
*/

?>
</body>
</html>
