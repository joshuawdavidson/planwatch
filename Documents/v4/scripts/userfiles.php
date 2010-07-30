<?php
/*
USERFILES.php

allows posting of files to be included in plans.
*/


// userfiles_prepare()
//
// builds the file listing for a user
//-----------------------------------------------------------------------------
function userfiles_prepare($fileowner='none')
{
	$fileowner_files_dir="$_SERVER[PWUSERS_DIR]/$fileowner/files";
	if (is_dir($fileowner_files_dir))
	    $groupmembers=files_list("$fileowner_files_dir","*");
	else $groupmembers=array();

	if (count($groupmembers)>0)
	foreach($groupmembers as $fileownerfile)
	{
		$fileownerfile=basename($fileownerfile);
		if (strpos($fileownerfile,'.jpg') || strpos($fileownerfile,'.png') || strpos($fileownerfile,'.gif')) $photolist[]=$fileownerfile;
		elseif (strpos($fileownerfile,'.mov') || strpos($fileownerfile,'.rm') || strpos($fileownerfile,'.mpg') || strpos($fileownerfile,'.asf')) $videolist[]=$fileownerfile;
		elseif (strpos($fileownerfile,'.mp3')) $audiolist[]=$fileownerfile;
		elseif (strpos($fileownerfile,'.rtf') || strpos($fileownerfile,'.txt') || strpos($fileownerfile,'.htm')) $docslist[]=$fileownerfile;
		else $misclist[]=$fileownerfile;
	}
	unset($groupmembers);
	
	if (isset($photolist))
	foreach($photolist as $photo)
	{
	    $photosize=round(filesize("$_SERVER[PWUSERS_DIR]/$fileowner/files/$photo")/1024,1)."K ";
	    $disp_photo=files_decode_safe_name($photo);
		$photos.="<a href='$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$photo' title='view $disp_photo' alt='link to image $disp_photo'><img src='$_SERVER[WEB_ROOT]/resources/graphics/smallpic.gif' width='15' height='15' border='0' /> $disp_photo</a>\n $photosize";
		if ($_SERVER['USER']==$fileowner) $photos.="<a class='edit_links' href='/userfiles/delete/$fileowner/$photo'>[delete]</a>\n";
		$photos.="<br />\n";
	}
	unset($photolist);

	if (isset($videolist))
	foreach($videolist as $video)
	{
	    $vidsize=round(filesize("$_SERVER[PWUSERS_DIR]/$fileowner/files/$video")/1024,1)."K ";
	    $disp_video=files_decode_safe_name($video);
		if (strpos($video,'.rm') || strpos($video,'.rv')) $logo='/resources/graphics/rm.gif';
		elseif (strpos($video,'.asf') || strpos($video,'.avi')) $logo='/resources/graphics/wm.gif';
		else $logo='/resources/graphics/qlogo.gif';

		$vidclips.="<a href='$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$video' title='view $disp_video' alt='link to video $disp_video'><img src='$logo' width='16' height='16' border='0' /> $disp_video </a>\n $vidsize";
		if ($_SERVER['USER']==$fileowner) $vidclips.="<a class='edit_links' href='/userfiles/delete/$fileowner/$video'>[delete]</a>\n";
		$vidclips.="<br />\n";
	}
	unset($videolist);

	if (isset($audiolist))
	foreach($audiolist as $audio)
	{
	    $audsize=files_format_size(filesize("$_SERVER[PWUSERS_DIR]/$fileowner/files/$audio"));
	    $disp_audio=files_decode_safe_name($audio);
		$audclips.="<a href='$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$audio' alt='link to audio file $disp_audio' title='listen to $disp_audio'><img src='$_SERVER[WEB_ROOT]/resources/graphics/smallaudio.gif' width='20' height='20' border='0' /> $disp_audio</a>\n $audsize";
		if ($_SERVER['USER']==$fileowner) $audclips.="<a class='edit_links' href='/userfiles/delete/$fileowner/$audio'>[delete]</a>\n";
		$audclips.="<br />\n";
	}
	unset($audiolist);

	if (isset($docslist))
	foreach($docslist as $doc)
	{
	    $docsize=files_format_size(filesize("$_SERVER[PWUSERS_DIR]/$fileowner/files/$doc"));
	    $disp_doc=files_decode_safe_name($doc);
		$docs.="<a href='$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$doc' alt='download document $disp_doc' title='download $doc'><img src='$_SERVER[WEB_ROOT]/resources/graphics/note.gif' width='8' height='10' border='0' /> $disp_doc</a>\n $docsize";
		if ($_SERVER['USER']==$fileowner) $docs.="<a class='edit_links' href='/userfiles/delete/$fileowner/$doc'>[delete]</a>\n";
		$docs.="<br />\n";
	}
	unset($docslist);

	if (isset($misclist))
	foreach($misclist as $misc)
	{
	    $miscsize=files_format_size(filesize("$_SERVER[PWUSERS_DIR]/$fileowner/files/$misc"));
	    $disp_misc=files_decode_safe_name($misc);
		$miscfiles.="<a href='$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$misc' alt='download document $disp_misc' title='download $disp_misc'><img src='$_SERVER[WEB_ROOT]/resources/graphics/note.gif' width='8' height='10' border='0' /> $disp_misc</a>\n $miscsize";
		if ($_SERVER['USER']==$fileowner) $miscfiles.="<a class='edit_links' href='/userfiles/delete/$fileowner/$misc'>[delete]</a>\n";
		$miscfiles.="<br />\n";
	}
	unset($misclist);
	
	if (isset($vidclips))	$vidclips="<h4>Video:</h4> $vidclips<br />\n";
	if (isset($audclips))	$audclips="<h4>Audio:</h4> $audclips<br />\n";
	if (isset($photos))		$photos="<h4>Images:</h4> $photos<br />\n";
	if (isset($docs))	 	$docs="<h4>Documents:</h4> $docs<br />\n";
	if (isset($miscfiles))	$miscfiles="<h4>Other:</h4> $miscfiles<br />\n";

	if (!isset($vidclips) && !isset($audclips) && !isset($photos) && !isset($docs) && !isset($miscfiles)) $photos="no files.";
	if (strpos(getenv('REQUEST_URI'),'list') && $_SERVER['USER']==$fileowner) $manage="<a href='$_SERVER[WEB_ROOT]/userfiles/manage/$_SERVER[USER]'>manage files</a>";
	if (strpos(getenv('REQUEST_URI'),'list')) $title="<h1>$fileowner's files</h1>";	
return array("title"=>$title,"vidclips"=>$vidclips,"audclips"=>$audclips,"photos"=>$photos,"docs"=>$docs,"miscfiles"=>$miscfiles,"manage"=>$manage);
}



