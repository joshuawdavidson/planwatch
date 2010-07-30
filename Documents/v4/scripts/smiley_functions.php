<?php
/*
SMILEY_FUNCTIONS.php

all the smiley library functions
*/



// SMILEYS_BUILD_MENU()
//
// returns javascript code for clickable smileys in plan_update()
//---------------------------------------------------------------------
function smileys_build_menu()
{
	$smileyarray=smiley_listall();
	$content="<b>Smileys</b><br />\n";
	foreach($smileyarray as $graphic=>$keylist)
	{
		$graphic_size=getimagesize("$_SERVER[FILE_ROOT]/resources/smileys/$graphic");

		$key=$keylist[0];
		if (!strstr($key,':') && !strstr($key,'>') && !strstr($key,'8') && !strstr($key,')')) $key=":$key:";

		$content.="<a href=\"javascript:insertTag('textbox',' $key ','');document.getElementById('smiley').style.display='none';void(0);\"><img border='0' src='/resources/smileys/$graphic' align='middle' $graphic_size[3] /></a>\n";
	}
return $content;
}




// SMILEY_LISTALL()
//
// finds all smileys, returns an array
//
// smileykey_a[] is a 2-d array where the first
// dimension is the name of the smiley file and the
// second dimension is an array of keys to that
// smiley taken from a file with the same base
// name as the smiley graphic, but with the ext
// .smiley, which is a \n delimited list of keys
// for that graphic
// 
// multiple identical keys are resolved by the
// last key taking precedence
//------------------------------------------------------------------------------
function smiley_listall()
{
	exec("ls $_SERVER[FILE_ROOT]/resources/smileys/*.gif",$smileylist);
	exec("ls $_SERVER[FILE_ROOT]/resources/smileys/*.png",$smileylist);
	foreach($smileylist as $smiley)
	{
		$smiley=basename($smiley);
		$smileykey_a[$smiley][0]=str_replace(array('.gif','.png'),'',$smiley);
		$smileyinfo_fn="$_SERVER[FILE_ROOT]/resources/smileys/".$smileykey_a[$smiley][0].".smiley";
		if (file_exists($smileyinfo_fn)) { $smileyinfo=file($smileyinfo_fn); }
		else $smileyinfo=array();

		foreach($smileyinfo as $smileykey)
		{
			$smileykey=trim($smileykey);
			if ($smileykey) $smileykey_a[$smiley][]=$smileykey;
		}
	}

return $smileykey_a;
}




// SMILEY_EDITKEYS()
//
// presents form for editing smiley keys
//------------------------------------------------------------------------------
function smiley_editkeys($smiley)
{
	
	// read in existing values, if any
	$smiley_fn="$_SERVER[FILE_ROOT]/resources/smileys/".basename(str_replace(array('.gif','.png'),'',$smiley)).".smiley";
	if (file_exists($smiley_fn)) $smileykeys=file($smiley_fn);
	else $smileykeys=array();

	// the form itself
	$content="<form action='$_SERVER[WEB_ROOT]/scripts/form_shim.php'>\n";

	foreach($smileykeys as $key)
	{
		$content.="<input name='keys[]' type='text' value='".trim($key)."'/><br/>\n";
	}

	for($i=0;$i<3;$i++)
	{
		$content.="<input name='keys[]' type='text' value=''/><br/>\n";
	}

	$content.="<input type='hidden' name='smiley' value='$smiley'/><input type='submit' name='action' value='smiley_writekeys'/></form>";

output("editing smiley $smiley",$content,''," editing smiley $smiley");
}




// SMILEY_UPLOAD()
//
// presents form for adding a new smiley
//------------------------------------------------------------------------------
function smiley_upload()
{
	$content="
	<form action='$_SERVER[WEB_ROOT]/scripts/form_shim.php' enctype='multipart/form-data' method='post'>
	<h1>Upload a New Smiley</h1>
	Smileys have to be .gif or .png format, and less than 10k<br/><br/>
	<input type='hidden' name='MAX_FILE_SIZE' value='10240'/>
	file: <input type='file' name='newsmiley'/><br/>
	name: <input type='text' name='newsmileyname' value=''/><br/><br/>
	<input type='submit' name='action' value='upload smiley'/>
	</form>";

output("Upload a New Smiley",$content,'','uploading a new smiley');
}




