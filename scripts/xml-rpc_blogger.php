<?php

/*
BLOGGER.XML_RPC.php

support for the blogger API
*/

//////////////////// BLOGGER API //////////////////////////////////////////////

/*
blogger.newPost: Makes a new post to a designated blog. Optionally, will publish the blog after making the post.
blogger.editPost: Edits a given post. Optionally, will publish the blog after making the edit.
blogger.getUsersBlogs: Returns information on all the blogs a given user is a member of.
blogger.getUserInfo: Authenticates a user and returns basic user info (name, email, userid, etc.).

(see blogger api notes in this directory for more info)
*/
$blogger_newPost_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
$blooger_newPost_doc='Supports the blogger.newPost function for posting a plan update.
returns post id on success. see the Blogger API docs at http://plant.blogger.com/api/index.html
for more information.';


function blogger_newPost($m)
{
	$appkey=$m->getParam(0); // discarded
	$blogid=$m->getParam(1); // this should equal $username
	$username=$m->getParam(2);
	$password=$m->getParam(3);
	$content=$m->getParam(4);
	$publish=$m->getParam(5); // the inverse of $privacy on pw.o

	unset($appkey); // this is just to drive the point home that we aren't using appkey.
									// i suppose we could log the appkeys just for the heck of it.

	$blogid=$blogid->scalarval();
	$username=$username->scalarval();
	$password=$password->scalarval();
	$content=$content->scalarval();
	$publish=$publish->scalarval();
	
	if (!$publish) $update_privacy='.p';
	else $update_privacy='';
	// authentication stuff. this should get simpler one day.
	fingerprint_verify(fingerprint_get($username,$password));
	global $pwuserinfo,$userinfo,$preferences,$user,$pass;
	$pwuserinfo=getenv('PWUSERINFO');
	parse_str($pwuserinfo,$userinfo);
	parse_str($pwuserinfo,$preferences);
	$user=$username;
	$pass=$password;

	// the actual writing of the update
	include_once('setplan.php');
	$_SERVER['BLOGPOST']=TRUE;

	$postid=''.time();
	if(updateplan(".".$postid,$content,$update_privacy,0,$blogid));

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	return new xmlrpcresp(new xmlrpcval($postid,'string'));
		}
}

$blogger_editPost_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
$blooger_editPost_doc='Supports the blogger.editPost function for posting a plan update.
returns TRUE on success. see the Blogger API docs at http://plant.blogger.com/api/index.html
for more information.';


function blogger_editPost($m)
{
	$appkey=$m->getParam(0); // discarded
	$postid=$m->getParam(1); // this is the post to edit
	$username=$m->getParam(2);
	$password=$m->getParam(3);
	$content=$m->getParam(4);
	$publish=$m->getParam(5); // the inverse of $privacy on pw.o

	unset($appkey); // this is just to drive the point home that we aren't using appkey.
									// i suppose we could log the appkeys just for the heck of it.

	$postid=".".$postid->scalarval();
	$username=$username->scalarval();
	$password=$password->scalarval();
	$content=$content->scalarval();
	$publish=$publish->scalarval();

	
	if (!$publish) $update_privacy='.p';
	else $update_privacy='';
	// authentication stuff. this should get simpler one day.
	fingerprint_verify(fingerprint_get($username,$password));
	global $pwuserinfo,$userinfo,$preferences,$user,$pass;
	$pwuserinfo=getenv('PWUSERINFO');
	parse_str($pwuserinfo,$userinfo);
	parse_str($pwuserinfo,$preferences);
	$user=$username;
	$pass=$password;
	
	// the actual writing of the update
	include_once('setplan.php');
	$_SERVER['BLOGPOST']=TRUE;
	if(updateplan($postid,$content,$update_privacy,0,$blogid));

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	return new xmlrpcresp(new xmlrpcval(TRUE,$xmlrpcBoolean));
		}
}

