<?php

/*
slogan_functions.php

various functions for dealing with slogans.
*/


// The slogans filename, used in all slogan functions. Here so I only have to
// change it once if I want/need to.
$GLOBALS['SLOGAN_FILENAME'] = "$_SERVER[FILE_ROOT]/resources/.slogans.txt";

// SLOGANS_FORMAT_LIST_HTML()
//
// displays a passed slogan list in a sortable html table
//------------------------------------------------------------------------------
// TODO:(v4.1) make a generic md_array > sortable table function

function slogans_format_list_html($sloganlist=FALSE,$sortby='rage',$type='list')
{
	foreach($sloganlist as $i=>$slogan)
	{
		$slogan_a=explode(';;;',$slogan);
		if (!isset($slogan_a[1])) $slogan_a[1]=2;
		$slogan_pop[$i]=$slogan_a[1];
		$slogan_text[$i]=stripslashes(stripslashes($slogan_a[0]));
		$slogan_owner[$i]=$slogan_a[2];
		$slogan_index[$i]=$i;
	}

	if ($sortby=='pop') array_multisort($slogan_pop,SORT_DESC,$slogan_owner,$slogan_text,$slogan_index);
	if ($sortby=='unpop') array_multisort($slogan_pop,SORT_ASC,$slogan_owner,$slogan_text,$slogan_index);
	if ($sortby=='owner') array_multisort($slogan_owner,SORT_ASC,$slogan_index,$slogan_pop,$slogan_text);
	if ($sortby=='rowner') array_multisort($slogan_owner,SORT_DESC,$slogan_index,$slogan_pop,$slogan_text);
	if ($sortby=='alpha') array_multisort($slogan_text,SORT_ASC,$slogan_owner,$slogan_pop,$slogan_index);
	if ($sortby=='ralpha') array_multisort($slogan_text,SORT_DESC,$slogan_owner,$slogan_pop,$slogan_index);
	if ($sortby=='rage') array_multisort($slogan_index,SORT_DESC,$slogan_owner,$slogan_pop,$slogan_text);

	foreach($slogan_pop as $pop) { if ($pop > 0) $totalpop+=$pop; }

	foreach($slogan_index as $i=>$index)
	{
		if ($slogan_owner[$i]==$_SERVER['USER']) $edit="<a href='$_SERVER[WEB_ROOT]/slogans/$index'>edit</a><br /><a href='$_SERVER[WEB_ROOT]/slogans/delete/$index'>delete</a>"; else $edit='';
		if ($totalpop > 0) $chance=(floor(10000*$slogan_pop[$i]/$totalpop)/100); else $chance="0";

		$thelist.="\n\t\t<tr><td colspan='5'><hr NOSHADE></td></tr>";
		$thelist.="\n\t\t<tr>";
		$thelist.="\n\t\t\t<td><font size='2' face='trebuchet ms'>$slogan_text[$i]</td>";
		$thelist.="\n\t\t\t<td><font size='1' face='trebuchet ms,helvetica,arial,sans-serif'>$slogan_pop[$i] [ <a href='$_SERVER[WEB_ROOT]/slogans/mod/$index/1'>+</a> <a href='$_SERVER[WEB_ROOT]/slogans/mod/$index/-1'>-</a> ]</font></td>";
		$thelist.="\n\t\t\t<td><font size='1' face='trebuchet ms,helvetica,arial,sans-serif'>$slogan_owner[$i]</font></td>";
		$thelist.="\n\t\t\t<td><font size='1' face='trebuchet ms,helvetica,arial,sans-serif'>$edit</font></td>";
		$thelist.="\n\t\t\t<td><font size='1' face='trebuchet ms,helvetica,arial,sans-serif'>$chance %</font></td>";
		$thelist.="\n\t\t</tr>";
	}

	$spacer="<tr><td colspan='5'><hr NOSHADE></td></tr>";
	$resort="<tr><th colspan='5'>sort by [ popularity: <a href='$_SERVER[WEB_ROOT]/slogans/$type/pop'><img src='$_SERVER[WEB_ROOT]/resources/graphics/up_arrow.gif' alt='/\' class='arrow'/></a> <a href='$_SERVER[WEB_ROOT]/slogans/$type/unpop'><img src='$_SERVER[WEB_ROOT]/resources/graphics/down_arrow.gif' alt='\/' class='arrow'/></a> | owner <a href='$_SERVER[WEB_ROOT]/slogans/$type/owner'><img src='$_SERVER[WEB_ROOT]/resources/graphics/up_arrow.gif' alt='/\' class='arrow'/></a> <a href='$_SERVER[WEB_ROOT]/slogans/$type/rowner'><img src='$_SERVER[WEB_ROOT]/resources/graphics/down_arrow.gif' alt='\/' class='arrow'/></a> | alphabet <a href='$_SERVER[WEB_ROOT]/slogans/$type/alpha'><img src='$_SERVER[WEB_ROOT]/resources/graphics/up_arrow.gif' alt='/\' class='arrow'/></a> <a href='$_SERVER[WEB_ROOT]/slogans/$type/ralpha'><img src='$_SERVER[WEB_ROOT]/resources/graphics/down_arrow.gif' alt='\/' class='arrow'/></a> | age <a href='$_SERVER[WEB_ROOT]/slogans/$type/index'><img src='$_SERVER[WEB_ROOT]/resources/graphics/up_arrow.gif' alt='/\' class='arrow'/></a> <a href='$_SERVER[WEB_ROOT]/slogans/$type/rage'><img src='$_SERVER[WEB_ROOT]/resources/graphics/down_arrow.gif' alt='\/' class='arrow'/></a> ]</font></th></tr>";
	$headers="<tr><td><b>slogan</b></td><td><b><img src='$_SERVER[WEB_ROOT]/gfx/pixel.gif' width='40' height='0'><br />mod</b></td><td><b>owner</b></td><td><b>edit</b></td><td><b>chance</b></td></tr>";

return "<table cellspacing='2'>$resort$spacer$headers$thelist$spacer$resort</table>";
}


