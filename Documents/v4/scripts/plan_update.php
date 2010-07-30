<?php

/*
PLAN_UPDATE.php

allows updating of local plans

	// the nonjournaling plan is stored as plan.txt in /pwusers/$user/plan/
	// archived nonjournaling plans are stored as plan.txt.xxxxxxxxxx
	// where the x's represent the timecode of the creation of the entry
	// so they can be referenced via archives.

	// the way to test for a nonjournaling plan vs. a journaling one
	// is to check the journaling field inside the userinfo.dat file.

	// journaling plans will have any number of plan.*.txt files, but not
	// a single plan.txt file

	// TODO:(v4.1) plan_write_traditional() and plan_write_journaling() could probably be consolidated
	// TODO:(v4.1) new /write interface
	// TODO:(v4.1) get blogger/mwl api solid for updating
	// TODO:(v5) allow updating of LJ and cross-node plans
*/



// PLAN_LIST_DRAFTS
//
// looks for drafts in the plan directory, returns an html list of results
function plan_list_drafts()
{
	$draft_list=files_list("$_SERVER[USER_ROOT]/plan/","draft*");
	if(is_array($draft_list))
	foreach($draft_list as $draft)
	{
		$draft=str_replace(array("draft",".txt"),'',basename($draft));
		$draft_time=formattime(str_replace('.','',$draft));
		$draft_links.="<li><a href='/write/$draft'>$draft_time</a> [ <a href=\"javascript:loadXMLDoc('http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/plan_update.php','action=Delete%20Draft%20Ajax&draft_time=$draft','drafts',processReqChangePOST);\">x</a> ]</li>\n";
	}
	if (!$draft_links) $draft_links="no saved drafts.";
	return $draft_links;
}

//plan_update_2010
// will replace plan_update() eventually
// with a subcall structure like plan_read
//   plan_update_mobile
//   plan_update_tiny
//   plan_update_lowfi
//   plan_update_modern
//   plan_update_tweak
function plan_update_2010($edit=FALSE,$writer=FALSE)
{
	if(!$writer) $writer=$_SERVER['USER'];
	// set dir for reading
	$_SERVER['WRITER']['plan_dir']="$_SERVER[PWUSERS_DIR]/$writer/plan";
	$_SERVER['WRITER']['journaling']=plan_is_journaling($writer);


	if(browser_is_modern()) $content=plan_update_modern($edit,$writer);
	if($edit=='tiny') $content=plan_update_tiny($edit,$writer);
	if($_SERVER['OUTPUT_MODE']=='AJAX') $content=plan_update_tweak($edit,$writer);
	if($_SERVER['OUTPUT_MODE']=='IPHONE') $content=plan_update_mobile($edit,$writer);
	if(!browser_is_modern()) $content=plan_update_lowfi($edit,$writer);

	return $content;
}

function plan_update_mobile($edit,$writer)
{
	
}


