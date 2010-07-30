<?php
/*
FEATURECREEP.PHP -- part of the planwatch library

a bug/feature tracking page
pretty rudimentary, but no one uses it anyway


the features are stored in 
$_SERVER['FILE_ROOT']/features/xxxxxxxxx.feature,
where the 'x's are the timecode of
first submission

they're textfiles in the form of URL variables:

	submitter=blah&feature=blah&...

which are processed by parse_str()
to become variables

*/



// GETFEATURES_ATOM()
//
// builds an rss feed of features currently pending
// or that have been resolved in the last
// 48 hours
//------------------------------------------------------------------------------
function getfeatures_atom($threshhold='2w',$sortby=FALSE,$sortdir=FALSE,$searchterm=FALSE)
{
	exec("ls -c /home/planwatc/public_html/features/*.feature",$featurelist);
	if (!$threshhold) $threshhold='2w';

	$thresh_url=$threshhold;
	if (strstr($threshhold,'r')) { $threshhold=str_replace('r','',$threshhold); $reverse=1; }

	if (strstr($threshhold,'w')) $threshhold=time()-(604800*str_replace('w','',$threshhold));
	if (strstr($threshhold,'d')) $threshhold=time()-(86400*str_replace('d','',$threshhold));
	if (strstr($threshhold,'h')) $threshhold=time()-(3600*str_replace('h','',$threshhold));
	if (strstr($threshhold,'m')) $threshhold=time()-(60*str_replace('m','',$threshhold));

	if (!$sortby) $sortby='featuretime';
	if (!$sortdir) $sortdir='down';

	$planowner="Feature Request/Bug Tracking";
	$rss_link="http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/feature";
	$lastupdatetime=time();

	if ($featurelist)
	{
		foreach($featurelist as $i=>$feature)
		{
			$feature=trim($feature);
			parse_str(file_get_contents($feature),$feature_array[$i]);
			$feature_array[$i]['featuretime']=filemtime($feature);
			$feature_array[$i]['url_feature']=basename($feature);
			$sort_array[$i]=$feature_array[$i][$sortby];
		}
		
		if ($sortdir=='down') array_multisort($sort_array,SORT_DESC,$feature_array);
		else array_multisort($sort_array,SORT_ASC,$feature_array);

		foreach($feature_array as $i=>$feature)
		{
			if (($feature['featuretime'] > $threshhold) || ($feature['status']!="Verified Fixed" && stripslashes($feature['status'])!="Won't Fix" && $feature['status']!="On Hold"))
			{
	            $somefeature=TRUE;
	            $entry_content=stripslashes(htmlentities($feature['response']."\n\n[Original Issue]\n".$feature['note']));
	            $entry_summary=smart_trim(strip_tags($entry_content),255,FALSE);
	            $entry_updated=date('Y-m-d\TH:i:s+00:00',$feature['featuretime']);
			    $plan.="
					<entry>
						<link rel=\"alternate\" href=\"http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/feature/description/$feature[url_feature]\" type=\"text/html\"/>
						<title type='html'>$feature[title] ($feature[priority], $feature[status])</title>
						<summary type='html'>$entry_summary</summary>
						<content type='html'>$entry_content</content>
						<updated>$entry_updated</updated>
						<id>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/feature/description/$feature[url_feature]</id>
						<author>
							<name> [$feature[submitter]] </name>
						</author>
					</entry>";
			}
				
		}		

	}

	if (!isset($somefeature))
	{
	            $entry_updated=date('Y-m-d\TH:i:s+00:00',time());
			    $plan.="
					<entry>
						<title type='html'>All Clear</title>
						<summary type='html'>Nothing to see here</summary>
						<content type='html'>All feature requests and bugs have been satisfied. Go have a cup of tea.</summary>
						<link rel=\"alternate\" href=\"http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/feature/\" type=\"text/html\"/>
						<updated>$entry_updated</updated>
						<id>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/feature/</id>
						<author>
							<name>jwdavidson</name>
						</author>
					</entry>";
	}

$plan="<link rel=\"self\" href=\"http://planwatch.org/scripts/featurecreep.php?action=getfeatures\" type=\"text/xml\"/>\n$plan";

//$_SERVER['OUTPUT_MODE']='ATOM1.0';
//$content=output("Bugs / Features","<!-- FEED_DIVIDER --> $plan");
//$_SERVER['OUTPUT_MODE']='HTML';
//echo $content;
	
return $plan;	
}


