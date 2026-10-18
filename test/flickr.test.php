<?php

$api_key="4038142368f7aee684284406e0db6eda";

$email="josdav@tfn.net";
$flickr_getid_stream=file_get_contents("http://www.flickr.com/services/rest/?method=flickr.people.findByEmail&find_email=$email&api_key=$api_key");

//Header("Content-type: text/plain");
//echo $flickr_getid_stream;

preg_match("|nsid=\\\"(.+)\\\">|",$flickr_getid_stream,$matches);
//print_r($matches);

$nsid=$matches[1];

$method="flickr.people.getPublicPhotos";
$flickr_stream=file_get_contents("http://www.flickr.com/services/rest/?method=$method&user_id=$nsid&api_key=$api_key");
$start=array_sum(explode(' ',microtime()));


//echo $flickr_stream;
echo "<h1>Photos</h1>";
$photo_list=explode("<photo ",$flickr_stream);
unset($photo_list[0]);
foreach($photo_list as $photo_line)
{
	preg_match("|id=\\\"([^\\\"]+)\\\"|",$photo_line,$matches);
	$id=$matches[1];
	preg_match("|owner=\\\"([^\\\"]+)\\\"|",$photo_line,$matches);
	$owner=$matches[1];
	preg_match("|secret=\\\"([^\\\"]+)\\\"|",$photo_line,$matches);
	$secret=$matches[1];
	preg_match("|server=\\\"([^\\\"]+)\\\"|",$photo_line,$matches);
	$server=$matches[1];
	preg_match("|title=\\\"([^\\\"]+)\\\"|",$photo_line,$matches);
	$title=$matches[1];
	
	echo "<img src='http://static.flickr.com/$server/{$id}_$secret.jpg' title='$title'/>";
}

$end=array_sum(explode(' ',microtime()));

echo round($end-$start,20);
?>