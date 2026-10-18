<?php
$topline="<style> body { margin: 0; padding: 0; }</style><div style='font-weight: bold; width: 100%; background-color: black; color: white;'><a style='text-decoration: none; color: white;' href='rtf_test.php'>&lt; BACK TO LIST</a></div><br><div style='margin: 10px;'>\n";
$output=$topline.shell_exec("$rootdir/resources/unrtf $rootdir/jwdavidson/rtf/".str_replace(' ','\ ',urldecode($file)).".rtf");
$output=str_replace(array('&aelig;','&ocirc;'),"&apos;",$output);
if ($output==$topline)
{
	unset($output);
	$output="<div><h1>D&amp;D Files</h1>\n";
	exec("ls $rootdir/jwdavidson/rtf/*.rtf",$rtflist);

	$output.="
	<style type='text/css'>
	  .TABLEHEAD { font-family: trebuchet ms; font-size: 10pt; margin-left: 5px; width: 200px; float: left; background-color: black; color: white; font-weight: bold; text-transform: uppercase; letter-spacing: 3pt; text-align: center; }
	  .TABLECELL { margin-left: 5px; width: 200px; float: left; font-size: 12pt; height: 18pt; vertical-align: bottom;}
	</style>
	";

	$output.="
	<div class='tablehead'>
		filename
	</div>
	<div class='tablehead'>
		action
	</div>
	<div class='tablehead'>
		last updated
	</div>
	<br clear='all'>\n";

	foreach($rtflist as $i=>$rtf_file)
	{
		if ($i/2 != round($i/2,0)) $background="#CCCCCC"; else $background="#FFFFFF";
		
		$time=filemtime($rtf_file);
		if ($time > mktime(0,0,0,date("m"),date("d"),date("Y"))) $bold='bold;';
		else $bold='normal;';
		
		$rtf_file_stub=str_replace('.rtf','',basename($rtf_file));
		$output.="
		<div class='tablecell' style='font-weight: $bold; background-color: $background;'>
			$rtf_file_stub
		</div>
		<div class='tablecell' style='font-weight: $bold; background-color: $background;'>
			[
			<a href='rtf_test.php?file=$rtf_file_stub'>preview</a> |
			<a href='../jwdavidson/rtf/$rtf_file_stub.rtf'>download</a> ]
		</div>
		<div class='tablecell' style='font-weight: $bold; background-color: $background;'>
			<i>".formattime($time)."</i>
		</div>
		<br clear='all'>\n";
	}
}
echo $output."\n</div>";

?>