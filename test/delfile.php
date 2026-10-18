<?php

$dir_to_affect="/home/planwatc/public_html/*/carp/";
echo "<h1>$dir_to_affect</h1>";
exec("ls $dir_to_affect",$lsresults);
print_r($lsresults);
/*
exec("rm -fr $dir_to_affect",$rmresults);
print_r($rmresults);
unset($lsresults);
echo "<br/><br/>";
exec("ls $dir_to_affect",$lsresults);
print_r($lsresults);
*/
?>