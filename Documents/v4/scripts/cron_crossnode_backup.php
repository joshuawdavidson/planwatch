<?php
$_SERVER['AUTH_COOKIE']='fingerprint_v4';
$_SERVER['FILE_ROOT']='/home/planwatc/public_html';

include_once('/home/planwatc/public_html/scripts/siteconfig.php');
include_once('/home/planwatc/public_html/scripts/plan_read.php');
include_once('/home/planwatc/public_html/backend/xmlrpc.inc');
include_once('/home/planwatc/public_html/scripts/standard_library/user_info_functions.php');
include_once('/home/planwatc/public_html/scripts/standard_library/plan_info_functions.php');
include_once('/home/planwatc/public_html/scripts/standard_library/file_functions.php');

/* BACKUP */
$_COOKIE[$_SERVER['AUTH_COOKIE']]="YmFja3Vw:::d53db979c480aa3ce0d9aaba69495fc8";
user_verify_fingerprint("YmFja3Vw:::d53db979c480aa3ce0d9aaba69495fc8");
$time=time();


$backup_permissions_dir="$_SERVER[FILE_ROOT]/stats/backup_permissions";
$backup_users_list=files_list($backup_permissions_dir,"*.permission");



foreach($backup_users_list as $userfile)
{
	$username=trim(str_replace(".permission","",basename($userfile)));
	$sptime=plan_get_last_update($username);
	$slastview=plan_get_last_view($username);

	if($sptime > $slastview
		|| (!$sptime
			&& (filemtime("$_SERVER[USER_ROOT]/files/$username.latest.backup") < ($time-3600*6))
		   )
	   )
	{
		$plan=plan_read($username,FALSE,FALSE,FALSE,TRUE);
		$file=fopen("$_SERVER[USER_ROOT]/files/$username.$time.backup",'w');
		fwrite($file,$plan);
		fclose($file);	

		$file=fopen("$_SERVER[USER_ROOT]/files/$username.latest.backup",'w');
		fwrite($file,$plan);
		fclose($file);	
	}
}


?>