<?php
/*
EDITSTYLES.PHP -- part of the planwatch library

allows editing of custom colors and styles for planwatch.org


the styles file is stored in each user's directory
(if he/she has custom styles) as styles.txt with the
following variable definitions (as url-variables to be processed
with parse_str() )

colors are HTML hex colors or the standard accepted word color names.
these are passed without editing to the HTML template.

$textcolor -- color of regular text on page
$titletextcolor -- color of top text (sitename, slogan)

$linkcolor -- basic link color, as per <BODY> tag
$alinkcolor -- active link color, as per <BODY> tag
$vlinkcolor -- visited link color, as per <BODY> tag
$listlinkcolor -- color of links to other plans (in navbar)

$pagebgcolor -- bgcolor attribute of <BODY>
$listsbgcolor -- bgcolor attribute of the watched, registered, and advertised tables
$headbgcolor -- bgcolor attribute of the table cells which have the basic buttons, slogan, and sitename
$planbgcolor -- bgcolor attribute of the table cell with the plan contents

$nfont,$nfsize -- font and size for advertised, watched, registered nav lists
$pfont,$pfsize -- base page font and size
$hfont,$hfsize -- font and size for site name
$sfont,$sfsize -- font and size for slogan

$extra_css -- any other css stuff the user wants to enter by hand

FUNCTIONS:
edit_styles() - form
styles_write() - write to disk
*/



// skin_create
//
// presents a form to turn custom styles into a skin
//------------------------------------------------------------------------------
function skin_create()
{
	if ($_SERVER['USER'])
	{
		$content.="<h1>Create A Planwatch Skin</h1> to make your custom colors, fonts, and images into a skin for others to use<br/>
				<form name='makeskin' action='$_SERVER[WEB_ROOT]/scripts/editstyles.php'>
				<input type='hidden' name='action' value='skin_create_write'/>
				Skin Name: <input type='text' name='skinname' value='enter skin name here'/>
				<input type='submit' name='submit' value='Create New Skin'/>
				</form>";
	}
	else $content="only logged in users can create skins";
	
output('create a skin',$content,'',' creating a planwatch.org skin');
}




// skin_select
//
// presents a form to choose a skin
//------------------------------------------------------------------------------
function skin_select($output=1)
{
	if ($_SERVER['USER'])
	{
		$styles_fn="$_SERVER[USER_ROOT]/styles.txt";
		$styles_fn="$_SERVER[USER_ROOT]/skin.txt";
		if (file_exists($styles_fn) && !file_exists($skin_fn))
		{
			parse_str(file_get_contents($styles_fn));
			if ($skin) @include($skin);
		}
		else
		{
			parse_str(file_get_contents($skin_fn));
			if ($skin) { include($skin); }
		}

		if(is_array($skinlist=files_list("$_SERVER[FILE_ROOT]/resources/skins","*.skin")))
		{
			$stylesheet_select="<select name='skin' style='width: 200px;'\">
			<option value='Standard'>Current settings</option>\n";
			foreach($skinlist as $skin)
			{
				$skin=str_replace('.skin','',basename($skin));
				$stylesheets.="<link rel='alternate stylesheet' type='text/css' href='/stylesheet/$skin.css' title='$skin' />\n";

				$prettyskin=str_replace('_',' ',str_replace('-',' ',$skin));
				$prettyskin=str_replace('  ',"'",str_replace('..','(',str_replace("...",")",$prettyskin)));
				$prettyskin=str_replace('(by'," <span class='edit_links'> (by",$prettyskin);
				$prettyskin=str_replace(')',")</span>",$prettyskin);

				$stylesheet_select.="<option value='$skin'>$prettyskin</option>\n";
			}
			$stylesheet_select.="</select>\n";
		}
		
		$wrapper.="
		<form name='skin' action='$_SERVER[WEB_ROOT]/scripts/editstyles.php'>
	    <h1><img src='$GLOBALS[toolsicon]' /> Choose A Planwatch Skin</h1>
	    <i>change all your colors and fonts to a preset style</i><br/>
		<input type='hidden' name='action' value='skin'/>\n";

		$content.=$stylesheet_select;
		$content.="<br /><input type='checkbox' CHECKED name='keep_fonts' value='1' /> Keep current font settings";
		$content.="<br /><input type='checkbox' CHECKED name='keep_colors' value='1' /> Keep current color settings";
		$content.="<br /><input type='checkbox' CHECKED name='keep_css' value='1' /> Keep custom css settings";
		
		
		$wrapper.="$content\n<br/><br/><input type='submit' value='select skin'/>\n</form>";
	}
	else $wrapper="only logged in users can select skins";
	
if ($output==1) output('choose a skin',"<div>$wrapper</div>",'',' choosing a planwatch.org skin');
else return $content;
}



