<?php
// we will do our own error handling
error_reporting(E_ERROR | E_PARSE);
//asdfasf
// user defined error handling function
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars)
{
   // define an assoc array of error string
   // in reality the only entries we should
   // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
   // E_USER_WARNING and E_USER_NOTICE
   $errortype = array (
               E_ERROR          => "Error",
               E_WARNING        => "Warning",
               E_PARSE          => "Parsing Error",
               E_NOTICE          => "Notice",
               E_USER_ERROR      => "User Error",
               E_USER_WARNING    => "User Warning",
               E_USER_NOTICE    => "User Notice",
               );

	if ($errno==E_USER_ERROR)
		$err = serialize($vars);
	else $err='';

	if ($errno==E_WARNING)
	{
		if (strstr($errmsg,'header information')) $errno=E_USER_NOTICE;
	}

	$basefilename=basename($filename);

	if ($errno != E_USER_NOTICE && $errno != E_NOTICE)
	{
		if (user_is_administrator()) $errorlink="<a target='_blank' href='/admin/source/$basefilename/$linenum#$linenum'>$filename on $linenum</a>";
		else $errorlink="$filename on $linenum";
		$_SERVER['ERRORS'].="$errortype[$errno]: $errmsg in $errorlink<br/>\n\n";
		if ($err) $_SERVER['ERROR_DETAILS'].="$errmsg $filename $linenum $err<br/><br/>\n";
	}

	if ($errno == E_USER_NOTICE) 
	{
		$basefilename=basename($filename);
		$_SERVER['DEBUG_INFO'].="$errmsg in <a target='_blank' href='/admin/source/$basefilename/$linenum#$linenum'>$filename on $linenum</a><br/>\n\n";
	}
	
	if ($errno == E_PARSE) echo $_SERVER['ERRORS'];
}

$old_error_handler = set_error_handler("userErrorHandler");

?>