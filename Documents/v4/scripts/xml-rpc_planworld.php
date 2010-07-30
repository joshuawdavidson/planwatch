<?php

/*
PLANWORLD.XML-RPC.php
contains the implementation of the planworld API
*/
include_once('user_info_functions.php');

////////////////////// UTILITY FUNCTIONS //////////////////////////////////////

//performs a name-to-id hash
function getuserid($username)
{
	foreach($username as $letter)
	{
		$userid.=ord($letter)-54;	
	}
return "1".$userid;
}

//undoes the name-to-id hash
function getusername($userid)
{
	$str=substr($userid,1);
	for ($i=0;$i<(strlen($userid)/2);$i+=2)
	{
		$username.=chr(($userid[$i].$userid[$i+1])+54);
	}
return $username;
}


// PLANWORLD API
//------------------------------------------------------------------------------
// based on the planworld xml-rpc API authored by snfitzsimmon@note.amherst.edu
// maintainer: josh davidson, help@planwatch.org

$planworld_plan_gettext_sig=array(
	// regular plan view
	array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean),
	// archives view
	array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean, $xmlrpcString)
	);
$planworld_plan_gettext_doc='when passed a planwatch.org username in $localuser, along with a remote username
in $remoteuser and a snitch value in the bool $snitch, a base64 response containing the plan of
$localuser will be returned.';

// planworld_plan_gettext()
//
// xml_rpc interface to plan_read.php:plan_read_local()
//------------------------------------------------------------------------------
function planworld_plan_gettext($m) {
	global $xmlrpcerruser;
	include_once('plan_read.php');
	$err="";
	// get the param values (should add integrity checking here)
	$lu=$m->getParam(0);
	$ru=$m->getParam(1);
	$sn=$m->getParam(2);
//	$ar=$m->getParam(3);

	$localuser  = $lu->scalarval();
	$remoteuser = $ru->scalarval();
	$snitch     = $sn->scalarval();
	list($localuser,$archives)=explode("___",$localuser);
//	$archives   = $ar->scalarval();

	// TODO:(v4.5) think about what to do with the archives variable

	$localuser=str_replace("@planwatch.org",'',$localuser);
	plan_get_owner_info($localuser);

	$_SERVER['USER']=$remoteuser;
	$_SERVER['USERINFO_ARRAY']['snitchlevel']=$snitch+1;
	$_SERVER['REMOTENODE']=strstr($remoteuser,'@');
	if ($archives=='archives')
	{
		$plan=plan_read_archives($localuser);
	}
	else $plan=plan_read($localuser,$archives);

	if (isset($_SERVER['PLANOWNER_INFO'])) $plan.="<!--planowner info set-->";
	$plan="<!--plan styles--><style type='text/css'>{$_SERVER['PLANOWNER_INFO']['css']}</style>".$plan;
    
	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return new xmlrpcresp(new xmlrpcval($plan,'base64'));
	}
}


$planworld_whois_sig_a=array(array($xmlrpcString, $xmlrpcString));
$planworld_whois_sig_b=array(array($xmlrpcString));
$planworld_whois_sig=array($planworld_whois_sig_a,$planworld_whois_sig_b);

$planworld_whois_doc='when passed a planwatch.org username in $localuser, along with a remote username
in $remoteuser, a base64 response containing the bio of $localuser will be returned.';

function planworld_whois($m) {
	global $xmlrpcerruser;
	include_once('bios.php');
	$err="";
	// get the param values (should add integrity checking here)
	$lu=$m->getParam(0);
	$ru=$m->getParam(1);

	$localuser=$lu->scalarval();
	$remoteuser=$ru->scalarval();

	$_SERVER['USER']=$remoteuser;
	$_SERVER['REMOTE']=TRUE;

	$localuser=str_replace("@planwatch.org",'',$localuser);
	planowner_get_info($localuser);

	$bio=getbio($localuser,'both');
    
	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return new xmlrpcresp(new xmlrpcval($plan,'base64'));
	}
}

$getlastaction_sig_string=array($xmlrpcInt, $xmlrpcString);
$getlastaction_sig_int=array($xmlrpcInt, $xmlrpcInt);
$getlastaction_sig_array=array($xmlrpcArray, $xmlrpcArray);
$getlastaction_sig_struct=array($xmlrpcStruct, $xmlrpcStruct);
$getlastaction_sig=array($getlastaction_sig_array,$getlastaction_sig_string,$getlastaction_sig_int,$getlastaction_sig_struct);

$getlastaction_doc='when passed a string localusername, returns a unix timestamp of the last time the
given user read a plan (this is not strictly spec behavior, but this system doesn\'t track login
times because many users (including the admin) stay logged in all the time). if passed an 
array of usernames, it will return a matched array of last action times. Returns timecodes in eastern
time as per the planworld/xmlrpc spec as written by snfitzsimmon@note.amherst.edu';


