<?php
/*
backend/INDEX.php

implements the planworld xml-rpc spec as written by snfitzsimmon@note.amherst.edu

this implementation written by Josh Davidson (help@planwatch.org) using code from
usefulinc.com

xml-rpc is a relative of SOAP. more information can be found at www.xml-rpc.com, a
UserLand Software site.

update 9 feb 2003: includes preliminary blogger API support and metaWebLog api support
for posting updates. this code is written from scratch by josh@planwatch.org using api
docs at http://plant.blogger.com/api/index.html and http://www.xmlrpc.com/metaWeblogApi

update 22 may 2005: added send support.

TODO:(v4.1) finish blogger API support
TODO:(v4.1) finish metaWebLog API support
*/

include_once("xmlrpc.inc");
include_once("xmlrpcs.inc");
include_once("xml-rpc_blogger.php");
include_once("xml-rpc_planworld.php");


//instantiate the server
$s=new xmlrpc_server( array( 

		// PLANWORLD API

       "planworld.send.sendMessage" => 
							 array("function" => "plan_send",
										 "signature" => $plan_send_sig,
										 "docstring" => $plan_send_doc),

       "planworld.user.plan_read" => 
							 array("function" => "planworld_plan_gettext",
										 "signature" => $planworld_plan_gettext_sig,
										 "docstring" => $planworld_plan_gettext_doc),
       "planworld.plan.getContent" => 
							 array("function" => "planworld_plan_gettext",
										 "signature" => $planworld_plan_gettext_sig,
										 "docstring" => $planworld_plan_gettext_doc),
       "plan.getText" => 
							 array("function" => "planworld_plan_gettext",
										 "signature" => $planworld_plan_gettext_sig,
										 "docstring" => $planworld_plan_gettext_doc),
       "planworld.user.getLastLogin" => 
							 array("function" => "getlastaction",
										 "signature" => $getlastaction_sig,
										 "docstring" => $getlastaction_doc),
       "users.getLastLogin" => 
							 array("function" => "getlastaction",
										 "signature" => $getlastaction_sig,
										 "docstring" => $getlastaction_doc),
       "planworld.user.getLastUpdate" => 
							 array("function" => "getlastupdate",
										 "signature" => $getlastupdate_sig,
										 "docstring" => $getlastaction_doc),
       "users.getLastUpdate" => 
							 array("function" => "getlastupdate",
										 "signature" => $getlastupdate_sig,
										 "docstring" => $getlastaction_doc),
       "planworld.stats.getNumUsers" => 
							 array("function" => "getnumusers",
										 "signature" => $getnumplans_sig,
										 "docstring" => $getlastaction_doc),						
       "planworld.stats.getNumUsers" => 
							 array("function" => "getnumusers",
										 "signature" => $getnumplans_sig,
										 "docstring" => $getlastaction_doc),						
       "stats.getNumUsers" => 
							 array("function" => "getnumusers",
										 "signature" => $getnumplans_sig,
										 "docstring" => $getlastaction_doc),						
       "stats.getNumPlans" => 
							 array("function" => "getnumplans",
										 "signature" => $getnumplans_sig,
										 "docstring" => $getnumplans_doc),
       "users.getID" => 
							 array("function" => "users_getid",
										 "signature" => $users_getid_sig,
										 "docstring" => $users_getid_doc),
       "planworld.user.list" => 
							 array("function" => "planworld_user_list",
										 "signature" => $planworld_user_list_sig,
										 "docstring" => $planworld_user_list_doc),
       "planworld.user.online" => 
							 array("function" => "planworld_online",
										 "signature" => $planworld_online_sig,
										 "docstring" => $planworld_online_doc),
       "planworld.online" => 
							 array("function" => "planworld_online",
										 "signature" => $planworld_online_sig,
										 "docstring" => $planworld_online_doc),
       "planworld.snoop.add" => 
							 array("function" => "snoopadd",
										 "signature" => $snoopadd_sig,
										 "docstring" => $snoopadd_doc),
       "snoop.addReference" => 
							 array("function" => "snoopadd",
										 "signature" => $snoopadd_sig,
										 "docstring" => $snoopadd_doc),
       "planworld.snoop.remove" => 
							 array("function" => "snooprem",
										 "signature" => $snooprem_sig,
										 "docstring" => $snooprem_doc),
       "snoop.removeReference" => 
							 array("function" => "snooprem",
										 "signature" => $snooprem_sig,
										 "docstring" => $snooprem_doc),
       "planworld.snoop.clear" => 
							 array("function" => "snoopclear",
										 "signature" => $snoopclear_sig,
										 "docstring" => $snoopclear_doc),
       "snoop.clear" => 
							 array("function" => "snoopclear",
										 "signature" => $snoopclear_sig,
										 "docstring" => $snoopclear_doc),
       "planworld.whois" => 
							 array("function" => "planworld_whois",
										 "signature" => $planworld_whois_sig,
										 "docstring" => $planworld_whois_doc),
		// BLOGGER API
		
       "blogger.newPost" =>
							 array("function" => "blogger_newPost",
										 "signature" => $blogger_newPost_sig,
										 "docstring" => $blogger_newPost_doc),
       "blogger.editPost" => 
							 array("function" => "blogger_editPost",
										 "signature" => $blogger_editPost_sig,
										 "docstring" => $blogger_editPost_doc),

       "blogger.getUsersBlogs" => 
							 array("function" => "blogger_getUsersBlogs",
										 "signature" => $blogger_getUsersBlogs_sig,
										 "docstring" => $blogger_getUsersBlogs_doc),

       "blogger.getUserInfo" => 
							 array("function" => "blogger_getUserInfo",
										 "signature" => $blogger_getUserInfo_sig,
										 "docstring" => $blogger_getUserInfo_doc),
       "blogger.getTemplate" => 
							 array("function" => "blogger_getTemplate",
										 "signature" => $blogger_getTemplate_sig,
										 "docstring" => $blogger_getTemplate_doc),
       "blogger.setTemplate" => 
							 array("function" => "blogger_setTemplate",
										 "signature" => $blogger_setTemplate_sig,
										 "docstring" => $blogger_setTemplate_doc),
       "blogger.getPost" => 
							 array("function" => "blogger_getPost",
										 "signature" => $blogger_getPost_sig,
										 "docstring" => $blogger_getPost_doc),
       "blogger.getRecentPosts" => 
							 array("function" => "blogger_getRecentPosts",
										 "signature" => $blogger_setRecentPosts_sig,
										 "docstring" => $blogger_setRecentPosts_doc),
       "blogger.deletePost" => 
							 array("function" => "blogger_deletePost",
										 "signature" => $blogger_deletePost_sig,
										 "docstring" => $blogger_deletePost_doc),
	   ));
?>