// STYLES_CSS_EDIT
//
// presents a textarea for creating custom CSS
//------------------------------------------------------------------------------
function styles_css_edit()
{
	if (file_exists("$_SERVER[USER_ROOT]/user_css.txt"))
		eval(str_replace('$','\$',file_get_contents("$_SERVER[USER_ROOT]/user_css.txt")));
		
	$content="

		<h1>Custom CSS</h1>
		<form name='css_form' action='$_SERVER[WEB_ROOT]/scripts/form_shim.php' method='post'>
		<input type='hidden' name='action' value='write_css'/>
		<textarea name='css_data' id='textbox'>$extra_css</textarea>
		<input type='submit' id='submit_button' value='Save Changes' accesskey='x' />
<br /><br /><a target='_blank' href='/help/formatting'>CSS formatting help</a> <br /><br /> <a href='/help/lynneformatting' target='_blank'>Lynne's CSS tips</a><br />
		</form>

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

return $content;
}






// STYLES_FONTS_EDIT
//
// presents a form to edit user styles
//------------------------------------------------------------------------------
function styles_fonts_edit()
{
	$font_array=array('nfont','pfont','sfont','hfont');
	$fontdefault_array=array($GLOBALS['nfonts'],$GLOBALS['pfonts'],$GLOBALS['sfonts'],$GLOBALS['hfonts']);
	$size_array=array('nfsize','pfsize','sfsize','hfsize');

	parse_str(@file_get_contents("$_SERVER[USER_ROOT]/fonts.txt"));

	$content.="<h2><img src='$GLOBALS[prefsicon]' /> customize fonts</h2>\n\n";

	$content.="<h3>fonts</h3>
	The fonts you select must be on your computer, and you have to enter their names as they appear in your font menu (though you can 
	ignore caps). If you view planwatch.org from a computer that doesn't have the fonts you specify, it will use the regular set instead.
	<br/><br/>\n\n
	<form action='$_SERVER[WEB_ROOT]/scripts/editstyles.php' method='post'>\n\n";

	foreach($font_array as $i=>$font)	//takes care of the fonts
	{
		$fontname=str_replace('font',' custom font',$font);
		$fontname=str_replace('n ','planlist ',$fontname);
		$fontname=str_replace('p ','base page ',$fontname);
		$fontname=str_replace('h ','header ',$fontname);
		$fontname=str_replace('s ','slogan ',$fontname);

		$content.="	
						$fontname: <input type='text' name='$font' value='".stripslashes($GLOBALS[$font])."' size='40'/><br/>
						default: <span style='font-family: $fontdefault_array[$i]'>$fontdefault_array[$i]</span><br/><br/>\n";
	}

	$content.="<h3>font sizes</h3>
								font sizes are specified in points (12pt, 15pt, etc.) or
								a number 1 (smallest) to 7 (largest)<br/><br/>\n";

	foreach($size_array as $i=>$size)	//takes care of the font sizes
	{
		$sizename=str_replace('size',' size',$size);
		$sizename=str_replace('nf ','planlist font ',$sizename);
		$sizename=str_replace('pf ','base page font ',$sizename);
		$sizename=str_replace('hf ','header font ',$sizename);
		$sizename=str_replace('sf ','slogan font ',$sizename);

		$content.="	$sizename: <input type='text' name='$size' value='".stripslashes($GLOBALS[$size])."' size='11'/><br/>\n";
	}

		$content.="<input type='submit' name='submit' value='update fonts'/>
		<input type='hidden' name='action' value='styles_fonts_write'/>
		</form>\n";

return $content;
}




// STYLES_FONTS_WRITE
//
// writes custom styles to disk
//------------------------------------------------------------------------------
function styles_fonts_write()
{	
	$styles_fn="$_SERVER[USER_ROOT]/fonts.txt";

	foreach($_POST as $i=>$postitem)
		$_POST[$i]=stripslashes($_POST[$i]);

	$styles_data="nfont=$_POST[nfont]&sfont=$_POST[sfont]&hfont=$_POST[hfont]&pfont=$_POST[pfont]&nfsize=$_POST[nfsize]&sfsize=$_POST[sfsize]&hfsize=$_POST[hfsize]&pfsize=$_POST[pfsize]";

	file_put_contents($styles_fn,$styles_data);

redirect("/tools");
}