// SMILEY_WRITENEW()
//
// copies new smiley to the proper place
//------------------------------------------------------------------------------
function smiley_writenew($newsmiley,$newsmileyname)
{
		if (strstr($newsmiley,'.jpg')) { redirect('/help/smileys'); exit; }

	$newsmileyname=str_replace(array('.gif','.png'),'',$newsmileyname);
	if (strstr($newsmiley,'.png')) $format='.png'; else $format='.gif';
	if (move_uploaded_file($newsmiley,"$_SERVER[FILE_ROOT]/resources/smileys/$newsmileyname$format")); else echo "not uploaded";
	chmod("$_SERVER[FILE_ROOT]/resources/smileys/$newsmileyname$format",0777);

redirect('/help/smileys');
}




// SMILEY_WRITEKEYS()
//
// presents form for editing smiley keys
//------------------------------------------------------------------------------
function smiley_writekeys($smiley,$keys)
{
	
	if ($keys)
	{
		// clean up smileys, prepare for writing
		foreach($keys as $i=>$key)
		{
			$key=stripslashes($key);
			if ($key && !strstr($key,':') && !strstr($key,'>') && !strstr($key,'8') && !strstr($key,')')) $key=":$key:";
			$fcontents.="$key";
			if ($keys[$i+1]) $fcontents.="\n";
		}
		
		// write file
		$f=fopen("$_SERVER[FILE_ROOT]/resources/smileys/".basename(str_replace(array('.gif','.png'),'',$smiley)).".smiley",'w');
		fwrite($f,$fcontents);
		fclose($f);
		chmod("$_SERVER[FILE_ROOT]/resources/smileys/".basename(str_replace(array('.gif','.png'),'',$smiley)).".smiley",0777);
	}
redirect('/help/smileys');
}




// SMILEY_DELETE()
//
// deletes a smiley
//------------------------------------------------------------------------------
function smiley_delete($smiley,$forsure=FALSE)
{
	
	if ($smiley && (file_exists("$_SERVER[FILE_ROOT]/resources/smileys/$smiley.smiley") || file_exists("$_SERVER[FILE_ROOT]/resources/smileys/$smiley.gif") || file_exists("$_SERVER[FILE_ROOT]/resources/smileys/$smiley.png")))
	{
		if ($forsure==TRUE)
		{
			rename("$_SERVER[FILE_ROOT]/resources/smileys/$smiley.gif","$_SERVER[FILE_ROOT]/resources/smileys/removed/$smiley.gif");
			@rename("$_SERVER[FILE_ROOT]/resources/smileys/$smiley.png","$_SERVER[FILE_ROOT]/resources/smileys/removed/$smiley.png");
			@rename("$_SERVER[FILE_ROOT]/resources/smileys/$smiley.smiley","$_SERVER[FILE_ROOT]/resources/smileys/removed/$smiley.smiley");
			redirect('/help/smileys');
		}
		else
		{
			if (file_exists("$_SERVER[FILE_ROOT]/resources/smileys/$smiley.gif")) $pic="<img src='$_SERVER[WEB_ROOT]/resources/smileys/$smiley.gif' />";
			if (file_exists("$_SERVER[FILE_ROOT]/resources/smileys/$smiley.png")) $pic="<img src='$_SERVER[WEB_ROOT]/resources/smileys/$smiley.png' />";

			output("are you sure you want to delete a defenseless smiley?","<h2>are you sure you want to delete a defenseless smiley?</h2><br /><br />
			$pic<br /><br />
			you shouldn't do this unless you're certain, and you shouldn't do it to someone else's smiley either, unless they've given you permission
			or everyone else agrees. this can't be undone.<br /><br />
			<a href='$_SERVER[WEB_ROOT]/edit/smiley/delete/$smiley/TRUE'>delete $smiley</a> | <a href='$_SERVER[WEB_ROOT]/'>never mind. i'm sorry.</a> ");
		}
	}
	else redirect('/help/smileys');
}

?>