/*
blogger.getUsersBlogs: Returns information on all the blogs a given user is a member of.
returns struct (1=>('blogid'=>$blogid, 'blogName'=>$blogName, 'url'=>$url))
# appkey (string): Unique identifier/passcode of the application sending the post. (See access info.)
# username (string): Login for the Blogger user who's blogs will be retrieved.
# password (string): Password for said username. 
*/
$blogger_getUsersBlogs_sig=array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$blogger_getUsersBlogs_doc='blogger.getUsersBlogs returns a struct containing all the plans for which valid user username, password
has write privs. for more info, consult the blogger API docs.';

function blogger_getUsersBlogs($m)
{
	$appkey=$m->getParam(0); // discarded
	$username=$m->getParam(1);
	$password=$m->getParam(2);

	unset($appkey);	// this is just to drive the point home that we aren't using appkey.
					// i suppose we could log the appkeys just for the heck of it.

	$username=$username->scalarval();
	$password=$password->scalarval();

	if (isvaliduser($username,$password))
		{
			 $planlist_a['blogid']=$username;
			 $planlist_a['blogName']="$username's plan";
			 $planlist_a['url']="http://planwatch.org/read/$username";
			 $xmlarray= new xmlrpcval(array(xmlrpc_encode($planlist_a)),'array');
			 $returnval=new xmlrpcresp($xmlarray);
		}
		else $err='not a valid user.';
	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	return $returnval;
		}
}

/*
blogger.getUserInfo: Authenticates a user and returns basic user info (name, email, userid, etc.).
returns a struct containing user's userid, firstname, lastname, nickname, email, and url.
# appkey (string): Unique identifier/passcode of the application sending the post. (See access info.)
# username (string): Login for the Blogger user who's blogs will be retrieved.
# password (string): Password for said username. 
*/
$blogger_getUserInfo_sig=array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$blogger_getUserInfo_doc='struct blogger.getUsersBlogs(string appkey, string username, string password) returns a struct containing user info
associated with valid username, password';

function blogger_getUserInfo($m)
{
	$appkey=$m->getParam(0); // discarded
	$username=$m->getParam(1);
	$password=$m->getParam(2);

	unset($appkey);	// this is just to drive the point home that we aren't using appkey.
					// i suppose we could log the appkeys just for the heck of it.

	$username=$username->scalarval();
	$password=$password->scalarval();

		if (user_is_valid($username,$password))
		{
			 parse_str(user_read_info($username),$userinfo);
		}

	list($firstname,$lastname)=explode(' ',$userinfo['real_name']);
	$returnlist['nickname']=new xmlrpcval($username,'string');
	$returnlist['userid']=new xmlrpcval($username,'string');
	$returnlist['firstname']=new xmlrpcval($firstname,'string');
	$returnlist['lastname']=new xmlrpcval($lastname,'string');
	$returnlist['nickname']=new xmlrpcval($userinfo['email'],'string');
	$returnlist['url']=new xmlrpcval("http://planwatch.org/read/$username",'string');

	$xmlarray= new xmlrpcval(array(xmlrpc_encode($returnlist)),'array');
	$returnval=new xmlrpcresp($xmlarray);

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	return $returnval;
		}
}

/*
blogger.getTemplate: Returns the main or archive index template of a given blog.
returns text of template as string or base64
params: appkey, blogid, username, password, templateType
# templateType (string): Determines which of the blog's templates will be returned. Currently, either "main" or "archiveIndex". 

blogger.setTemplate: Edits the main or archive index template of a given blog.
returns TRUE
params: appkey, blogid, username, password, template, templateType
# template (string): The text for the new template (usually mostly HTML). Must contain opening and closing <Blogger> tags, since they're needed to publish.

THESE ARE JUST DUMMY FUNCTIONS AT THE MOMENT.
AND PROBABLY WILL BE FOREVER UNLESS THEY NEED TO BE OTHERWISE.
*/
$blogger_getTemplate_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$blogger_getTemplate_doc='blogger.getTemplate returns an empty string.';

function blogger_getTemplate()
{
 return new xmlrpcresp(' ','string');
}

$blogger_setTemplate_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$blogger_setTemplate_doc='blogger.setTemplate returns bool TRUE';

