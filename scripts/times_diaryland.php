<?php

header("Content-type: text/plain");

date_default_timezone_set("America/New_York");
$_ENV['PWUSERS_DIR']="/var/www/planwatch.org/pwusers";
$_ENV['FILE_ROOT']="/var/www/planwatch.org/public_html";

echo "<hr/><h1>Diaryland Users</h1>\n";

$diaryland_array = array_unique(unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/plans_crossnode.dat")));

if (is_array($diaryland_array))
{
	foreach($diaryland_array as $i=>$plan)
	{
		$plan = trim($plan);
		$plan = str_replace(array('@DL','@dl','@diaryland','@diaryland.com','http://','.diaryland.com'),'',$plan);
		$url  = "http://members.diaryland.com/edit/profile.phtml?user=$plan";
	
		$result = file_get_contents($url);
		preg_match("|last updated:.*(\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d)|",$result,$match);
		$time=strtotime($match[1]);
		$filestring.="!!!".$plan."@DL...".$time;
		$diaryland_times_array[$plan]=$time;
		echo "$plan@DL: $time (".date("F jS h:ia",$time).")\n";
	}
}

$file=fopen("$_SERVER[FILE_ROOT]/stats/times_diaryland.dat",'w');
fwrite($file,serialize($diaryland_times_array));
fclose($file);

?>