// SLOGANS_LIST_ALL()
//
// returns a list of all slogans rated above zero
// currently only outputs to HTML via slogans_format_list_html
//------------------------------------------------------------------------------
function slogans_list_all($sortby='rage')
{
	if ($sortby=='') $sortby='rage';
	$sloganlist=file($GLOBALS['SLOGAN_FILENAME']);

	foreach($sloganlist as $i=>$slogan)
	{
		$slogan_a=explode(';;;',$slogan);
		if (!isset($slogan_a[1])) $slogan_a[1]=2;
		if ($slogan_a[1]<=0) unset ($sloganlist[$i]);
	}

	if ($sloganlist)
	{
		$thelist=slogans_format_list_html($sloganlist,$sortby,'list');
	}
	else
	{
		$thelist="There are currently no slogans rated more than zero.";
	}

output("all slogans","$thelist");
}




// SLOGANS_LIST_MINE()
//
// returns a list of slogans authored by the current user
// currently only outputs to HTML via slogans_format_list_html
//------------------------------------------------------------------------------
function slogans_list_mine($sortby='rage')
{
	$sloganlist=file($GLOBALS['SLOGAN_FILENAME']);
	foreach($sloganlist as $i=>$slogan)
		if (!strpos($slogan,";;;$$_SERVER[USER]")) unset($sloganlist[$i]);

	if ($sloganlist)
		$mylist=slogans_format_list_html($sloganlist,$sortby,'listmine');

	else $mylist="
		<br /><br />Sorry, $_SERVER[USER]: you have no slogans.
		You can <a href='$_SERVER[WEB_ROOT]/slogans/new'>add one</a> if you'd like.<br /><br />\n";

output("my slogans","$mylist"," viewing $_SERVER[USER]'s slogans");
}





// SLOGANS_LIST_INACTIVE()
//
// returns a list of all slogans rated below zero
// (these slogans don't get selected by slogans_get_one)
// currently only outputs to HTML via slogans_format_list_html
//------------------------------------------------------------------------------
function slogans_list_inactive($sortby='rage')
{
	if ($sortby=='') $sortby='rage';

	$sloganlist=file($GLOBALS['SLOGAN_FILENAME']);

	foreach($sloganlist as $i=>$slogan)
	{
		$slogan_a=explode(';;;',$slogan);
		if (!isset($slogan_a[1])) $slogan_a[1]=2;
		if ($slogan_a[1]>0) unset ($sloganlist[$i]);
	}

	if ($sloganlist)
		$thelist=slogans_format_list_html($sloganlist,$sortby,'listinactive');

	else $thelist="No slogans are presently rated zero or lower.";

output(
	 "inactive slogans",
	 "these slogans will not appear because their rating is below zero. after two weeks on this list, slogans are deleted.<br /><br />$thelist",
	 " viewing all inactive slogans"
	);
}



// SLOGANS_GET_ONE()
//
// returns a single slogan, along with its rating, author, and index
// only called by output.php so far
//------------------------------------------------------------------------------
function slogans_get_one()
{
	$sloganlist=file($GLOBALS['SLOGAN_FILENAME']);
	foreach($sloganlist as $i=>$slogan)
	{
		$slogan_a=explode(';;;',$slogan);
		if (!isset($slogan_a[1])) $slogan_a[1]=2;
		for($j=0;$j<$slogan_a[1];$j++)
		{
			$sloganarray[]=$slogan_a[0];
			$writerarray[]=$slogan_a[2];
			$indexarray[]=$i;
			$mod[]=$slogan_a[1];
		}
	}
	srand ((float) microtime() * 10000000);
	$k=array_rand($sloganarray);
	$featuredslogan=stripslashes(stripslashes($sloganarray[$k]));
	$featuredindex=$indexarray[$k];
	$featuredwriter=$writerarray[$k];
	$featuredmod=$mod[$k];

return array($featuredslogan,$featuredindex,$featuredwriter,$featuredmod);
}




