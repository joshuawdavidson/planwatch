
document.write("<div id='format_toolbar_container'><ul id='format_toolbar' class='menubar'><li><input type='button' value='Headline' onclick=\"insertTag('textbox','<h1>','</h1>');void(0);\"></li><li><input type='button' value='Subhead' onclick=\"insertTag('textbox','<h2>','</h2>');void(0);\"></li><li><input type='button' value='B' onclick=\"insertTag('textbox','<strong>','</strong>');void(0);\"></li><li><input type='button' value='i' onclick=\"insertTag('textbox','<em>','</em>');void(0);\"></li><li><input type='button' value='&gt; |' onclick=\"insertTag('textbox','<blockquote>','</blockquote>');void(0);\"></li><li><input type='button' value='&quot;' onclick=\"insertTag('textbox','&amp;quot;','&amp;quot;');void(0);\"></li><li><input type='button' value='link' onclick=\"DoPrompt('url');void(0);\"></li><li><input type='button' value='email' onclick=\"DoPrompt('email');void(0);\"></li><li><input type='button' value='snoop' onclick=\"DoPrompt('snoop');void(0);\"></li><li style='float: right; text-align: right;' class='menubutton' id='insertanchor' onclick=\"loadXMLDoc('/userfiles/list','','insert');document.getElementById('insert').style.display='block';void(null);\">files <img src='/resources/graphics/down_arrow.gif' id='insert_arrow'  style='width: 9px; height: 9px;' /><ul id='insert'><li>&nbsp;</li></ul></li><li style='float: right; text-align: right;' class='menubutton' id='draftsanchor' onclick=\"loadXMLDoc('/write/list_drafts','','drafts');layerToggle('drafts','draftsanchor');void(null);\">drafts <img src='/resources/graphics/down_arrow.gif' id='insert_arrow'  style='width: 9px; height: 9px;' /><ul id='drafts'><li>&nbsp;</li></ul></li><li style='float: right; text-align: right; display: none;' class='menubutton' id='photosanchor' onclick=\"loadXMLDoc('/flickr/ajax','','flickr');layerToggle('flickr','photosanchor');void(null);\">flickr <img src='/resources/graphics/down_arrow.gif' id='insert_arrow'  style='width: 9px; height: 9px;' /><ul id='flickr'><li>&nbsp;</li></ul></li></ul></div><br />");

function insertTag(fieldId, tagStart, tagEnd) {
	/* FROM JS QuickTags version 1.1
	//
	// Copyright (c) 2002-2004 Alex King
	// http://www.alexking.org/
	//
	// ADAPTED by jwdavidson for planwatch.org
	*/

	myField=document.getElementById(fieldId);

	/* the above will fail if we're coming from an iframe menu, so we try again */
	if (!myField) myField=window.parent.document.getElementById(fieldId);

	/*IE support*/
	if (document.selection)
	{
		myField.focus();
	    sel = document.selection.createRange();
		if (sel.text.length > 0)
		{
			sel.text = tagStart + sel.text + tagEnd;
		}
		else
		{
			sel.text = tagStart + tagEnd;
		}
		myField.focus();
	}

	/*MOZILLA/NETSCAPE support*/
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		var cursorPos = endPos;
		var scrollTop = myField.scrollTop;

		if (startPos != endPos) {
			myField.value = myField.value.substring(0, startPos)
			              + tagStart
			              + myField.value.substring(startPos, endPos) 
			              + tagEnd
			              + myField.value.substring(endPos, myField.value.length);
			cursorPos += tagStart.length + tagEnd.length;
		}
		else {
			myField.value = myField.value.substring(0, startPos)
						  + tagStart
						  + tagEnd
						  + myField.value.substring(endPos, myField.value.length);
			cursorPos = startPos + tagStart.length + tagEnd.length;
		}
		myField.focus();
		myField.selectionStart = cursorPos;
		myField.selectionEnd = cursorPos;
		myField.scrollTop = scrollTop;
	}
	else {
		myField.value += tagStart + tagEnd;
		myField.focus();
	}
}




//------------------------------------------------------------------------------

function DoPrompt(action,fieldId)
{
	if (!fieldId) fieldId='textbox';

	if (action == "url") {
		var thisURL = prompt("Enter the complete URL for the link you wish to add.", "http://");
		if (thisURL == null){return;}

		insertTag(fieldId,"<a href='" + thisURL + "'>","</a>");
	}

	if (action == "email") {
		var thisURL = prompt("Enter the email address you wish to add.", "");
		if (thisURL == null){return;}

		insertTag(fieldId,"<a href='mailto:" + thisURL + "'>","</a>");
	}

	if (action == "snoop") {
		var thisURL = prompt("Enter the user you want to snoop.", "");
		if (thisURL == null){return;}

		insertTag(fieldId,"!" + thisURL + ":","!");
	}
}