// styles_write
//
// writes custom styles to disk
//------------------------------------------------------------------------------
function styles_write()
{
	$styles_fn="$_SERVER[USER_ROOT]/colors.txt";
	
	foreach($_POST as $i=>$postitem)
		$_POST[$i]=stripslashes($_POST[$i]);

	unlink("$_SERVER[USER_ROOT]/styles.txt");

	$styles_data="pagebgimage=$_POST[pagebgimage]&listsbgimg=$_POST[listsbgimg]&planbgimg=$_POST[planbgimg]&headbgimg=$_POST[headbgimg]&pwlogo=$_POST[pwlogo]&writeicon=$_POST[writeicon]&toolsicon=$_POST[toolsicon]&snitchicon=$_POST[snitchicon]&prefsicon=$_POST[prefsicon]&helpicon=$_POST[helpicon]&logouticon=$_POST[logouticon]&navtextcolor=$_POST[navtextcolor]&textcolor=$_POST[textcolor]&linkcolor=$_POST[linkcolor]&navlinkcolor=$_POST[navlinkcolor]&vlinkcolor=$_POST[vlinkcolor]&alinkcolor=$_POST[alinkcolor]&planlinkcolor=$_POST[planlinkcolor]&pagebgcolor=$_POST[pagebgcolor]&listsbgcolor=$_POST[listsbgcolor]&headbgcolor=$_POST[headbgcolor]&planbgcolor=$_POST[planbgcolor]&titletextcolor=$_POST[titletextcolor]&nfont=$_POST[nfont]&sfont=$_POST[sfont]&hfont=$_POST[hfont]&pfont=$_POST[pfont]&nfsize=$_POST[nfsize]&sfsize=$_POST[sfsize]&hfsize=$_POST[hfsize]&pfsize=$_POST[pfsize]&sitename=$_POST[sitename]&viewicon=$_POST[viewicon]";

	$styles_file=fopen($styles_fn,'w');
	fwrite($styles_file,$styles_data);
	fclose($styles_file);
redirect("/tools");
}




// skin_write
//
// writes skin selection to disk
//------------------------------------------------------------------------------
function skin_write($skin,$return=0,$username='',$password='')
{
	$styles_fn="$_SERVER[USER_ROOT]/skin.txt";
 
	if (!strstr($skin,'.skin')) $skin.=".skin";
	$styles_data="skin=$skin";

	file_put_contents($styles_fn,$styles_data);
	unlink("$_SERVER[USER_ROOT]/styles.txt");

	$fonts_fn="$_SERVER[USER_ROOT]/fonts.txt";
	$colors_fn="$_SERVER[USER_ROOT]/colors.txt";
	$css_fn="$_SERVER[USER_ROOT]/user_css.txt";
	if (!$_POST['keep_fonts'] && !$_GET['keep_fonts'] && !strstr($_SERVER['REQUEST_URI'],'apply') && file_exists($fonts_fn)) unlink($fonts_fn);
	if (!$_POST['keep_colors'] && !$_GET['keep_colors'] && !strstr($_SERVER['REQUEST_URI'],'apply') && file_exists($colors_fn)) unlink($colors_fn);
	if (!$_POST['keep_css'] && !$_GET['keep_css'] && !strstr($_SERVER['REQUEST_URI'],'apply') && file_exists($css_fn)) unlink($css_fn);

if (!$return) redirect("/prefs/skin");
else redirect("/");
}



// skin_create_write
//
// writes new skin to disk
//------------------------------------------------------------------------------
function skin_create_write($skinname)
{
	$skinname=str_replace(array(" ","'",'"'),"_",$skinname)."_..by_$user....skin";

	$daytime=date("d M Y, h:i:s a");
	$styles_data.=file_get_contents("$_SERVER[USER_ROOT]/styles.txt");
	$styles_data=file_get_contents("$_SERVER[USER_ROOT]/skin.txt");
	$styles_data=file_get_contents("$_SERVER[USER_ROOT]/colors.txt");
	$styles_data.=file_get_contents("$_SERVER[USER_ROOT]/fonts.txt");
	$styles_data=str_replace("font","fonts",$styles_data);
	$styles_data=str_replace("fontss","fonts",$styles_data);
	$styles_data.=file_get_contents("$_SERVER[USER_ROOT]/user_css.txt");
	$styles_data="<"."?"."php\n/*\n".strtoupper($skinname)."\nthis skin was automatically created on $daytime GMT\nfrom $user's color and font prefs\n*/\n\n$".str_replace("=","=\"",str_replace("&","\";\n$",$styles_data)).'";'."\n?".'>';

	$newskin_fn="$_SERVER[FILE_ROOT]/resources/skins/$skinname";
	$newskin_file=fopen($newskin_fn,'w');
	fwrite($newskin_file,$styles_data);
	fclose($newskin_file);

	exec("chmod 777 $newskin_fn");

redirect("/prefs/skin");
}



