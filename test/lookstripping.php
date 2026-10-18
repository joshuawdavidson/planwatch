<?php
include_once('plan_read.php');

		$i=0;	/* Reset $i for reuse. */

//$userLookPlans[$i] = plan_read("jwdavidson");
$userLookPlans[$i] = plan_read_xmlrpc("jwdavidson","@planwatch.org");
$userLookKeys[$i] = "jwdavidson";

/*Get rid of anything in a style tag. */
while(stristr($userLookPlans[$i],"<STYLE"))	
{
	$start=stripos($userLookPlans[$i],"<STYLE");
	$end=stripos($userLookPlans[$i],"</STYLE>",$start);
	echo "start $start; end $end<br />";
	$styleblock=substr($userLookPlans[$i],$start,$end-$start+8);
	$userLookPlans[$i]=str_replace($styleblock,'',$userLookPlans[$i]);
}
/* Now get rid of any links to offsite style sheets. */
$userLookPlans[$i]=preg_replace("/STYLE=['\"][^'\"]*['\"]/i",'',$userLookPlans[$i]);
$userLookPlans[$i]=preg_replace("/<link [^\>]*STYLESHEET[^\>]*>/i",'',$userLookPlans[$i]);
$userLookPlans[$i]=wordwrap($userLookPlans[$i], 160, "\n");	/* Wrap plans to a specific length. 160 may be too long. */

/* Finally, get rid of the display header, since some of its functions don't work in Look mode. */
while(strstr($userLookPlans[$i],'<tt><a href='))	/*Get rid of anything in a style tag. */
{
	$startHeader=strpos($userLookPlans[$i],'<tt><a href=');
	$endHeader=strpos($userLookPlans[$i],'Plan:</tt>');
	$userLookPlans[$i]=str_replace(substr($userLookPlans[$i],$startHeader,($endHeader-($startHeader-10))),'',$userLookPlans[$i]);
}

$i=0;	/* Reset $i for reuse. */

/* One table per plan to look at. Lots of tricky formatting -- look carefully. */
foreach($userLookPlans as $i=>$plan) {
//for($i = 0; $i < ($index - 1); $i++){ /* This loop displays each plan. */
	echo "<div id='e" . ($i+1) . "'>";
	$j = 0;
	echo '<a name="look_' . $userLookKeys[$i] . '"></a><a href="#looktop"><b><u>Return to Top of Look Page</u></b></a> | ';
	echo '<a href="index.php"><b><u>Return to Main Planworld Page</u></b></a><br />';
	for($j = 0; $j < ($index - 1); $j++){ /* This loop creates the index that is displayed above each plan. */
		if($j !=0) echo '&nbsp;&nbsp; |';
		echo '&nbsp;&nbsp;<a href="#look_' . $userLookKeys[$j] . '">' . $userLookKeys[$j] . '&nbsp;&nbsp;</a> ';
	}
	echo '<P></P>';
	echo '<br />' . $userLookKeys[$i] . '\'s plan<br />';
	echo $userLookPlans[$i] . '<br />';
	echo '<hr width="82%"><p></p>';
	echo'</div>';
}
	/* Footer for pages with something on them. */
	echo "<div id='e" . ($i+1) . "'>";
	echo '<a name="look_' . $userLookKeys[$i] . '"></a><a href="#looktop"><b><u>Return to Top of Look Page</u></b></a><br />';
	echo '<a href="index.php"><b><u>Return to Main Planworld Page</u></b></a><br />';
	echo'</div>';
//	echo '</body></html>';

echo "<hr>".plan_read_xmlrpc("jwdavidson","@planwatch.org");

?>