<?php
/*
PREFS_FORMS.php

the array defs for the prefs form
relies on gravity_forms.php and gravity_filestore.php

*/
//			"sectiontitle"		=>	header of form section, printed before the field
//			"spaceafter"		=>	bool - do we print a blank line after this field?
//			"ruleafter"		=>	bool - do we print a horizontal rule after this field?


	$tzdata_fn="$_SERVER[FILE_ROOT]/resources/zone.tab";
	$tzcache_fn="$_SERVER[FILE_ROOT]/resources/zone.cache";

	if(filemtime($tzcache_fn)<filemtime($tzdata_fn))
	{
		$tzdata=file($tzdata_fn);
		foreach($tzdata as $i=>$tzline)
		{
			if ($tzline[0]!='#')
			{
				$zonedata=explode("\t",$tzline);
				$zone_tz=$zonedata[2];
				$zone_desc=str_replace(array('_','/'),array(' ',': '),$zone_tz);
				$zoneinfo[$zone_desc]=$zone_tz;

				if ($tzline[0]=='U' && $tzline[1]=='S') $uszoneinfo[$zone_desc]=$zone_tz;
			}
		}
		$tzchoices=array_merge($uszoneinfo,array('----------'=>'GMT+0'),$zoneinfo);
		file_put_contents($tzcache_fn,serialize($tzchoices));
	}
	else {
		$tzcache_data = file_get_contents($tzcache_fn);
		$tzchoices=unserialize($tzcache_data);
		unset($tzcache_data);
	}

	if(!isset($helpicon))
	{
		include_once('default.skin');
	}

	$prefs_form_definitions=array
	(
		"hide_contribute_links"	=>	array
		(
			"type"				=>	'hidden',
			"choices"			=>	array("Don't show me contribute links"=>1,"I've contributed"=>2,"Remind me to chip in"=>0),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Show Contribute Links?',
		),

		"allow_analytics"		=>	array
		(
			"type"				=>	'hidden',
			"choices"			=>	array("Include my usage"=>1,"Hide my usage"=>0),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Collect Anonymous Usage Stats?',
	        "remarks"           =>  "<a target='_blank' href='/help/analytics'>what does this mean?</a>",
		),

		"beta_tester"			=>	array
		(
			"type"				=>	'hidden',
			"choices"			=>	array("nah, i'd rather not"=>0,"sure, i'll try stuff"=>1),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Test beta features?',
	        "remarks"           =>  "",
		),

		"timezone"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	$tzchoices,
			"default"			=>	'America/New-York',
			"public"			=>	TRUE,
			"formlabel"			=>	'What is your time zone?',
	        "sectiontitle"      =>  "Time",
		),

		"clock"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Normal 12 Hour"=>12,"Military 24 Hour"=>24,"Wacky Swatch .beats!"=>'b'),
			"default"			=>	12,
			"public"			=>	TRUE,
			"formlabel"			=>	'Clock Format',
		),

		"hatessmileys"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("yes"=>0,"replace with text"=>1,"DESTROY THEM!!!"=>2),
			"default"			=>	1,
			"public"			=>	TRUE,
			"formlabel"			=>	'Show graphical smileys?',
	        "remarks"           =>  "<a href='$_SERVER[WEB_ROOT]/help/smileys'><img src='$helpicon' border='0' /></a>",
	        "sectiontitle"      =>  "Things You Can Hide"
		),

		"strip_css"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("i love the beautiful mess!"=>0,"just keep it contained"=>2,"plain text for me please"=>1),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Control styles in plans you read?'
		),

		"hatespictures"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("yes"=>0,"no"=>1),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Show images in plans?',
	        "remarks"           =>  "",
	        "sectiontitle"      =>  ""
		),

		"no_slogans"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("yes"=>0,"no"=>1),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Show Slogans?',
			"spaceafter"		=>	TRUE
		),

		"wlpos"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Top of the page"=>0,"Bottom of the page"=>1,"Left side of the page"=>2,"Right side of the page"=>3),
			"default"			=>	3,
			"public"			=>	1,
			"formlabel"			=>	'Where do you want your watched list?',
	        "sectiontitle"      =>  "Snitch, Snoop, Watched List"
		),

		"fingerbox"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Nah"=>0,"Handy!"=>1),
			"default"			=>	0,
			"public"			=>	1,
			"formlabel"			=>	'Do you want a finger box in your watched list?'
		),

		"snitchlevel"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Off"=>1,"On"=>3),
			"default"			=>	3,
			"public"			=>	1,
			"formlabel"			=>	'How do you want your snitch?',
	        "remarks"           =>  "<a href='https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/help/snitch'><img src='$helpicon' border='0' /></a>",
			"onchange_js"		=>  "if (this.value==1) document.getElementById('snitch_default_days_line').style.display='none'; else document.getElementById('snitch_default_days_line').style.display='flex';",
			"onload_js"			=>  "snitchlevel=document.getElementById('snitchlevel'); if (snitchlevel.value > 1) document.getElementById('snitch_default_days_line').style.display='flex'; else document.getElementById('defaultdays_line').style.display='none';"
		),

		"snitch_default_days"	=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	2,
			"public"			=>	TRUE,
			"formlabel"			=>	'Days of Snitch to Show',
		),

		"showsnoop"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Watched List"=>1,"Snitch Page"=>0),
			"default"			=>	1,
			"public"			=>	1,
			"formlabel"			=>	'Where do you want to see your snoops?',
			"spaceafter"		=>	TRUE
		),

		"pushsend"				=>	array
		(
			"type"				=>	'hidden',
			"choices"			=>	array("No thanks"=>0,"Yeah, that sounds fun"=>1),
			"default"			=>	0,
			"public"			=>	1,
			"formlabel"			=>	'Do you want to get Prowl notices of sends?',
	        "remarks"           =>  "You'll need the <a href='http://click.linksynergy.com/fs-bin/click?id=tspFh8jh3l4&subid=&offerid=146261.1&type=10&tmpid=3909&RD_PARM1=http%3A%2F%2Fitunes.apple.com%2Fus%2Fapp%2Fprowl-growl-client%2Fid320876271%3Fmt%3D8%2526uo%3D4'>iPhone app</a> and a <a href='//$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/prefs/prowl'>Prowl key</a>",
	        "sectiontitle"      =>  "Send Notifications"
		),

		"emailsend"				=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("No thanks"=>0,"Yeah, that sounds fun"=>1),
			"default"			=>	0,
			"public"			=>	1,
			"formlabel"			=>	'Do you want to get email notices of sends?',
	        "remarks"           =>  "(replying to sends via email works too)",
		),

		"prefer_markdown"	=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Sounds like a plan"=>1,"HTML's good enough for me"=>0),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Prefer writing in Markdown?',
	        "remarks"           =>  "<a href='https://michelf.com/projects/php-markdown/concepts/'><img src='$helpicon' border='0' /></a>",
	        "sectiontitle"      =>  "Writing Options"
		),

		"displayloadtime"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("nah"=>0,"what the heck..."=>1),
			"default"			=>	0,
			"public"			=>	1,
			"formlabel"			=>	'Display Page Load times?',
			"sectiontitle"      =>  'Boring Admin Settings',
		),

	//SPECIAL FIELD
		"form_disposition"	=>	array
		(
			"storage_format"	=>	'file',
			"storage_directory"	=>	$_SERVER['USER_ROOT'],
			"storage_filename"	=>	"preferences.dat",
			"storage_clobber"	=>	TRUE
		)
	);

	$user_form_definitions=array
	(

		"username"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	'guest',
			"public"			=>	TRUE,
			"formlabel"			=>	'User Name',
			"sectiontitle"		=>	'Basic User Info'
		),

		"userpass"		=>	array
		(
			"type"				=>	'link',
			"choices"			=>	'',
			"default"			=>	"&nbsp; <a ref='/prefs/passphrase'>Change passphrase</a>",
			"public"			=>	TRUE,
			"formlabel"			=>	'Passphrase',
		),

		"real_name"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"formlabel"			=>	'Real (Full) Name'
		),

		"email"		=>	array
		(
			"type"				=>	'email',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"formlabel"			=>	'Email Address',
			"spaceafter"		=>	TRUE
		),

		"plantype"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("local"=>'@local',"planworld.net"=>'@planworld.net',"amherst.edu"=>"@amherst.edu","livejournal"=>"@livejournal","diaryland"=>"@DL","xanga"=>"@xanga","deadjournal"=>"@deadjournal","RSS or Atom Feed"=>'RSS'),
			"default"			=>	'@local',
			"public"			=>	TRUE,
			"formlabel"			=>	'Plan Server',
			"sectiontitle"		=>	'Plan Location',
			"onchange_js"		=>  "username_label=document.getElementById('planusername_label'); if (this.value=='RSS') { username_label.innerHTML = 'Feed URL'; } else { username_label.innerHTML = 'Username'; } if (this.value=='@local') { document.getElementById('planusername_line').style.display='none'; document.getElementById('archiveurl_line').style.display='none'; } else { document.getElementById('planusername_line').style.display='flex'; document.getElementById('archiveurl_line').style.display='flex'; }",
			"onload_js"			=>	"plantype=document.getElementById('plantype');\n username_label=document.getElementById('planusername_label');\n if (plantype.value=='RSS') { username_label.innerHTML = 'Feed URL'; }\n else { username_label.innerHTML = 'Username'; }\n if (plantype.value=='@local')\n { document.getElementById('planusername_line').style.display='none';\n document.getElementById('archiveurl_line').style.display='none'; } else { document.getElementById('planusername_line').style.display='flex'; document.getElementById('archiveurl_line').style.display='flex'; }\n"
		),

		"planusername"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"formlabel"			=>	'Username or Feed URL',
			"remarks"			=>	"",
		),

		"archiveurl"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"formlabel"			=>	'Archive URL (if not local)',
			"spaceafter"		=>	TRUE
		),

		"journaling"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Journaling (multiple posts)"=>1,"Traditional (one file)"=>0),
			"default"			=>	1,
			"public"			=>	TRUE,
			"formlabel"			=>	'Plan Type',
			"sectiontitle"		=>	"Plan Format <a href='$_SERVER[WEB_ROOT]/help/writing'><img src='$helpicon' border='0'/></a>",
			"onchange_js"		=>  "if (this.value==1) document.getElementById('defaultdays_line').style.display='flex'; else document.getElementById('defaultdays_line').style.display='none';",
			"onload_js"			=>  "journaling=document.getElementById('journaling'); if (journaling.value) document.getElementById('defaultdays_line').style.display='flex'; else document.getElementById('defaultdays_line').style.display='none';"
		),

		"defaultdays"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	2,
			"public"			=>	TRUE,
			"formlabel"			=>	'Days of Entries to Show',
			"spaceafter"		=>	TRUE
		),

		"privacy"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Everyone!"=>1,"Eh. Anyone."=>2,"Registered Users Only"=>3,"Only My Allowed List"=>4),
			"default"			=>	1,
			"public"			=>	TRUE,
			"formlabel"			=>	'Who should be able to read your plan?',
			"sectiontitle"		=>	'Plan Privacy',
	        "remarks"      =>  "<a href='https://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/help/privacy'><img src='$helpicon' border='0'/></a>"
		),

		"fingerpref"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Sure"=>1,"No way"=>0),
			"default"			=>	1,
			"public"			=>	TRUE,
			"formlabel"			=>	"How about readers from other sites?<br/>(<a href='http://planworld.net'>planworld.net</a> and <a href='http://www.amherst.edu'>Amherst</a>)",
		        "remarks"			=>  	"",
			"spaceafter"			=>	TRUE
		),

		"secretword"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"remarks"			=>  "<br />for posts by email, send to username.secretword@planwatch.mailgun.org",
			"formlabel"			=>	'Secret Word',
		),

		"secretfeedword"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"remarks"			=>  "<br />for private feeds, the url is https://planwatch.org/read/<em>secret_feed_key</em>/privatefeed",
			"formlabel"			=>	'Secret Feed Key',
		),

		"removefromtitles"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"remarks"			=>  "<br />this text will get clipped from your post titles in the feed",
			"formlabel"			=>	'Vestigial Text',
		),


		//SPECIAL FIELD
		"form_disposition"	=>	array
		(
			"storage_format"	=>	'file',
			"storage_directory"	=>	$_SERVER['USER_ROOT'],
			"storage_filename"	=>	"userinfo.dat",
			"storage_clobber"	=>	TRUE
		)
	);

	$pass_form_definitions=array
	(
		"oldpass"		=>	array
		(
			"type"				=>	'password',
			"choices"			=>	'',
			"default"			=>	"",
			"public"			=>	TRUE,
			"formlabel"			=>	'Old Passphrase',
		),

		"newpassA"		=>	array
		(
			"type"				=>	'password',
			"choices"			=>	'',
			"default"			=>	"",
			"public"			=>	TRUE,
			"formlabel"			=>	'New Passphrase',
			"remarks"			=>	'72 characters or fewer',
		),

		"newpassB"		=>	array
		(
			"type"				=>	'password',
			"choices"			=>	'',
			"default"			=>	"",
			"public"			=>	TRUE,
			"formlabel"			=>	'Again',
		),

		//SPECIAL FIELD
		"form_disposition"	=>	array
		(
			"storage_format"	=>	'hash',
			"storage_directory"	=>	$_SERVER['USER_ROOT'],
			"storage_filename"	=>	".hash.bc",
			"storage_clobber"	=>	TRUE
		)
	);

	$recover_form_definitions=array
	(
		"username_stored"		=>	array
		(
			"type"				=>	'hidden',
			"choices"			=>	'',
			"default"			=>	"$_SERVER[USER]",
			"public"			=>	TRUE,
			"formlabel"			=>	'',
		),

		"username"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	"",
			"public"			=>	TRUE,
			"formlabel"			=>	'Username',
		),

		"newpassA"		=>	array
		(
			"type"				=>	'password',
			"choices"			=>	'',
			"default"			=>	"",
			"public"			=>	TRUE,
			"formlabel"			=>	'New Passphrase',
			"remarks"			=>	'72 characters or fewer',
		),

		"newpassB"		=>	array
		(
			"type"				=>	'password',
			"choices"			=>	'',
			"default"			=>	"",
			"public"			=>	TRUE,
			"formlabel"			=>	'Again',
		),

		//SPECIAL FIELD
		"form_disposition"	=>	array
		(
			"storage_format"	=>	'hash',
			"storage_directory"	=>	$_SERVER['USER_ROOT'],
			"storage_filename"	=>	".hash.bc",
			"storage_clobber"	=>	TRUE
		)
	);
?>