// userfiles_manage()
//
// presents a form for adding and removing files
//-----------------------------------------------------------------------------
function userfiles_manage($fileowner='none')
{
	if ($_SERVER['USER']==$fileowner)
	{
		$quota=10000; // in kilobytes
		if ($_SERVER['USER']=='listen') $quota=10000000;
		if ($_SERVER['USER']=='jwdavidson') $quota=10000000;

		// TODO: provide a correct class for this box
		$content.="
	
		<div style='float: right'>
			<h3>Existing Files</h3>
		";
		$content.=implode("",userfiles_prepare($fileowner));
	
		$content.="
		</div>\n";
			
	    if (($ds=dir_size("$_SERVER[PWUSERS_DIR]/$fileowner/files/")) < 1024*$quota)
	    {
	        $content.="
	        <form action='$_SERVER[WEB_ROOT]/scripts/userfiles.php' method='post' enctype='multipart/form-data'>
			<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"".(1024*$quota/2)."\"/>
	        <h1><img src='$GLOBALS[toolsicon]' /> Managing Files for $fileowner</h1>";
	                    
			$content.="<div style='float: left; margin-right: 10px; width: 100px; background: gray; border: thin solid black;'>
				<div style='width: ".(round($ds/($quota*1024),2)*100)."px; background: black; color: black;'>  .</div></div>";

	        $content.=(round($ds/1024,1))."K used. ".round((1024*$quota - $ds)/1024,1)."K free.";
	            	
			$content.="<br />
			<div class='column'><h3>Add Files</h3><br />\n";
		
			for($i=0;$i<5;$i++)
			{
				$content.="\n	<input type='file' name='files[]'/><br/>";
			}
		
			$content.="
			<hr>
			<input type='submit' name='save changes' value='send files'/>
			</div>";

			$content.="
			<input type='hidden' name='fileowner' value='$fileowner'/>
			<input type='hidden' name='action' value='write files'/>
			</form><br clear='all'>\n
			";
	    }
	    else
	    {
	        $content.="You have exceeded your $quota K quota. Please delete a file before you add new ones.<br /><br />";
	    }		

	}
	else $content="
		<div class='alert'>
			you are not $fileowner, you're $_SERVER[USER], so you can't manage $fileowner's files.
		</div>\n\n";

return $content;
}


