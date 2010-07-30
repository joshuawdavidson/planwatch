<?php
/*
FILEFUNCTIONS.PHP

*/



// files_encode_safe_name()
//
// encodes filenames so they're URL-safe and POSIX-safe
//-----------------------------------------------------------------------------
function files_encode_safe_name($filename=FALSE)
{
	if ($filename!==FALSE)
	{
		$filename=stripslashes($filename);
		$filename=urlencode($filename);
		$filename=preg_replace("|%(\d.)|","_\\1_",$filename);
	}
return $filename;
}



// files_decode_safe_name()
//
// decodes filenames so they're URL-safe and POSIX-safe
//-----------------------------------------------------------------------------
function files_decode_safe_name($filename=FALSE)
{
	if ($filename!==FALSE)
	{
		$filename=preg_replace("|_(\d.)_|","%\\1",$filename);
		$filename=urldecode($filename);
//		$filename=stripslashes($filename);
	}
return $filename;
}


// files_move_entry()
//
// changes an entry's filename
//-----------------------------------------------------------------------------
function files_move_entry($timecode,$prefix='',$suffix='',$remove=array())
{
	$entry_list=files_list("$_SERVER[USER_ROOT]/plan","*$timecode*");

	if ($entry_list)
	foreach($entry_list as $entry_filename)
	{
		$entry_filename=basename($entry_filename);
		$old_entry_filename="$_SERVER[USER_ROOT]/plan/".$entry_filename;
		$new_entry_filename="$_SERVER[USER_ROOT]/plan/".str_replace($remove,'',$prefix.$entry_filename.$suffix);
		$success=rename($old_entry_filename,$new_entry_filename);
	}
	else $success=FALSE;
	
return $success;
}


// files_get_entry_filename()
//
// changes an entry's filename
//-----------------------------------------------------------------------------
function files_get_entry_filename($entry)
{
	$entry_list=files_list("$_SERVER[USER_ROOT]/plan","*$entry*");
	return basename($entry_list[0]);
}


// files_list
// gets list of files in a directory
//------------------------------------------------------------------------------
function files_list($directory='',$filter='*',$switch='')
{
	$refilter=str_replace('.','\.',$filter);
	$refilter=str_replace('*','.*',$refilter);
	$refilter="|^$refilter\$|";
	if (!strstr($directory,'/home/planwatc')) $directory=$_SERVER['FILE_ROOT']."/".$directory;

	$handle = opendir($directory);
	while (false !== ($file = readdir($handle))) { 
		if ($file != "." && $file != ".." && preg_match($refilter,$file))
		{
				$fl[]=$file;
		} 
	}
	closedir($handle);

	if (!$switch && is_array($fl)) sort($fl);

return $fl;
}




// files_format_size
//
// takes a number of bytes, returns human readable
// file size
//------------------------------------------------------------------------------
function files_format_size($num)
{
	if ($num < 1024) return "$num bytes";
	if (($num > 1024) && ($num < (1024*1024))) return round($num/1024,2)." KB";
	if (($num > 1024*1024) && ($num < (1024*1024*1024))) return round($num/(1024*1024),2)." MB";
}





// FILE_PUT_CONTENTS
//
// reads entire contents of a file, returns a string
//------------------------------------------------------------------------------
if(!function_exists('file_put_contents')) {
  function file_put_contents($filename, $data, $file_append = false) {
   $fp = fopen($filename, (!$file_append ? 'w+' : 'a+'));
   if(!$fp) {
	 trigger_error('file_put_contents cannot write in file.', E_USER_ERROR);
	 return;
   }
   fputs($fp, $data);
   fclose($fp);
  }
}


// DIR_SIZE()
//
// finds the size of a directory
// by dmitri926 AT yahoo DOT com
// 06-Jan-2003 04:08
// in comments to http://php.net/filesize
//-----------------------------------------------------------------------------
function dir_size($dir) 
{
	$totalsize=0;
	if ($dirstream = @opendir($dir)) 
	{
		while (false !== ($filename = readdir($dirstream))) 
		{
			if ($filename!="." && $filename!="..")
			{
				if (is_file($dir."/".$filename))
					$totalsize+=filesize($dir."/".$filename);

				if (is_dir($dir."/".$filename))
					$totalsize+=dir_size($dir."/".$filename);
			}
		}
	}
	closedir($dirstream);
	return $totalsize;
}

?>