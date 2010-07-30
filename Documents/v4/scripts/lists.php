<?php
/*
LISTS.PHP -- part of the planwatch library

sets up / edits allowed, blocked, and watched lists
*/

// planwatch_mark_all_unread()
//
// sets the lastread times of every watched plan to jan 1, 1970
// equivalent to planwatch reset on the vax
//------------------------------------------------------------------------------

function planwatch_mark_all_unread()
{
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass'])
		&& is_dir($_SERVER['USER_ROOT']))
	{
		exec("rm -f $_SERVER[USER_ROOT]/lastread.dat");
	}

	redirect("/");
}


// planwatch_mark_all_read()
//
// sets the lastread times of every watched plan to the current time
// equivalent to planwatch update on the vax
//------------------------------------------------------------------------------
function planwatch_mark_all_read()
{
	$list_fn="$_SERVER[USER_ROOT]/watchedlist.txt";
	$list=file($list_fn);
	$lastread_dat_fn="$_SERVER[USER_ROOT]/lastread.dat";

	$lastread_a=unserialize(file_get_contents($lastread_dat_fn));

	foreach($list as $i=>$item)
	{
		$item=trim($item);
	    if (strstr($item,'!') && strstr($item,':'))
	    {
			list($item,$junk)=explode(':',str_replace('!','',$item),3);
			if ($junk[0]=='/') $item.=":$junk";
	    }
	
		$lastread_a[$item]=time();
	}

	file_put_contents($lastread_dat_fn,serialize($lastread_a));

	redirect("/");
}







//list_edit()
//
//presents a form for editing the contents of a list file
//------------------------------------------------------------------------------
// TODO:(v4.5) better watched list format
function list_edit($listname)
{
	if (user_is_valid($_SERVER['USERINFO_ARRAY']['username'],$_SERVER['USERINFO_ARRAY']['userpass']))
	{
		$list_fn="$_SERVER[USER_ROOT]/{$listname}list.txt";
		if (file_exists($list_fn))	$list_array=file($list_fn);
		else $list_array=array();

		if (strstr($list_array[0],'sort'))
		{
			$sb=str_replace('sort by ','',trim($list_array[0]));
			$list_array[0]='';
			$sc[$sb]='SELECTED';
		}
		else $sc['none']=='SELECTED';

		foreach($list_array as $i=>$listitem)
		{
			if (strstr($listitem,'!!!'))
			{
				$prune='!!!';
				$prune_visibility='visible';
				$prune_checked='CHECKED';
				$list_array[$i]='';
			}

			if (!$prune) $prune_visibility='hidden';

			if (strstr($listitem,'!prune'))
			{ 
				$prunenum=str_replace('!prune','',trim($listitem)); 
				if (strstr($prunenum,'s')) $pn['s']='SELECTED';
				if (strstr($prunenum,'m')) $pn['m']='SELECTED';
				if (strstr($prunenum,'b')) $pn['b']='SELECTED';
				if (strstr($prunenum,'h')) $pn['h']='SELECTED';
				if (strstr($prunenum,'d')) $pn['d']='SELECTED';
				if (strstr($prunenum,'w')) $pn['w']='SELECTED';
				if (strstr($prunenum,'n')) $pn['n']='SELECTED';
				if (strstr($prunenum,'y')) $pn['y']='SELECTED';
				if (strstr($prunenum,'k')) $pn['k']='SELECTED';
				$prunenum=ereg_replace("[[:alpha:]]",'',$prunenum);
				$list_array[$i]='';
			}
			if (strstr($listitem,'!onlynew')) { $onlynew='CHECKED'; $list_array[$i]=''; }
			if (strstr($listitem,'!alwaysnew')) { $alwaysnew='CHECKED'; $list_array[$i]=''; }
		}

		$listcontent=trim(implode('',$list_array));

		$content.="<form action='$_SERVER[WEB_ROOT]/scripts/lists.php' method='post' class='edit_links'><br />\n"
			."<h1>$_SERVER[USER]'s ".ucwords($listname)." List</h1>
			enter one user per line<br />\n";
		$content.="<br />\n<textarea id='textbox' style='margin-right: 8px;' name='addedlist' wrap='off'>$listcontent</textarea>\n<div>\n";

		$content.="<input type='submit' id='submit_button' value='Save Changes' /><br />\n";

		if ($listname=='watched')
		{
			$content.="<a href='$_SERVER[WEB_ROOT]/help/watched'>Watched List Help</a>
			<h4 style='margin-bottom: 0px;'>List sorting</h4>
			<select name='sortby'>
				<option value='name' $sc[name]>by name</option>
				<option value='time' $sc[time]>by time</option>
				<option value='none' $sc[none]>unsorted</option>
			</select>
			<h4 style='margin-bottom: 0px;'>Plans to include</h4>
			<input type='checkbox' value='!!!' name='prune' $prune_checked
				onclick=\"if(this.checked) document.getElementById('prunedetails').style.visibility='visible'; else document.getElementById('prunedetails').style.visibility='hidden';\"/>
			Prune older plans
			<div id='prunedetails' style='visibility: $prune_visibility;'>
			&nbsp; &nbsp; &nbsp; after
			<input type='text' value='$prunenum' name='prunenum' size='3'/>
			<select name='pruneunit'>
				<option value=''> </option>
				<option value='s' ".$pn['s'].">seconds</option>
				<option value='m' ".$pn['m'].">minutes</option>
				<option value='b' ".$pn['b'].">beats</option>
				<option value='h' ".$pn['h'].">hours</option>
				<option value='d' ".$pn['d'].">days</option>
				<option value='w' ".$pn['w'].">weeks</option>
				<option value='n' ".$pn['n'].">months</option>
				<option value='y' ".$pn['y'].">years</option>
				<option value='k' ".$pn['k'].">decades</option>
			</select><br/>\n";

			$content.="&nbsp; &nbsp; &nbsp; <input type='checkbox' value='!onlynew' name='onlynew' $onlynew /> only show unread plans<br/>\n"; 
			$content.="&nbsp; &nbsp; &nbsp; <input type='checkbox' value='!alwaysnew' name='alwaysnew' $alwaysnew /> always show unread plans</div>\n";
		}

		$content.="
		<input type='hidden' name='listname' value='$listname'/><input type='hidden' name='username' value='$_SERVER[USER]'/>
		</form>
		</div>\n";
	}
	else $content="<div class='alert'>Your attempt failed because you do not have permission
	to edit the $listname list. If you feel this is an error, send
	<a href='mailto:help@planwatch.org'>help@planwatch.org</a> an email.</div>\n";
	
	return $content;
}


