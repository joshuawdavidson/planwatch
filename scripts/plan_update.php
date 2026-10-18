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

include_once('/var/www/planwatch.org/siteconfig.php');
include_once('essential.php');


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
		$draft_links.="<li><a href='/write/$draft'>$draft_time</a> [ <a href=\"javascript:loadXMLDoc('https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/plan_update.php','action=Delete%20Draft%20Ajax&draft_time=$draft','drafts',processReqChangePOST);\">x</a> ]</li>\n";
	}
	if (!isset($draft_links)) {$draft_links="no saved drafts.";}
	return $draft_links;
}

// plan_update()
//
// the function which allows plan updates via the web interface and
// the blogger API (still buggy)
// TODO:(v4.5) fix the blogger / metaweblog api stuff
//---------------------------------------------------------------------
function plan_update($edit=FALSE,$plan_to_write=FALSE)
{
	$plandata=$autocontent=$textarea_mobile=$draft_edit=$tiny=$title=$tags=$nofeed_check=$privateentry=$hiddenentry=$extrajs='';
	if (!$plan_to_write || empty(trim($plan_to_write))) { $plan_to_write=$_SERVER['USER']; }
	if ($edit=='tiny') { $tiny=TRUE; $edit=FALSE; }
	$mau = isset($_COOKIE['mau']) ? $_COOKIE['mau'] : '';

	$content='';
	if (isset($tiny))
	{
		$content.="<style> textarea { font-size: 12pt; font-family: trebuchet ms; width: 300px; height: 300px; } </style>";
	}

	$content.="<!--USER: $_SERVER[USER] $_SERVER[SESSION_ID] $mau ".file_get_contents("$_SERVER[SESSION_DIR]/$_SERVER[SESSION_ID].skey")." -->";

	// set dir for reading
	$plan_dir="$_SERVER[PWUSERS_DIR]/$plan_to_write/plan";
	if (
		(user_verify_sessionkey($_SERVER['SESSION_ID'],$plan_to_write,FALSE))
		|| plan_user_may_write($plan_to_write,$_SERVER['USER'])
	   )
	{
		if($_SERVER['USERINFO_ARRAY']['prefer_markdown']==1) $markdown_check=' checked=\'checked\' ';
		$journaling_test=plan_is_journaling($plan_to_write);

		$postable_plans=user_get_postable_plans($_SERVER['USER']);
		if(count($postable_plans)>1)
		{
			$planselect="<select name='writer' onchange='document.getElementById(\"hltext\").innerHTML=\"Update \" + this.value; document.getElementById(\"stinger\").innerHTML=\"$_SERVER[USER] editing the \" + this.value + \" plan\";'>";
			$postable_plans=user_get_postable_plans($_SERVER['USER']);
			foreach($postable_plans as $postable_plan)
			{
				if($postable_plan==$plan_to_write) { $selected=" selected='selected' "; } else { $selected=""; }
				$planselect.="<option $selected value='$postable_plan'>$postable_plan</option>";
			}
			$planselect.="</select>";
		}
		else $planselect="<input type='hidden' name='writer' value='$plan_to_write' />";

		if ($journaling_test)
		{
			$draft_links=plan_list_drafts();
		}

		if (($journaling_test || $edit=='header' || $edit=='footer' || $edit=='css') && $edit)
		{
			if ($edit=='draft')
			{
				if ($_SERVER['URL_ARRAY'][4]) $privateentry=' checked=\'checked\' ';
				if ($_SERVER['URL_ARRAY'][5]) $nlb_check=' checked=\'checked\' ';
				if ($_SERVER['URL_ARRAY'][6] ) $markdown_check=' checked=\'checked\' ';
				if ($_SERVER['URL_ARRAY'][7]) $nofeed_check=' checked=\'checked\' ';
				$plandata=stripslashes(file_get_contents("$_SERVER[FILE_ROOT]/temp/draft...$plan_to_write...{$_SERVER[URL_ARRAY][3]}...{$_SERVER[URL_ARRAY][4]}...{$_SERVER[URL_ARRAY][5]}...txt"));
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
				if (file_exists("$plan_dir/hidden.plan$edit.txt"))
				{
					$hiddenentry=' CHECKED ';
					$filename="$plan_dir/hidden.plan$edit.txt";
				}
				if (file_exists("$plan_dir/draft$edit.txt"))
				{
					$filename="$plan_dir/draft$edit.txt";
					$draft_edit=$edit;
				}

				$plandata=stripslashes(file_get_contents($filename));
				$plandata=preg_replace("|&(\S+);|","&amp;\\1;",$plandata);

				$markdown_check='';
				if (strstr($plandata,'<!--markdown-->'))
				{
					$markdown_check=' CHECKED ';
					$plandata=str_replace('<!--markdown-->','',$plandata);
				}

				$nofeed_check='';
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
			$plandata='';
			$oldplan_fn="$_SERVER[PWUSERS_DIR]/$plan_to_write/plan/plan.txt";
			$markdown_check='';

			if(file_exists($oldplan_fn)) // if there's a previous old plan, read it in
			{
				$plandata=stripslashes(file_get_contents($oldplan_fn));
			}
			else					// if not, make a dummy one to simplify write
			{						// NOTE: the oldest nonjournaling plan entry will always be blank
				touch($oldplan_fn);
			}
		}

		if (!empty($plandata) && strpos($plandata,'nolinebreaks')!==FALSE)
		{
			$plandata=str_replace(array('<!--nolinebreaks-->','#nolinebreaks#'),'',$plandata);
			$nlb_check='CHECKED';
		}
		else $nlb_check='';

		if (!$edit)
		{
			$content.="<h1><img src='$GLOBALS[writeicon]' /> <span id='hltext'>Update $plan_to_write</span></h1>";
		}
		elseif (strpos($edit,'er') || strpos($edit,'ss'))
		{
			$planselect="<input type='hidden' name='writer' value='$plan_to_write' />";
			$content.="<h1><img src='$GLOBALS[writeicon]' /> Edit your plan $edit</h1>";
		}
		elseif(is_numeric($edit))
		{
			$planselect="<input type='hidden' name='writer' value='$plan_to_write' />";
			$content.="<h1><img src='$GLOBALS[writeicon]' /> Edit your plan entry from ".formattime(substr($edit,1))."</h1>";
		}


		if ($plan_to_write!=$_SERVER['USER']) $content.="<div id='stinger'>$_SERVER[USER] editing the $plan_to_write plan</div>";
		else $content.="<div id='stinger'></div>";

		$formdec="<form title='Write' selected='true' action='$_SERVER[WEB_ROOT]/scripts/plan_update.php' method='post' name='plan_update' enctype='multipart/form-data'>\n";
		if($_SERVER['USER']=='testuser') $formdec="<form title='Write' selected='true' action='$_SERVER[WEB_ROOT]/postnew' method='post' name='plan_update' enctype='multipart/form-data'>\n";

		if($_SERVER['OUTPUT_MODE']!='MOBILE')
		{
			$formatlinks="<div id='format_toolbar_container'><ul id='format_toolbar' class='menubar'><li><input type='button' value='Headline' onclick=\"insertTag('textbox','<h1>','</h1>');void(0);\"></li><li><input type='button' value='Subhead' onclick=\"insertTag('textbox','<h2>','</h2>');void(0);\"></li><li><input type='button' value='B' onclick=\"insertTag('textbox','<strong>','</strong>');void(0);\"></li><li><input type='button' value='i' onclick=\"insertTag('textbox','<em>','</em>');void(0);\"></li><li><input type='button' value='&gt; |' onclick=\"insertTag('textbox','<blockquote>','</blockquote>');void(0);\"></li><li><input type='button' value='&quot;' onclick=\"insertTag('textbox','&amp;quot;','&amp;quot;');void(0);\"></li><li><input type='button' value='link' onclick=\"DoPrompt('url');void(0);\"></li><li><input type='button' value='email' onclick=\"DoPrompt('email');void(0);\"></li><li><input type='button' value='snoop' onclick=\"DoPrompt('snoop');void(0);\"></li><li style='float: right; text-align: right; position: relative;' class='menubutton' id='insertanchor' onclick=\"loadXMLDoc('/userfiles/list','','insert');if(document.getElementById('insert').style.display=='block') { document.getElementById('insert').style.display='none'; } else {document.getElementById('insert').style.display='block';}void(null);\">files <img src='/resources/graphics/down_arrow.gif' id='insert_arrow'  style='width: 9px; height: 9px;' /><ul id='insert' style='position: absolute; right: 0px; width: 200px; max-height: 50vh; overflow-y: scroll;'><li>&nbsp;</li></ul></li><li style='float: right; text-align: right; position: relative;' class='menubutton' id='draftsanchor' onclick=\"loadXMLDoc('/write/list_drafts','','drafts');document.getElementById('drafts').style.display='block';void(null);\">drafts <img src='/resources/graphics/down_arrow.gif' id='insert_arrow'  style='width: 9px; height: 9px;' /><ul id='drafts' style='position: absolute; right: 0px;'><li>&nbsp;</li></ul></li></ul><br clear='all' /></div>";
			$textarea_desktop="<textarea name='newplan' id='textbox' rows='12' cols='32'>".trim($plandata.$autocontent)."</textarea>";
			$textarea_mobile='';
		}
		else
		{
			$textarea_mobile="<textarea name='newplan' id='textbox' rows='12' cols='32'>".trim($plandata.$autocontent)."</textarea>";
			$textarea_desktop=$formatlinks='';
			$formatlinks="<div id='format_toolbar_container'><ul id='format_toolbar' class='menubar'><li><input type='button' value='H1' onclick=\"insertTag('textbox','<h1>','</h1>');void(0);\"></li><li><input type='button' value='H2' onclick=\"insertTag('textbox','<h2>','</h2>');void(0);\"></li><li><input type='button' value='B' onclick=\"insertTag('textbox','<strong>','</strong>');void(0);\"></li><li><input type='button' value='i' onclick=\"insertTag('textbox','<em>','</em>');void(0);\"></li><li><input type='button' value='&gt; |' onclick=\"insertTag('textbox','<blockquote>','</blockquote>');void(0);\"></li><li><input type='button' value='&quot;' onclick=\"insertTag('textbox','&amp;quot;','&amp;quot;');void(0);\"></li><li><input type='button' value='link' onclick=\"DoPrompt('url');void(0);\"></li><li><input type='button' value='email' onclick=\"DoPrompt('email');void(0);\"></li><li><input type='button' value='snoop' onclick=\"DoPrompt('snoop');void(0);\"></li><li style='float: right; text-align: right; position: relative;' class='menubutton' id='draftsanchor' onclick=\"loadXMLDoc('/write/list_drafts','','drafts');document.getElementById('drafts').style.display='block';void(null);\">drafts <img src='/resources/graphics/down_arrow.gif' id='insert_arrow'  style='width: 9px; height: 9px;' /><ul id='drafts' style='position: absolute; right: 0px;'><li>&nbsp;</li></ul></li></ul><br clear='all' /></div>";
		}

		$content.="
		$formatlinks
		$formdec
		$textarea_mobile
		<div class='settingsbox' >
		<div style='text-align: center;'>
			<input
				  type      = 'submit'
				  name      = 'update'
				  value     = 'Post'
				  accesskey = 'x'
				  id     = 'submit_button'
			/>
			";


	$attachlink="
			<div style='visibility:hidden; opacity:0; position: fixed; inset: 0; background: rgba(255,255,255,0.8);' id='droparea'>
				<a style='position: absolute; top: 0px; right: 0px; text-decoration: none; color: gray;' href='javascript:document.getElementById(\"droparea\").style.display=\"none\";'><img src='/resources/graphics/closebox.svg'></a>

				  <div style='position: absolute; inset: 30%; font-size: 4rem; font-weight: bold; text-align: center;'>+<br>Drop files to upload</div>

				  <div id=\"gallery\" /></div>
				  <progress style='width: 100%;' id=\"progress-bar\" max=100 value=0></progress>

				<div id='responseArea' class='sans'>&nbsp;</div>
			</div>
			<input type='hidden' name='user' id='user' value='$plan_to_write' />
			<div>Drop files to upload or
			<input style='visibility: hidden;' type=\"file\" id=\"fileElem\" multiple accept=\"image/*\" onchange=\"handleFiles(this.files)\">
			<label class=\"button\" for=\"fileElem\">Select some files</label>
			<br><br><em>20MB file size limit</em></div>
		<script type='text/javascript' src='/resources/javascript/upload.js'></script>

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
				  onclick   = \"loadXMLDoc('https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/plan_update.php','draft_time=$draft_time&action=Autosave%20Ajax&newplan=' + escape(element('textbox').value),'autosave_alert',processReqChangePOST);\"
				  id        = 'draft_button'
			/>

			<div id='autosave_alert'></div>
			</div>
			";


			$content.="
			<div>
			$planselect
			</div>
			";


			$content.="
			<div>Optional Info<br />
				Title <input type='text' id='writetitle' name='title' value='$title' placeholder='Title'><br />
				Tags <input type='text' id='writetags' name='tags' value='$tags' placeholder='Private Tags'>
			</div>

			<div>
				privacy<br/>
				<label for='nofeed' id='label_nofeed'>
				<input type='checkbox' $nofeed_check name='nofeed' id='nofeed' value='<!--no feed-->' onchange='if (this.checked) { element(\"textbox\").style.background=\"#efefef\"; } else { element(\"textbox\").style.background=\"transparent\"; ' />
				not in feed</label>
				<a href='$_SERVER[WEB_ROOT]/help/writing#nofeed' target='_blank'>[?]</a><br />
				<label for='private'>
				<input type='checkbox' name='private' id='private' value='.p' $privateentry onchange='if (this.checked) { document.getElementById(\"textbox\").style.background=\"#eeeeee\"; } else { document.getElementById(\"textbox\").style.background=\"transparent\"; }'/>
				private entry</label><br/>
				<label for='hidden' id='label_hidden'>
				<input type='checkbox' name='hidden' id='hidden' value='hidden.' $hiddenentry
				onchange='if (this.checked) { element(\"label_hidden\").style.background=\"green\"; element(\"label_hidden\").style.opacity=\"1.0\"; element(\"textbox\").style.background=\"#dddddd\"; } else { element(\"label_hidden\").style.background=\"transparent\"; element(\"label_hidden\").style.opacity=\"0.5\"; element(\"textbox\").style.background=\"transparent\"; '/>
				hidden entry</label><br/>
				<a href='$_SERVER[WEB_ROOT]/help/privacy' target='_blank'>privacy help</a><br />
				<a href='$_SERVER[WEB_ROOT]/lists/edit/allowed' target='_blank'>edit allowed users</a><br />
			</div>

			";
//			else $content.="<div class='nomobile' >Attach a file<br /><input type='file' name='attached_file' style='width: 90px;' /></div>\n";

			}
			else $content.="<br /><br /><br /><input type='checkbox' name='private' id='private' value='.p' $privateentry onchange='if (this.checked) { document.getElementById(\"textbox\").style.background=\"#eeeeee\"; }'/> <label for='private'>private</label> &nbsp; ";

		}
		else {$content.="</div>";} // if it's css just close the post box




		if(!strpos($edit,"er") && !strpos($edit,"ss"))
		{
			if (!$tiny)
			{
				$content.="
					<div>
						display<br/>
						<label for='markdown'>
						<input type='checkbox' $markdown_check name='markdown' id='markdown' value='<!--markdown-->' />
						markdown </label>
						<a target='_blank' href='http://michelf.com/projects/php-markdown/concepts/'>[?]</a>
						<br />

						<label for='nolinebreaks'>
						<input type='checkbox' $nlb_check name='nolinebreaks' id='nolinebreaks' value='1' />
						ignore line breaks</label>
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
						$content.=$attachlink;
		}
		elseif(strstr($edit,'er'))
		{
			$content.="
		</div><div>
		$planselect<br />
		<input type='checkbox' $nlb_check name='nolinebreaks' id='nolinebreaks' value='1' />
		<label for='nolinebreaks'>no line breaks</label>
		<a href='$_SERVER[WEB_ROOT]/help/writing#linebreaks' target='_blank'>[?]</a>
		<br />
<input type='checkbox' $markdown_check name='markdown' id='markdown' value='<!--markdown-->' />
<label for='markdown'><a target='_blank' href='http://michelf.com/projects/php-markdown/concepts/'>markdown</a></label>
</div></div>
$attachlink
		";
		}
		elseif(strstr($edit,'ss'))
		{
			$content.="
		</div>
		<div>
		<br /><br /><a target='_blank' href='/help/formatting'>CSS formatting help</a> <br /><br /> <a href='/help/lynneformatting' target='_blank'>Lynne's CSS tips</a><br />
		</div>
		</div>
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
	}


		$content.="

			<input type='hidden' id='draft_edit' name='draft_edit' value='$draft_edit'>
			<input type='hidden' name='edit' value='$edit'/>
			<input type='hidden' name='sid' value='$_SERVER[SESSION_ID]'/>
		</div>";

		if ($journaling_test || $edit=='header' || $edit=='footer') $content.="<input type='hidden' name='action' value='Update Journaling Plan' />\n";
		else $content.="<input type='hidden' name='writer' value='$plan_to_write' /><input type='hidden' name='action' value='Update Nonjournaling Plan' />\n";

		$content.="		$textarea_desktop
</form><div id='draftList'></div>\n";

	}
	else $content.="<div class='alert'><h1>$_SERVER[USER] may not post to $plan_to_write</h1>Your attempt to update $plan_to_write failed because
	your current user does not have the right to post. Please login as a user with appropriate rights, and mail
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
function plan_write_journaling($edit,$plandata,$private=FALSE,$nolinebreaks=FALSE,$writer=FALSE)
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
		if ($nolinebreaks) {$plandata.="<!--nolinebreaks-->";}

		if ($_POST['title']) { $plandata.="<!--title $_POST[title] -->";}
		if ($_POST['tags']) { $plandata.="<!--tags $_POST[tags] -->";}

		// if we weren't editing an existing (already-posted) entry, set the filename for the current time.
		if (!($_POST['edit']) || $_POST['edit']==$_POST['draft_edit']) $_POST['edit']=".$time";
		$plan_fn="$plan_dir/$_POST[hidden]plan$_POST[edit].txt$_POST[private]";

		if (!file_exists($plan_fn) && !$_POST['hidden'])
		{
			file_put_contents("$_SERVER[PWUSERS_DIR]/$planowner/stats/lastupdate",$time);
		}

		if ($_FILES['attached_file']['tmp_name'])
		{
			rename("{$_FILES['attached_file']['tmp_name']}","$_SERVER[USER_ROOT]/files/{$_FILES['attached_file']['name']}");
			$plandata=str_replace("<!--ATTACHED FILE -->",plan_attach_file($_FILES['attached_file']['name'],$writer),$plandata);
		}

		if($_POST['filepicker_file'] && strstr($_POST['filepicker_file'],'https://'))
		{
			$file_data=file_get_contents($_POST['filepicker_file']);
			$upload_temp_filename="$_SERVER[USER_ROOT]/files/".basename(str_replace("https:","",$_POST['filepicker_file']));
			file_put_contents($upload_temp_filename,$file_data);
			if(strpos(basename($upload_temp_filename),'.')===FALSE)
			{
				$filename=file_append_extension($upload_temp_filename);
			}
			else
			{
				$filename=$upload_temp_filename;
			}
			$attachstring=plan_attach_file($filename,$writer);
			$plandata=str_replace("<!--ATTACHED FILE -->",$attachstring,$plandata);

			// we need to kill the link so it can't be misused
		}

		if($_POST['filepicker_files'] && strstr($_POST['filepicker_files'],'https://'))
		{
			$file_list=explode(",",$_POST['filepicker_files']);
			$filename_list=explode(",,,!!,,,",$_POST['filepicker_filenames']);
			foreach($file_list as $i=>$file_url)
			{
				if(strstr(trim($file_url),'https://'))
				{
					$file_data=file_get_contents($file_url);
					$upload_temp_filename="$_SERVER[USER_ROOT]/files/".$filename_list[$i];
	//				$upload_temp_filename="$_SERVER[USER_ROOT]/files/".basename(str_replace("https:","",$file_url));
					file_put_contents($upload_temp_filename,$file_data);
					if(!file_exists($upload_temp_filename)) { echo "FILE NOT WRITTEN<hr>$upload_temp_filename<hr>$file_url<hr>$filename_list[$i]"; exit; }
					if(strpos(basename($upload_temp_filename),'.')===FALSE)
					{
						$filename=file_append_extension($upload_temp_filename);
					}
					else
					{
						$filename=$upload_temp_filename;
					}
					$attachstring=plan_attach_file($filename,$writer);
					$plandata=str_replace("<!--ATTACHED FILE $filename_list[$i] -->",$attachstring,$plandata);
				}
			}

			// we need to kill the link so it can't be misused
		}


		$plandata.=$_POST['markdown'];
		$plandata.=$_POST['nofeed'];

		// save old headers and footers.
		if (strstr($plan_fn,'header') || strstr($plan_fn,'footer'))
		{
			rename($plan_fn,$plan_fn.$time);
		}

		// write the update to disk.
		file_put_contents($plan_fn,$plandata);


		// new feature: SPIEL
		// here's the part where spiels are found
		// TODO(v4.5): replace spiel syntax with hashtags
		if (!$private && !$edit) {spiel_find($plandata,$planowner,$time);}

		// here's the part where sends are found
		if (!$private && !$edit) {send_find($plandata,$planowner,$time);}

		if (file_exists($plan_fn))
		{
			if ($private && file_exists("$plan_dir/plan$edit.txt")) exec("mv $plan_dir/plan$edit.txt $plan_dir/rem.plan$edit.txt");
			if (!$private && file_exists("$plan_dir/plan$edit.txt.p")) exec("mv $plan_dir/plan$edit.txt.p $plan_dir/rem.plan$edit.txt.p");
			if (!$_POST['hidden'] && file_exists("$plan_dir/hidden.plan$edit.txt")) exec("mv $plan_dir/hidden.plan$edit.txt $plan_dir/rem.hidden.plan$edit.txt");
			if ($_POST['hidden'] && file_exists("$plan_dir/plan$edit.txt")) exec("mv $plan_dir/plan$edit.txt $plan_dir/rem.plan$edit.txt");
			if ($_POST['draft_edit'] && file_exists("$plan_dir/draft$_POST[draft_edit].txt")) unlink("$plan_dir/draft$_POST[draft_edit].txt");

			// clean up old drafts
			if ($drafts=files_list("$plan_dir/","draft*.txt"))
			{
				foreach($drafts as $draft)
				{
					if (filemtime("$plan_dir/$draft")<(time()-60*24*3600)) {unlink("$plan_dir/$draft");}
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
		if ($_SERVER['SESSION_ID'])
		{
			if ($_SERVER['AJAX_POST'])
			{
				return $plandata;
			}
			elseif($_POST['mailpost'])
			{
				header("http/1.1 200 ok");
				echo "posted"; exit;
			}
			else
			{
			// This is an ordinary post from the write form.
				if($_POST['sid'] && !$_COOKIE[$_SERVER['SESSION_COOKIE']]) $sid=$_SERVER['SESSION_ID'];
				redirect("/read/$planowner/$sid");
			}

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


function plan_attach_file($filename,$writer)
{
	$filename=basename($filename);
	if (stristr($filename,'jpg')
		|| stristr($filename,'gif')
		|| stristr($filename,'jpeg')
		|| stristr($filename,'webp')
		|| stristr($filename,'png'))
	{
		$imagesize=getimagesize("$_SERVER[USER_ROOT]/files/$filename");
		if($imagesize[0]>600) { photo_resize("$_SERVER[USER_ROOT]/files/$filename",'width',600);
			$plandata.="<a href='https://planwatch.org/userfiles/view/$writer/$filename'><img src='https://planwatch.org/userfiles/view/$writer/$filename.width.600.jpg' /></a>";
			}
		else
			$plandata.="<img src='https://planwatch.org/userfiles/view/$writer/$filename' />";
	}
	elseif (stristr($filename,'mp3')
		|| stristr($filename,'m4a')
		|| stristr($filename,'wav'))
	{
		if(strstr($filename,'mp3')) $flash_fallback="				<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='25' height='20'
			codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab'>
			<param name='movie' value='https://planwatch.org/resources/singlemp3player.swf?file=sound.mp3&backColor=990000&frontColor=ddddff&repeatPlay=false&songVolume=30' />

			<param name='wmode' value='transparent' />
			<embed wmode='transparent' width='25' height='20' src='https://planwatch.org/resources/singlemp3player.swf?file=sound.mp3&backColor=990000&frontColor=ddddff&repeatPlay=false&songVolume=30'

			type='application/x-shockwave-flash' pluginspage='http://www.macromedia.com/go/getflashplayer' />
		</object>";

		$plandata.="
	<audio controls>
		<source src='https://planwatch.org/userfiles/view/$writer/$filename' />
		$flash_fallback
	</audio>
	";
	}
	else $plandata.="\n<a href='https://planwatch.org/userfiles/view/$writer/$filename'>$filename</a>";

	// add the enclosure tag for rss feeds
	$length=filesize("$_SERVER[USER_ROOT]/files/$filename");
	$mime_type=file_get_type("$_SERVER[USER_ROOT]/files/$filename");

	//$plandata.="<enclosure url='https://planwatch.org/userfiles/view/$writer/$filename' length='$length' type='$mime_type' />";

	return $plandata;

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

	if($_POST['filepicker_files'] && strstr($_POST['filepicker_files'],'https://'))
	{
		$file_list=explode(",",$_POST['filepicker_files']);
		$filename_list=explode(",,,!!,,,",$_POST['filepicker_filenames']);
		foreach($file_list as $i=>$file_url)
		{
			if(strstr(trim($file_url),'https://'))
			{
				$file_data=file_get_contents($file_url);
				$upload_temp_filename="$_SERVER[USER_ROOT]/files/".$filename_list[$i];
//				$upload_temp_filename="$_SERVER[USER_ROOT]/files/".basename(str_replace("https:","",$file_url));
				file_put_contents($upload_temp_filename,$file_data);
				if(!file_exists($upload_temp_filename)) { echo "FILE NOT WRITTEN<hr>$upload_temp_filename<hr>$file_url<hr>$filename_list[$i]"; exit; }
				if(strpos(basename($upload_temp_filename),'.')===FALSE)
				{
					$filename=file_append_extension($upload_temp_filename);
				}
				else
				{
					$filename=$upload_temp_filename;
				}
				$attachstring=plan_attach_file($filename,$writer);
				$plandata=str_replace("<!--ATTACHED FILE $filename_list[$i] -->",$attachstring,$plandata);
			}
		}

		// we need to kill the link so it can't be misused
	}

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


//!- MAIN ACTIONS ========================================================================
if (user_verify_sessionkey($_SERVER['SESSION_ID']))
{
	if (isset($_POST['action']))
	{
		switch ($_POST['action']):

		case  'Write Blocked Message':
			plan_write_blocked_message($_POST['message']);
			break;

		case 'Update Journaling Plan':
			if(!isset($_POST['private'])) { $_POST['private']=FALSE; }
			if(!isset($_POST['nolinebreaks'])) { $_POST['nolinebreaks']=FALSE; }
			if(!isset($_POST['hidden'])) { $_POST['hidden']=FALSE; }

			plan_write_journaling($_POST['edit'],$_POST['newplan'],$_POST['private'],$_POST['nolinebreaks'],$_POST['writer'],$_POST['hidden']);
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

// PHOTO_RESIZE
//
// creates a thumbnail of the passed image
function photo_resize($image_filename,$dimension='width',$final_size=320,$format=FALSE,$thumbs_directory=FALSE)
{

	// small images are stored as pngs
	// larger ones as jpgs
	// unless another script tells it differently
	if (!$format)
	{
		if ($final_size > 256) $format='.jpg';
		else $format='.png';
	}

	// define the orginal filenames
	if($thumbs_directory=='FALSE')
		$thumbs_directory="$_SERVER[USER_DIR]/files";

	if(!strstr($image_filename,'/planwatch.org/'))
	{
		$original_filename="$_SERVER[USER_DIR]/files/$image_filename";
		$thumb_filename="$thumbs_directory/$image_filename.$dimension.$final_size$format";
	}
	else
	{
		$original_filename=$image_filename;
		$thumb_filename="$thumbs_directory/".basename($image_filename).".$dimension.$final_size$format";
	}

	// determine the final dimensions of the image
	if ($dimension=='width')
	{
		$final_width=$final_size;
	}

	if ($dimension=='height')
	{
		$final_height=$final_size;
	}

	if ($dimension=='square')
	{
		$currentsize=getimagesize($original_filename);
		// make sure we're cropping out a piece rather than
		// making a matte
		if ($currentsize[0]>$currentsize[1]) $final_height=$final_size;
		else $final_width=$final_size;
	}


	// create the thumbs directory if it doesn't already exist
	if(!is_dir($thumbs_directory))
	{
		trigger_error("creating directory $thumbs_directory");
		$old=umask(0);
		mkdir($thumbs_directory,0755);
		umask($old);
		if(!is_dir($thumbs_directory))
		{
			trigger_error("Thumbs directory not created.",E_USER_WARNING);
		}
		else trigger_error("$thumbs_directory exists.");
	}
	else trigger_error("$thumbs_directory exists.");

	// only try to resize if the original filename exists
	if (file_exists($original_filename))
	{
		if ($dimension=='square')
		{
			trigger_error($thumb_filename);
			$results=shell_exec("/usr/bin/vipsthumbnail --rotate --size {$final_width}x{$final_height} --smartcrop attention -c -d --format=\"$thumb_filename\" \"$original_filename\"");
			trigger_error($results);
		}
		else
		{
			trigger_error($thumb_filename);
			$results=shell_exec("/usr/bin/vipsthumbnail --rotate --size {$final_width}x{$final_height} -d --format=\"$thumb_filename\"[Q=80,optimize_coding] \"$original_filename\"");
			trigger_error($results);
		}
	}
	else
	{
		trigger_error("Original file <strong>$original_filename</strong> does not exist.",E_USER_WARNING);
	}

	if(file_exists($thumb_filename))
	{
		trigger_error("$thumb_filename exists");
		return TRUE;
	}
	else
	{
		trigger_error("Failed to create <strong>$thumb_filename</strong>",E_USER_WARNING);
		return FALSE;
	}
}

?>