<?php

echo "<hr/><h1>Diaryland Users</h1>\n";

if ($diaryland_array)
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

$file=fopen("$_SERVER[FILE_ROOT]/stats/times_diaryland.txt",'w');
fwrite($file,$filestring);
fclose($file);

$file=fopen("$_SERVER[FILE_ROOT]/stats/times_diaryland.dat",'w');
fwrite($file,serialize($diaryland_times_array));
fclose($file);
//file_put_contents("$_SERVER[FILE_ROOT]/stats/times_diaryland.txt",$filestring);
?>