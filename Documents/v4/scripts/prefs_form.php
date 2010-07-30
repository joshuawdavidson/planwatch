<?php
/*
PREFS_FORMS.php

the array defs for the prefs form
relies on gravity_forms.php and gravity_filestore.php

*/
//			"sectiontitle"		=>	header of form section, printed before the field
//			"spaceafter"		=>	bool - do we print a blank line after this field?
//			"ruleafter"		=>	bool - do we print a horizontal rule after this field?


	$tzdata=file("$_SERVER[FILE_ROOT]/resources/zone.tab");
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

	$prefs_form_definitions=array
	(
		"hide_contribute_links"	=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Don't show me contribute links"=>1,"I've contributed"=>2,"Remind me to chip in"=>0),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Show Contribute Links?',
			"sectiontitle"		=>	'Helping Out'
		),

		"allow_analytics"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Include my usage"=>1,"Hide my usage"=>0),
			"default"			=>	1,
			"public"			=>	TRUE,
			"formlabel"			=>	'Collect Anonymous Usage Stats?',
	        "remarks"           =>  "<a target='_blank' href='/help/analytics'>what does this mean?</a>",
		),

		"html5_template"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("nah, i'd rather not"=>0,"sure, i'll test it"=>1),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Test the HTML5 template?',
	        "remarks"           =>  "ideally you won't notice any difference",
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
	        "remarks"           =>  "<a href='$_SERVER[WEB_ROOT]/help/smileys'><img src='$GLOBALS[helpicon]' border='0' /></a>",
	        "sectiontitle"      =>  "Things You Can Hide"
		),

		"strip_css"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("i love the beautiful mess!"=>0,"just keep it contained"=>2,"plain text for me please"=>1),
			"default"			=>	0,
			"public"			=>	TRUE,
			"formlabel"			=>	'Control styles in plans you read?',
//	        "remarks"           =>  "\"Plain text\" will remove as much CSS as we can find. \"Keep it Contained\" is experimental."
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
			"choices"			=>	array("Top of the page"=>0,"Left side of the page"=>2,"Right side of the page"=>3),
			"default"			=>	0,
			"public"			=>	1,
			"formlabel"			=>	'Where do you want your watched list?',
	        "sectiontitle"      =>  "Snitch, Snoop, Watched List"
		),

		"snitchlevel"		=>	array
		(
			"type"				=>	'menu',
			"choices"			=>	array("Off"=>1,"Limited"=>2,"Full"=>3),
			"default"			=>	3,
			"public"			=>	1,
			"formlabel"			=>	'How do you want your snitch?',
	        "remarks"           =>  "<a href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/help/snitch'><img src='$GLOBALS[helpicon]' border='0' /></a>",
			"onchange_js"		=>  "if (this.value==1) document.getElementById('snitch_default_days_line').style.display='none'; else document.getElementById('snitch_default_days_line').style.display='block';",
			"onload_js"			=>  "snitchlevel=document.getElementById('snitchlevel'); if (snitchlevel.value > 1) document.getElementById('snitch_default_days_line').style.display='block'; else document.getElementById('defaultdays_line').style.display='none';"
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
			"default"			=>	'',
			"public"			=>	TRUE,
			"formlabel"			=>	'User Name',
			"sectiontitle"		=>	'Basic User Info'
		),

		"userpass"		=>	array
		(
			"type"				=>	'password',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"formlabel"			=>	'Password',
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
			"onchange_js"		=>  "username_label=document.getElementById('planusername_label'); if (this.value=='RSS') { username_label.innerHTML = 'Feed URL'; } else { username_label.innerHTML = 'Username'; } if (this.value=='@local') { document.getElementById('planusername_line').style.display='none'; document.getElementById('archiveurl_line').style.display='none'; } else { document.getElementById('planusername_line').style.display='block'; document.getElementById('archiveurl_line').style.display='block'; }",
			"onload_js"			=>	"plantype=document.getElementById('plantype');\n username_label=document.getElementById('planusername_label');\n if (plantype.value=='RSS') { username_label.innerHTML = 'Feed URL'; }\n else { username_label.innerHTML = 'Username'; }\n if (plantype.value=='@local')\n { document.getElementById('planusername_line').style.display='none';\n document.getElementById('archiveurl_line').style.display='none'; } else { document.getElementById('planusername_line').style.display='block'; document.getElementById('archiveurl_line').style.display='block'; }\n"
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
			"sectiontitle"		=>	"Plan Format <a href='$_SERVER[WEB_ROOT]/help/writing'><img src='$GLOBALS[helpicon]' border='0'/></a>",
			"onchange_js"		=>  "if (this.value==1) document.getElementById('defaultdays_line').style.display='block'; else document.getElementById('defaultdays_line').style.display='none';",
			"onload_js"			=>  "journaling=document.getElementById('journaling'); if (journaling.value) document.getElementById('defaultdays_line').style.display='block'; else document.getElementById('defaultdays_line').style.display='none';"
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
	        "remarks"      =>  "<a href='http://$_SERVER[HTTP_HOST]$_SERVER[WEB_ROOT]/help/privacy'><img src='$GLOBALS[helpicon]' border='0'/></a>"
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
			"remarks"			=>  "<br />for posts by email, send to username.secretword@post.planwatch.org",
			"formlabel"			=>	'Secret Word',
		),

		"secretfeedword"		=>	array
		(
			"type"				=>	'text',
			"choices"			=>	'',
			"default"			=>	'',
			"public"			=>	TRUE,
			"remarks"			=>  "<br />for private feeds, the url is http://planwatch.org/read/<em>secret_feed_key</em>/privatefeed",
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
?>