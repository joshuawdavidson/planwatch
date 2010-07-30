<?php

/*
SPIEL.php

a feature for enhancing discussions.
*/


// SPIEL_FORMAT()
//
// makes spiel links visible on plans
//------------------------------------------------------------------------------
function spiel_format($plan,$planowner)
{
	$_SERVER['STOPWATCH']["speil.format_begin"]=array_sum(explode(' ',microtime()));

	preg_match_all("|!sp[ie][ie]l:([^!]*)!|",$plan,$matches);
	$matches=$matches[1];

	if ($matches)
	foreach($matches as $i=>$match)
	{
		if (strpos($match,':')) list($topic,$title)=explode(':',$match);
		else { $topic=$match; $title=$match; }
		$topic=files_encode_safe_name($topic);
		$link=$timecode;
		$plan=str_replace(array("!spiel:$match!","!speil:$match"),"<a href='$_SERVER[WEB_ROOT]/spiel/view/$topic'>$title</a>",$plan);
	}
	
	$plan=str_replace(array('!spiel!','!speil!'),'',$plan);


	//makes sends invisible
	$plan=preg_replace("|(!send:[^!]*!.*!send!)|",'',$plan);

	$_SERVER['STOPWATCH']["speil.format_end"]=array_sum(explode(' ',microtime()));
	return $plan;
}





// SPIEL_FIND()
//
// identifies spiel links in plans
//------------------------------------------------------------------------------
function spiel_find($plan,$planowner,$timecode)
{
	preg_match_all("|!sp[ie][ie]l:([^!]*)!|",$plan,$matches);

	$matches=$matches[1];

	foreach($matches as $i=>$match)
	{
		$summary_begin=strpos($plan,$match);
		$summary=substr($plan,$summary_begin+strlen($match)+1,1024);
		if ($endtag=strpos($plan,'!spiel!',$summary_begin))
		{
			$summary=substr($plan,$summary_begin+strlen($match)+1,$endtag-1);
		}
		elseif (strlen($summary) > 1000)
		{
			$lastspace=strrpos($summary,' ');
			$summary=substr($summary,0,$lastspace+1);
			$summary.="...";
		}

		if (strpos($summary,'!spiel') || strpos($summary,'!speil'))
		{
			$lastspace=max(strpos($summary,'!spiel'),strpos($summary,'!speil'));
			$summary=substr($summary,0,$lastspace);
		}
		
		if (strpos($match,':')) list($topic,$title)=explode(':',$match);
		else { $topic=$match; $title=$match; }
		$topic=files_encode_safe_name($topic);
		$link=$timecode;
		spiel_add_entry($topic,$planowner,$link,$summary,$title);
		$currentspiels=@implode('',@array_unique(@file("$_SERVER[PWUSERS_DIR]/$planowner/spielslist.txt")));
		if (strpos($currentspiels,"\n$topic")===FALSE) $currentspiels.="\n$topic";
		file_put_contents("$_SERVER[PWUSERS_DIR]/$planowner/spielslist.txt",$currentspiels);
	}

return TRUE;
}





// SPIEL_ADD_ENTRY()
//
// add a post to a spiel
//------------------------------------------------------------------------------
function spiel_add_entry($topic,$user,$link,$summary,$title=FALSE)
{
		if (!$title) $title=date("F jS Y, h:ia");
	if (file_exists("$_SERVER[FILE_ROOT]/spiels/$topic.rss"))
	{
		// append a new entry
		$file=file_get_contents("$_SERVER[FILE_ROOT]/spiels/$topic.rss");
		list($header,$body)=explode("<items>",$file);
	$entry="
 <item rdf:about=\"http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$user/.$link\">
  <title>$title</title>
  <link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$user/.$link</link>
  <description>
  $summary
  </description>
  <dc:creator>$user</dc:creator>
  <dc:subject>".files_decode_safe_name($topic)."</dc:subject>
 <dc:date>".gmdate('Y-m-d\TH:i:s')."+00:00</dc:date>
 </item>
";

	$content=$header."<items>\n".$entry."\n".$body;
	}
	else
	{
		// create a new file
		$content="<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?".">

<rdf:RDF
 xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"
 xmlns=\"http://purl.org/rss/1.0/\"
 xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
>

<channel rdf:about=\"http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/\">
 <title>".files_decode_safe_name($topic)."</title>
 <link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/spiel/view/$topic</link>
 <description>A discussion about ".files_decode_safe_name($topic)."</description>
 <dc:language>en-us</dc:language>
 <dc:rights>Copyright &amp;copy; 2000-".date('Y')." the authors listed</dc:rights>
 
 <dc:publisher>planwatch.org</dc:publisher>
 <dc:creator>system@planwatch.org</dc:creator>
 <dc:subject>".files_decode_safe_name($topic)."</dc:subject>

<items>
 <item rdf:about=\"http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$user\">
  <title>$topic</title>
  <link>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/read/$user/.$link</link>
  <description>
  $summary
  </description>
  <dc:creator>$user</dc:creator>
 <dc:date>".gmdate('Y-M-D\TH:i:s')."+00:00</dc:date>
 </item>
</items>
</channel>
</rdf:RDF>
";
	}

file_put_contents("$_SERVER[FILE_ROOT]/spiels/$topic.rss",$content);

return true;
}