// plan_update()
//
// the function which allows plan updates via the web interface and
// the blogger API (still buggy)
// TODO:(v4.5) fix the blogger / metaweblog api stuff
//---------------------------------------------------------------------
function plan_update($edit=FALSE,$writer=FALSE)
{
	if (!$writer) $writer=$_SERVER['USER'];
	if ($edit=='tiny') { $tiny=TRUE; $edit=FALSE; }
	
	if ($tiny) $content="
	<style> textarea { font-size: 12pt; font-family: trebuchet ms; width: 300px; height: 300px; } </style>
	";
	
	// set dir for reading
	$plan_dir="$_SERVER[PWUSERS_DIR]/$writer/plan";

	// user_is_administrator() is here because admins can update the system plan
	// TODO(v5): add credentialing so people can easily write to multiple plans, multiple people can write to a given plan
	if (user_is_valid($writer,$_SERVER['USERINFO_ARRAY']['userpass']) || (user_is_administrator() && $writer=='system')) 
	{
		$journaling_test=plan_is_journaling($writer);

		if ($journaling_test)
		{
			$draft_links=plan_list_drafts();
		}

		if (($journaling_test || $edit=='header' || $edit=='footer' || $edit=='css') && $edit)
		{
			if ($edit=='draft')
			{
				if ($_SERVER['URL_ARRAY'][4]) $privateentry=' CHECKED ';
				if ($_SERVER['URL_ARRAY'][5]) $nlb_check=' CHECKED ';
				if ($_SERVER['URL_ARRAY'][6]) $markdown_check=' CHECKED ';
				if ($_SERVER['URL_ARRAY'][7]) $nofeed_check=' CHECKED ';
				$plandata=stripslashes(file_get_contents("$_SERVER[FILE_ROOT]/temp/draft...$writer...{$_SERVER[URL_ARRAY][3]}...{$_SERVER[URL_ARRAY][4]}...{$_SERVER[URL_ARRAY][5]}...txt"));
				$edit=$_SERVER['URL_ARRAY'][3];
			}
			elseif (file_exists("$plan_dir/plan$edit.txt") || file_exists("$plan_dir/plan$edit.txt.p") || file_exists("$plan_dir/draft$edit.txt"))	// gets file to edit
			{
				if (file_exists("$plan_dir/plan$edit.txt"))
				{
					$filename="$plan_dir/plan$edit.txt";
				}
				if (file_exists("$plan_dir/plan$edit.txt.p"))
				{
					$privateentry=' CHECKED ';
					$filename="$plan_dir/plan$edit.txt.p";
				}
				if (file_exists("$plan_dir/draft$edit.txt"))
				{
					$filename="$plan_dir/draft$edit.txt";
					$draft_edit=$edit;
				}

				$plandata=stripslashes(file_get_contents($filename));
				$plandata=preg_replace("|&(\S+);|","&amp;\\1;",$plandata);

				if (strstr($plandata,'<!--markdown-->'))
				{
					$markdown_check=' CHECKED ';
					$plandata=str_replace('<!--markdown-->','',$plandata);
				}

				if (strstr($plandata,'<!--no feed-->'))
				{
					$nofeed_check=' CHECKED ';
					$plandata=str_replace('<!--no feed-->','',$plandata);
				}

				if (preg_match("|<!--title (.*)-->|",$plandata,$titlematches))
				{
					$title=$titlematches[1];					
					$plandata=str_replace($titlematches[0],'',$plandata);
				}

				if (preg_match("|<!--tags (.*)-->|",$plandata,$tagmatches))
				{
					$tags=$tagmatches[1];					
					$plandata=str_replace($tagmatches[0],'',$plandata);
				}

			}
		}
		elseif (!($journaling_test || $edit=='header' || $edit=='footer'))
		{
			$oldplan_fn="$_SERVER[PWUSERS_DIR]/$writer/plan/plan.txt";
					
			if(file_exists($oldplan_fn)) // if there's a previous old plan, read it in
			{
				$plandata=stripslashes(file_get_contents($oldplan_fn));
			}
			else					// if not, make a dummy one to simplify write
			{						// NOTE: the oldest nonjournaling plan entry will always be blank
				$plandata='';
				touch($oldplan_fn);
			}
		}

		if (strpos($plandata,'nolinebreaks')!==FALSE)
		{
			$plandata=str_replace(array('<!--nolinebreaks-->','#nolinebreaks#'),'',$plandata);
			$nlb_check='CHECKED';
		}
		else $nlb_check='';

		if (!$edit) $content.="<h1><img src='$GLOBALS[writeicon]' /> Write a plan update</h1>";
		elseif (strpos($edit,'er') || strpos($edit,'ss'))
		{
			$content.="<h1><img src='$GLOBALS[writeicon]' /> Edit your plan $edit</h1>";
		}
		else $content.="<h1><img src='$GLOBALS[writeicon]' /> Edit your plan entry from ".formattime(substr($edit,1))."</h1>";
		
		
		if (user_is_administrator() && $writer=='system') $content.="$_SERVER[USER] editing the system plan.<br />";

		if($_SERVER['OUTPUT_MODE']!='IPHONE' && $_SERVER['OUTPUT_MODE']!='MOBILE')
			$formatlinks="<script src='/resources/javascript/edit.js'></script>";
		$content.="
		$formatlinks
		<form title='Write' selected='true' action='$_SERVER[WEB_ROOT]/scripts/plan_update.php' method='post' name='plan_update' enctype='multipart/form-data'>\n
		<textarea name='newplan' id='textbox' rows='12' cols='32'>".trim($plandata.$autocontent)."</textarea>
		<div class='settingsbox' >
		<div style='text-align: center;'>
			<input
				  type      = 'submit'
				  name      = 'update'
				  value     = 'Post'
				  accesskey = 'x'
				  style     = 'font-size: 20px; font-family: $GLOBALS[nfonts]; background: $GLOBALS[linkcolor]; color: $GLOBALS[planbgcolor]; padding: 2px;'
			/>
			";

		if ($journaling_test && $edit!='divider' && $edit!='header' && $edit!='footer' && $edit!='css')
		{
			if ($draft_edit) $draft_time=str_replace(".",'',$draft_edit);
			else { $draft_time=time(); $draft_edit=".$draft_time"; }

			$_SERVER['PLAN_DRAFT_TIME']=$draft_time;
			
			if (!$tiny)
			{

			$content.="
			<input
				  type      = 'button'
				  name      = 'draft'
				  value     = 'Save'
				  accesskey = 'd'
				  onclick   = \"loadXMLDoc('http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/plan_update.php','draft_time=$draft_time&action=Autosave%20Ajax&newplan=' + escape(element('textbox').value),'autosave_alert',processReqChangePOST);\"
				  style     = 'font-size: 12px; font-family: $GLOBALS[nfonts]; background: $GLOBALS[navlinkcolor]; color: $GLOBALS[listsbgcolor]; padding: 2px;'
			/>

			<div id='autosave_alert'></div>
			</div>

			<div>Optional Info<br />
				Title <input type='text' name='title' value='$title' style='width: 10%;' placeholder='Title'><br />
				Tags <input type='text' name='tags' value='$tags' style='width: 10%;' placeholder='Private Tags'>
			</div>

			<div>
				privacy<br/>
				<input type='checkbox' $nofeed_check name='nofeed' id='nofeed' value='<!--no feed-->' />
				<label for='nofeed'>not in feed</label>
				<a href='$_SERVER[WEB_ROOT]/help/writing#nofeed' target='_blank'>[?]</a><br />
				<input type='checkbox' name='private' id='private' value='.p' $privateentry onchange='if (this.checked) { document.getElementById(\"textbox\").style.background=\"#eeeeee\"; }'/>
				<label for='private'>private entry</label><br/>
				<a href='$_SERVER[WEB_ROOT]/help/privacy' target='_blank'>privacy help</a><br />
				<a href='$_SERVER[WEB_ROOT]/lists/edit/allowed' target='_blank'>edit allowed users</a><br />
			</div>

			<div>Attach a file<br /><input type='file' name='attached_file' style='width: 90px;' /></div>
			";
			}
			else $content.="<br /><br /><br /><input type='checkbox' name='private' id='private' value='.p' $privateentry onchange='if (this.checked) { document.getElementById(\"textbox\").style.background=\"#eeeeee\"; }'/> <label for='private'>private</label> &nbsp; ";

		}

		if(!strpos($edit,"er") && !strpos($edit,"ss"))
		{
			if (!$tiny)
			{
				$content.="
					<div>
						display<br/>
						<input type='checkbox' $markdown_check name='markdown' id='markdown' value='<!--markdown-->' />
						<label for='markdown'><a target='_blank' href='http://michelf.com/projects/php-markdown/concepts/'>markdown</a></label><br />
						<input type='checkbox' $nlb_check name='nolinebreaks' id='nolinebreaks' value='1' />
						<label for='nolinebreaks'>no line breaks</label>
						<a href='$_SERVER[WEB_ROOT]/help/writing#linebreaks' target='_blank'>[?]</a>
						<br />
					</div>\n";
		
				if ($edit=='divider') $content.="
					<div style='font-size: 9px; font-family: sans-serif; width: 100px;'>
						<a href='$_SERVER[WEB_ROOT]/help/divider' target='_blank'><img src='$_SERVER[WEB_ROOT]$GLOBALS[helpicon]' alt='?' /> help customizing your divider</a>
					</div>
					";
			}
	
			else $content.="
			<input type='checkbox' $markdown_check name='markdown' id='markdown' value='<!--markdown-->' /> <label for='markdown'>markdown</label> &nbsp; <input type='checkbox' $nofeed_check name='nofeed' id='nofeed' value='<!--no feed-->' /> not in feed
						";
		}	
		elseif(strstr($edit,'er'))  $content.="
		</div><div>
		<input type='checkbox' $nlb_check name='nolinebreaks' id='nolinebreaks' value='1' />
		<label for='nolinebreaks'>no line breaks</label>
		<a href='$_SERVER[WEB_ROOT]/help/writing#linebreaks' target='_blank'>[?]</a>
		<br />
<input type='checkbox' $markdown_check name='markdown' id='markdown' value='<!--markdown-->' />
<label for='markdown'><a target='_blank' href='http://michelf.com/projects/php-markdown/concepts/'>markdown</a></label>
</div><div style='display: none;'>
		";
		elseif(strstr($edit,'ss'))  				$content.="<br /><br /><a target='_blank' href='/help/formatting'>CSS formatting help</a> <br /><br /> <a href='/help/lynneformatting' target='_blank'>Lynne's CSS tips</a><br />
		<style>.CodeMirror-wrapping { width: 80% !important; float: left !important; clear: none !important; height: 350px !important; border: thin solid black !important; }</style>
		
			    <script src='/resources/javascript/codemirror65/js/codemirror.js' type='text/javascript'></script>
		<script type='text/javascript'>
		  var editor = CodeMirror.fromTextArea('textbox', {
		    border:     '1px solid black',
		    parserfile: 'parsecss.js',
		    stylesheet: '/resources/javascript/codemirror65/css/csscolors.css',
		    path: '/resources/javascript/codemirror65/js/'
		  });
		</script>
		";
		

		$content.="
			<input type='hidden' id='draft_edit' name='draft_edit' value='$draft_edit'>
			<input type='hidden' name='writer' value='$writer'/>
			<input type='hidden' name='edit' value='$edit'/>
			<input type='hidden' name='fingerprint' value='".$_COOKIE[$_SERVER['AUTH_COOKIE']]."'/>
			<input type='hidden' name='sid' value='$_SERVER[SESSION_ID]'/>
		</div>";
		
		if ($journaling_test || $edit=='header' || $edit=='footer') $content.="<input type='hidden' name='action' value='Update Journaling Plan' />\n";
		else $content.="<input type='hidden' name='action' value='Update Nonjournaling Plan' />\n";

		$content.="</form><div id='draftList'></div>\n";
		
	}
	else $content="<div class='alert'>Your attempt to update your plan failed because
	you supplied an invalid username or password. Please login again, and mail
	<a href='mailto:help@planwatch.org'>help@planwatch.org</a> if you believe this message
	is in error.</div>";

	// if the form was actually presented, include the relevant javascript files to make it all work.
	if (!strpos($content,'alert'))
	{
		$content.="\n\n<script type='text/javascript' language='javascript'>document.getElementById('textbox').focus();</script>\n";
		$content.="\n\n<script language='javascript' type='text/javascript' src='$_SERVER[WEB_ROOT]/resources/javascript/setplan.js'></script>\n";
		//$content.="<script language='javascript' type='text/javascript' src='$_SERVER[WEB_ROOT]/resources/javascript/speller/spellChecker.js'></script>\n\n";
	}

	return $content;
}


