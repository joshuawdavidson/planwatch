<?php

header("Content-type: text/plain");

date_default_timezone_set("America/New_York");
$_ENV['PWUSERS_DIR']="/var/www/planwatch.org/pwusers";
$_ENV['FILE_ROOT']="/var/www/planwatch.org/public_html";

include_once("$_ENV[FILE_ROOT]/scripts/db_functions.php");
include_once("$_ENV[FILE_ROOT]/scripts/standard_library/simplepie-1.5/autoloader.php");

function _plan_multifeed_sort_items($a, $b)
{
	$array=SimplePie::sort_items($a, $b);
	return $array;
}


echo "\n\n-----\n##### RSS Times #####\n\n";

$feed_chunks=2;
$minutes=date('i');

error_reporting(E_ERROR | E_WARNING | E_PARSE);

$feeds_array       = array_unique(unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/plans_feeds.dat")));
$feeds_times_array = unserialize(file_get_contents("$_ENV[FILE_ROOT]/stats/times_feeds.dat"));
$maildata.=serialize($feeds_array)."\n";

$feed_count=count($feeds_array);
$feed_section=ceil($feed_count / $feed_chunks);
$feed_step=$minutes % $feed_chunks;
//$feed_step=mt_rand(0,$feed_chunks);
$first=max(0,($feed_step*$feed_section) - 2);
$last=min($feed_count,($feed_step*$feed_section) + $feed_section + 2);
//$first = 0; $last = $feed_count;
$feeds_array=array_slice($feeds_array,$first,$feed_section+1);
echo "FEED COUNT: $feed_count (first: $first; last: $last) $feed_step\n";
$maildata.="FEED COUNT: $feed_count (first: $first; last: $last) step $feed_step\n";

foreach($feeds_array as $i=>$urls)
{
	$urls=$feeds_array[$i];
	echo "$i: $urls\n";
	$maildata.="$i: $urls\n";

	// manually fixed urls
	if($urls=="http://www.livejournal.com/users/http://www.planwatch.org/read/avocados_number:salsa24@roberlyn/data/rss")
		$urls="http://avocados_number:salsa24@www.livejournal.com/users/roberlyn/data/rss?auth=digest";
	if($urls=="http://whatnottocrochet.wordpress.com/feed/") $urls="http://feeds.feedburner.com/WhatNotToCrochet";


	$multifeed=TRUE;
	if (is_string($urls))
	{
		$original_urls=$urls;
		if (substr_count($urls,",http") > 0) { $urls=explode(",http",$urls); foreach($urls as $i=>$url) if (!strstr($url,'http')) $urls[$i]="http".$url; }
		else	{ $urls=array($urls); $multifeed=FALSE; }
	}

	$feed_o = new SimplePie($urls);
	$items=$feed_o->get_items();
	$items=array_slice($items,0,count($urls)+1);

	usort($items, "_plan_multifeed_sort_items");
	$items=array_slice($items,0,1);

	if ($items[0])
	{
		$time=$items[0]->get_date('U');
	}
//	else { if(!strstr($original_urls,'esica') && !strstr($original_urls,'deby')) mail("joshuawdavidson@gmail.com","failed feed","$original_urls"); }

	$feed=$original_urls;
	$filestring.="!!!".$feed."...".$time;
	$feeds_times_array[$feed]=$time;
	$maildata.="$feed: $time ".date("F jS Y g:ia",$time)."\n";
	echo "$feed: $time ".date("F jS g:ia",$time)."\n";
}

$file=fopen("$_ENV[FILE_ROOT]/stats/times_feeds.dat",'w');
fwrite($file,serialize($feeds_times_array));
fclose($file);


//mail("joshuawdavidson@icloud.com", "times_rss", $maildata);

?>