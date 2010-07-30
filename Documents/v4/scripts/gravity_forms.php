<?php
/*******************************************************************************
GRAVITY_FORMS.PHP

generic functions for generating forms and writing their results
from structs.

a form fields struct looks like this:

	$form_definitions=array
	(
		$name_of_field		=>	array
		(
			"type"				=>	[ 'text' || 'menu' || 'list' || 'radio' || 'check' || 'textblock' || 'file' || 'password' || 'hidden' || 'email' ]
			"choices"			=>	assoc array of options for menu, list, check, popup types
			"default"			=>	one of the elements of "choices" or anything, if it's a text or textblock type
			"public"			=>	bool - do we display this to just anyone in the output?
			"formlabel"			=>	pretty-formatted name of field for form presentation
			"sectiontitle"		=>	header of form section, printed before the field
			"spaceafter"		=>	bool - do we print a blank line after this field?
			"spaceafter"		=>	bool - do we print a horizontal rule after this field?
		)

	//SPECIAL FIELD
		"form_disposition"	=>	array
		(
			"storage_format"	=>	[ 'database' || 'file' ]
			"storage_database"	=>	name of database for storage
			"storage_table"		=>	name of table for storage
			"storage_directory"	=>	name of directory for storage
			"storage_filename"	=>	name of file for storage
			"storage_clobber"	=>	bool - overwrite or append existing data?

			// database & table are defined XOR directory & filename are defined
		)
	)
--------------------------------------------------------------------------------

functions:
	form_build_from_struct()
	form_process_from_struct()

--------------------------------------------------------------------------------

AUTHOR:   Josh Davidson / help@planwatch.org

*******************************************************************************/




// FORM_BUILD_FROM_STRUCT()
//
// creates a form based on the struct passed in (see example in header)
//------------------------------------------------------------------------------