// SPIEL_VIEW_PAGE()
//
// presents a whole spiel on a page, like a plan
//------------------------------------------------------------------------------
function spiel_view_page($topic)
{
//	$result = new MagpieRSS(file_get_contents("$_SERVER[FILE_ROOT]/spiels/$topic.rss"));
	$url="http://planwatch.org/spiels/$topic.rss";
	include_once('plan_read.php');
	$content=plan_read_simplepie_rss($url);
/*
	$channel_info=$result->channel;
	$content.= "<h1>$channel_info[title]</h1>\n";
	$content.= "<a href='$_SERVER[WEB_ROOT]/write/?autocontent=!spiel:$channel_info[title]:TITLE%20OF%20YOUR%20ENTRY!'>join the conversation</a><br />\n";
	$content.= "<a href='$_SERVER[WEB_ROOT]/lists/add/spiels/$topic'>watch</a><br /><br />\n";

	foreach($result->items as $item)
	{
		if ($item['items_title'])
		$content.= "
		 <h3><a href='$item[link]'>
		  $item[items_title]
		 </a></h3>\n";

		$dc=$item['dc'];
		if ($dc['items_date']) $timecodes="posted ".formattime(parse_w3cdtf($dc['items_date']));
		$content.= "<span class='edit_links'>$dc[items_subject] | $dc[items_creator] | $timecodes</span><br />\n";
		if ($item['items_description']) $content.= "$item[items_description]<br />";
		
		$content.= "<br />\n\n";
	}

*/	return $content;
}




// SPIEL_GET_LAST_UPDATE()
//
// determines when a spiel was last modified
//------------------------------------------------------------------------------
function spiel_get_last_update($topic)
{
//	if ($topic)	return @filemtime("$_SERVER[FILE_ROOT]/spiels/$topic.rss");
	//else
	return FALSE;
}




// SPIEL_FORMAT_LIST_HTML()
//
// presents a list of spiels
//------------------------------------------------------------------------------
function spiel_format_list_html()
{
	$already_listed=array();
	
	if (file_exists("$_SERVER[PWUSERS_DIR]/$_SERVER[USER]/spielslist.txt"))
		$list=array_unique(file("$_SERVER[PWUSERS_DIR]/$_SERVER[USER]/spielslist.txt"));


	if (is_array($list))	
	foreach($list as $item)
	{
		if (($item=trim($item)) && !in_array(trim($item),$already_listed))
		$content.="<li><a href='$_SERVER[WEB_ROOT]/spiel/view/$item'>".files_decode_safe_name($item)."</a> ".formattime(spiel_get_last_update($item))." [ <a href='$_SERVER[WEB_ROOT]/spiel/ignore/$item'>x</a> ]\n </li>";
		$already_listed[]=trim($item);
	}

	if ($content) $content="<li class='listheader'>Watched Spiels</li>\n $content";

return $content;
}





// SPIEL_IGNORE()
//
// removes a spiel from a user's list
//------------------------------------------------------------------------------
function spiel_ignore($topic)
{
	$list=file("$_SERVER[PWUSERS_DIR]/$_SERVER[USER]/spielslist.txt");

	foreach($list as $i=>$item)
	{
		if (trim($item)==$topic) $list[$i]='';
	}

	file_put_contents("$_SERVER[PWUSERS_DIR]/$user/spielslist.txt",str_replace("\n\n","\n",implode("\n",$list)));
	return TRUE;
}
?>