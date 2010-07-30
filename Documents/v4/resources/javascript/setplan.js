function saveDraft(draftTimePassed)
{
	if (element('textbox').value)
		loadXMLDoc('http://' + http_host + web_root + '/scripts/plan_update.php','draft_time=' + draftTimePassed + '&action=Autosave%20Ajax&newplan=' + escape(element('textbox').value),'autosave_alert',processReqChangePOST);

	setTimeout("saveDraft(" + draftTimePassed + ");",61131);
}

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


function openSpellChecker(textAreaId,web_root)
{
	// get the textarea we're going to check
	var txt = document.getElementById(textAreaId);
	// give the spellChecker object a reference to our textarea
	// pass any number of text objects as arguments to the constructor:
	var speller = new spellChecker( txt );
	speller.popUpUrl = web_root + '/resources/javascript/speller/spellchecker.html';
	speller.spellCheckScript = web_root + '/resources/javascript/speller/server-scripts/spellchecker.php';
	// kick it off
	speller.openChecker();
}


//------------------------------------------------------------------------------

function DoPrompt(action,fieldId)
{
	if (!fieldId) fieldId='textbox';

	if (action == "url") {
		var thisURL = prompt("Enter the complete URL for the link you wish to add.", "http://");
		if (thisURL == null){return;}

		insertTag(fieldId,"<a href='" + thisURL + "'>",'</a>');
	}

	if (action == "spiel") {
		var thisTopic = prompt("Enter the topic name you wish to discuss.", "generic topic");
		if (thisTopic == null){return;}

		insertTag(fieldId,'!spiel:' + thisTopic + ':','!');
	}

	if (action == "snoop") {
		var thisURL = prompt("Enter the name of the plan you wish to snoop.", "system");
		if (thisURL == null){return;}

		insertTag(fieldId,'!' + thisURL + ':','!');
	}

	if (action == "image") {
		var thisURL = prompt("Enter the URL of the image you wish to insert.", "http://");
		if (thisURL == null){return;}

		insertTag(fieldId,'<img src="' + thisURL + '"/>','');
	}
}

//------------------------------------------------------------------------------

function searchReplace(text,_out,_add)
{
	var temp = "" + text;

	var out=_out;
	var add=_add;
	var pos=0;

	while (temp.indexOf(out)>-1)
	{
		pos= temp.indexOf(out);
		temp = "" + (temp.substring(0, pos) + add + temp.substring((pos + out.length), temp.length));
	}
	/* this finds the replacement if it is the last item on the input string */
	if(temp.indexOf(_out)==temp.length-_out.length)
		temp=""+temp.substring(0,temp.length-_out.length)+_add.substring(0,_add.length);
	
	text = temp;
	return text;
}

function transformToList(fieldId, listType) {
	/* FROM JS QuickTags version 1.1
	//
	// Copyright (c) 2002-2004 Alex King
	// http://www.alexking.org/
	//
	// ADAPTED by jwdavidson for planwatch.org
	*/

	myField=document.getElementById(fieldId);

	if (listType = 'ul')
	{
		tagStart = '<ul>';
		tagEnd = '</ul>';
	}
	else
	{
		tagStart = '<ol>';
		tagEnd = '</ol>';
	}

	/*IE support*/
	if (document.selection)
	{
		myField.focus();
			sel = document.selection.createRange();
		if (sel.text.length > 0)
		{
			sel.text = searchReplace(sel.text,String.fromCharCode(10),'</li> <li>');
			sel.text = tagStart + ' <li>' + sel.text + '</li> ' + tagEnd;
		}
		else
		{
			sel.text = tagStart + ' <li> list item </li> ' + tagEnd;
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
			listItems = searchReplace(myField.value.substring(startPos, endPos),String.fromCharCode(10),'</li> <li>');
			myField.value = myField.value.substring(0, startPos)
										+ tagStart
										+ ' <li>'
										+ listItems 
										+ '</li> '
										+ tagEnd
										+ myField.value.substring(endPos, myField.value.length);
			cursorPos += tagStart.length + tagEnd.length;
		}
		else {
			myField.value = myField.value.substring(0, startPos)
							+ tagStart
							+ ' <li> list item </li> '
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
		myField.value += tagStart + ' <li> list item </li> ' + tagEnd;
		myField.focus();
	}
}
