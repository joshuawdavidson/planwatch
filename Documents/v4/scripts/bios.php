<?php

/*
BIOS.PHP -- part of the planwatch library

reads/writes a user's bio.

bios are stored in each user's directory. there are 2 possible bios:
public (bio.txt) and private (bio.txt.p)

they're just plaintext.


FUNCTIONS:
bio_read() -- reads a user's bio
	bio_read_livejournal()
	bio_read_diaryland()
bio_edit() -- form for editing a bio
bio_write() -- writes form output to disk

*/

// bio_read_LIVEJOURNAL()
//
// returns $bio_owner's bio, if any
//------------------------------------------------------------------------------
function bio_read_livejournal($bio_owner,$deadjournal)
{
	list($bio_owner)=explode('@',$bio_owner);
	if ($deadjournal) 
		$url="http://www.deadjournal.com/userinfo.bml?user=$bio_owner";
	else
		$url="http://www.livejournal.com/userinfo.bml?user=$bio_owner";
	$bio=file_get_contents($url);

	// pulls out the substantive part, ignoring the LJ template
	list($crap,$bio)=explode('<!-- Content -->',$bio);
	list($bio,$crap)=explode('<!-- /Content -->',$bio);

	// eliminates the table structure. not sure why
	$bio=str_replace(array('</td>','</TD>','</tr>','</TR>'),array(' ',' ',"<br />\n","<br />\n"),$bio);

	// get rid of bad formatting or javascript that might have been inserted	
	$bio=removeEvilTags($bio);

	return $bio;
}



// bio_read_DIARYLAND()
//
// returns $bio_owner's bio, if any
//------------------------------------------------------------------------------
function bio_read_diaryland($bio_owner)
{
	list($bio_owner)=explode('@',$bio_owner);
	$url="http://members.diaryland.com/profile.phtml?user=$username";
	$bio=file_get_contents($url);
	
	// pulls out the substantive part, ignoring the DL template
	list($crap,$bio)=explode('<!-- content goes here -->',$bio);
	
	// eliminates the table structure. not sure why
	$bio=str_replace(array('</td>','</TD>','</tr>','</TR>'),array(' ',' ',"<br />\n","<br />\n"),$bio);
	
	// get rid of bad formatting or javascript that might have been inserted	
	$bio=removeEvilTags($bio);
	
	return $bio;
}




// bio_read()
//
// returns $bio_owner's bio, if any
// also returns a private bio, if any, for users on $bio_owner's allowed list
// 
// makes use of bio_read_diaryland() and bio_read_livejournal() as necessary
// does not involve any xml-rpc at present, because NOTEworld has no bio feature
//------------------------------------------------------------------------------
function bio_read($bio_owner,$which='both',$edit=FALSE)
{
	include_once('plan_read.php');

	if (strpos($bio_owner,'@dl') || strpos($bio_owner,'@diaryland'))
		$bio=bio_read_diaryland($bio_owner);

	if (strpos($bio_owner,'@lj') || strpos($bio_owner,'@livejournal'))
		$bio=bio_read_livejournal($bio_owner);

	if (strpos($bio_owner,'@dj') || strpos($bio_owner,'@deadjournal'))
		$bio=bio_read_livejournal($bio_owner,'dead');

	$bio_fn="$_SERVER[PWUSERS_DIR]/$bio_owner/bio.txt";
	$bio_p_fn=$bio_fn.".p";
	if (file_exists($bio_fn) && ($which=='public' || $which=='both'))
	{
		$bio=plan_add_user_links(stripslashes(stripslashes(file_get_contents($bio_fn))));
	}
	
	if (file_exists($bio_p_fn) && ($which=='private' || $which=='both') && user_is_authorized($bio_owner,$_SERVER['USER']))
	{
		$bio_p=plan_add_user_links(stripslashes(stripslashes(file_get_contents($bio_p_fn))));
	}

	if ($bio!=$bio_p && $which=='both' && !$edit) $bio.="\n<br />\n".$bio_p;	
	if ($which=='private') $bio=$bio_p;
	
	if (!($bio)) $bio='';

//	if($bio_owner==$_SERVER['USER'] && !$edit) $bio.="$bio_owner $_SERVER[USER] <br clear='all' /><a class='bigbutton' href='/write/bio'>&#x270e; edit your bio</a>";	
	
return $bio;
}




// bio_edit()
//
// presents a form which allows a user to write his/her bio
//------------------------------------------------------------------------------
function bio_edit()
{
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
	{
		$bio_fn="$_SERVER[USER_ROOT]/bio.txt";
		$bio_p_fn="$_SERVER[USER_ROOT]/bio.txt.p";
		if (file_exists($bio_fn))
		{
			$bio=stripslashes(file_get_contents($bio_fn));
		}

		if (file_exists($bio_p_fn))
		{
			$bio_p=stripslashes(file_get_contents($bio_p_fn));
		}
		
		$content="<h1><img src='$GLOBALS[writeicon]' alt='write icon' />Edit Your Bios</h1>\n<form action='$_SERVER[WEB_ROOT]/scripts/bios.php' method='post'>"
			."<h3>public bio:</h3><textarea name='bio' class='textbox'>".stripslashes(trim($bio))."</textarea><input type='submit' value='Update Bio' style='font-size: 20px; font-weight: bold; background: $GLOBALS[linkcolor]; color: $GLOBALS[planbgcolor];'/><br clear='all' />\n"
			."<h3>private bio:</h3><textarea name='bio_p' class='textbox'>".stripslashes(trim($bio_p))."</textarea>\n"
			."<input type='hidden' name='username' value='$username'/>\n<input type='hidden' name='action' value='Write Bio'/>\n\n";
	}
	else $content='failed. no privs.';
	
output("edit $_SERVER[USER]'s bio",$content);
}




// bio_write()
//
// puts the result of bio_edit() to disk.
//------------------------------------------------------------------------------
function bio_write()
{
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
	{	
		if ($_POST['bio'])
		{
			$bio_fn="$_SERVER[USER_ROOT]/bio.txt";
			file_put_contents($bio_fn,stripslashes(trim($_POST['bio'])));
		}
		if ($_POST['bio_p'])
		{
			$bio_p_fn="$_SERVER[USER_ROOT]/bio.txt.p";
			file_put_contents($bio_p_fn,stripslashes(trim($_POST['bio_p'])));
		}
	}

redirect("/read/$_SERVER[USER]/bio");
}	

if ($_POST['action']=='Write Bio') bio_write();

?>