// userfiles_build_menu()
//
// returns javascript code for clickable file insertion in plan_update()
//------------------------------------------------------------------------------
// no inputs
// returns string $content
// called by userfiles.case and plan_update.php
function userfiles_build_menu()
{
	$quota=10000; // in kilobytes
	if ($_SERVER['USER']=='listen') $quota=10000000;
	if ($_SERVER['USER']=='jwdavidson') $quota=10000000;

	$content.="<html>
	<head>
	<script type='text/javascript' src='$_SERVER[WEB_ROOT]/resources/javascript/setplan.js' />
	<link rel='stylesheet' href='$_SERVER[WEB_ROOT]/stylesheet'>
	</head>
	<body>";

	$fileowner=$_SERVER['USER'];
	$fileowner_files_dir="$_SERVER[USER_ROOT]/files";
	if (is_dir($fileowner_files_dir))
	    $groupmembers=files_list("$fileowner_files_dir","*","date");
	else $groupmembers=array();

	if (count($groupmembers)>0)
	foreach($groupmembers as $fileownerfile)
	{
		$fileownerfile=basename($fileownerfile);
		if (strpos($fileownerfile,'.jpg') || strpos($fileownerfile,'.png') || strpos($fileownerfile,'.gif'))
			$photolist[]=$fileownerfile;
		elseif (
		   strpos($fileownerfile,'.wav')
		|| strpos($fileownerfile,'.mp3')
		|| strpos($fileownerfile,'.m4a')
		|| strpos($fileownerfile,'.au')
		|| strpos($fileownerfile,'.wma')
		|| strpos($fileownerfile,'.ogg')
		)
			$audiolist[]=$fileownerfile;
		else $misclist[]=$fileownerfile;
	}
	unset($groupmembers);

	if (isset($photolist))
	{
		foreach($photolist as $photo)
		{
			$photosize=getimagesize("$_SERVER[USER_ROOT]/files/$photo");
			$photo_link="<img src=\'http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$photo\' alt=\'$photo\' title=\'$photo\' width=\'$photosize[0]\' height=\'$photosize[1]\' />";
			$photo_content.="
			<li><a  href=\"javascript:insertTag('textbox','$photo_link',''); window.parent.document.getElementById('insert').style.display='none'; void(0);\">
			<img src='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/resources/graphics/posticon.gif' />
			$photo
			</a>
			<a href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/userfiles/delete/$fileowner/$photo/menu'>
			<img src='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/resources/graphics/x.gif' width='8' height='8' />
			</a></li>\n";
		}
		$content.="<li class='listheader'>Images</li>\n$photo_content\n\n";
	}

	if (isset($audiolist))
	{
		foreach($audiolist as $audio)
		{
			$audio_link="<a href=\'http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$audio\' alt=\'$audio\'/>";
			$audio_content.="
			<li><a  href=\"javascript:insertTag('textbox','$audio_link',''); window.parent.document.getElementById('insert').style.display='none'; void(0);\">
			<img src='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/resources/graphics/smallaudio.gif' />
			$audio
			</a>
			<a href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/userfiles/delete/$fileowner/$audio/menu'>
			<img src='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/resources/graphics/x.gif' width='8' height='8' />
			</a></li>\n";
		}
		$content.="<li class='listheader'>Sounds</li>\n$audio_content\n\n";
	}

	if (isset($misclist))
	{
		foreach($misclist as $misc)
		{
			$misc_display=files_decode_safe_name($misc);
			$misc_link="<a href=\'http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$misc\'>$misc_display</a>";
			$misc_content.="
			<li><a  href=\"javascript:insertTag('textbox','$misc_link',''); window.parent.document.getElementById('insert').style.display='none'; void(0);\">
			<img src='$_SERVER[WEB_ROOT]/resources/graphics/posticon.gif' />
			$misc
			</a>
			<a href='$_SERVER[WEB_ROOT]/userfiles/delete/$fileowner/$misc/menu'>
			<img src='$_SERVER[WEB_ROOT]/resources/graphics/x.gif' width='8' height='8' />
			</a></li>\n";
		}
		$content.="<li class='listheader'>Documents</li>\n$misc_content\n\n";
	}

	if (($ds=dir_size("$_SERVER[USER_ROOT]/files/")) < 1024*$quota)
	{
		$uploadform="<li>".round($ds/1024)." K used of $quota K total
		<form target='hiddenUploadFrame' action='$_SERVER[WEB_ROOT]/scripts/userfiles.php' method='post' enctype='multipart/form-data'>
		<input type='file' name='files[1]' id='fileToUpload'/>
		<input type='submit' value='upload file' onclick=\"element('insert').style.display='none';void(0);\"/>
		<input type='hidden' name='action' value='write files'/>
		<input type='hidden' name='fileowner' value='$_SERVER[USER]'/>
		<input type='hidden' name='source' value='menu'/>
		<iframe id='hiddenUploadFrame' name='hiddenUploadFrame' style='height: 1px; width: 1px; visibility: hidden;'></iframe>
		</form></li>\n";
		
	}
	else
	{
		$content.="<li><strong>QUOTA EXCEEDED. Please delete ".files_format_size($ds - $quota*1024)." of files.</li>";
	}

	$content.="<li><a href='$_SERVER[WEB_ROOT]/userfiles/manage/$_SERVER[USER]' target='_top'><img src='$_SERVER[WEB_ROOT]$GLOBALS[toolsicon]'> manage your files</a></li>\n";
	$content.="<li><a href='javascript:window.location.reload();'><img src='$_SERVER[WEB_ROOT]$GLOBALS[toolsicon]'> refresh the list</a></li>\n";

	$content=$uploadform.$content;

return $content;
}