function blogger_setTemplate()
{
 return new xmlrpcresp(TRUE,'boolean');
}


$blogger_getPost_sig=array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$blogger_getPost_doc='blogger.getPost returns struct';
function blogger_getPost($m)
{
	$appkey=$m->getParam(0); // discarded
	$postid=$m->getParam(1);
	$username=$m->getParam(2);
	$password=$m->getParam(3);

	unset($appkey);	// this is just to drive the point home that we aren't using appkey.
					// i suppose we could log the appkeys just for the heck of it.

	$username=$username->scalarval();
	$password=$password->scalarval();
	$postid=$postid->scalarval();

	if (isvaliduser($username,$password))
	{
		parse_str(readuser($username),$userinfo);
	}

	$returnlist['content']=new xmlrpcval(getplan($username,".".$postid),'string');
	$returnlist['userid']=new xmlrpcval($username,'string');
	$returnlist['postid']=new xmlrpcval($postid,'string');
	$returnlist['datecreated']=new xmlrpcval($postid,'string');

	$xmlarray= new xmlrpcval(xmlrpc_encode($returnlist));
	$returnval=new xmlrpcresp($xmlarray);

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	return $returnval;
		}
}

$blogger_getRecentPosts_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcInt));
$blogger_getRecentPosts_doc='blogger.getRecentPosts returns array of structs';
function blogger_getRecentPosts($m)
{
	$appkey=$m->getParam(0); // discarded
	$blogid=$m->getParam(1);
	$username=$m->getParam(2);
	$password=$m->getParam(3);
	$numberOfPosts=$m->getParam(4);

	unset($appkey);	// this is just to drive the point home that we aren't using appkey.
					// i suppose we could log the appkeys just for the heck of it.

	$blogid=$blogid->scalarval();
	$username=$username->scalarval();
	$password=$password->scalarval();
	$numberOfPosts=$m->scalarval();

	if (isvaliduser($username,$password))
	{
		parse_str(readuser($username),$userinfo);
	}

	$postlist=getfilelist("$_SERVER[PWUSERS_DIR]/$username/plan/","plan*.txt*");
	rsort($postlist);
	for($i=0;$i<$numberOfPosts;$i++)
	{
		$returnlist['content'] = new xmlrpcval(getplan($username,".".$postid),'string');
		$returnlist['userid'] = new xmlrpcval($username,'string');
		$returnlist['postid'] = new xmlrpcval($postid,'string');
		$returnlist['datecreated'] = new xmlrpcval($postid,'string');
	
		$xmlarray[] = new xmlrpcval(xmlrpc_encode($returnlist));
	}
	$returnarray=new xmlrpcval($xmlarray,'array');
	$returnval=new xmlrpcresp($returnarray);

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	return $returnval;
		}
}

$blogger_deletePost_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcInt));
$blogger_deletePost_doc='blogger.getRecentPosts returns array of structs';
function blogger_deletePost($m)
{
	$appkey=$m->getParam(0); // discarded
	$postid=$m->getParam(1);
	$username=$m->getParam(2);
	$password=$m->getParam(3);

	unset($appkey);	// this is just to drive the point home that we aren't using appkey.
					// i suppose we could log the appkeys just for the heck of it.

	$postid=$postid->scalarval();
	$username=$username->scalarval();
	$password=$password->scalarval();

	if (isvaliduser($username,$password))
	{
		parse_str(readuser($username),$userinfo);
	}

	$plan_dir="$_SERVER[PWUSERS_DIR]/$username/plan";
	if (file_exists("$plan_dir/plan.$postid.txt")) unlink("$plan_dir/plan.$postid.txt");
	if (file_exists("$plan_dir/plan.$postid.txt.p")) unlink("$plan_dir/plan.$postid.txt.p");
	

	// if we generated an error, create an error return response
	if ($err) {
	return new xmlrpcresp(0, $xmlrpcerruser, $err);
	} else {
	// otherwise, we create the right response
	return new xmlrpcresp(TRUE,'boolean');
		}
}

?>