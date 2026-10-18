<?php
/*
STYLESHEET.php

provides a CSS-compliant stylesheet based on user preferences
and passed values
*/
$_SERVER['STOPWATCH']['skin_begin']=array_sum(explode(' ',microtime()));

include('default.skin');				// reads default styles.

$styles_fn="$_SERVER[USER_ROOT]/styles.txt";		//reads user styles.
$colors_fn="$_SERVER[USER_ROOT]/colors.txt";		//reads user styles.
$skin_fn="$_SERVER[USER_ROOT]/skin.txt";		//reads user styles.

if (file_exists($styles_fn) && !file_exists($skin_fn) && !file_exists($colors_fn))
{
	parse_str(@file_get_contents($styles_fn),$parse_result);
	extract($parse_result);
	if ($skin) { $headbgimg=''; $extra_css=''; $skin_css=''; @include($skin); }
	$sitename=stripslashes($sitename);
}

parse_str(@file_get_contents("$_SERVER[USER_ROOT]/skin.txt"),$parse_result);		//reads user fonts
extract($parse_result);

if (isset($skin)) { $headbgimg=''; $extra_css=''; $skin_css=''; @include($skin); }

parse_str(@file_get_contents("$_SERVER[USER_ROOT]/colors.txt"),$parse_result);		//reads user fonts
extract($parse_result);

parse_str(@file_get_contents("$_SERVER[USER_ROOT]/fonts.txt"),$parse_result);		//reads user fonts
extract($parse_result);

eval(@file_get_contents("$_SERVER[USER_ROOT]/user_css.txt"));		//reads user css

if (isset($_GET['skin']) && $_GET['skin']!='normal') { $headbgimg=''; @include_once("$_GET[skin].skin"); }

$GLOBALS['pfsize_css'] = html_size_to_css_size($pfsize);
$GLOBALS['nfsize_css'] = html_size_to_css_size($nfsize);
$GLOBALS['sfsize_css'] = html_size_to_css_size($sfsize);
$GLOBALS['hfsize_css'] = html_size_to_css_size($hfsize);

if ($pfont) {$GLOBALS['pfonts']="$pfont,$pfonts";}
if ($hfont) {$GLOBALS['hfonts']="$hfont,$hfonts";}
if ($nfont) {$GLOBALS['nfonts']="$nfont,$nfonts";}
if ($sfont) {$GLOBALS['sfonts']="$sfont,$sfonts";}

if (isset($listsbgimg) && !empty(trim($listsbgimg))) {$listsbgimage="url($listsbgimg)";} else { $listsbgimage = ''; }
if (isset($headbgimg) && !empty(trim($headbgimg))) {$headbgimage="url($headbgimg)";} else { $headbgimage = ''; }
if (isset($planbgimg) && !empty(trim($planbgimg))) {$planbgimage="url($planbgimg)";} else { $planbgimage =''; $pagebgimage = '';}
if (isset($pagebgimage) && !empty(trim($pagebgimage)) && empty(trim($planbgimg))) {$pagebgimage="url($pagebgimage)";}
else { $pagebgimage =''; }

if (trim($headbgcolor)) { $menubox_text_color=$titletextcolor; $menubox_background_color=$headbgcolor; }
else { $menubox_background_color=$listsbgcolor; $menubox_text_color=$navtextcolor; }

if (isset($GLOBALS['pwlogo']))
{
	$logo_size=getimagesize($_SERVER['FILE_ROOT'].'/'.$GLOBALS['pwlogo']);
	$header_height = ($logo_size[1] + 20) . "px";
	$slogan_top = ($logo_size[1] + 10) . "px";
}
else
{
	$header_height = "75px";
	$slogan_top = "60px";
}