// GETFEATURES()
//
// displays list of features currently pending
// or that have been resolved in the last
// 48 hours
//------------------------------------------------------------------------------
function getfeatures($threshhold='2d',$sortby=FALSE,$sortdir=FALSE,$searchterm=FALSE)
{
	if (!$searchterm) exec("ls $_SERVER[FILE_ROOT]/features/*.feature",$featurelist);
	else exec("grep -il '$searchterm' $_SERVER[FILE_ROOT]/features/*.feature",$featurelist);
	if (!$threshhold) $threshhold='2w';

	$thresh_url=$threshhold;
	if (strstr($threshhold,'r')) { $threshhold=str_replace('r','',$threshhold); $reverse=1; }

	if (strstr($threshhold,'w')) $threshhold=time()-(604800*str_replace('w','',$threshhold));
	if (strstr($threshhold,'d')) $threshhold=time()-(86400*str_replace('d','',$threshhold));
	if (strstr($threshhold,'h')) $threshhold=time()-(3600*str_replace('h','',$threshhold));
	if (strstr($threshhold,'m')) $threshhold=time()-(60*str_replace('m','',$threshhold));

	if (!$sortby) $sortby='featuretime';
	if (!$sortdir) $sortdir='down';

	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
		$watch_link="[ <a href='$_SERVER[WEB_ROOT]/lists/add/watched/!http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/feature/atom:planbugs!'>add to your watched list</a> ]";

	$content.=stripslashes("

<div align='center'>
<h1>Planwatch.org Bugs</h1>
<a style=' width: 250px; margin: auto; margin-bottom: 20px; display: block; background: #293; font-size: 20px; font-weight: bold; border-radius: 10px; -moz-border-radius: 10px; -webkit-border-radius: 10px; border-left: 2px solid rgba(255,255,255,0.5); border-top: 2px solid rgba(255,255,255,0.5); border-right: 2px solid rgba(0,0,0,0.5); border-bottom: 2px solid rgba(0,0,0,0.5); color: white;' href='$_SERVER[WEB_ROOT]/feature/new'>Report a Bug!<br /><span style='font-size: 12px; font-weight: normal;'>(or request a feature)</span></a>

<style type='text/css'>
	ul { list-style: none; margin: 0px; padding: 0px; }
	li { text-align: left; margin-bottom: 5px; background: $GLOBALS[listsbgcolor]; color: $GLOBALS[navtextcolor]; }
	li h1 { font-size: 22px; margin: 0px; }
	li.fixed { opacity: 0.5; }
	li.fixed h1 { font-size: 14px; }
	li.testing { opacity: 0.7; background-color: $GLOBALS[navtextcolor]; color: $GLOBALS[listsbgcolor]; }
	li.testing a { color: $GLOBALS[listsbgcolor]; }
	li.testing h1 { font-size: 18px; }
	li.progress { opacity: 0.9; background-color: $GLOBALS[navlinkcolor]; color: $GLOBALS[listsbgcolor]; }
	li.progress a { color: $GLOBALS[listsbgcolor]; }
	li.progress h1 { font-size: 20px; }
</style>
<ul>");

	if ($featurelist)
	{
		foreach($featurelist as $i=>$feature)
		{
			$feature=trim($feature);
			parse_str(file_get_contents($feature),$feature_array[$i]);
			$feature_array[$i]['featuretime']=filemtime($feature);
			$feature_array[$i]['url_feature']=basename($feature);
			$sort_array[$i]=$feature_array[$i][$sortby];
		}
		
		if ($sortdir=='down') array_multisort($sort_array,SORT_DESC,$feature_array);
		else array_multisort($sort_array,SORT_ASC,$feature_array);

		foreach($feature_array as $i=>$feature)
		{
			if (($feature['featuretime'] > $threshhold) || ($feature['status']!="Verified Fixed" && stripslashes($feature['status'])!="Won't Fix" && $feature['status']!="On Hold"))
			{
	            $somefeature=TRUE;
				if (!$feature['latest_responder']) $feature['latest_responder']=$feature['submitter'];

				$lastresponse="\n\t\t".formattime($feature['featuretime'])." by $feature[latest_responder] \n\t\t";

				$class='';
				if($feature['status']=='Verified Fixed') $class='fixed';
				if($feature['status']=='Testing') $class='testing';
				if($feature['status']=='In Progress') $class='progress';

				$content.="<li class='$class'><div style='float: right; width: 140px; text-align: right;'>$feature[priority]<br />$feature[status]</div><h1><a href='$_SERVER[WEB_ROOT]/feature/description/$feature[url_feature]'>$feature[title]</a></h1>from $feature[submitter] &lowast; $lastresponse</li>";
			}
				
		}		

	}
	$content.="<input type='search' id='searchinput' placeholder='search' style='width: 90%; font-size: 16px;' />";
	$content.="</ul>";

return $content;	
}




// EDITFEATURE
//
// a form to edit or create a feature or
// bug report
//
// new entries can be created by anyone
// with a username
//
// existing entries can only be edited by
// the submitter or one of the admins
//------------------------------------------------------------------------------
function editfeature($featurename='new')
{
	if (file_exists("$_SERVER[FILE_ROOT]/features/$featurename"))
	{
		parse_str($raw_data=file_get_contents("$_SERVER[FILE_ROOT]/features/$featurename"));
		$status=stripslashes($status);
		$note=stripslashes($note);
		$response=stripslashes($response);
		$pagetitle="<h1>".str_replace("'","&apos;",stripslashes(stripslashes($title)))."</h1>";
		$formtitle=str_replace("'","&apos;",stripslashes(stripslashes($title)));
		$time_noticed=stripslashes(stripslashes($time_noticed));
		$note=stripslashes(stripslashes($note));
	}
	else
	{
//		$title='this thing... it, um, broke';
		$time_noticed='just now, actually';
		$note='';
		$pr['AAH!!']='SELECTED';
		$user_agent=$_SERVER['HTTP_USER_AGENT'];
		$ip=$_SERVER['REMOTE_ADDR'];
		$status='New';
	}

	if ($featurename=='new' || !$featurename) $submitter=$_SERVER['USER'];

	$st[$status]=' selected="selected" ';
	$pr[$priority]=' selected="selected" ';

	$content.="
	<form action='$_SERVER[WEB_ROOT]/scripts/featurecreep.php' method='post' style='margin: 0px; float: left;'>";


	$content.="
			<textarea name='title' style='font-size: 30px; font-weight: bold; border: 0px;'>$formtitle</textarea><br />\n";
	if (user_is_administrator())
	{
		$content.="
					Status
						<select name='status'>
							<option value='New' $st[New]>New</option>
							<option value='Scheduled' ".$st['Scheduled'].">Scheduled</option>
							<option value='In Progress' ".$st['In Progress'].">In Progress</option>
							<option value='Testing' ".$st['Testing'].">Testing</option>
							<option value='Verified Fixed' ".$st['Verified Fixed'].">Verified Fixed</option>
							<option value=\"Won't Fix\" ".$st["Won't Fix"].">Won't Fix</option>
							<option value='On Hold' ".$st['On Hold'].">On Hold</option>
						</select><br />
	            ";
	}


	if ($featurename && $featurename!='new')
	{
		$content.="
						Response <input type='hidden' name='latest_responder' value='$_SERVER[USER]'/><br />
						<textarea style='width: 70%; height: 100px;' onfocus='this.style.height=\"300px\"' onblur='this.style.height=\"100px\"' name='latest_response'></textarea>
						<textarea name='response' style='width: 10px; height: 10px; visibility: hidden;'>".htmlentities($response)."</textarea>
				\n";

		$oldinfo.="
		  <aside style='width: 300px; font-size: 12px;'>
					  <input type='hidden' name='title' value='$formtitle'/>
			<i>Submitted by:</i> $submitter<br />
					  <input type='hidden' value='$submitter' name='submitter'/>
			<i>Priority:</i> $priority<br />
					  <input type='hidden' value='$priority' name='priority'/>
			<i>Noticed:</i> $time_noticed<br />
					  <input type='hidden' value='$time_noticed' name='time_noticed'/>
			<i>Description:</i><br /> ".nl2br($note)."<br /><br />
					  <textarea name='note' style='width: 10px; height: 10px; visibility: hidden;'>".htmlentities($note)."</textarea>
				Status: <b>$status</b><br />
			Previous Response:<br />
			".nl2br($response)."
		</aside>\n";

	}
	else
	{
		$content.="
						<br />What happened? <span style='font-size: smaller;'>What did you expect? (details are helpful)</span><br />
						<textarea rows='10' cols='60' wrap='hard' name='note'>".htmlentities($note)."</textarea>
						<br /><br />When did you notice it?
						<input type='text' name='time_noticed' size='20' value=\"$time_noticed\"/>
						<br /><br />Give it a name
						<input type='text' name='title' size='40' value=\"$formtitle\"/>
		\n";
	}

	$animal_array=array("bear","bunny","penguin");
	$animal=$animal_array[rand(0,count($animal_array)-1)];
	$animal_hash=md5($animal);
	

	$content.="
					<br /><br />How big a deal is this? 
						<select name='priority'>
							<option value='AAAHHHH!!!!' ".$pr['AAAHHHH!!!!'].">AAAHHHH!!!!</option>
							<option value='AAH!!' ".$pr['AAH!!'].">AAH!!</option>
							<option value='Hmm?' ".$pr['Hmm?'].">Hmm?</option>
							<option value='eh.' ".$pr['eh.'].">eh.</option>
							<option value='Feature Request' ".$pr['Feature Request'].">Feature Request</option>
						</select><br /><br />
						<table style='background: rgba(0,0,0,0.3);'><tr><td>
						<img id='captcha' src='/resources/animals/$animal.jpg' alt='animal' /> </td><td>
						bear, bunny, or penguin? <br /><input type='text' name='animal' style='font-size: 15px;' /></td></tr></table>
						<input type='hidden' name='animal_hash' value='$animal_hash' />
						<input type='submit' value='send report' style='font: 20px sans-serif; font-weight: bold; background: #263; color: white;' />
	


		<input type='hidden' value='$user_agent' name='user_agent'/>
		<input type='hidden' value='$ip' name='ip'/>
		<input type='hidden' value='$featurename' name='oldtitle'/>
		<input type='hidden' value='$_SERVER[USER]' name='submitter'/>
		<input type='hidden' value='$featurename' name='oldtitle'/>
		<input type='hidden' value='write' name='action'/>
		</form><!--$raw_data-->\n\n";

		$content.="
		  <div style='font-size: 12px; float: right; width: 300px;'>
					  <input type='hidden' name='title' value='$formtitle'/>
			<i>Submitted by:</i> $submitter<br />
					  <input type='hidden' value='$submitter' name='submitter'/>
			<i>Priority:</i> $priority<br />
					  <input type='hidden' value='$priority' name='priority'/>
			<i>Noticed:</i> $time_noticed<br />
					  <input type='hidden' value='$time_noticed' name='time_noticed'/>
			<i>Description:</i><br /> ".nl2br($note)."<br /><br />
					  <textarea name='note' style='width: 10px; height: 10px; visibility: hidden;'>".htmlentities($note)."</textarea>
				Status: <b>$status</b><br />
			Previous Response:<br />
			".nl2br($response)."
		</div>\n";



	if ($_SERVER['USER']!=$submitter && !user_is_administrator() && $submitter)
		$content="you can't edit this feature/bug.";


	return $content;
}

// WRITEFEATURE
//
// writes feature to disk as described
// in the intro
//------------------------------------------------------------------------------
function writefeature()
{
	extract($_POST);
	if (md5($_POST['animal'])!=$_POST['animal_hash'])
	{
		output("Failed","<h1>Sorry</h1> You can't seem to tell the
		difference between cute animal babies, so I'm guessing you're not human. You can try again, if you want.");
	}
	

//	$title=str_replace("'",'"',$title);
	$title=htmlentities($title);
	$title_fn=files_encode_safe_name($title);
	
	$feature_fn="$_SERVER[FILE_ROOT]/features/$title_fn.feature";
	
	if (file_exists($feature_fn) && $oldtitle!="$title_fn.feature")
	{
		$title_fn.=time();
		$feature_fn="$_SERVER[FILE_ROOT]/features/$title_fn.feature";
	}

	if ($oldtitle!="$title.feature" && $oldtitle!='new')
		rename("$_SERVER[FILE_ROOT]/features/$oldtitle","$_SERVER[FILE_ROOT]/features/$oldtitle.off");
	
	if ($response || $latest_response) $response.="\n\n-----\n\n[$latest_responder]:\n$latest_response";
	
	if (!$latest_responder) $latest_responder=$submitter;
	$feature_data=
		"title=".urlencode(stripslashes($title)).
		"&note=".urlencode(stripslashes($note)).
		"&status=".urlencode(stripslashes($status)).
		"&response=".urlencode(stripslashes($response)).
		"&submitter=".urlencode(stripslashes($submitter)).
		"&time_noticed=".urlencode(stripslashes($time_noticed)).
		"&user_agent=".urlencode(stripslashes($user_agent)).
		"&ip=".urlencode(stripslashes($ip)).
		"&priority=".urlencode(stripslashes($priority)).
		"&latest_responder=".urlencode(stripslashes($latest_responder))
		;

	if (!$failed)
	{
		file_put_contents($feature_fn,$feature_data);
		mail(
			"help@planwatch.org",
			"Bug updated: ".urlencode(stripslashes($title)),
			stripslashes("$title    [ $priority ]    [ $status ]
			$submitter $_SERVER[USER] {$_SERVER['USERINFO_ARRAY']['email']} {$_SERVER['USERINFO_ARRAY']['real_name']}
			\n-------------------------------------------------------------
			\n[$latest_responder] $latest_response
			\n$response
			\n-------------------------------------------------------------
			\nOriginal report:\n$note
			\n-------------------------------------------------------------
			\nView: http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/feature/description/$title_fn.feature
			\nEdit: http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/feature/edit/$title_fn.feature"),
			"From: $submitter <bugs@planwatch.org>");
		exec("rm -f $_SERVER[FILE_ROOT]/temp/magpie_cache/".base64_encode("http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/features/index.atom"));
		file_put_contents("$_SERVER[FILE_ROOT]/features/index.atom",getfeatures_atom());
		redirect("/feature");
	}

return 0;
}

// DISPLAYFEATURE
//
// displays a feature in a non-editable
// manner
//------------------------------------------------------------------------------
function displayfeature($featurename='')
{
	if (file_exists("$_SERVER[FILE_ROOT]/features/$featurename"))
	{
		parse_str(file_get_contents("$_SERVER[FILE_ROOT]/features/$featurename",'r'));
		$real=1;
	}	

	if (!$response) $response='none yet';

	$content.=stripslashes("
	<h1>$title</h1>
	Status:	<b>$status</b><br />
	Priority: <b>$priority</b><br />
	Time Noticed: <b>$time_noticed</b><br />
	Submitted by: <b>$submitter</b><br />
	IP: <b>$ip</b><br /><br />
	Browser Used: <b>$user_agent</b><br /><br />

	<b>Description:</b><br />
	".nl2br($note)."
	<br /><br />

	<b>Response:</b><br />
	".nl2br($response)."
	<br /><br />\n");
	if (user_is_administrator() || $_SERVER['USER']==$submitter)
	$content.="	[ <a href='$_SERVER[WEB_ROOT]/feature/edit/$featurename'>edit</a> ]";

if ($real) return $content;
else return "<div class='alert'>We can't find a feature request or bug report by the name <i>$featurename</i>.</div>";
}

if ($_POST['action']=='write') writefeature();
if ($_GET['action']=='getfeatures') { $_SERVER['OUTPUT_MODE']='ATOM1.0'; output("Features",getfeatures_atom()); }

?>