<?php

if ($_POST['action']=='display')
{
	Header("Content-type: text/plain;");
	var_dump($_POST);
	exit;
}

$region_array=array("head","title","slogan","button_bar","buttons","titles","menus","status_bar","watched","watched_list","snoop","spiel","edit_links","content","plan_header","entries","plan_footer","plan_sidebar","reader_toolbar","trailer");
$ruletype_array=array("background","border","font","link_color","custom_css");

foreach($region_array as $i=>$region) 
{
	$region_options.="<option value='$region'>$region</option>";
}

foreach($ruletype_array as $j=>$ruletype)
{
	$ruletype_options.="<option value='$ruletype'>$ruletype</option>";
}

$page="
<html>
<head>
<style type='text/css'>
a { text-decoration: none; font-weight: bold; }
</style>
<script type='text/javascript'>
function element_add(parent_id,last_child_id)
{
	var new_child_id = last_child_id + 1;
	var block_to_modify=document.getElementById(new_child_id);
	var old_controls_id = \"controls_\" + last_child_id;
	var old_controls = document.getElementById(old_controls_id);
	var parent_div = document.getElementById(parent_id);
	var element_string;

	element_string= new_child_id + \": \";
	element_string= element_string + \"<select name='region_\" + new_child_id + \"'>$region_options</select>\";
	element_string= element_string + \"<select name='ruletype_\" + new_child_id + \"' id='ruletype_\" + new_child_id + \"' onChange='element_details_change(\" + new_child_id + \",this.value);void(0);'>$ruletype_options</select>\";
	element_string= element_string + \"<div id='ruledetails_\" + new_child_id + \"' style='display: inline;'>image: <input type='text' size='3' name='backgroundimage[\" + element_id + \"]'>, color: <input type='text' size='6' name='backgroundcolor[\" + element_id + \"]'></div>\";
	element_string= element_string + \"<span id='controls_\" + new_child_id + \"'><a href='javascript:element_add(0,\" + new_child_id + \");void(0);'> + </a></span>\";

	var div_to_append = document.createElement('div');
	var content_of_div = document.createDocumentFragment;
/*	var text_of_div = document.createTextNode(element_string);
*/
	div_to_append.setAttribute('id',new_child_id);
	parent_div.appendChild(div_to_append);

	div_to_append.innerHTML = element_string;
	

	old_controls.innerHTML = \"<a href='javascript:element_remove(\" + last_child_id + \");void(0);'>-</a>  \";

return 1;
}

function element_remove(id)
{
	var block_to_modify = document.getElementById(id);
	block_to_modify.innerHTML=' ';

return 1;
}

function element_details_change(element_id,detail_type)
{
	var ruledetails_id = \"ruledetails_\" + element_id;
	var select_id = \"ruletype_\" + element_id;
	var details=document.getElementById(ruledetails_id);
	var detail_form;

	switch(detail_type)
	{
		case 'border': detail_form=\"thickness: <input type='text' size='3' name='borderwidth[\" + element_id + \"]'> pixels, color: <input type='text' size='6' name='bordercolor[\" + element_id + \"]'>, style: <select name='borderstyle[\" + element_id + \"]'><option value='solid'>solid</option><option value='dashed'>dashed</option><option='dotted'>dotted</option></select>\";break;
		case 'background': detail_form=\"image: <input type='text' size='3' name='backgroundimage[\" + element_id + \"]'>, color: <input type='text' size='6' name='backgroundcolor[\" + element_id + \"]'>\";break;
		case 'font': detail_form=\"family: <input type='text' size='10' name='fontfamily[\" + element_id + \"]'>, size: <input type='text' size='2' name='fontsize[\" + element_id + \"]'> point, color: <input type='text' size='6' name='fontcolor[\" + element_id + \"]'>\";break;
		case 'link_color': detail_form=\"text color: <input type='text' size='6' name='textcolor[\" + element_id + \"]'>, link color: <input type='text' size='6' name='linkcolor[\" + element_id + \"]'>\";break;
		case 'custom_css': detail_form=\"<input type='text' size='20' name='customcss[\" + element_id + \"]'>\";break;
	}

	details.innerHTML = detail_form;

return 1;
}

</script>
</head>
<body style='padding: 10px; background: red;'>

<form action='$PHP_SELF' method=POST>
<div style='border: 2px solid black; background: white;' id='0'>
<h1>New CSS Styling Form: Semi-Working Prototype 1</h1>
<input type='hidden' name='action' value='display'>
<input type='submit' value='see output'>
	<div id='1'>1: 
		<select name='region_1'>$region_options</select>
		<select name='ruletype_1' id='ruletype_1' onChange='element_details_change(1,this.value);void(0);'>$ruletype_options</select>
		<div id='ruledetails_1' style='display: inline;'>image: <input type='text' size='3' name='backgroundimage[1]'>, color: <input type='text' size='6' name='backgroundcolor[1]'></div>
		<span id='controls_1'><a href='javascript:element_add(0,1);void(0);'> + </a></span>
	</div>
</div>
</form>

</body>
</html>";

echo $page;

?>