function getlastaction($m) {
	global $xmlrpcerruser;
	$err="";
	// get the param values (should add integrity checking here)
	$ul=$m->getParam(0);

	if ($ul->kindOf()=='struct')
	{
		$arr=xmlrpc_decode($ul);
		foreach($arr as $i=>$username)
		{
			if (is_int($username)) $username=getusername($username);
			$lastact_a[$username]=user_get_last_action($username);
		}

		$return_a=xmlrpc_encode($lastact_a);
		$returnval=new xmlrpcresp($return_a);
	}
	if ($ul->kindOf()=='array')
	{
		$arr=xmlrpc_decode($ul);
		foreach($arr as $i=>$username)
		{
			if (is_int($username)) $username=getusername($username);
			$lastact_a[$i]=user_get_last_action($username);
		}

		$return_a=xmlrpc_encode($lastact_a);
		$returnval=new xmlrpcresp(new xmlrpcval($return_a,'array'));
	}
	if ($ul->kindOf()=='scalar')
	{
		if ($ul->scalartyp()=='int') $username=getusername($ul->scalarval());
		else $username=$ul->scalarval();
		list($username,$archives)=explode("___",$username);
		$lastact_val=user_get_last_action($username);
		$returnval=new xmlrpcresp(new xmlrpcval($lastact_val,'int'));
	}

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return $returnval;
	}
}


$getlastupdate_sig_string=array($xmlrpcInt, $xmlrpcString);
$getlastupdate_sig_int=array($xmlrpcInt, $xmlrpcInt);
$getlastupdate_sig_array=array($xmlrpcArray, $xmlrpcArray);
$getlastupdate_sig_struct=array($xmlrpcStruct, $xmlrpcStruct);
$getlastupdate_sig=array($getlastupdate_sig_array,$getlastupdate_sig_string,$getlastupdate_sig_int,$getlastupdate_sig_struct);

$getlastupdate_doc='takes a username, returns the last update time for that username';

function getlastupdate($m) {
	global $xmlrpcerruser;
	$err="";
	// get the param values (should add integrity checking here)
	$ul=$m->getParam(0);

	if ($ul->kindOf()=='struct' || $ul->kindOf()=='array')
	{
		$arr=xmlrpc_decode($ul);
		foreach($arr as $i=>$username)
		{
			if (is_int($username)) { $arr[$i]=getusername($username); }
		}
		$lastupdate_a=plan_get_last_update($arr);

		foreach($lastupdate_a as $i=>$update)
		{
			$returnarray[$arr[$i]]=$update;
		}
		
		$return_a=xmlrpc_encode($returnarray);
		$returnval=new xmlrpcresp($return_a);
//		mail("joshuawdavidson@gmail.com","update time request",$_SERVER['REMOTE_ADDR']."\n\n".print_r($ul,TRUE)."\n\n".print_r($returnarray,TRUE),"From: system@planwatch.org");
	}

	if ($ul->kindOf()=='scalar')
	{
		if ($ul->scalartyp()=='int') $username=getusername($ul->scalarval());
		else $username=$ul->scalarval();
		list($username,$archives)=explode("___",$username);
		$lastact_val=plan_get_last_update($username);
		$returnval=new xmlrpcresp(new xmlrpcval($lastact_val,'int'));
	}

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return $returnval;
	}
}

$getnumusers_sig=array(array($xmlrpcInt, $xmlrpcString, $xmlrpcInt),array($xmlrpcInt, $xmlrpcString),array($xmlrpcInt, $xmlrpcInt),array($xmlrpcInt));

$getnumusers_doc='will return the number of users (optionally those who have logged in in the last
$recent seconds)';

function getnumusers($m) {
	global $xmlrpcerruser;
	$err="";
	$usercount=0;

	// get the param values (should add integrity checking here)

	// ignored, as all users who have been created have logged in
	$allorloginstring=$m->getParam(0);
	$recent=$m->getParam(1);

	if ($recent) $threshhold=time()-($recent->scalarval());
	exec("ls -d $_SERVER[PWUSERS_DIR]/*",$userlist);
	if (!$threshhold || $threshhold==time()) $usercount=count($userlist);
	else
	{
		foreach($userlist as $user)
		{
			if (user_get_last_action(basename($user)) > $threshhold) $usercount+=1;
		}
	}

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return new xmlrpcresp(new xmlrpcval($usercount,'int'));
	}
}

$getnumplans_sig=array(array($xmlrpcInt, $xmlrpcInt),array($xmlrpcInt));

$getnumplans_doc='will return the number of plans (optionally those who have updated in in the last
$recent seconds)';

function getnumplans($m) {
	global $xmlrpcerruser;
	$err="";
	$usercount=0;

	// get the param values (should add integrity checking here)
	$recent=$m->getParam(0); // 

	if ($recent) $threshhold=time()-($recent->scalarval());
	
	exec("ls -d $_SERVER[PWUSERS_DIR]/*",$userlist);
	if (!$threshhold || $threshhold==time()) $returnval=count($userlist);
	else
	{
		foreach($userlist as $i=>$userline)
		{
			$userlist[$i]=basename($userline);
		}
		$plantimes=plan_get_last_update($userlist);
		foreach($plantimes as $i=>$updatetime)
		{
			if ($updatetime > $threshhold) $usercount+=1;
		}
		$returnval=$usercount;
	}

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return new xmlrpcresp(new xmlrpcval($returnval,'int'));
	}
}

