<?php
/*
HELP.PHP -- part of the planwatch library

helps people with functions of planwatch.org

help files are now in $_SERVER['FILE_ROOT']/resources/help/*.help

------------------------------------------------------------------------
function list
------------------------------------------------------------------------
help_display()
help_write()
help_edit()

------------------------------------------------------------------------
changelog
------------------------------------------------------------------------
27 jan 2003 - made more modular by jwdavidson
18 apr 2005 - revised as part of general planwatch refactoring

------------------------------------------------------------------------
maintained by
------------------------------------------------------------------------
Josh Davidson, 
help@planwatch.org

*/

$_SERVER['HELP_ROOT']="$_SERVER[FILE_ROOT]/resources/help";


// help_display()
//
// reads and returns a help file
//------------------------------------------------------------------------------
function help_display($helpname=FALSE)
{
	include_once('plan_read.php');
	include_once('markdown.php');
	include_once('smartypants.php');
	if ($helpname)
		if (file_exists("$_SERVER[HELP_ROOT]/$helpname.help"))
			include_once("$_SERVER[HELP_ROOT]/$helpname.help");
		else
		{
			$filelist=files_list("$_SERVER[HELP_ROOT]/","*$helpname*.help");
			if ($filelist)
			{
				include_once("$_SERVER[HELP_ROOT]/$filelist[0]");
				$helpname=str_replace('.help','',$filelist[0]);
			}
			else return FALSE;
		}
	
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
		$editlink="<a style='font-size: 9pt; font-family: sans-serif; background: #eec; padding: 2px; border: thin solid #eee;' href='$_SERVER[WEB_ROOT]/help/edit/$helpname'>edit</a>";
	else $editlink='';

	$content=Smartypants(Markdown(plan_process_directives(plan_add_user_links(plan_process_smileys("<h1><a href='/help'><img src='$GLOBALS[helpicon]' /></a> $title $editlink</h1>\n\n$body")))));
 

if ($title || $body) return $content; else return FALSE;
}




// help_write()
//
// puts a help file on disk (from form passed)
//------------------------------------------------------------------------------
function help_write()
{
	if (!user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
		{ redirect(); exit; }

//	if (!strstr($_POST['body'],"<br")) $body=smart_nl2br($_POST['body']);
	else $body=$_POST['body'];
	$body=str_replace('"','&quot;',$body);

	$body='<?php
	$title="'.$_POST['helptitle'].'";
	$body="'.str_replace('$','\$',stripslashes($body)).'";
	?'.'>';

	if ($_POST['oldhelpname']!='new')
		rename(	"$_SERVER[HELP_ROOT]/$_POST[oldhelpnum].$_POST[oldhelpname].help",
				"$_SERVER[HELP_ROOT]/$_POST[oldhelpnum].$_POST[oldhelpname].help.old"
			  );

	file_put_contents("$_SERVER[HELP_ROOT]/$_POST[helpnum].$_POST[helpname].help",$body);
	@chmod("$_SERVER[HELP_ROOT]/$_POST[helpnum].$_POST[helpname].help",0755);
	exec("rm -f $_SERVER[FILE_ROOT]/temp/help*.cache");

redirect("/help/$_POST[helpname]");
}




// help_edit()
//
// presents a form for editing a help file
//------------------------------------------------------------------------------
function help_edit($helpname=FALSE)
{
	if (!user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
		{ redirect(); exit; }

	if ($helpname && $helpname!='new')
		include_once("$_SERVER[HELP_ROOT]/$helpname.help");
	else
		include_once("$_SERVER[HELP_ROOT]/empty.help.template");

	$body=preg_replace("|&(\S+);|","&amp;\\1;",$body);

	$helpnum=substr($helpname,0,strpos($helpname,'.'));
	$helpname=substr($helpname,strpos($helpname,'.'));
	if ($helpname[0]=='.') $helpname=substr($helpname,1);
	$content="
	<form action='$_SERVER[WEB_ROOT]/scripts/help.php' method='post'>

	<input type='hidden' name='oldhelpname' value='$helpname'/>
	<input type='hidden' name='oldhelpnum' value='$helpnum'/>

	<h1><img src='$GLOBALS[helpicon]' />Edit \"$title\"</h1>

	<strong>Title:</strong>
	 <input type='text' style='border: none; background: #ffe; font-size: 20px; width: 90%;' name='helptitle' value='$title'/><br />
	 <em style='opacity: 0.5'>a descriptive title or the question you're answering.</em><br /><br />

	<strong>Link:</strong>
	 <u>http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/help/</u>
	 <input type='text' style='border: none; font-size: $GLOBALS[pfsize]; font-family: $GLOBALS[pfonts]; background: #ffe;' name='helpname' value='$helpname'/>
	 <em style='opacity: 0.5'>one short word, letters and dashes.</em><br />

	<strong>Index #</strong>
	<input type='text'  style='border: none; font-size: $GLOBALS[pfsize]; font-family: $GLOBALS[pfonts]; background: #ffe;' name='helpnum' value='$helpnum'/>
	<em style='opacity: 0.5'>how the help list is sorted</em>

	<textarea name='body' id='textbox'>$body</textarea>

	<input type='hidden' name='action' value='write help'/>

	<br /><br />
	<input type='submit' id='submit_button' name='write help file' value='write help file'/>
	</form>
";
	

output("editing help for $helpname",$content);
}


// help_display_LIST()
//
// returns an HTML list of all the help files as links
//------------------------------------------------------------------------------
function help_display_list()
{
	profile('help','begin');

//	$help_cache_fn="$_SERVER[FILE_ROOT]/temp/help.cache";

	if (!file_exists($help_cache_fn) || @filemtime($help_cache_fn)<(time()-1800))
	{
		$content.="<style>.linkbox li { margin: 10px; }</style>";
		$helplist=files_list("$_SERVER[HELP_ROOT]/","*.help");
		$content.="<ul class='linkbox flicklist' style='float: left;'><br />";
	
		foreach($helplist as $i=>$helpfile)
		{
			$helpfile=basename($helpfile);
			include_once("$_SERVER[HELP_ROOT]/$helpfile");
			$helpname=str_replace('.help','',$helpfile);
			$helpnum=substr($helpname,0,strpos($helpname,'.'));
			if ($helpnum[0] > $lasthelpnum[0] && $lasthelpnum)
				$content.="</ul>\n<ul class='linkbox flicklist' style='float: left;'><br />";
			
			$content.="<li><a href='$_SERVER[WEB_ROOT]/help/$helpname'>$title</a></li>";
			$lasthelpnum=$helpnum;
		}
	
		$content.="</ul>\n<a class='bigbutton' href='$_SERVER[WEB_ROOT]/help/edit/new'>+ new help file</a>\n";
//		file_put_contents($help_cache_fn,$content);
	}
	else $content=file_get_contents($help_cache_fn);
	profile('help','end');


return "\n".$content."\n";
}



// ACTION==WRITE HELP
//----------------------------------------------------------------------
if ($_POST['action']=='write help') help_write();

?>