function form_build_from_struct($form_name='',$form_definitions=FALSE)
{
	$form.="
	<style type='text/css'>
	.form_line			{ height: 40px; padding: 4px; text-align: left; background-color: $GLOBALS[listsbgcolor]; color: $GLOBALS[navtextcolor]; border-top: 1px solid $GLOBALS[planbgcolor]; width: 80%; }
	.form_label			{ text-align: right; padding: 4px; color: $GLOBALS[navtextcolor]; float: left; width: 50%; background-color: $GLOBALS[listsbgcolor];}
	.form_comments		{ font-style: italic; font-size: smaller; }
	.form_section_title { font-size: larger; font-weight: bold; }
	.form_line input	{ font-family: $GLOBALS[pfont],$GLOBALS[pfonts]; font-size: $GLOBALS[pfsize_css]; }
	.form_line select	{ font-family: $GLOBALS[pfont],$GLOBALS[pfonts]; font-size: $GLOBALS[pfsize_css]; }
	.form_submit_line	{ margin-top: 20px; padding: 4px; text-align: center; background: $GLOBALS[navtextcolor]; width: 80%; }
	</style>\n";

	$form.="<h1>$form_name</h1>\n";

	if (!is_array($form_definitions)) return array("error"=>'form_build_from_struct() ERROR: $form_definitions is not an array');
	else $form.="\n<!--FORM-->\n<form name='$form_name' action='$_SERVER[WEB_ROOT]/scripts/gravity_forms.php' method='post' enctype='multipart/form-data'>\n";

	$form.="\n<input type='hidden' name='form_operation' value='form_process_with_struct'/>";
	$form.="\n<input type='hidden' name='form_name' value='$form_name'/>";
	$form.="\n<input type='hidden' name='form_definitions' value='".base64_encode(serialize($form_definitions))."'/>";

	if (file_exists($form_definitions['form_disposition']['storage_directory'].'/'.$form_definitions['form_disposition']['storage_filename']))
		$defaults=unserialize(file_get_contents($form_definitions['form_disposition']['storage_directory'].'/'.$form_definitions['form_disposition']['storage_filename']));

	if ($defaults)
	foreach($defaults as $variable=>$value)
	{
		$form_definitions[$variable]['default']=$value;	
	}

	foreach($form_definitions as $field=>$fieldoptions)
	{
		if($fieldoptions['sectiontitle']) $form.="<div class='form_section_title'>$fieldoptions[sectiontitle]</div>";

		if ($field!="form_disposition")
		switch($fieldoptions['type']):

		case "hidden":
			$form.="<input type='hidden' name='$field' value='$fieldoptions[default]'/>";
			break;

		case "text":
			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>
			<input type='text'	name='$field' value='$fieldoptions[default]'/>";
			$form.="\n<span class='form_comments'>$fieldoptions[remarks]</span>";
			$form.="\n</div>";
			break;

		case "email":
			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>
			<input type='email'	name='$field' value='$fieldoptions[default]'/>";
			$form.="\n<span class='form_comments'>$fieldoptions[remarks]</span>";
			$form.="\n</div>";
			break;

		case "menu":
			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>
			<select name='$field' onchange=\"$fieldoptions[onchange_js]\" id='$field'>";
				foreach($fieldoptions['choices'] as $prettychoice=>$choice)
				{
					if ($choice==$fieldoptions['default']) $sel='SELECTED'; else $sel='';
					$form.="\n\t<option value='$choice' $sel>$prettychoice</option>";
				}
			$form.="\n</select>";
			$form.="\n<span class='form_comments'>$fieldoptions[remarks]</span>";
			$form.="\n</div>";
			break;

		case "list":
			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>
			<select name='$field' multiple size='4'>";
				foreach($fieldoptions['choices'] as $prettychoice=>$choice)
				{
					if ($choice==$fieldoptions['default']) $sel='SELECTED'; else $sel='';
					$form.="\n\t<option value='$choice'>$prettychoice</option>";
				}
			$form.="\n</select>";
			$form.="\n<div class='form_comments'>$fieldoptions[remarks]</div>";
			$form.="\n</div>";
			break;

		case "radio":
			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>";
			foreach($fieldoptions['choices'] as $prettychoice=>$choice)
			{
				if (strlen($prettychoice)<=1) $prettychoice=$choice;
				if ($choice==$fieldoptions['default']) $sel='CHECKED'; else $sel='';
				$form.="\n\t<span class='form_checkbox'><input type='radio' id='$field"."_$choice' name='$field' value='$choice' $sel /><label for='$field"."_$choice'>$prettychoice</label></span>";
			}
			$form.="\n<span class='form_comments'>$fieldoptions[remarks]</span>";
			$form.="\n</div>";
			break;

		case "check":
			if ($fieldoptions['choices'][0]==$fieldoptions['default']) $sel='CHECKED'; else $sel='';

			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>
					<span class='form_checkbox'><input type='checkbox' id='$field' name='$field' value='$fieldoptions[default]' $sel /><label for='$field'>$fieldoptions[default]</label></span>";
			$form.="\n<span class='form_comments'>$fieldoptions[remarks]</span>";
			$form.="\n</div>";
			break;

		case "textblock":
			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>";
			$form.="\n\t<textarea name='$field'>$fieldoptions[default]</textarea>";
			$form.="\n<span class='form_comments'>$fieldoptions[remarks]</span>";
			$form.="\n</div>";
			break;

		case "file":
			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>";
			$form.="\n\t<input type='file' name='$field' />";
			$form.="\n<span class='form_comments'>$fieldoptions[remarks]</span>";
			$form.="\n</div>";
			break;

		case "password":
			$form.="\n<div class='form_line' id='$field"."_line'><div class='form_label' id='$field"."_label'>$fieldoptions[formlabel]</div>";
			$form.="\n\t<input type='password' name='$field' value='$fieldoptions[default]' />";
			$form.="\n<span class='form_comments'>$fieldoptions[remarks]</span>";
			$form.="\n</div>";
			break;

		default: break;
		endswitch;

		if ($fieldoptions['onload_js']) $form_onload_js.=$fieldoptions[onload_js]."\n";
		if($fieldoptions['spaceafter']===TRUE) $form.="\n<br /><br />\n\n";
		if($fieldoptions['ruleafter']===TRUE) $form.="\n<hr>\n";
	}

	$form.="
	\n\n<script type='text/javascript'>\n/* Form Onload Javascript */\n $form_onload_js\n</script>\n\n
	<div class='form_submit_line' style='text-align: center;'><input type='submit' value='Save Changes' /></div></form>\n";

return $form;
}





// FORM_PROCESS_WITH_STRUCT()
//
//	string form_name
//	struct form_definitions
//	struct form_data
//
// parses input from form according to struct form_definitions (see header
// for example)
//------------------------------------------------------------------------------