// list_resort()
//
// re-sorts the listfile
//------------------------------------------------------------------------------
function list_resort($listname,$sortby)
{
	$list_fn="$_SERVER[USER_ROOT]/$listname"."list.txt";
	$list_string=file_get_contents($list_fn);
	
	$sort_string="sort by $sortby";

	$sortend=strpos($list_string,"\n");
	if(strstr($sub=substr($list_string,0,$sortend),'sort by'))
	{
		$list_string=str_replace($sub,$sort_string,$list_string);
	}
	else $list_string=$sort_string."\n".$list_string;

	file_put_contents($list_fn,$list_string);
	
	return TRUE;
//	redirect("/");
}



// list_write()
//
//writes the listfile
//------------------------------------------------------------------------------
function list_write()
{
	$list=explode("\n",$_POST['addedlist']);

	foreach($list as $entry)
	{
		$entry=stripslashes(stripslashes(trim($entry)));
		$list_string.=$entry."\n";
	}

	if ($_POST['prunenum'] && $_POST['pruneunit']) $prune="$_POST[prune]\n!prune$_POST[prunenum]$_POST[pruneunit]";


	$list_string="sort by $_POST[sortby]\n$_POST[alwaysnew]\n$_POST[onlynew]\n$prune\n$list_string";
	
	file_put_contents("$_SERVER[USER_ROOT]/$_POST[listname]"."list.txt",$list_string);

redirect("/");
}

if ($_POST['addedlist']) list_write();
elseif ($_POST['username'] && $_POST['listname'] && !($_POST['addedlist'])) list_edit($_POST['username'],$_POST['listname']);

?>