<?php
/*
DATEFUNCTIONS.PHP

*/


// FIXLASTMOD()													   
//
// converts most english dates to timecodes						  
//------------------------------------------------------------------------------
function fixlastmod($lastmod)
{
	$lastmod=str_replace(' @','',$lastmod);
	$lastmod=str_replace('.','',$lastmod);
	$lastmod=str_replace(',','',$lastmod);
	$lastmod=str_replace('th','',$lastmod);
	$lastmod=str_replace('-',' ',$lastmod);
	$lastmod=str_replace(';','',$lastmod);
	$lastmod_array=explode(' ',$lastmod);

	$months=array('','jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
	
	foreach($lastmod_array as $datepart)
	{
		if (strlen($datepart)==3) $month=array_search(strtolower($datepart),$months);
		if (strlen($datepart)<=2 && !(strstr($datepart,'M'))) $day=$datepart;
		if (strlen($datepart)==4) $year=$datepart;
		if (strlen($datepart)>4 && strstr($datepart,':'))
		{
			$timearray=explode(':',$datepart);
			$hours=$timearray[0];
			$minutes=$timearray[1];
			$seconds=$timearray[2];
		}
		if (strstr($datepart,'PM')) $hours+=12;
	}
	$lastmod=mktime($hours,$minutes,$seconds,$month,$day,$year);
	return $lastmod;
}




// FORMATTIME()
//
// formats a timecode to be human readable
// according to the clockpref variable of a logged in user and the
// time since the last update
//------------------------------------------------------------------------------
function formattime($timecode=0)
{
	$clockpref=$_SERVER['USERINFO_ARRAY']['clock'].$_SERVER['USERINFO_ARRAY']['seconds'];

	if (strpos($clockpref,'24')!==FALSE) $timestring="G:i";
	elseif (strpos($clockpref,'b')!==FALSE) $timestring="B";
	else $timestring="g:ia";
	if (strpos($clockpref,'s')!==FALSE && strpos($clockpref,'b')===FALSE) $timestring=str_replace('i','i:s',$timestring);

 	$now=time();
	$today=mktime(0,0,0,date("m",$now),date("d",$now),date("Y",$now));
	
	$thisweek=$today-(24*3600*7);
	$thisyear=mktime(0,0,0,1,1,date("Y",$now));
	if ($timecode)
	{
		if ($timecode<$today)
		{
			if ($timecode<$thisyear) $timecode=date("M Y",$timecode);
			else if ($timecode<$thisweek) $timecode=date("M jS",$timecode);
			else $timecode=date("D $timestring",$timecode);
		}
		else $timecode=date($timestring,$timecode);
	}

$timecode=str_replace(' ','&nbsp;',$timecode);
return $timecode;
}



// TIME_CALCULATE_THRESHHOLD
//
// takes a planwatch "ago" string, calculates the unix timecode threshhold
//------------------------------------------------------------------------------
function time_calculate_threshhold($ago)
{
	$threshhold = $ago;

	if (strstr($threshhold,'s')) $threshhold = str_replace('s','',$threshhold);
	if (strstr($threshhold,'m')) $threshhold = str_replace('m','',$threshhold)*60;
	if (strstr($threshhold,'b')) $threshhold = str_replace('b','',$threshhold)*86.4;
	if (strstr($threshhold,'h')) $threshhold = str_replace('h','',$threshhold)*3600;
	if (strstr($threshhold,'d')) $threshhold = str_replace('d','',$threshhold)*3600*24;
	if (strstr($threshhold,'w')) $threshhold = str_replace('w','',$threshhold)*3600*24*7;
	if (strstr($threshhold,'n')) $threshhold = str_replace('n','',$threshhold)*3600*24*30;
	if (strstr($threshhold,'y')) $threshhold = str_replace('y','',$threshhold)*3600*24*365;
	if (strstr($threshhold,'k')) $threshhold = str_replace('k','',$threshhold)*3600*24*365*10;

	return $threshhold;
}

?>