function form_process_with_struct()
{
	include_once('snoop.php');
	include_once('plan_read.php');
	extract($_POST);


	unlink("$_SERVER[FILE_ROOT]/stats/planlocations.dat");
	unlink("$_SERVER[FILE_ROOT]/stats/plan_locations.dat");
	unlink("$_SERVER[FILE_ROOT]/stats/plan_failures.dat");

	foreach($_FILES as $i=>$file)
	{
		if ($file['size'] > 0)
			move_uploaded_file($file['tmp_name'],"$_SERVER[FILE_ROOT]/resources/".$file['name']);
	}

	$olduserinfo = $_SERVER['USERINFO_ARRAY'];

	$form_definitions=unserialize(base64_decode($form_definitions));

	extract($form_definitions['form_disposition']);
	unset($form_definitions['form_disposition']);

	if ($storage_format=='file')
	{
		$keys=array_keys($form_definitions);
		foreach($keys as $key)
		{
			$output_array[$key]=$_POST[$key];
		}

		file_put_contents("$storage_directory/$storage_filename",serialize($output_array));


		// CHANGING PLAN TYPE
		if ($olduserinfo['journaling'] != $output_array['journaling'] && isset($output_array['journaling']))
		{
			if ($olduserinfo['journaling'])
			{
				$plan_fn="$_SERVER[PWUSERS_DIR]/$olduserinfo[username]/plan/plan.txt";
				@rename($plan_fn,$plan_fn.time());
				file_put_contents($plan_fn,$oldplan);
			}

			if ($output_array['journaling'])
			{
				$plan_fn="$_SERVER[PWUSERS_DIR]/$olduserinfo[username]/plan/plan.".time().".txt";
				file_put_contents($plan_fn,$oldplan);
			}
		}


		// ADVERTISED LIST HANDLING
		$advlist=@file_get_contents("$_SERVER[FILE_ROOT]/stats/advertised.txt");
		if ($output_array['privacy']==1 && strpos($advlist,$output_array['username'])===FALSE)
		{
			file_put_contents("$_SERVER[FILE_ROOT]/stats/advertised.txt","\n".$output_array['username'],FILE_APPEND);
		}

		if ($output_array['privacy']!=1 && strpos($advlist,$output_array['username'])!==FALSE)
		{
			file_put_contents("$_SERVER[FILE_ROOT]/stats/advertised.txt",str_replace("\n$output_array[username]",'',$advlist));
		}

		// private feed
		unlink("$_SERVER[FILE_ROOT]/resources/privatefeeds/$olduserinfo[secretword].owner");
		file_put_contents("$_SERVER[FILE_ROOT]/resources/privatefeeds/$output_array[secretfeedword].owner","$output_array[planusername]");
		
		
		$oa_un=$output_array['username'];

		exec("rm -f $_SERVER[FILE_ROOT]/temp/*$oa_un*.cache");

		$_SERVER['PLAN_LOCATION_ARRAY']=unserialize(file_get_contents("$_SERVER[FILE_ROOT]/stats/plan_locations.dat"));
		$_SERVER['PLAN_LOCATION_ARRAY'][$oa_un]=plan_get_real_location($output_array['planusername'].str_replace(array('RSS','@local'),'',$output_array['plantype']));
		file_put_contents("$_SERVER[FILE_ROOT]/stats/plan_locations.dat",serialize($_SERVER['PLAN_LOCATION_ARRAY']));

		// RENAMED USER HANDLING
		if ($output_array['username'] && $output_array['username']!=$_SERVER['USER'])
		{
			rename($storage_directory,str_replace($_SERVER['USER'],$output_array['username'],$storage_directory));

			// SNOOP STUFF
			if ($output_array['plantype']=='@local')
			{
				if ($old_snoop_array!=FALSE)
				{
					$new_snoop_array=snoop_find(plan_read($output_array['username']));
					snoop_clean(array_unique($old_snoop_array),$olduserinfo['username']);
					snoop_add(array_unique($new_snoop_array),$output_array['username']);	
				}
			}
			exec("ls $_SERVER[PWUSERS_DIR]/*/*list.txt",$list_list);
			foreach($list_list as $list)
			{
				$list_data=file_get_contents($list);
				$list_data=str_replace($olduserinfo['username'],$output_array['username'],$list_data);
				file_put_contents($list,$list_data);
			}

			login($output_array['username'],$output_array['userpass']);

		}
		else redirect('/');
	}

}

if ($_POST['form_operation']=='form_process_with_struct') { form_process_with_struct(); }

?>