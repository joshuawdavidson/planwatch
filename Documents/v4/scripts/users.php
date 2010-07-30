<?php
/*
USERS.PHP -- part of the planwatch library

deletes users, creates new users.

some of the stuff this used to do is now taken care of
by prefs_form.php and gravity_forms.php
*/


// DELETEUSER()
//
// removes user data from disk
//------------------------------------------------------------------------------
function user_delete($username_to_delete)
{
	if (!$username_to_delete) redirect('/');
	if (($username_to_delete==$_SERVER['USER'] || user_is_administrator()) && user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
	{
	    $userdir="$_SERVER[PWUSERS_DIR]/$username_to_delete";
		exec("rm -fR $userdir",$delresults);

		exec("grep -rli $username_to_delete $_SERVER[PWUSERS_DIR]/*/watchedlist.txt",$watchedlists);

		foreach($watchedlists as $watched)
		{
			$data=file_get_contents($watched);

			if (strstr($data,"!$planowner"))
			{
				preg_match("|(!$planowner.*!)|",$data,$matches);
				$remove=$matches[0];
			}
			// otherwise, detect all lines that are just the planowner
			else $remove="\n$planowner\n";
		
			// remove whatever we found
			$data=str_replace($remove,'',$data);

			// break down multiple linebreaks so the list doesn't look weird in the edit view
			$data=str_replace("\n\n","\n",$data);

			file_put_contents($watched,$data);
		}
	}
	else output("Error deleting $username_to_delete","
	<div class='alert'>
	You can't delete $username_to_delete. Talk to an
	<a href='mailto:help@planwatch.org'>admin</a>.
	Click <a href='$_SERVER[WEB_ROOT]/'>here</a> to go back to the main page.
	</div>
	");
	
	if ($username_to_delete==$user) logout("$username_to_delete has been deleted.");
	else redirect('/');
}




// CHANGE_ONE_PREF()
//
// this function allows the changing of a single pref
// in the userdata.txt file of a user. it then logs
// them back in to refresh their cookie.
// this can only change prefs in the userdata.txt file,
// not prefs that require other files for storage.
//
// this is a quick hack to enable the move links on the
// watched list bar. like everything else in pw.o, it
// needs cleanup and standardization
//------------------------------------------------------------------------------
function change_one_pref($prefname,$newvalue)
{
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
	{
		$userinfo=unserialize(file_get_contents("$_SERVER[USER_ROOT]/userinfo.dat"));
		$preferences=unserialize(file_get_contents("$_SERVER[USER_ROOT]/preferences.dat"));

		if (isset($userinfo[$prefname])) $userinfo[$prefname]=$newvalue;
		else $preferences[$prefname]=$newvalue;

		file_put_contents("$_SERVER[USER_ROOT]/userinfo.dat",serialize($userinfo));
		file_put_contents("$_SERVER[USER_ROOT]/preferences.dat",serialize($preferences));
	}

redirect("/");
}

// USER_REGISTER_FORM()
//
// presents a form for creating a new user which verifies a valid username and email address.
//------------------------------------------------------------------------------
function user_register_form($error_type=FALSE)
{
	if ($_GET['name'] && !$_GET['real_name']) $_GET['real_name']=$_GET['name'];
	if ($_GET) extract($_GET);

	$oldusername=$username;
	$username=strtolower($username);
	preg_match_all("/[a-z0-9]/",$username,$matches);
	$username=implode('',$matches[0]);

	$content.="
	<script type='text/javascript' src='$_SERVER[WEB_ROOT]/resources/javascript/validate.js'></script>
	";

	if ($error_type=='email_error') $content.="
	<div class='alert'>
		The email address $email is not valid. Please enter a valid email address.
		You will need to confirm your registration by receiving an email at the address you enter.
		If you feel this is an error, email details to
		<a href='mailto:help@planwatch.org'>help@planwatch.org</a>
	</div>";

	if ($error_type=='username_error') $content.="
	<div class='alert'>
		The username $oldusername is not valid. Please select a new username, such as $username.
		If you feel this is an error, email details to
		<a href='mailto:help@planwatch.org'>help@planwatch.org</a>
	</div>";

	if ($error_type=='username_omitted_error') $content.="
	<div class='alert'>
		You did not provide a username. Please do so now.
		If you feel this is an error, email details to
		<a href='mailto:help@planwatch.org'>help@planwatch.org</a>
	</div>";

	if ($error_type=='realname_error') $content.="
	<div class='alert'>
		The 'real name' $real_name is not valid. Please enter your actual name, first and last.
		If you feel this is an error, email details to
		<a href='mailto:help@planwatch.org'>help@planwatch.org</a>
	</div>";

	if ($error_type=='planworld_error')
	{
		$planusername=base64_decode($planusername);
		if (strpos($planusername,'amherst')) $plantype_guess='@amherst.edu';
		if (strpos($planusername,'planworld.net')) $plantype_guess='@planworld.net';

		$username_guess=substr($planusername,strpos($planusername,'?id=')+4);
		$content.="
		<div align='center'>
		<div class='alert'>
			It looks like you're trying to register an account with a plan
			hosted on NOTE or planworld.net.<br /><br />
			<div class='column'>
				You entered<br />
				<span>
				username: <i>$planusername</i><br />
				plan server: <i>$plantype</i>
				</span>
			</div>
			<div class='column'>
				It should probably be<br />
				<span>
				username: <i>$username_guess</i><br />
				plan server: <i>$plantype_guess</i>
				</span>
			</div>
			<br clear='all'><br />
			Check the form below and hit 'create user' if it's correct.<br />
			If you've received this message in error, email <a href='mailto:help@planwatch.org'>help@planwatch.org</a>
		</div>\n\n";

		$close_script.="
		<script>
			advanced_div = document.getElementById('advanced').style.display='block';
			adv_link_div = document.getElementById('advanced_link').style.display='none';
			sim_link_div = document.getElementById('simple_link').style.display='inline';
		</script>\n";
		
		$plantype=$plantype_guess;
		$planusername=$username_guess;
	}

	$content.="
		<form action='$_SERVER[WEB_ROOT]/scripts/users.php' name='registerForm' method='post'>

		<fieldset>
		";
	
	$content.="<br/>
			<strong>real (full) name</strong>: <input type='text' name='real_name' value='$real_name' onblur=\"if(checkRealname(this.value)!='') { this.style.background='#ffff99'; } else this.style.background='white'; \"/><br/><br/>
			<strong>email</strong>: <input type='text' name='email' value='$email' onblur=\"if(checkEmail(this.value)!='') { this.style.background='#ffff99'; } else this.style.background='white'; \"/><br/>
			must be valid to confirm account <br/><br/>
		</fieldset>
		<fieldset>
			<br/><strong>user name</strong>: <br/><input id='register_username' type='text' style='text-transform: lowercase;' name='username' value='$username' onblur=\"if(checkUsername(this.value)!='') { this.style.background='#ffff99'; } else this.style.background='white'; \"/><br/>
			ex. basmith for Bob A. Smith<br/><br/> 
			<strong>password</strong>:<br/><input  id='register_userpass' type='password' name='userpass' value='$userpass'/><br/><br/>
			<input type='hidden' name='action' value='registerwritenew'/>
		</fieldset>

		<fieldset style='display: none;' id='advanced'>
	<br/>Use this panel if you want to set another plan or blog as the source for your pw.o account.<br/><br/>
			plan server:
			<select name='plantype'  onchange=\"
				if (this.value=='RSS') { document.getElementById('planusername_div').style.display='block'; window.document.register_form.planusername.value='http://site.name/index.rss'; document.getElementById('planusername_label').innerHTML='Feed URL'; }
				if (this.value=='@planworld.net' || this.value=='@amherst.edu') { document.getElementById('planusername_div').style.display='block'; window.document.register_form.planusername.value='jqpublic'; document.getElementById('planusername_label').innerHTML='Username'; }
				if (this.value=='local') document.getElementById('planusername_div').style.display='none';
				\">\n";

	if ($plantype=='local') $ls='SELECTED';
	if ($plantype=='@planworld.net') $ps='SELECTED';
	if ($plantype=='@amherst.edu') $as='SELECTED';
	if ($plantype=='@livejournal') $ljs='SELECTED';
	if ($plantype=='@diaryland') $dls='SELECTED';
	if ($plantype=='@deadjournal') $djs='SELECTED';
	if ($plantype=='@xanga') $xs='SELECTED';
	if ($plantype=='@myspace') $ms='SELECTED';
	if ($plantype=='RSS') $rs='SELECTED';

	$content.="
				<option value='local' $ls>local</option>
				<option value='@planworld.net' $ps>planworld.net</option>
				<option value='@amherst.edu' $as>amherst.edu</option>
				<option value='@livejournal' $ljs>livejournal</option>
				<option value='@deadjournal' $djs>deadjournal</option>
				<option value='@diaryland' $dls>diaryland</option>
				<option value='@xanga' $xs>xanga</option>
				<option value='@myspace' $ms>myspace</option>
				<option value='RSS' $rs>RSS or Atom Feed</option>\n";

	if (!$planusername) $planusername=$username;
	$content.="
			</select>
			<div id='planusername_div'><span id='planusername_label'>Username</span> <input type='text' name='planusername' value='$planusername'/></div>
		</fieldset>
<br clear='all'/>
			<input type='hidden' name='inviter' value='$inviter'>
			<input type='submit' name='submit' value='create user'/>
			<input type='button' name='cancel' value='cancel' onclick='reg_toggle();'/>
			<input type='button' id='advanced_link' value='advanced' onclick='advanced_toggle();'/>
			<input type='button' id='simple_link' value='simple' onclick='advanced_toggle();' style='display: none;'/>
		</form>
	$close_script
";

if ($error_type) output("Register",$content);
else return $content;
}


function user_confirm()
{
		extract($_GET);
	$local_confirm=file_get_contents("$_SERVER[PWUSERS_DIR]/$username/unconfirmed");
	if (trim($code) == trim($local_confirm))
	{
		unlink("$_SERVER[PWUSERS_DIR]/$username/unconfirmed");
		extract(unserialize(file_get_contents("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat")));
		login($username,$userpass,FALSE,FALSE);
		redirect("/alert/Email confirmation successful.+Thanks.");
		exit;
	}
	else { redirect("/alert/Confirmation+failed.+Please+email+help@planwatch.org"); exit; }
}

///////////////////////////////////////////////////////////////////////////////////////////
// ACTION==REGISTERWRITENEW
//
//------------------------------------------------------------------------------
if ($_POST['action']=='registerwritenew')
{
	extract($_POST);
		$getvars="&username=$username&userpass=$userpass&plantype=$plantype&planusername=$planusername&real_name=$real_name&email=$email&inviter=$inviter";
	
	if (!$planusername) $planusername=$username;

	if (strpos($planusername,'planworld')!==FALSE)
	{
		$planusername=base64_encode($planusername);
		redirect("$_SERVER[WEB_ROOT]/scripts/users.php?action=correct_form&error=planworld_error$getvars");
		exit;
	}

	$username=strtolower($username);
	preg_match_all("/[a-z0-9]/",$username,$matches);
	$newusername=implode('',$matches[0]);

	if (!$real_name || !strstr($real_name,' '))
	{
		redirect("$_SERVER[WEB_ROOT]/scripts/users.php?action=correct_form&error=realname_error$getvars");
		exit;
	}

	if (!$username)
	{
		redirect("$_SERVER[WEB_ROOT]/scripts/users.php?action=correct_form&error=username_omitted_error$getvars");
		exit;
	}

	if ($newusername!=$username)
	{
		redirect("$_SERVER[WEB_ROOT]/scripts/users.php?action=correct_form&error=username_error$getvars");
		exit;
	}

	if (!strstr($email,'@'))
	{
		redirect("$_SERVER[WEB_ROOT]/scripts/users.php?action=correct_form&error=email_error$getvars");
		exit;
	}
	else
	{
		$confirmtime=str_replace(" ","_",microtime());
		mail($email,
			"Confirm your planwatch.org account",
			"Click the link below to confirm your new planwatch.org account.\n\nhttp://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/scripts/users.php?action=confirm&code=$confirmtime&username=$username\n\nIf something goes wrong, contact help@planwatch.org.",
			"From: register@planwatch.org");
	}

	if (!file_exists("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat"))
	{
	    $old_umask=umask(0);
		mkdir("$_SERVER[PWUSERS_DIR]/$username",0755);
		mkdir("$_SERVER[PWUSERS_DIR]/$username/stats",0755);
		mkdir("$_SERVER[PWUSERS_DIR]/$username/plan",0755);
		mkdir("$_SERVER[PWUSERS_DIR]/$username/files",0755);
	    umask($old_umask);
	    
	    copy("$_SERVER[FILE_ROOT]/resources/defaults/preferences.dat","$_SERVER[PWUSERS_DIR]/$username/plan/preferences.dat");
	    copy("$_SERVER[FILE_ROOT]/resources/defaults/planheader.txt","$_SERVER[PWUSERS_DIR]/$username/plan/planheader.txt");
	    copy("$_SERVER[FILE_ROOT]/resources/defaults/planfooter.txt","$_SERVER[PWUSERS_DIR]/$username/plan/planfooter.txt");
	    copy("$_SERVER[FILE_ROOT]/resources/defaults/plandivider.txt","$_SERVER[PWUSERS_DIR]/$username/plan/plandivider.txt");
	    copy("$_SERVER[FILE_ROOT]/resources/defaults/plan.0.txt","$_SERVER[PWUSERS_DIR]/$username/plan/plan.0.txt");

		file_put_contents("$_SERVER[PWUSERS_DIR]/$username/unconfirmed",$confirmtime);

	    chmod("$_SERVER[PWUSERS_DIR]/$username/plan/plan.0.txt",0755);
	    chmod("$_SERVER[PWUSERS_DIR]/$username/plan/planheader.txt",0755);
	    chmod("$_SERVER[PWUSERS_DIR]/$username/plan/planfooter.txt",0755);
	    chmod("$_SERVER[PWUSERS_DIR]/$username/plan/plandivider.txt",0755);
	    
		$userinfo=array(
	                    'username'=>$username,
	                    'userpass'=>$userpass,
	                    'plantype'=>$plantype,
	                    'planusername'=>$planusername,
	                    'email'=>$email,
	                    'real_name'=>$real_name,
	                    'journaling'=>1,
	                    'privacy'=>2,
	                    'fingerpref'=>1,
	                    'rlpref'=>1,
	                    'defaultdays'=>2,
	                    'dontlist'=>0,
	                    'inviter'=>$inviter
	                   );
	
		file_put_contents("$_SERVER[PWUSERS_DIR]/$username/userinfo.dat",serialize($userinfo));
	
		login($username,$userpass,0,'/firstlogin',TRUE);
	}
	else redirect("$_SERVER[WEB_ROOT]/scripts/users.php?action=correct_form&error=already_exists");
}

if ($_GET['action']=='correct_form') user_register_form($_GET['error']);
if ($_GET['action']=='confirm') user_confirm();
if ($_GET['action']=='delete') user_delete($username);

?>