// ACTION==WRITE FILES
//
// simplified file handling thanks to a post from
// quinten+spam at andrew dot cmu dot edu
// to the php.net comments on handling multi-file uploads
// http://www.php.net/manual/en/features.file-upload.multiple.php
// modified to work with post-4.1 globals
//------------------------------------------------------------------------------
if ($_POST['action']=='write files')
{
	$fileowner=$_POST['fileowner'];

	if ($fileowner==$_SERVER['USER'])
	{
		$path_to_file="$_SERVER[PWUSERS_DIR]/$fileowner/files";

		if (!is_dir($path_to_file))
		{
			mkdir($path_to_file,0777);
		}
	
		$files = $_FILES['files'];

		
		// adds a trailing slash to the path name if necessary.
		if (!ereg("/$", $path_to_file))
				$path_to_file = $path_to_file."/";
	
		// iterates through the array
		foreach ($files['name'] as $key=>$name)
		{
			if ($files['size'][$key])
			{
	
				// clean up file name
	            $name=files_encode_safe_name($name);
				$location = $path_to_file.$name;
	
				copy($files['tmp_name'][$key],$location);
	
				unlink($files['tmp_name'][$key]);
			}
		}
	}

if ($source=='menu') redirect("/userfiles/list/$fileowner");
else redirect('/userfiles/manage');
}

?>