if (!$GLOBALS['headbgcolor'] && !$headbgimage) $GLOBALS['headbgcolor']='transparent';


	$base_sheet="
		*			   { box-sizing: border-box; }
		a              { text-decoration: none; color: $GLOBALS[linkcolor]; }
		a:hover        { text-decoration: underline; }
		body           { background: $GLOBALS[planbgcolor] $pagebgimage;
						 color: $GLOBALS[textcolor]; padding: 0px; margin: 0px;
						 font: $GLOBALS[pfsize_css] $GLOBALS[pfonts]; }
		textarea { font-family: $GLOBALS[pfonts]; font-size: $GLOBALS[pfsize_css]; }
		img            { border: none; }
		h1			   { margin: 0px; font-size: 2em; }


		.planwatch     { background: $GLOBALS[listsbgcolor] $listsbgimage;
						 list-style: none; padding-left: 0px; margin-left: 0px;
						 color: $GLOBALS[navtextcolor]; font: $GLOBALS[nfsize_css] $GLOBALS[nfonts];
						 padding: 4px; }
		.planwatch a   { color: $GLOBALS[navlinkcolor]; }
		.planwatch .listheader { margin-top: 10px; display: block; }
		.planwatch .listheader.nomobile { margin-top: 0px; }
		.planwatch .updatetime { font-size: 0.8em; }
		.planwatch li:hover a { text-decoration: underline; }
		#fingerbox { margin-bottom: 10px; }

		.listheader    { font-weight: bold; }

		#navbar         { z-index: 100; position: absolute; top: 0px; left: 0px; padding: 2px; list-style: none; margin-top: 0px; }
		.menubar li.menuicon { display: inline; font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; float: left; width: 75px; }
		.menubar li.menuicon a { color: $GLOBALS[titletextcolor]; }

		.content       { padding: 4px; overflow: visible; padding: 4px; }

		.nodesktop	   { display: none; }
		.header        { background: $GLOBALS[headbgcolor] $headbgimage;
						 font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; padding: 4px; height: $header_height; }

		#sitename      { position: absolute; top: 2px; right: 2px;
						 font: $GLOBALS[hfsize_css] $GLOBALS[hfonts]; color: $GLOBALS[titletextcolor]; }
		#slogan        { position: absolute; right: 0px; top: $slogan_top; font: $GLOBALS[sfsize_css] $GLOBALS[sfonts]; color: $GLOBALS[titletextcolor]; }

		.widebox       { margin: 10px; border: thin solid $GLOBALS[listsbgcolor];
						 font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; text-align: center; }
		.widebox a     { margin-right: 10px; }

		.linkbox       { float: right; width: 170px; margin-left: 20px; margin-top: 20px;
						 font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; }
		.linkbox a     { display: block; }

		.menubox { display: none; position: absolute; background: $GLOBALS[listsbgcolor] $listsbgimage;
				border: thin solid $GLOBALS[navtextcolor]; padding: 4px; font: $GLOBALS[nfsize_css] $GLOBALS[nfonts];
				color: $GLOBALS[navtextcolor]; }
		.menubox a { color: $GLOBALS[navlinkcolor]; }
		ul.menubox { list-style: none; padding-left: 0px; margin-left: 0px; }

		#navbar ul li i { display: block; font-size: smaller; opacity: 0.7; margin-bottom: 10px; }


		#reader_toolbar{ list-style: none; margin-left: 0px; padding-left: 0px;
						 font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; }
		#reader_toolbar li { display: inline; padding-left: 4px; padding-right: 4px;  border-right: 1px solid $GLOBALS[listsbgcolor]; }
		#reader_toolbar li.plan_data_block   { display: block; border: none; }

		.editLayer     { display: none; }

		.menubar { list-style: none; }
		.planwatch .menubar li ul li.listheader { margin: 0px; }
		.menubar li ul { display: none; border: thin solid $GLOBALS[titletextcolor]; margin-left: 0px; padding-left: 0px; padding: 2px; }
		.menubar li:hover ul { display: block; }
		.menubar li ul { background-color: $menubox_background_color; color: $menubox_text_color; width: 200px; z-index: 100; }
		.menubar li ul li { float: none; display: block; background-color: $menubox_background_color; color: $menubox_text_color; }
		.menubar li ul li a { color: $menubox_text_color; font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; }

		/* formatting toolbar for plan editing */
		/* TODO: simplify this css block (format_toolbar) */
		#format_toolbar { clear: both; padding: 0px; margin: 0px; background: $GLOBALS[listsbgcolor] $listsbgimage;  font-size: $GLOBALS[nfsize_css]; font-family: $GLOBALS[nfonts];}
		#format_toolbar li { display: inline; font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; }
		#format_toolbar_container { padding: 0px; margin: 0px; background: $GLOBALS[listsbgcolor] $listsbgimage; }
		#format_toolbar li:hover ul { display: none; }
		#format_toolbar li ul { position: absolute; }
		#format_toolbar li ul li { display: block; }
		#format_toolbar .menubutton { background: $GLOBALS[planbgcolor]; padding: 2px; font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; margin: 2px;}
		#format_toolbar input[type=\"button\"] { font: $GLOBALS[nfsize_css] $GLOBALS[nfonts]; background: $GLOBALS[listsbgcolor]; border: 1px solid $GLOBALS[listsbgcolor]; }
		#format_toolbar input[type=\"button\"]:hover { background: $GLOBALS[linkcolor]; color: $GLOBALS[planbgcolor]; border: 1px solid $GLOBALS[planbgcolor]; }


		/* error notice */
		#error_link     { position: fixed; bottom: 10px; right: 10px; z-index: 10; color: $listsbgcolor; background: $navtextcolor; border: thin solid $listsbgcolor; padding: 1px; }
		#error_report   { display: none; bottom: 1px; left: 1px; right: 80px; height: 50px; position: fixed; overflow: auto; background: $navtextcolor; color: $listsbgcolor; border-top: thick solid $listsbgcolor; font-size: $GLOBALS[nfsize_css]; font-family: $GLOBALS[nfonts]; }
		#error_report:first-line { font-size: 120%; font-weight: bold; }
		#error_report a { font-weight: normal; border: thin solid $listsbgcolor; padding: 2px; }

		/* debug popup - admin only */
		#debug_link     { position: fixed; bottom: 30px; right: 10px; z-index: 10; color: $navtextcolor; background: $listsbgcolor; border: thin solid $navtextcolor; padding: 1px; }
		#debug_report   { display: none; bottom: 1px; left: 1px; right: 80px; height: 50px; position: fixed; overflow: auto; background: $listsbgcolor; color: black; border-top: thick solid $navtextcolor; font-size: $GLOBALS[nfsize_css]; font-family: $GLOBALS[nfonts]; }
		#debug_report:first-line { font-size: 120%; font-weight: bold; }
		#debug_report a { font-weight: normal; border: thin solid $navtextcolor; padding: 2px; }

		.unread			{ font-weight: bold; }
		.read			{ font-weight: normal; }
		.entry_title	{ font-size: 150%; font-weight: bold; }
		.entry_content	{ font-family: $GLOBALS[pfonts]; font-size: $GLOBALS[pfsize_css]; }
		.plan_entry		{ margin-top: 15px; }
		#archives_calendar			{ width: 300px; height: 200px; border: 4px solid $listsbgcolor; }

		/* send */
		.send_from		{ font-weight: bold; background: $listsbgcolor; margin-bottom: 10px; float: right; width: 90%; clear: both !important; }
		.send_to		{ font-size: 90%; margin-bottom: 10px; float: left; width: 90%; clear: both !important; }
		.send_to p,.send_from p { margin: 0px; }
		#sendmessage   { height: 8em; }

		#hidden_response	{ display: none; }
		#message_bar		{ background: $listsbgcolor; color: $navtextcolor; }
		#message_bar a		{ color: $navlinkcolor; }

		/* lists */
		ul.peek_list { height: 200px; overflow: auto; width: 40%; float: left; list-style: none; padding-left: 0px; margin-left: 0px; margin-right: 20px; padding: 4px; line-height: 1.4em; }
		ul.peek_list li.listheader { font-size: larger; font-weight: bold; border-bottom: 1px solid $navtextcolor; }
		ul.flicklist { list-style: none; padding-left: 0px; margin: 0px; line-height: 1.25rem; }

		ul#insert { text-align: left; }

		/* standard textbox style */
		.textbox,#textbox { float: left; width: 80%; height: 25em; margin-right: 0px; margin-top: 7px; }
		.expanding_textbox { height: 50px; }
		.expanding_textbox:focus { height: 400px; }
		#writetitle, #writetags { width: 100%; }
		#attachlink { font-size: 16px; }

		.settingsbox    { float: right; width: 19%; margin: 0px; }
		.settingsbox div { border: thin solid $GLOBALS[listsbgcolor]; margin: 4px; font-size: 9px; font-family: sans-serif; padding: 2px; }
		.settingsbox div div { border: none; padding: 0px; margin: 0px; }
		.hometools { float: left; font-size: 0.8em; line-height: 1.4em; }

		/* buttons */
		.bigbutton { float: right; display: block; margin: 4px; border: 2px solid $GLOBALS[linkcolor]; background: $GLOBALS[linkcolor]; border-style: outset; color: $GLOBALS[planbgcolor]; font-size: 20px; border-radius: 4px; padding: 4px; }
		#submit_button { font-size: 20px; font-family: $GLOBALS[nfonts]; background: $GLOBALS[linkcolor]; color: $GLOBALS[planbgcolor]; padding: 2px; }
		#draft_button  { font-size: 12px; font-family: $GLOBALS[nfonts]; background: $GLOBALS[navlinkcolor]; color: $GLOBALS[listsbgcolor]; padding: 2px; }

		/* tips for inline editing tools (gear icons) */
		a.tool { float: right; font-size: 80%; padding: 1px; display: block; border-radius: 4px; }
		a.tool span.hidden { display: none; }
		a.tool:hover { text-decoration: none; background: $GLOBALS[linkcolor]; color: $GLOBALS[planbgcolor]; }
		a.tool:hover span.hidden { display: inline; }

		/* if the watched list is at the top, don't float the [x] */
		section#body.wlpos_0 .planwatch li.snoop a.tool	{ float: none; display: inline; padding: 0px; }

		/* new reader toolbar design */
		#reader_toolbar { background: $GLOBALS[listsbgcolor]; }
		#reader_toolbar li { margin-left: 2px; background: rgba(255,255,255,0.6); border-top: 1px solid #222; border-right: 1px solid #222; border-left: 1px solid #222; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px; border-top-left-radius: 5px; border-top-right-radius: 5px; padding: 2px; border-bottom: 0px; padding-bottom: 0px;}
		#reader_toolbar li.listheader { background: #fff; font-size: larger; border-bottom: 0px; }
		#reader_toolbar li.plan_data_block { border: 0px; background: #fff; -moz-border-radius-topleft: 0px; -moz-border-radius-topright: 0px; border-radius: 0px; margin-left: 0px; border-top: 3px solid $GLOBALS[planbgcolor]; padding-bottom: 0px; }
		#reader_toolbar li.action { border: 0px; border-left: 1px solid #ffe; background: transparent; padding-bottom: 0px; }
		#reader_toolbar li.action:hover { border: 1px solid #fff; color: #fff; background: #222; padding-bottom: 0px;}
		#reader_toolbar li.action:hover a { color: #fff; background: #222;padding-bottom: 0px;}

		/* new html5 elements to simplify plan layout */
		aside.sidebar { float: right; width: 200px; margin-left: 20px; }
		nav.toc { }

		section#body.wlpos_0 .planwatch li,section#body.wlpos_1 .planwatch li 			{  display: inline; margin-right: 1em; }
		section#body.wlpos_0 .planwatch li.listheader,section#body.wlpos_1 .planwatch li.listheader {  display: block; }
		section#body.wlpos_2 .planwatch		{ position: absolute; left: 4px; width: 170px; }
		section#body.wlpos_3 .planwatch 	{ position: absolute; right: 4px; width: 170px; }
		section#body.wlpos_2 .content		{ position: absolute; right: 4px; left: 195px; }
		section#body.wlpos_3 .content		{ position: absolute; right: 195px; left: 4px; }

		button,.button
		{
			background: radial-gradient(circle, rgba(63,94,251,0) 0%, rgba(0,0,0,0.25) 82%, rgba(0,0,0,0.5) 100%),linear-gradient(315deg, rgba(0,0,0,0.25) 0%, rgba(253,187,45,0) 100%);
			text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.4);
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 1px rgba(0, 0, 0, 0.2);
			border-radius: 4px;
			border: solid 1px #666;
			color: #ffe;
			font-family: var(--sans);
			font-size: var(--fs-500);
			font-weight: normal;
			padding: .5vh;
			text-decoration: none;
			cursor: pointer;
			white-space: nowrap;
			text-align: center;
			background-color: gray;
			flex: 0 1;
			text-decoration: none;
			margin: 0.5em;
		}

		.button.primary
		{
			//used in #modal_form
			color: white;
			background-color: #338;
			border-color: #20538D;
			font-size: 2.5vh;
		}

		.button.secondary
		{
			//used in #modal_form
			color: white;
			background-color: #655;
			border-color: #433;
			font-size: 1.5vh;
		}

		.button img
		{
			height: 1.5em;
			width: 1.5em;
			border: 0;
			vertical-align: middle;
		}
		";

	if (isset($skin_css))
	{
		$base_sheet.="\n$skin_css\n";
	}


	if (isset($extra_css))
	{
		$base_sheet.="\n$extra_css\n";
	}

	if($_SERVER['OUTPUT_MODE']=='MOBILE' || OUTPUT_MODE=='MOBILE')
	{
		$evaltext='$mobile_sheet="'.addslashes(file_get_contents("$_SERVER[FILE_ROOT]/resources/templates/iui3.css")).'";';
		eval($evaltext);
		$base_sheet.="\n\n$mobile_sheet";
	}


//}

$_SERVER['STOPWATCH']['skin_end']=array_sum(explode(' ',microtime()));



//$time_to_load=$_SERVER['STOPWATCH']['skin_end']-$_SERVER['STOPWATCH']['skin_begin'];
$base_sheet=str_replace(array('; ','{ ',' {',' }'),array(';','{','{','}'),str_replace(array("\r\n","\r","\n","\t",'  ','    ','    '),"",preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!','',$base_sheet)));

if($base_sheet) header("http/1.0 200 ok");

	$base_sheet=strip_whitespace($base_sheet);



if($_SERVER['cache_stylesheet']) { file_put_contents("$_SERVER[DOCUMENT_ROOT]/temp/".md5($_SERVER['USER']).time().".".OUTPUT_MODE.".styles.css",$base_sheet); file_put_contents("$_SERVER[DOCUMENT_ROOT]/temp/".md5($_SERVER['USER']).".".OUTPUT_MODE.".styles.css",$base_sheet); }
else { $base_sheet=gzencode($base_sheet); Header("Content-type: text/css"); header("Content-Encoding: gzip"); echo $base_sheet; exit; }//."\n/* ".round($time_to_load,ceil(0 - log10($time_to_load)) + 3)." seconds to load */\n";;

?>