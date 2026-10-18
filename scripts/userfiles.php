<?php
/*
USERFILES.php

allows posting of files to be included in plans.
*/

include_once('/var/www/planwatch.org/siteconfig.php');

function userfiles_handle_upload($user=false,$type=false)
{
	if(empty($user)) { $user = $_POST['user']; }
	if(empty($user)) {
		header("Content-type: application/json");
		echo json_encode(["error"=>"user not specified"]);
	}

	$upload_filename=$_POST['upload_filename'];
	$filename="$_SERVER[PWUSERS_DIR]/$user/files/$type".str_replace(array(" ","'",'"',"(",")"), "_", $upload_filename);

	if (!is_dir("$_SERVER[DOCUMENT_ROOT]/../pwusers/$user/files")) {
		mkdir("$_SERVER[PWUSERS_DIR]/$user/files");
	}

	foreach ($_FILES as $i=>$file) {
		// exit;
		if (strlen($file['tmp_name'])>0 && file_exists($file['tmp_name']))
		{
			$success = move_uploaded_file($file['tmp_name'], $filename);
			if($success===false)
			{
				header("http/1.1 404 Not Found");
				header("Content-type: application/json");
				echo json_encode(["error"=>"ERROR move_uploaded_file failed"]);
				exit;
			}
		}
		else
		{
			header("http/1.1 404 not found");
			header("Content-type: application/json");
			echo json_encode(["error"=>"no file provided"]);
		}
	}

	if (file_exists($filename)) {
		header("http/1.1 200 OK");
		$imagesize = getimagesize($filename);
		$resizestring='';
		if($imagesize[0]>1920)
		{
			$resizestring=".width.1920.jpg";
		}
		echo "/userfiles/view/$user/".basename($filename).$resizestring;
		exit;
	}
}

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
		if (stristr($fileownerfile,'.jpg') || stristr($fileownerfile,'.jpeg') || stristr($fileownerfile,'.webp') || stristr($fileownerfile,'.png') || stristr($fileownerfile,'.gif')) $photolist[]=$fileownerfile;
		elseif (stristr($fileownerfile,'.mov') || stristr($fileownerfile,'.rm') || stristr($fileownerfile,'.mpg') || stristr($fileownerfile,'.asf') || stristr($fileownerfile,'.m4v')) $videolist[]=$fileownerfile;
		elseif (stristr($fileownerfile,'.mp3')) $audiolist[]=$fileownerfile;
		elseif (stristr($fileownerfile,'.rtf') || stristr($fileownerfile,'.txt') || stristr($fileownerfile,'.htm')) $docslist[]=$fileownerfile;
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
		if (stristr($video,'.rm') || stristr($video,'.rv')) $logo='/resources/graphics/rm.gif';
		elseif (stristr($video,'.asf') || stristr($video,'.avi')) $logo='/resources/graphics/wm.gif';
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
	if (stristr(getenv('REQUEST_URI'),'list') && $_SERVER['USER']==$fileowner) $manage="<a href='$_SERVER[WEB_ROOT]/userfiles/manage/$_SERVER[USER]'>manage files</a>";
	if (stristr(getenv('REQUEST_URI'),'list')) $title="<h1>$fileowner's files</h1>";
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
		$quota=100000; // in kilobytes

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
function userfiles_build_menu($sortby='date',$sortorder='desc')
{
	$photolist=$audiolist=$misclist=array();
	$quota=100000; // in kilobytes
	$content.="
	<li><a href='$_SERVER[WEB_ROOT]/userfiles/manage/$_SERVER[USER]' target='_blank'><img src='$_SERVER[WEB_ROOT]$GLOBALS[toolsicon]'> manage your files</a></li>
	<li><a href='javascript:loadXMLDoc('/userfiles/list/' + Math.random(),'','insert');'><img src='$_SERVER[WEB_ROOT]$GLOBALS[toolsicon]'> refresh the list</a></li>
	<li><hr></li>";

	$fileowner=$_SERVER['USER'];
	$fileowner_files_dir="$_SERVER[USER_ROOT]/files";
	if (is_dir($fileowner_files_dir))
		$groupmembers=scandir($fileowner_files_dir);
//	    $groupmembers=files_list("$fileowner_files_dir","*","date");
	else $groupmembers=array();

	// print_r($fileowner_files_dir);

	if (count($groupmembers)>0)
	foreach($groupmembers as $fileownerfile)
	{
		$fileownerfile=basename($fileownerfile);

		if($sortby=='date')
		{
			$fileindex=filemtime($fileowner_files_dir.'/'.$fileownerfile);
		}
		else
		{
			$fileindex=strtolower($fileownerfile);
		}


		if (stristr($fileownerfile,'.jpg') || stristr($fileownerfile,'.jpeg') || stristr($fileownerfile,'.webp') || stristr($fileownerfile,'.png') || stristr($fileownerfile,'.gif'))
		{
			if(!strpos($fileownerfile,'width') && !strpos($fileownerfile,'square.') && !strpos($fileownerfile,'Screenshot'))
			{
				$photolist[$fileindex]=$fileownerfile;
			}
		}
		elseif (
		   stristr($fileownerfile,'.wav')
		|| stristr($fileownerfile,'.mp3')
		|| stristr($fileownerfile,'.m4a')
		|| stristr($fileownerfile,'.au')
		|| stristr($fileownerfile,'.wma')
		|| stristr($fileownerfile,'.ogg')
		)
		{
			$audiolist[$fileindex]=$fileownerfile;
		}
		elseif($fileownerfile[0]!='.') {$misclist[$fileindex]=$fileownerfile;}
	}
	else $content.="no files found";
	unset($groupmembers);

	// echo "sortby $sortby sortorder $sortorder<hr>";

	if($sortorder=='desc')
	{
		krsort($photolist);
		krsort($audiolist);
		krsort($misclist);
	}
	else
	{
		ksort($photolist);
		ksort($audiolist);
		ksort($misclist);
	}

	if (!empty($photolist))
	{
		$old_divider = FALSE;
		foreach($photolist as $idx=>$photo)
		{
			if(is_numeric($idx) && $idx<time()-24*3600*7 && !$old_divider)
			{
				$photo_content.="<li><hr></li>\n";
				$old_divider=TRUE;
			}
			$photosize=getimagesize("$_SERVER[USER_ROOT]/files/$photo");
			if($photosize[0]>1920)
			{
				$photo_url=$photo.".width.1920.jpg";
				$ratio = 1920/$photosize[0];
				$photosize[0]=1920;
				$photosize[1]=floor($ratio*$photosize[1]);
			}
			else
			{
				$photo_url=$photo;
			}
			$photo_alt = str_replace(array("-","_",".jpg",".jpeg",".png",".JPG",".JPEG",".PNG")," ",$photo);
			$photo_link="<img src=\'//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$photo_url\' alt=\'$photo_alt\' title=\'$photo_alt\' width=\'$photosize[0]\' height=\'$photosize[1]\' />";
			$photo_content.="
			<li><a  href=\"javascript:insertTag('textbox','$photo_link',''); window.parent.document.getElementById('insert').style.display='none'; void(0);\">
			<img src='//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/resources/graphics/posticon.gif' />
			$photo
			</a></li>\n";
		}
		$content.="<li class='listheader'>Images</li>\n$photo_content\n\n";
	}

	if (!empty($audiolist))
	{
		foreach($audiolist as $audio)
		{
			$audio_link="<a href=\'http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$audio\' alt=\'$audio\'/>";
			$audio_content.="
			<li><a  href=\"javascript:insertTag('textbox','$audio_link',''); window.parent.document.getElementById('insert').style.display='none'; void(0);\">
			<img src='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/resources/graphics/smallaudio.gif' />
			$audio
			</a></li>\n";
		}
		$content.="<li class='listheader'>Sounds</li>\n$audio_content\n\n";
	}

	if (!empty($misclist))
	{
		foreach($misclist as $misc)
		{
			$misc_display=files_decode_safe_name($misc);
			$misc_link="<a href=\'http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/userfiles/view/$fileowner/$misc\'>$misc_display</a>";
			$misc_content.="
			<li><a  href=\"javascript:insertTag('textbox','$misc_link',''); window.parent.document.getElementById('insert').style.display='none'; void(0);\">
			<img src='$_SERVER[WEB_ROOT]/resources/graphics/posticon.gif' />
			$misc
			</a></li>\n";
		}
		$content.="<li class='listheader'>Documents</li>\n$misc_content\n\n";
	}

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

	if ($source=='menu')
	{
		redirect("/userfiles/list/$fileowner");
	}
	else
	{
		redirect('/userfiles/manage');
	}
}

if (isset($_POST['action']) && $_POST['action']=='upload')
{
	userfiles_handle_upload();
}


?>