$users_getid_sig=array(array($xmlrpcInt, $xmlrpcString));

$users_getid_doc='returns the userid of the user passed';

function users_getid($m) {
	global $xmlrpcerruser;
	$err="";

	// get the param values (should add integrity checking here)
	$un=$m->getParam(0); // 
	$username=$un->scalarval();
	
	list($username,$archives)=explode("___",$username);
	
	if (file_exists("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat")) $userid=getuserid($username);
	else $userid=0;
	
	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return new xmlrpcresp(new xmlrpcval($userid,'int'));
	}
}

$planworld_online_sig=array(array($xmlrpcArray));
$planworld_online_doc='returns a list of users that are currently online';

function planworld_online($m) {
	global $xmlrpcerruser;
	$err="";
	$onlineusers=array();
		
	exec("ls -d $_SERVER[PWUSERS_DIR]/*",$userlist);

	$threshhold=time()-(18600);

	foreach($userlist as $user)
	{
		$user=ltrim(rtrim(basename($user)));
		$last=user_get_last_action($user);
		if ($last > $threshhold) $onlineusers[]=new xmlrpcval($user,'string');
	}

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return new xmlrpcresp(new xmlrpcval($onlineusers,'array'));
	}
}

$planworld_user_list_sig=array(array($xmlrpcArray));
$planworld_user_list_doc='returns a list of users that are currently online';

function planworld_user_list($m) {
	global $xmlrpcerruser;
	$err="";
	$onlineusers=array();
		
	exec("ls -d $_SERVER[PWUSERS_DIR]/*",$userlist);

	$threshhold=time()-(18600);

	foreach($userlist as $user)
	{
		$user=ltrim(rtrim(basename($user)));
		$onlineusers[]=new xmlrpcval($user,'string');
	}

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	return new xmlrpcresp(new xmlrpcval($onlineusers,'array'));
	}
}

$snoopadd_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString));

$snoopadd_doc='adds a snoop reference from a remote node';

function snoopadd($m) {
	global $xmlrpcerruser;
	include_once('snoop.php');
	$err="";
	// get the param values (should add integrity checking here)
	$lu=$m->getParam(0);
	$ru=$m->getParam(1);

	$localuser=$lu->scalarval();
	$remoteuser=$ru->scalarval();

	$success=snoop_add_local($localuser,$remoteuser);

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	header("http/1.1 200 ok");
	return new xmlrpcresp(new xmlrpcval($success,'boolean'));
	}
}

$snooprem_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString));

$snooprem_doc='removes a snoop reference from a remote node';

function snooprem($m) {
	global $xmlrpcerruser;
	include_once('snoop.php');
	$err="";
	// get the param values (should add integrity checking here)
	$lu=$m->getParam(0);
	$ru=$m->getParam(1);

	$localuser=$lu->scalarval();
	$remoteuser=$ru->scalarval();

	$success=snoop_remove_local($localuser,$remoteuser);

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	header("http/1.1 200 ok");
	return new xmlrpcresp(new xmlrpcval($success,'boolean'));
	}
}

$snoopclear_sig=array(array($xmlrpcBoolean));

$snoopclear_doc='clears all snoop references from a specific node';

function snoopclear($m) {
	global $xmlrpcerruser;
	include_once('snoop.php');
	$err="";
	// get the param values (should add integrity checking here)
	$lu=$m->getParam(0);
	$ru=$m->getParam(1);

	$localuser=$lu->scalarval();
	$remoteuser=$ru->scalarval();

	$success=removesnoop_local($localuser,$remoteuser);

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	// with the state name
	header("http/1.1 200 ok");
	return new xmlrpcresp(new xmlrpcval($success,'boolean'));
	}
}


$plan_send_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$plan_send_doc='receives a send message from another node, in the form '
	.'(string sender, string recipient, string message). returns (boolean success). ';

function plan_send($m)
{
	$sender    = $m->getParam(0);
	$recipient = $m->getParam(1);
	$message   = $m->getParam(2);

	$sender    = $sender->scalarval();
	$recipient = $recipient->scalarval();
	$message   = $message->scalarval();

	list($recipient,$archives)=explode("___",$recipient);
	include_once('send.php');
	$success   = send_add_local($sender,$recipient,$message);

	// if we generated an error, create an error return response
	if ($err)
	{
		return new xmlrpcresp(0, $xmlrpcerruser, $err);
	}
	else
	{
		// otherwise, we create the right response
		// with the state name
		header("http/1.1 200 ok");
		return new xmlrpcresp(new xmlrpcval($success,'boolean'));
	}
}

?>