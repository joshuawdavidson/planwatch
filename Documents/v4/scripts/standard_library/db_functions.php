<?php
/*
MYSQL_FUNCTIONS.php


db_get_one_row()
db_get_all_rows
db_insert
db_update
db_delete
db_search
*/

// GLOBAL DATABASE INFO
$db=array("server"=>$_SERVER['db_server'],"user"=>$_SERVER['db_username'],"password"=>$_SERVER['db_password'],"database"=>$_SERVER['db_name']);
$db_link=@mysql_connect($db['server'],$db['user'],$db['password']);
@mysql_select_db($db['database'],$db_link);


// DB_PREPARE_VARS
//
// returns one row from $table, where $column matches $key
function db_prepare_vars($value)
{
   // Stripslashes
   if (get_magic_quotes_gpc()) {
       $value = stripslashes($value);
   }
   // Quote if not integer
   if (!is_numeric($value)) {
       $value = "'" . mysql_real_escape_string($value) . "'";
   }
   return $value;
}


// DB_GET_ONE_ROW
//
// makes variables safe against injection
function db_get_one_row($table,$column,$key)
{
	global $db_link;

	if (($table=='blog' || $table=='images' ) && $_COOKIE['my'] && $_SERVER['USER'])
	{
		$myselector=="and client like '%{$_SERVER['USERINFO_ARRAY']['client']}%'";
	}

	if (($table=='blog' || $table=='images' ) && is_array($_COOKIE['filters']))
	{
		$filterstring=" and (1 ";
		foreach($_COOKIE['filters'] as $filtercolumn=>$value)
		{
			$filterstring.=" and `$filtercolumn` like '%$value%'";
		}
		$filterstring.=") ";
	}

	$result=mysql_query("select * from $table where `$column`='$key' $myselector $filterstring ",$db_link);
//	trigger_error("QUERY: select * from $table where `$column`='$key' $myselector $filterstring");

	if (!mysql_error($db_link)) $result_row=mysql_fetch_assoc($result);
	else
	{
//		trigger_error(mysql_error($db_link)." QUERY: <code>select * from $table where `$column`='$key' $myselector $filterstring</code>",E_USER_WARNING);
	}

return $result_row;
}


// DB_QUERY
//
// returns the result as a complete array
// of assoc arrays
//
// from an anonymous submission to
// http://us2.php.net/manual/en/function.mysql-fetch-row.php
//
// modified to collapse single element arrays
// and test results by josh@planwatch.org
function db_query($data_select,$assoc=FALSE)
{
	global $db_link;

	if ((stristr($data_select,'from blog') || stristr($data_select,'from images')) && $_COOKIE['my'] && $_SERVER['USER'])
	{
		$data_select=str_replace("where","where (client like '%{$_SERVER['USERINFO_ARRAY']['client']}%') and ",$data_select);
	}

	if ((stristr($data_select,'from blog') || stristr($data_select,'from images')) && is_array($_COOKIE['filters']))
	{
		$filterstring=" (1 ";
		foreach($_COOKIE['filters'] as $column=>$value)
		{
			$filterstring.=" and `$column` like '%$value%'";
		}
		$filterstring.=") ";

		$data_select=str_replace("where","where $filterstring and ",$data_select);
	}

	$sql_query = mysql_query($data_select, $db_link);

	// if we got a good connection, condense it into an assoc. array
	if (!mysql_error($db_link) && is_resource($sql_query))
	{
		while($tmp = mysql_fetch_array($sql_query))
		{
			if(count($tmp) > 2)
			{
				if ($assoc) $info_elements[$tmp[0]]=$tmp;
				else $info_elements[]=$tmp;
			}
			else $info_elements[]=$tmp[0];
		}
	}

	elseif (mysql_error($db_link))
	{
//		trigger_error(mysql_error($db_link)." QUERY: <code>$data_select</code>",E_USER_WARNING);
	}
//	trigger_error("QUERY: <code>$data_select</code>");

	return $info_elements;
}

function mysql_buffered_query($data_select, $data_connection) 
{ 
	$sql_query = mysql_query($data_select, $data_connection); 
	while($tmp = mysql_fetch_row($sql_query)) 
	{ 
		$info_elements[]=$tmp; 
	} 
	return $info_elements; 
} 