// plan_write_journaling()
//
// writes the update to a file.
//---------------------------------------------------------------------
function plan_write_journaling($edit,$plandata,$private,$nolinebreaks=FALSE,$writer=FALSE)
{
	include_once('plan_read.php');
	include_once('snoop.php');
	include_once('spiel.php');
	include_once('send.php');

	$planowner=$writer;

	// make sure all the timecodes are the same
	$time=time();
	
	// find the character encoding of the plan entry, convert it to something
	// more universal
	mb_detect_order("UTF-8, UTF-8, Windows-1252");
	if (mb_detect_encoding($plandata) == "Windows-1252") {
	$plandata = mb_convert_encoding($plandata, UTF-8, Windows-1252);
	}

	// make sure no one can post an update to someone else's plan
	// this will need to be smarter if we ever implement group plans
	// but probably we won't, so no biggie.
	if ($planowner!=$_SERVER['USER'] && !user_is_administrator()) $planowner=$_SERVER['USER'];
	$plan_dir="$_SERVER[PWUSERS_DIR]/$planowner/plan";


	// Find the old snoops. We have to masquerade briefly as 'cacheuser' to do
	// this without leaving a spurious snitch or getting private entries.
	// We remain 'cacheuser' until after snoop_add() below.
	$_SERVER['USER']='cacheuser';
		// find old snoops, for later clearing
		$old_snoop_array=snoop_find(plan_read_local($planowner,($_SERVER['USERINFO_ARRAY']['defaultdays']+3).'d'),$planowner);
	
		// delete the (now-invalid) cache files
		cache_clear($planowner);
		
		// leave a reminder to plan_read_local to ignore linebreaks.
		if ($nolinebreaks) $plandata.="<!--nolinebreaks-->";

		if ($_POST['title'])  $plandata.="<!--title $_POST[title] -->";
		if ($_POST['tags'])  $plandata.="<!--tags $_POST[tags] -->";

		// if we weren't editing an existing (already-posted) entry, set the filename for the current time.
		if (!($_POST['edit']) || $_POST['edit']==$_POST['draft_edit']) $_POST['edit']=".$time";
		$plan_fn="$plan_dir/plan$_POST[edit].txt$_POST[private]";
	
		if (!file_exists($plan_fn))
		{
			file_put_contents("$_SERVER[PWUSERS_DIR]/$planowner/stats/lastupdate",$time);
		}
	
		if ($_FILES['attached_file']['tmp_name'])
		{
			rename("{$_FILES['attached_file']['tmp_name']}","$_SERVER[USER_ROOT]/files/{$_FILES['attached_file']['name']}");
			if (strstr($_FILES['attached_file']['name'],'jpg')
				|| strstr($_FILES['attached_file']['name'],'gif')
				|| strstr($_FILES['attached_file']['name'],'png'))
			{
				$plandata.="<img src='/userfiles/view/$writer/{$_FILES['attached_file']['name']}' />";
			}
			else $plandata.="\n<a href='/userfiles/view/$writer/{$_FILES['attached_file']['name']}'>{$_FILES['attached_file']['name']}</a>";
		}
	//	else trigger_error("No Files Uploaded");
	
		$plandata.=$_POST['markdown'];
		$plandata.=$_POST['nofeed'];
	
		// save old headers and footers.
		if (strstr($plan_fn,'header') || strstr($plan_fn,'footer'))
		{
			exec("mv $plan_fn $plan_fn.$time");
		}
	
		// write the update to disk.
		file_put_contents($plan_fn,$plandata);
	
	
		// new feature: SPIEL
		// here's the part where spiels are found
		// TODO(v4.5): replace spiel syntax with hashtags
		if (!$private && !$edit) spiel_find($plandata,$planowner,$time);
	
		// here's the part where sends are found
		if (!$private && !$edit) send_find($plandata,$planowner,$time);
	
		if (file_exists($plan_fn))
		{
			if ($private && file_exists("$plan_dir/plan$edit.txt")) exec("mv $plan_dir/plan$edit.txt $plan_dir/rem.plan$edit.txt");
			if (!$private && file_exists("$plan_dir/plan$edit.txt.p")) exec("mv $plan_dir/plan$edit.txt.p $plan_dir/rem.plan$edit.txt.p");
			if ($_POST['draft_edit'] && file_exists("$plan_dir/draft$_POST[draft_edit].txt")) unlink("$plan_dir/draft$_POST[draft_edit].txt");
			
			// clean up old drafts
			if ($drafts=files_list("$plan_dir/","draft*.txt"))
			{
				foreach($drafts as $draft)
				{
					if (filemtime("$plan_dir/$draft")<(time()-7*24*3600)) unlink("$plan_dir/$draft");
				}
			}		
		}
	
		@chmod($plan_fn,0755);
	
		// clean old snoops and add new ones
		$new_snoop_array=snoop_find(plan_read_local($planowner),$planowner);
		$snoops_to_remove=array_unique(array_diff($old_snoop_array,$new_snoop_array));
		$snoops_to_set=array_unique(array_diff($new_snoop_array,$old_snoop_array));
		$remove_status=snoop_clean($snoops_to_remove,$planowner);
		$add_status=snoop_add($snoops_to_set,$planowner);
	$_SERVER['USER']=$_SERVER['USERINFO_ARRAY']['username'];
	// done masquerading
	
	// report the good news if we wrote the post to disk.
	if (file_exists($plan_fn))
	{
		if ($_SERVER['AJAX_POST'])
		{
			return $plandata;
		}

		if (!$_SERVER['BLOGPOST'])
		{
			if ($_COOKIE[$_SERVER['AUTH_COOKIE']])
			{
				if ($_SERVER['AJAX_POST'])
				{
					return $plandata;
				}
				else
				{
					redirect("/read/$planowner");
				}
			}
			elseif (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
			{
				if($_POST['mailpost'])
				{
					echo "posted"; exit;
				}
				else
				{
					// If the writer's cookie expired while updating, log her back in.
					login($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass'],0,"/read/$planowner");
					exit;
				}
			}
		}
		else
		{
			return ".$time";
		}
	}
	else 
	{
		if ($_SERVER['BLOGPOST'])
		{
			return FALSE;
		}
		else
		{
			output('Error Updating',"<div class='alert'>There was an error writing $_SERVER[USER]'s plan entry to $plan_fn $edit. <a href='$_SERVER[WEB_ROOT]/feature'>File a bug</a> so we know about this problem. Here's your plan text for safekeeping:<br /><br />$plandata</div>",'',' had an error');
		}
	}
}




// plan_write_traditional()
//
// writes a new nonjournal plan
//------------------------------------------------------------------------------
function plan_write_traditional($newplan,$planowner)
{
	include_once('plan_read.php');
	include_once('snoop.php');
	include_once('spiel.php');
	include_once('send.php');
	
	mb_detect_order("UTF-8, windows-1252");
	if (mb_detect_encoding($newplan) == "Windows-1252") {
	$newplan = mb_convert_encoding($newplan, UTF-8, Windows-1252);
	}

	$oldplan_fn="$_SERVER[PWUSERS_DIR]/$planowner/plan/plan.txt";		  // current oldplan name
	$oldplan_fn_moved=$oldplan_fn.".".filectime($oldplan_fn); // archival oldplan name
	$oldplan_array=explode("\n",$oldplan);
	
	$_SERVER['USER']='cacheuser';
	$old_snoop_array=snoop_find(plan_read_local($planowner),$planowner);

	exec("mv $oldplan_fn $oldplan_fn_moved");
	exec("gzip $oldplan_fn_moved");
	exec("rm -f $_SERVER[FILE_ROOT]/temp/$planowner*.cache");

	$oldplan_fn_moved.=".gz";
	$newplan.=$_POST['nolinebreaks'];
	$newplan.=$_POST['markdown'];
	$newplan.=$_POST['nofeed'];
	if ($_POST['title'])  $newplan.="<!--title $_POST[title]-->";
	if ($_POST['tags'])  $newplan.="<!--tags $_POST[tags]-->";

	$newplan_fn=$oldplan_fn;
	$newplan_file=fopen($newplan_fn,'w');
	fwrite($newplan_file,$newplan);
	fclose($newplan_file);
	$newplan_array=explode("\n",$newplan);
	
	$diffplan=implode('',array_diff($newplan_array,$oldplan_array));
	spiel_find($diffplan,$planowner,time());

	$new_snoop_array=snoop_find(plan_read_local($planowner),$planowner);

	snoop_clean(array_unique($old_snoop_array),$planowner);
	snoop_add(array_unique($new_snoop_array),$planowner);	
	
	touch("$_SERVER[PWUSERS_DIR]/$planowner/stats/lastupdate");

	if (file_exists($newplan_fn) && (file_exists($oldplan_fn_moved) || !file_exists($oldplan_fn))) redirect("/read/$planowner");
	else
	{
		output("Error updating","<div class='alert'>Your plan update failed:<br/><br/>$newplan</div>");
	}


}


// MAIN ========================================================================
if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
{
	if (is_string($_POST['action']))
	{
		switch ($_POST['action']):
		
		case  'Write Blocked Message':
			plan_write_blocked_message($_POST['message']);
			break;

		case 'Update Journaling Plan':
			//echo "posted";
			plan_write_journaling($_POST['edit'],$_POST['newplan'],$_POST['private'],$_POST['nolinebreaks'],$_POST['writer']);
			break;
			
		case 'Autosave Ajax':
			$draft_filename="$_SERVER[USER_ROOT]/plan/draft.$_POST[draft_time].txt";
			file_put_contents($draft_filename,"$_POST[nolinebreaks]\n$_POST[private]\n$_POST[edit]\n$_POST[newplan]");
			if (file_exists($draft_filename)) echo "Last Saved ".formattime(time());
			else echo "Save failed";
			break;

		case 'Save Draft Ajax':
			file_put_contents("$_SERVER[FILE_ROOT]/temp/draft...$_POST[writer]...$_POST[edit]...$_POST[private]...$_POST[nolinebreaks]...txt",$_POST['newplan']);
			echo "Sending entry to full-page editor...";
			break;
	
		case 'Delete Draft Ajax':
			$draft_filename="$_SERVER[USER_ROOT]/plan/draft.$_POST[draft_time].txt";
			unlink($draft_filename);
			echo plan_list_drafts();
			break;

		case 'Update Journaling Plan Ajax':
			$_SERVER['AJAX_POST']=TRUE;
			$return = plan_write_journaling($_POST['edit'],$_POST['newplan'],$_POST['private'],$_POST['nolinebreaks'],$_POST['writer']);
			$return = stripslashes(stripslashes($return));
			if ($_POST['nolinebreaks']!=1) $return = nl2br($return);
			$return = plan_process_directives(plan_add_user_links(plan_add_alias_links($return,$_POST['writer'])));
			echo $return;
			break;
	
		case 'Update Nonjournaling Plan':
			plan_write_traditional($_POST['newplan'],$_POST['writer']);
			break;
		
		default:
			echo "ERROR: no valid action selected.";
			print_r($_POST);
	
		endswitch;
	}
}
else { echo "invalid user $_POST[writer]"; exit; }//redirect("/");



// plan_edit_blocked_message()
//
// presents a form for editing the contents of a list file
//------------------------------------------------------------------------------
function plan_edit_blocked_message()
{
	if ($_SERVER['USER'])
	{
		$blocked_fn="$_SERVER[USER_ROOT]/blockedmessage.txt";
		if (file_exists($blocked_fn))
			$blockedmessage=stripslashes(file_get_contents($blocked_fn));
		else $blockedmessage="$user's plan is not available.";

		$content="
		<form action='$_SERVER[WEB_ROOT]/scripts/plan_update.php' method='post'>
		<textarea rows='3' cols='76' name='message'>$blockedmessage</textarea>
		<input type='submit' name='submit' value='submit'/>
		<input type='hidden' name='action' value='Write Blocked Message'/>
		</form>
		";
	}
	else $content='please log in';

output("edit your custom blocked message",$content,''," editing your custom blocked message");
}




// plan_write_blocked_message()
//
// presents a form for editing the contents of a list file
//------------------------------------------------------------------------------
function plan_write_blocked_message($message)
{
	if ($_SERVER['USER'])
	{
		$blocked_filename = "$_SERVER[USER_ROOT]/blockedmessage.txt";
		$message = stripslashes(stripslashes($message));
		file_put_contents($blocked_filename,$message);
		redirect("/");
	}
	else output("write failed","<div class='alert'>You can't write a blocked message because you are not logged in. Message:<br/>$message</div>");
}

?>