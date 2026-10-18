<?php
/*
FAVORITES_LIST.php

goals:
allows you to compile a list of favorite plan entries from yourself and other journaling users.
displays as a sidebar or on its own page.
some favorites are private, some are public

optional, quasi-related:
allow categories (user-defined?) to be attached to each entry.
how do we maintain category associations? perhaps a !category:name of category! directive?
maybe also a !title:title of entry! directive as well?
*/

favorites_append($writer,$entry,$title,$description)
{
	global $rootdir,$user,$pass;
	if (isvaliduser($user,$pass))
	{
		$favorites_list_file="$rootdir/pwusers/$user/favorites.dat";
		$new_favorite=serialize(array("writer"=>$writer,"entry"=>$entry,"title"=>$title,"description"=>$description));
		file_append_contents($favorites_list_file,"\n".$new_favorite."\n");
	}

return TRUE;
}

favorites_remove($writer,$entry)
{
	global $rootdir,$user,$pass;
	if (isvaliduser($user,$pass))
	{
		$favorites_list_file="$rootdir/pwusers/$user/favorites.dat";
		$favorites_list=file($favorites_list_file);
		foreach($favorites_list as $i=>$fave)
		{
			if (strstr($fave,$entry)) $favorites_list[$i]='';
		}

		$favorites_list=str_replace("\n\n","\n",$favorites_list);
		$favorites_list=str_replace("\n\n","\n",$favorites_list);

		file_write_contents($favorites_list_file,$favorites_list);
	}

return TRUE;
}

favorites_display($writer)
{
	global $rootdir,$user,$pass;
	if (isvaliduser($user,$pass))
	{
		$favorites_list_file="$rootdir/pwusers/$user/favorites.dat";
		$favorites_list=file($favorites_list_file);
		foreach($favorites_list as $i=>$fave)
		{
			extract(unserialize($fave));
			$content.="<a href='/read/$writer/.$entry'>$title</a><br>\n$description<br>\n";
		}

		$favorites_list=str_replace("\n\n","\n",$favorites_list);
		$favorites_list=str_replace("\n\n","\n",$favorites_list);

		file_write_contents($favorites_list_file,$favorites_list);
	}

return $content;
}

?>