/****************************************************
EDIT_STYLES

presents a form to edit user styles
****************************************************/
function styles_colors_edit()
{
	if ($_SERVER['USER'])
	{
		$skin_fn="$_SERVER[USER_ROOT]/skin.txt";
		$colors_fn="$_SERVER[USER_ROOT]/colors.txt";
		$styles_fn="$_SERVER[USER_ROOT]/styles.txt";
		
		include('default.skin');
		if (file_exists($styles_fn) && !file_exists($colors_fn) && !file_exists($skin_fn))
		{
			parse_str(file_get_contents($styles_fn));
			if ($skin && file_exists("$_SERVER[FILE_ROOT]/resources/skins/$skin")) { include($skin); }
			if ($planlinkcolor) $navlinkcolor=$planlinkcolor;
		}
	
		if (file_exists($skin_fn))
		{
			parse_str(file_get_contents($skin_fn));
			if ($skin) { include($skin); }
		}
		if (file_exists($colors_fn))
		{
			parse_str(file_get_contents($colors_fn));
		}

		$bgcolor_array=array('pagebgcolor','planbgcolor','headbgcolor','listsbgcolor');
		$linkcolor_array=array('linkcolor','alinkcolor','vlinkcolor','navlinkcolor');
		$textcolor_array=array('textcolor','titletextcolor','navtextcolor');
		$font_array=array('nfont','pfont','sfont','hfont');
		$fontdefault_array=array($nfonts,$pfonts,$sfonts,$hfonts);
		$size_array=array('nfsize','pfsize','sfsize','hfsize');
		$image_array=array('pagebgimage','planbgimg','headbgimg','listsbgimg');
		$icon_array=array('pwlogo','snitchicon','writeicon','toolsicon','helpicon','prefsicon','logouticon','viewicon');

		$content.="
		<h1>Customize Your Planwatch Colors</h1>
		<b>your custom colors:</b> [ <a href='/help/color' target='_blank'>color help</a> ]<br/>
		<form action='/scripts/editstyles.php' method=POST>

		legal English colors:<br/>
	<font color='aqua'>aqua</font>
	 &nbsp; <font color='black'>black</font>
	 &nbsp; <font color='blue'>blue</font>
	 &nbsp; <font color='fuchsia'>fuchsia</font>
	 &nbsp; <font color='gray'>gray</font>
	 &nbsp; <font color='green'>green</font>
	 &nbsp; <font color='lime'>lime</font>
<br/><font color='maroon'>maroon</font>
	 &nbsp; <font color='navy'>navy</font>
	 &nbsp; <font color='olive'>olive</font>
	 &nbsp; <font color='purple'>purple</font>
	 &nbsp; <font color='red'>red</font>
	 &nbsp; <font color='silver'>silver</font>
	 &nbsp; <font color='teal'>teal</font>
	 &nbsp; <font color='white'>white</font>
	 &nbsp; <font color='yellow'>yellow</font>
	<br/><br/>
	site name: <input type='text' size='20' value=\"".stripslashes($sitename)."\" name='sitename'>
";
		$contentcode='$content.="<table border=\'0\' width=\'100\'>';
		
		$contentcode.="<tr><td colspan='6'><br/><br/><font size='+1'><b>background colors</b></font><br/></td></tr>";
		
		foreach($bgcolor_array as $color)	//takes care of the background color specifiers
		{
			$colorname=str_replace('bg',' background ',$color);
			$contentcode.="<tr><td><nobr>$colorname:</nobr></td>";
			$contentcode.="<td><input type='text' name='$color' value='$$color' size='11'></td>";
			$contentcode.="<td>sample: </td><td bgcolor='$$color'>&nbsp;</td></tr>";
		}

		$contentcode.="<tr><td colspan='6'><br/><br/><font size='+1'><b>background images</b></font><br/></td></tr>";
		foreach($image_array as $image)	//takes care of the images
		{
			$imagename=str_replace('bg',' background ',str_replace('img','image',$image));
			$contentcode.="<tr><td><nobr>$imagename:</nobr></td><td colspan='5'> <input type='text' name='$image' value='".stripslashes($$image)."' size='50'></td></tr>";
		}

		$contentcode.="<tr><td colspan='6'><br/><br/><font size='+1'><b>link colors</b></font><br/></td></tr>";
		foreach($linkcolor_array as $color)	//takes care of the link colors
		{
			$colorname=str_replace('link',' link ',$color);
			$contentcode.="	<tr><td>$colorname:</td><td> <input type='text' name='$color' value='".stripslashes($$color)."' size='11'></td>
						<td>sample: </td>
						<td bgcolor='$planbgcolor' class='bodytest'>
							<font color='$$color'>text</font>
						</td>
						<td bgcolor='$headbgcolor'>
							<font color='$$color'>text</font>
						</td>
						<td bgcolor='$listsbgcolor'>
							<font color='$$color'>text</font>
						</td>
					</tr>
					";
		}

		$contentcode.="<tr><td colspan='6'><br/><br/><font size='+1'><b>text colors</b></font><br/></td></tr>";
		foreach($textcolor_array as $color)	//takes care of the text colors
		{
			$colorname=str_replace('text',' text ',$color);
			$contentcode.="	<tr><td>$colorname:</td><td> <input type='text' name='$color' value='".stripslashes($$color)."' size='11'></td>
						<td>sample: </td>
						<td bgcolor='$planbgcolor'>
							<font color='$$color'>text</font>
						</td>
						<td bgcolor='$headbgcolor'>
							<font color='$$color'>text</font>
						</td>
						<td bgcolor='$listsbgcolor'>
							<font color='$$color'>text</font>
						</td>
					</tr>
					";
		}
		
$contentcode.="<!--\n";
		$contentcode.="<tr><td colspan='5'><br/><br/><font size='+1'><b>fonts</b></font><br/></td></tr>";

		$contentcode.="<tr><td colspan='5'>
		<br/><font size='-1'>
The fonts you select must be on your computer, and you have to enter their names as they appear in your font menu (though you can 
ignore caps). If you view planwatch.org from a computer that doesn't have the fonts you specify, it will use the regular set instead.
</font><br</td></tr>
";

		foreach($font_array as $i=>$font)	//takes care of the fonts
		{
			$fontname=str_replace('font',' custom font',$font);
			$fontname=str_replace('n ','planlist ',$fontname);
			$fontname=str_replace('p ','base page ',$fontname);
			$fontname=str_replace('h ','header ',$fontname);
			$fontname=str_replace('s ','slogan ',$fontname);

			$contentcode.="	<tr><td>
							$fontname:</td><td> <input type='text' name='$font' value='".stripslashes($$font)."' size='11'>
								default: </td>
								<td colspan='4'>
								<font face='$fontdefault_array[$i]'>$fontdefault_array[$i]</font>
								</td>
										</tr>
					";
		}

		$contentcode.="<tr><td>&nbsp;</td></tr>";
		$contentcode.="<tr><td colspan='5'><br/><br/><font size='+1'><b>font sizes</b></font><br/></td></tr>";

		$contentcode.="	<tr><td colspan='5'>
									<p><br/>
									font sizes are specified from 1 to 7, 1 being the smallest
									<p>
								</td>
							</tr>";

		foreach($size_array as $i=>$size)	//takes care of the font sizes
		{
			$sizename=str_replace('size',' size',$size);
			$sizename=str_replace('nf ','planlist font ',$sizename);
			$sizename=str_replace('pf ','base page font ',$sizename);
			$sizename=str_replace('hf ','header font ',$sizename);
			$sizename=str_replace('sf ','slogan font ',$sizename);

			$contentcode.="	<tr><td>
							$sizename:</td><td> <input type='text' name='$size' value='".stripslashes($$size)."' size='11'>
								</td>
										</tr>
					";
		}

$contentcode.="-->\n";

		$contentcode.="<tr><td colspan='6'><br/><br/><font size='+1'><b>header/toolbar icons</b></font><br/></td></tr>";
		foreach($icon_array as $icon)	//takes care of the icons
		{
			$iconname=str_replace('icon',' icon',$icon);
			$contentcode.="<tr><td>$iconname:</td><td colspan='5'> <input type='text' name='$icon' value='".stripslashes($$icon)."' size='50'></td></tr>";
		}

		$contentcode.='</table>";';
		
		eval($contentcode);
		
		$content.="<div align='center'>
		<input type='submit' name='submit' value='update styles'>
		<input type='hidden' name='action' value='write'>
		</div>
		</form>

		";

	}
	else $content='you must log in to set custom styles';

output('edit custom styles',$content,'',' editing custom styles');
}






if ($_POST['action']=='styles_fonts_write') styles_fonts_write();
if ($_POST['action']=='write') styles_write();
if ($_POST['action']=='skin_create_write') skin_create_write($_POST['skinname']);
if ($_GET['action']=='skin' && $_GET['remskin']) { skin_write($_GET['remskin'],$_GET['return']); exit; }
if ($_GET['action']=='skin' && $_GET['skin']) { skin_write($_GET['skin'],$_GET['return']); exit; }




?>
