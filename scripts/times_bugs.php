<?php

/*TIMES_BUGS.php*/

header("Content-type: text/plain");

date_default_timezone_set("America/New_York");
$_ENV['PWUSERS_DIR']="/var/www/planwatch.org/pwusers";
$_ENV['FILE_ROOT']="/var/www/planwatch.org/public_html";

echo "\n\n-----\n##### Bug Times #####\n\n";


error_reporting(E_ERROR | E_WARNING | E_PARSE);

$bugs_array       = array_unique(unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/plans_bugs.dat")));
$bugs_times_array = unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/times_bugs.dat"));

echo count($bugs_array)." bugs\n\n";

foreach($bugs_array as $url)
{
	$cachefn=md5($url).".bug.cache";

	// if the cache is less than 30m old, read that in. otherwise get the real thing.
	if(file_exists("$_ENV[FILE_ROOT]/temp/$cachefn") && (filemtime("$_ENV[FILE_ROOT]/temp/$cachefn")>(time()-3600)))
	{
		$cached=TRUE;
		$data=file_get_contents("$_ENV[FILE_ROOT]/temp/$cachefn"); 
	}
	else
	{
		$data=file_get_contents($url);
	}

	$xml=simplexml_load_string($data);
	$time=strtotime($xml->bug->delta_ts);

	$filestring.="!!!".$bug."...".$time;
	$feeds_times_array[$bug]=$time;
	echo "$url: $time ".date("F jS g:ia",$time)." (reported ".$xml->bug->delta_ts.")\n";
//	if($cached) echo "cached! $cachefn\n";
//	else echo "not cached! $url\n";
	
	if(!$cached) file_put_contents("$_ENV[FILE_ROOT]/temp/$cachefn",$data);
}

$file=fopen("$_ENV[FILE_ROOT]/stats/times_bugs.dat",'w');
fwrite($file,serialize($bugs_times_array));
fclose($file);

?>