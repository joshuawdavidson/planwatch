<?php
/*
RSS_FINDER.PHP

finds an rss feed for a given web page
*/

function url_read_contents($url)
{
	$file=@fopen($url,'r');
	$body=@fread($file,1024000);
	@fclose($file);

return $body;
}

function feed_choose_preferred($list)
{
	foreach($list as $match)
	{
		if (strpos($match,'atom')) $feed_url=$match;
		if (strpos($match,'xml')) $xml_url=$match;
		if (strpos($match,'rss')) $rss_url=$match;
		if (strpos($match,'rdf')) $rdf_url=$match;
	}
	if (!$feed_url)	$feed_url=$xml_url;
	if (!$feed_url) $feed_url=$rss_url;
	if (!$feed_url) $feed_url=$rdf_url;

return $feed_url;
}

function find_feed_url($url)
{
	// get the passed url
	if (!$url) $url=$_GET['url'];
	echo "get the passed url: $url<br>\n";
	
	// do some basic correction
	if (strpos($url,'http://')===FALSE) $url="http://$url";
	if (substr_count($url,'.')==0) $url=$url.".com";
	echo "do some basic correction: $url<br>\n";
	
	// first attempt
	$body=url_read_contents($url);
	echo "first attempt: $url<br>\n";
	
	// second attempt
	// try it with a www. if there's only 1 period and there was a failure
	if (!$body && substr_count($url,'.')==1)
	{
		$testurl=str_replace('//','//www.',$url);
		$body=url_read_contents($testurl);
	echo "second attempt: $testurl<br>\n";
	}
	
	// third attempt
	// try it with a .org instead of .com or .net if there was a failure
	if (!$body && (strpos($url,'.com') || strpos($url,'.net')))
	{
		$testurl=str_replace(array('.com','.net'),'.org',$url);
		$body=url_read_contents($testurl);
	echo "third attempt: $testurl<br>\n";
	}
	
	// fourth attempt
	// try it with a .net instead of .com or .org if there was a failure
	if (!$body && (strpos($url,'.com') || strpos($url,'.org')))
	{
		$testurl=str_replace(array('.com','.org'),'.net',$url);
		$body=url_read_contents($testurl);
	echo "fourth attempt: $testurl<br>\n";
	}
	
	// fifth attempt
	// try it with a .com instead of .net or .org if there was a failure
	// this may result in some duplicated effort for really broken urls
	if (!$body && (strpos($url,'.net') || strpos($url,'.org')))
	{
		$testurl=str_replace(array('.net','.org'),'.com',$url);
		$body=url_read_contents($testurl);
	echo "fifth attempt: $testurl<br>\n";
	}
	
	// sixth attempt
	// try it with .blogger.com in case we're really being lazy about our inputs
	if (!$body && !strpos($_GET['url'],'.net') && !strpos($_GET['url'],'.org') && !strpos($_GET['url'],'.com'))
	{
		$testurl="http://$_GET[url].blogger.com";
		$body=url_read_contents($testurl);
	echo "sixth attempt: $testurl<br>\n";
	}
	
	// seventh attempt
	// same as sixth, but with blogspot
	if (!$body && !strpos($_GET['url'],'.net') && !strpos($_GET['url'],'.org') && !strpos($_GET['url'],'.com'))
	{
		$testurl="http://$_GET[url].blogspot.com";
		$body=url_read_contents($testurl);
	echo "seventh attempt: $testurl<br>\n";
	}
	
	// if we don't have body by now, we never will. but just for fun, let's try google's 
	// I'm Feeling Lucky
	if (!$body)
	{
		include_once('google.php');
		$results=googleSearch("$_GET[url]",0);
		$results=$results->results; //[0]['url'];
		$testurl=$results[0]->url;
	//	var_dump($results[0]);
	//	$testurl="http://www.google.com/search?q=$_GET[url]&btnI=I%27m+Feeling+Lucky";
		$body=url_read_contents($testurl);
	echo "lucky attempt: $testurl<br>\n";
	}
	
	////--------------------------------------------------------------------------------------
	// right. so now we have the body of *something*. we should start parsing it.
	////--------------------------------------------------------------------------------------
	
	// first, see if there's a link alternate. prefer atom over rss if available.
	preg_match_all("<link rel=[\"']alternate[\"'].* href=[\"'](\S+)[\"']>",$body,$matches[0]);
	preg_match_all("<link.* href=[\"'](\S+)[\"'] rel=[\"']alternate[\"']>",$body,$matches[1]);
	$matches=array_merge($matches[1][1],$matches[0][1]);
	
	$feed_url=feed_choose_preferred($matches);
	echo "link alternates: $feed_url<br>\n";
	
	
	// if there aren't any link alternates, look for same-server rss or atom or xml links
	list($crap,$server)=explode('.',$url,2);
	
	if (!$feed_url && strlen($body)>100)
	{
		preg_match_all("|<a.*href=[\"']($server.*rss[^\"'<> ]*)[\"'].*>|",$body,$matches[0]);
		preg_match_all("|<a.*href=[\"']($server.*xml[^\"'<> ]*)[\"'].*>|",$body,$matches[1]);
		preg_match_all("|<a.*href=[\"']($server.*atom[^\"'<> ]*)[\"'].*>|",$body,$matches[2]);
		preg_match_all("|<a.*href=[\"'](/.*atom[^\"'<> ]*)[\"'].*>|",$body,$matches[3]);
		preg_match_all("|<a.*href=[\"'](/.*rss[^\"'<> ]*)[\"'].*>|",$body,$matches[4]);
		preg_match_all("|<a.*href=[\"'](/.*xml[^\"'<> ]*)[\"'].*>|",$body,$matches[5]);
		$matches=array_merge($matches[1][1],$matches[0][1],$matches[2][1],$matches[3][1],$matches[4][1],$matches[5][1]);
	
		$feed_url=feed_choose_preferred($matches);
	echo "same server links: $feed_url<br>\n";
	}
	
	
	
	// if there aren't any same-server links, look for off-server links
	if (!$feed_url && strlen($body)>100)
	{
		preg_match_all("<a.*href=[\"'](.*atom[^\"'<> ]*)[\"'].*>",$body,$matches[0]);
		preg_match_all("<a.*href=[\"'](.*rss[^\"'<> ]*)[\"'].*>",$body,$matches[1]);
		preg_match_all("|<a.*href=[\"'](.*xml[^\"'<> ]*)[\"'].*>|",$body,$matches[2]);
		$matches=array_merge($matches[0][1],$matches[1][1],$matches[2][1]);
		
		$feed_url=feed_choose_preferred($matches);
	echo "off server links: $feed_url<br>\n";
	}
	
	
	// if that doesn't work, try syndic8 with the url.
	if (!$feed_url && strlen($body)>10)
	{
		include_once('xmlrpc.inc');
	
		$xmlstring=new xmlrpcval($url,'string');
		
		$f=new xmlrpcmsg('syndic8.FindFeeds');
		$f->addParam($xmlstring);
		$c=new xmlrpc_client('/xmlrpc.php','www.syndic8.com',80);
		
		$c->setDebug(0);
		$r=$c->send($f);
		if ($r)
		{
			$c->setDebug(0);
			$f=new xmlrpcmsg('syndic8.GetFeedInfo');
			$f->addParam($r->value());
	//		$f->addParam(new xmlrpcval(array("status","fetchable","siteurl","dataurl"),'array'));
			$r=$c->send($f);
			if ($r)
				if (!$r->faultCode())
					$feedlist=xmlrpc_decode($r->value());
		
			foreach($feedlist as $i=>$feed)
			{
				if ($feed['status']=='Syndicated' && $feed['fetchable']) $difference[$i]=levenshtein($url,$feed['siteurl']); 
			}
			$most_similar=@min($difference);
			$feed_key=@array_search($most_similar,$difference);
			$feed_url=$feedlist[$feed_key]['dataurl'];
		}
	
	echo "syndic8 attempt 1: $feed_url<br>\n";
	}
	
	// if that doesn't work, try syndic8 with the passed value.
	if (!$feed_url && strlen($body)>10)
	{
		include_once('xmlrpc.inc');
	
		$xmlstring=new xmlrpcval($_GET['url'],'string');
		
		$f=new xmlrpcmsg('syndic8.FindFeeds');
		$f->addParam($xmlstring);    
		$c=new xmlrpc_client('/xmlrpc.php','www.syndic8.com',80);
		
		$c->setDebug(0);
		$r=$c->send($f);
		if ($r)
		{
			$c->setDebug(0);
			$f=new xmlrpcmsg('syndic8.GetFeedInfo');
			$f->addParam($r->value());
			$f->addParam(new xmlrpcval(array("status","fetchable","siteurl","dataurl"),'array'));
			$r=$c->send($f);
			if ($r)
				if (!$r->faultCode())
					$feedlist=xmlrpc_decode($r->value());
		
			foreach($feedlist as $i=>$feed)
			{
				if ($feed['status']=='Syndicated' && $feed['fetchable']) $difference[$i]=levenshtein($url,$feed['siteurl']); 
			}
			$most_similar=@min($difference);
			$feed_key=array_search($most_similar,$difference);
			$feed_url=$feedlist[$feed_key]['dataurl'];
		}
	echo "syndic8 attempt 2: $feed_url<br>\n";
	}
	
	echo "<strong>feed url: ".$feed_url."</strong>";

return $feed_url;
}

if (!$_GET['url'])
{
	$content="<form action='/test/rss_finder.php' method=GET><h1>Find an RSS or Atom Feed</h1>
	Enter a URL or site name here: <input type='text' name='url' value=''><input type='submit' value='find feed'>
	</form>";

	output("Feed Finder","$content");
}

if ($_GET['url']) redirect("/read/".find_feed_url($_GET['url']));

?>