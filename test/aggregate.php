<?php
/*
AGGREGATE.PHP
*/

include_once('magpie_rss/rss_fetch.inc');
include_once('magpie_rss/rss_utils.inc');

function test_aggregate($urls)
{

	foreach($urls as $url)
	{
		$results[]=@fetch_rss($url);
	}

	foreach($results as $i=>$result)
	{
		foreach($result->items as $j=>$item)
		{
			$item['source']=$result->channel['title'];
			if ($item['dc']['date']) $postdate=parse_w3cdtf($item['dc']['date']);
			elseif ($item['pubdate']) $postdate=strtotime($item['pubdate']);
			elseif ($item['issued'])
				if ($item['modified']!=$item['issued']) $postdate=parse_w3cdtf($item['modified']);
				else  $postdate=parse_w3cdtf($item['issued']);
			$allstories[$postdate]=$item;
		}
		$allimages[$i]=$result->image;
		$allchannels[$i]=$result->channel;
	}

//	@array_multisort($postdates,SORT_DESC,$allstories);
	krsort($allstories);

	foreach($allimages as $image_info)
	if ($image_info)
	{
		$image.="<a href='$image_info[link]' title='$image_info[title]'><img src='$image_info[url]' border='0'></a>";
	}

/*
	foreach($allchannels as $channel_info)
	if ($channel_info)
	{
		$content.= "<h1>$image<a href='$channel_info[link]'>$channel_info[title]</a></h1>
		<i>$channel_info[description]</i><br><br>\n\n";
	}
*/
	$k=0;
	foreach($allstories as $i=>$item)
	{
		if ($k<10)
		{
			if ($item['title'])
			$content.= "
			<div class='story' id='$k'>
			 <a class='title' href='$item[link]'>
			  $item[title]
			 </a>\n";
	
			if ($item['dc']['date']) $timecodes="posted ".formattime(parse_w3cdtf($item['dc']['date']));
			if ($item['pubdate']) $timecodes="posted ".formattime(strtotime($item['pubdate']));
			if ($item['issued']) $timecodes="posted ".formattime(parse_w3cdtf($item['issued']));
			if ($item['modified']!=$item['issued']) $timecodes.=" modified ".formattime(parse_w3cdtf($item['modified']));
	
			$content.= "\n<div class='meta'><span class='source'>[ $item[source] ]</span> $dc[subject] $dc[creator] $timecodes</div>";		
			$content.= "\n<div class='body'>";
			if ($item['atom_content'] && !$item['content']['encoded']) $content.= "$item[atom_content]<br>\n\n";
			if ($item['description'] && !$item['content']['encoded']) $content.= "$item[description]<br>\n";
			if ($item['content']['encoded']) $content.= $item['content']['encoded']."<br>\n\n";
			
			$content.= "</div></div>";
			$k+=1;
			$sidebar.="<a class='title' href='$item[link]'>$item[title]</a><br/><br/>\n";
		}
	}
	$content.=$image;
	
    $content=str_replace("src='/","src='$channel_info[link]/",$content);
    $content=str_replace("src=\"/","src=\"$channel_info[link]/",$content);
    $content=str_replace("SRC='/","src='$channel_info[link]/",$content);
    $content=str_replace("SRC=\"/","src=\"$channel_info[link]/",$content);
return "<div class='sidebar'>Story List<br/>$sidebar</div>\n$content";
}

$urls=file("$rootdir/pwusers/$user/watchedlist.txt");
//$dontagg=file("$rootdir/pwusers/$user/dontaggregate.txt");
foreach($urls as $i=>$url)
{
	if (!strstr($url,'http')) unset($urls[$i]);
	else list($http,$urls[$i],$junk)=explode(':',str_replace('!','',$url));
	if (strstr($urls[$i],'//')) $urls[$i]='http:'.$urls[$i];
}

//$urls=explode(',',$urls);
echo "<html>\n<head>\n<style type='text/css'>
.sidebar { float: right; border: thin solid gray; background: #CCFFFF; width: 20%;}
.sidebar .title { font-size: 9px; font-color: black; }
.story { border: 0px; background: #FFFFCC; width: 35%; margin: 10px; padding: 2px; float: left; height: 100; overflow: scroll; font-size: 10px; font-family: trebuchet ms,verdana;}
.title { font-family: trebuchet ms; font-weight: bold; color: black; font-size: 13px; text-decoration: none; }
.meta { clear: both; font-family: trebuchet ms; font-weight: normal; color: black; font-size: 9px; }
.body { border-top: thick solid #cccc99; font-family: georgia; font-weight: normal; color: black; font-size: normal; background-color: white; }
.source { clear: both; font-family: trebuchet ms; font-weight: bold; }
#0,#1 { background: white; font-size: 12px; margin-top: 0px; }
#0 { background: #FFFFEE; float: left; }
body { text-align: center; }
div { -moz-border-radius: 4px; }
</style>\n</head>\n<body>\n";
echo test_aggregate($urls);
echo "\n</body>\n</html>";
?>