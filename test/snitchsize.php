<?php
$rootdir='/home/planwatc/www';
exec ("ls $rootdir/pwusers/*/stats/planlog.txt",$planlogsize);
foreach($planlogsize as $log)
{
	$user=str_replace(array("$rootdir/pwusers/","/stats/planlog.txt"),'',$log);
	$log_a[$user]=round(filesize($log)/1024,0);
}

arsort($log_a);

foreach($log_a as $k=>$line) echo "$k: $line Kb<br>";
?>
