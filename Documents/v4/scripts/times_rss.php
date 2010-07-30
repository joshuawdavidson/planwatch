<?php

function _plan_multifeed_sort_items($a, $b)
{
	$array=SimplePie::sort_items($a, $b);
	return $array;
}


$_SERVER['PWUSERS_DIR']="/home/planwatc/pwusers";
$_SERVER['FILE_ROOT']="/home/planwatc/public_html";

echo "\n\n-----\n##### RSS Times #####\n\n";

include_once("standard_library/db_functions.php");
include_once('simplepie.inc');

error_reporting(E_ERROR�|�E_WARNING�|�E_PARSE);

$feeds_array       = array_unique(unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/feedlist.dat")));
$feeds_times_array = unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/times_feeds.dat"));


$feed_count=count($feeds_array);
$feed_quintile=ceil($feed_count / 5);
//$feed_step=$minutes % 5;
$feed_step=rand(0,5);
$first=max(0,($feed_step*$feed_quintile) - 2);
$last=min($feed_count,($feed_step*$feed_quintile) + $feed_quintile + 2);
//$first = 0; $last = $feed_count;
$feeds_array=array_slice($feeds_array,$first,$feed_quintile+1);
echo "FEED COUNT: $feed_count (first: $first; last: $last)\n";

foreach($feeds_array as $i=>$urls)
{
	$urls=$feeds_array[$i];
	echo "$i: $urls\n";

	if($urls=="http://www.livejournal.com/users/http://www.planwatch.org/read/avocados_number:salsa24@roberlyn/data/rss")
		$urls="http://avocados_number:salsa24@www.livejournal.com/users/roberlyn/data/rss?auth=digest";

	$multifeed=TRUE;
	if (is_string($urls))
	{
		$original_urls=$urls;
		if (substr_count($urls,",http") > 0) { $urls=explode(",http",$urls); foreach($urls as $i=>$url) if (!strstr($url,'http')) $urls[$i]="http".$url; }
		else	{ $urls=array($urls); $multifeed=FALSE; }
	}

	$feed_o = new SimplePie($urls);
	$items=$feed_o->get_items();
	usort($items, "_plan_multifeed_sort_items");
	$items=array_slice($items,0,1);

	if ($items[0])
	{
		$time=$items[0]->get_date('U');
	}
	else { if(!strstr($original_urls,'esica') && !strstr($original_urls,'deby')) mail("joshuawdavidson@gmail.com","failed feed","$original_urls"); }

	$feed=$original_urls;
	$filestring.="!!!".$feed."...".$time;
	$feeds_times_array[$feed]=$time;
	echo "$feed: $time ".date("F jS g:ia",$time)."\n";
}

$file=fopen("$_SERVER[FILE_ROOT]/stats/times_feeds.dat",'w');
fwrite($file,serialize($feeds_times_array));
fclose($file);


?>