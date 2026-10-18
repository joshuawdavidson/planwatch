<?php

$meetadir = "$_SERVER[USER_ROOT]/plan";
echo $meetadir; 

echo "<br>removing deleted entries..." ; 
exec("rm -f $meetadir/rem.*",$delresults); 

echo "<br>removing archived planheaders..." ; 
exec("rm -f $meetadir/planheader.txt.*",$delresults); 

echo "<br>removing archived planfooters..." ; 
exec("rm -f $meetadir/planfooter.txt.*",$delresults); 

echo "<br>removing selected timeframe plan entries..." ; 
exec("rm -f $meetadir/plan.123*.txt",$delresults);  
exec("rm -f $meetadir/plan.124*.txt",$delresults);  
exec("rm -f $meetadir/plan.125*.txt",$delresults);  
exec("rm -f $meetadir/plan.1260*.txt",$delresults);  
exec("rm -f $meetadir/plan.1261*.txt",$delresults);  

// note for next time: don't get rid of the BIG E ENTRY!!! 
?>

