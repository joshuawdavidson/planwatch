<?php

$text="
!spiel:blah:blha1! this is an example.

!speil:blah2:adsga sfasd s! this is another.
";

echo spiel_format($text);

function spiel_format($plan)
{
	preg_match_all("|!sp[ie][ie]l:([^!]*)!|",$plan,$matches);
//	preg_match_all("|!speil:(.*)!|",$plan,$matches_misspell);
//	$matches=array_merge($matches[1],$matches_misspell[1]);
	$matches=$matches[1];
//	var_dump($matches);
	if ($matches)
	foreach($matches as $match)
	{
		if (strpos($match,':')) list($topic,$title)=explode(':',$match);
		else { $topic=$match; $title=$match; }
		$topic=safe_filename_encode($topic);
		$plan=str_replace(
			"!spiel:$match!",
			"<em>$title</em>
			<span style='font-size: smaller;'>
				[ <a href='/spiel/view/$topic'>view all</a>
				  <a href='/lists/add/watched/!http://planwatch.org/stats/spiels/$topic.rss:$topic!'>watch</a>
				]
			</span>",
			$plan);
		$plan=str_replace(
			"!speil:$match!",
			"<em>$title</em>
			<span style='font-size: smaller;'>
				[ <a href='/spiel/view/$topic'>view all</a>
				  <a href='/lists/add/watched/!http://planwatch.org/stats/spiels/$topic.rss:$topic!'>watch</a>
				]
			</span>",
			$plan);
	}

return $plan;
}

?>