// SLOGANS_MODIFY_ONE_RATING()
//
// increases or decreases the rating of the passed slogan by one
// slogans cannot be rated above 5 or below -5
//------------------------------------------------------------------------------
function slogans_modify_one_rating($index=3.14,$value=0)
{
		if ($value > 1) $value=1;
	if ($value < -1) $value=-1;
	$sloganlist=file($GLOBALS['SLOGAN_FILENAME']);
	copy($GLOBALS['SLOGAN_FILENAME'],$GLOBALS['SLOGAN_FILENAME'].date("YMD"));
	$slogan_a=explode(';;;',$sloganlist[$index]);
	if (!isset($slogan_a[1])) $slogan_a[1]=2;

	//Disallow extreme slogan values
	$slogan_a[1]+=$value;
	   if ($slogan_a[1]>5) $slogan_a[1]=5;        
	   if ($slogan_a[1]<-5) $slogan_a[1]=-5;

	$sloganlist[$index]=implode(';;;',$slogan_a);
	$slogantext=implode("\n",$sloganlist);
	$slogantext=str_replace("\n\n","\n",$slogantext);
	$slogantext=str_replace("\n\n","\n",$slogantext);
	file_put_contents($GLOBALS['SLOGAN_FILENAME'],$slogantext);

return $slogan_a[1];
}



// SLOGANS_DELETE_ONE()
//
// deletes a slogan
//------------------------------------------------------------------------------
function slogans_delete_one($index=3.14)
{
	$sloganlist=file($GLOBALS['SLOGAN_FILENAME']);
	$slogan_a=explode(';;;',$sloganlist[$index]);
	if (trim($slogan_a[2])==$_SERVER['USER'] || $_SERVER['USER']=='system')
	{
		copy($GLOBALS['SLOGAN_FILENAME'],$GLOBALS['SLOGAN_FILENAME'].date("YMD"));
		$sloganlist[$index]="";
		$slogantext=implode("\n",$sloganlist);
		$slogantext=str_replace("\n\n","\n",$slogantext);
		$slogantext=str_replace("\n\n","\n",$slogantext);
		file_put_contents($GLOBALS['SLOGAN_FILENAME'],$slogantext);
		@unlink("$_SERVER[FILE_ROOT]/resources/slogan.$index.belowzero");
	}

redirect('/');
}




// SLOGANS_EDIT_ONE()
//
// presents an html form to edit a single slogan
//------------------------------------------------------------------------------
function slogans_edit_one($index=3.14)
{
	// TODO:(v4.1) comment slogan_functions.php
	$sloganlist=file($GLOBALS['SLOGAN_FILENAME']);
	$slogan_a=explode(';;;',$sloganlist[$index]);
	if (trim($slogan_a[2])==$_SERVER['USER'] || $index==3.14 || !$index)
	{
		$slogan_a[0]=stripslashes(stripslashes($slogan_a[0]));
		$content="<form action='$_SERVER[WEB_ROOT]/scripts/slogan_functions.php'>
				<textarea name='slogan' rows='2' cols='80'>$slogan_a[0]</textarea>
				<br />
				<input type='submit' value='write slogan'/>
				<input type='hidden' name='action' value='slogans_write_one'/>
				<input type='hidden' name='index' value='$index'/>
				<input type='hidden' name='oldslogan' value=".urlencode($slogan_a[0])."/>
				</form>
				";
	}

output("editing slogan $index",$content,''," editing slogan $index");
}




// SLOGANS_WRITE_ONE()
//
// writes a slogan to disk
//------------------------------------------------------------------------------
function slogans_write_one($slogan=' ',$oldslogan='',$index=3.14)
{
	copy($GLOBALS['SLOGAN_FILENAME'],$GLOBALS['SLOGAN_FILENAME'].date("YMD"));
	if ($oldslogan!='' && $index!=3.14)
	{
		$sloganlist=file($GLOBALS['SLOGAN_FILENAME']);
		$slogan_a=explode(';;;',$sloganlist[$index]);
		if ($slogan!=$oldslogan) $slogan_a[0]=$slogan;
		if ($slogan_a[1]=='') $slogan_a[1]=2;
		$sloganlist[$index]=implode(';;;',$slogan_a);
		$slogantext=implode("\n",$sloganlist);
		$slogantext=str_replace("\n\n","\n",$slogantext);
		$slogantext=str_replace("\n\n","\n",$slogantext);
		file_put_contents($GLOBALS['SLOGAN_FILENAME'],$slogantext);
	}
	else
	{
		file_put_contents($GLOBALS['SLOGAN_FILENAME'],"$slogan;;;2;;;$_SERVER[USER];;;\n",FILE_APPEND);
	}

return $slogan;
}

if ($action=='slogans_write_one')
{
	slogans_write_one($slogan,$oldslogan,$index);
	redirect('/');
}

?>
