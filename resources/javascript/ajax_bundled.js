var req;
var XMLResultElementId='';
var XMLBlankElementId='';
var XMLEditBoxId='';
var XMLRequiredKey='';
var redirectLocation='';
var d=new Date();
if(document.getElementById('planwatch')) setInterval("watched_list_refresh();",120001);

try{req=new XMLHttpRequest();}catch(trymicrosoft){try{req=new ActiveXObject("Msxml2.XMLHTTP");}catch(othermicrosoft){try{req=new ActiveXObject("Microsoft.XMLHTTP");}catch(failed){req=false;}}}

function getlastaction(user)
{
	loadXMLDoc("/user/lastlogin/" + user + "/ajax","","lastaction","processReqChangeGET");
}

function send_display_more(correspondent)
{
	loadXMLDoc("/send/ajax/" + correspondent + "/" + (parseInt(document.getElementById("last_send").value) + 1),"","send_div","processReqAppendGET");
	//alert("/send/ajax/" + correspondent + "/" + (parseInt(document.getElementById("last_send").value) + 1));
	document.getElementById("last_send").value=(parseInt(document.getElementById("last_send").value)+30)+"";
}

function modify_watch_state(url,name,state)
{
	url=url.replace(/\//g,"|slash|");
	name=name.replace(/\//g,"|slash|");
	loadXMLDoc(location.protocol + "//" + http_host + web_root + "/lists/" + state + "_ajax/watched/" + url + "/" + name,null);
	if(state=='add')
		document.getElementById('watch_link').innerHTML="<a onclick='modify_watch_state(\"" + url + "\",\"" + name + "\",\"remove\");'>unwatch</a>";
	if(state=='remove')
		document.getElementById('watch_link').innerHTML="<a onclick='modify_watch_state(\"" + url + "\",\"" + name + "\",\"add\");'>watch</a>";
	if(document.getElementById('planwatch')) setTimeout("watched_list_refresh();",500);
}

function refresh(id,uri)
{
	if (element(id)) loadXMLDoc(location.protocol + "//" + http_host + web_root + uri,null,id,'processReqChangeGET',null,' ');
}

function sendMessage()
{
	var sender = element('sender').value;
	var recipient = element('recipient').value;
	var sendmessage = element('sendmessage').value;
	var requestData = "action=send&ajax=1&sender=" + sender + "&recipient=" + recipient + "&sendmessage=" + encodeURIComponent(sendmessage);

	loadXMLDoc(location.protocol + "//" + http_host + web_root + '/scripts/send.php?ajax=_ajax',requestData,'send_div','processReqChangePOST','sendmessage');
}

function addNewEntry()
{
	var date     = new Date();
	var timecode = Math.round(date.getTime() / 1000);
	loadXMLDoc(location.protocol + "//" + http_host + web_root + "/entry/ajax_new/" + timecode,null,'plan_body','processReqAddGET',null);
	setTimeout("swapContent('editLayer_' + " + timecode + ",'entry_content_' + " + timecode + ");",500);
}


function displayEditLayer(timecode)
{
	resultId     = 'editLayer_' + timecode;
	headerId     = 'entry_header_' + timecode;
	element(headerId).innerHTML = element(headerId).innerHTML + "<div id='" + resultId + "'>Loading edit form...</div>";
	loadXMLDoc(location.protocol + "//" + http_host + web_root + "/entry/editbox/." + timecode,null,resultId,null,processReqChangeGET,"textarea");
	layerOff('entry_content_' + timecode);
}

function element(id) { return document.getElementById(id); }

function sendToFullEditor(timecode,privacy,nolinebreaks,writer)
{
	XMLEditBoxId           = 'editBox_' + timecode;
	XMLResultElementId     = 'entry_content_' + timecode;

	var box                = document.getElementById(XMLEditBoxId);
	var planData           = box.value;
	var entryDiv           = document.getElementById(XMLResultElementId);
	var requestData        = "action=Save%20Draft%20Ajax&private=" + privacy + "&nolinebreaks=" + nolinebreaks + "&edit=." + timecode + "&writer=" + writer + "&newplan=" + escape(planData);

	redirectLocation       = location.protocol + "//" + http_host + web_root + '/write/draft/.' + timecode + '/' + privacy + '/' + nolinebreaks + '/' + writer;

//	alert(editorLocation);
	swapContent(XMLResultElementId, 'editLayer_' + timecode);
	entryDiv.innerHTML = "Saving Draft... ";
	loadXMLDoc(location.protocol + "//" + http_host + web_root + "/scripts/plan_update.php", requestData,'entry_content_' + timecode,'processReqChangePOST',null);
	setTimeout("window.location.href=redirectLocation;",500);
}

function updatePlan(timecode,privacy,nolinebreaks,writer)
{
	XMLEditBoxId           = 'editBox_' + timecode;
	XMLResultElementId     = 'entry_content_' + timecode;

	var planData           = document.getElementById('editBox_' + timecode).value;
	var entryDiv           = document.getElementById('entry_content_' + timecode);
	var requestData        = "action=Update%20Journaling%20Plan%20Ajax&private=" + privacy + "&nolinebreaks=" + nolinebreaks + "&edit=." + timecode + "&writer=" + writer + "&newplan=" + escape(planData);

	document.getElementById('editLayer_' + timecode).style.display='none';
	document.getElementById('edit_links_' + timecode).style.display='none';
	element('entry_content_' + timecode).style.display='block';
	document.getElementById('entry_content_' + timecode).innerHTML = "Processing Update... ";
	loadXMLDoc(location.protocol + "//" + http_host + web_root + "/scripts/plan_update.php", requestData,'entry_content_' + timecode,'processReqChangePOST',null);
}

function watched_list_refresh()
{
	if (document.getElementById('key'))
		var key=document.getElementById('key').innerHTML;
	else var key='1';
	var d = new Date;
	loadXMLDoc(location.protocol + "//" + http_host + '/' + web_root + 'watched/' + user + '/ajax/' + key + '/' + d.getTime(),null,'planwatch','processReqChangeGET',null,'Watched Plans');
}

function send_refresh()
{
	var sender = element('sender').value;
	var recipient = element('recipient').value;
	var sendmessage = element('sendmessage').value;
	var requestData = "action=refresh&ajax=1&sender=" + sender + "&recipient=" + recipient;

	loadXMLDoc(location.protocol + "//" + http_host + web_root + '/scripts/send.php',requestData,'send_div','processReqChangePOST',null);
}

function list_move(wlpos)
{
	document.getElementById("body").setAttribute("class", "wlpos_" + wlpos);
}

function slogans_edit_one(currentId)
{
	XMLResultElementId = 'slogan_text';
	var slogan_element = document.getElementById(XMLResultElementId);
	var currentText    = slogan_element.innerHTML;

	var slogan = prompt('Edit your slogan and click "OK"',currentText);

	if (slogan != null)
	{
		slogan = escape(slogan);
		loadXMLDoc(location.protocol + "//" + http_host + web_root + "/slogans/write_ajax/" + currentId + '/' + slogan,null,XMLResultElementId,'',null);
	}
}


function slogans_modify_one_rating(slogan_index,direction)
{
	loadXMLDoc(location.protocol + "//" + http_host + web_root + "/slogans/mod/" + slogan_index + "/" + direction,null,'slogan_rating','',null);
}


function processReqCallbackGET() {
    // only if req shows "loaded"
    if (req.readyState == 4) {
        // only if "OK"
        if (req.status == 200) {
            // ...processing statements go here...
			var result_element=document.getElementById(XMLResultElementId);
			var blank_element = document.getElementById(XMLBlankElementId);
			if (req.responseText!='IGNORE.NULL')
			{
		        result_element.innerHTML = req.responseText;
				blank_element.value='';
			}
        } else {
//            alert("There was a problem retrieving the XML data:\n" +
//                req.statusText);
        }
    }
}

function processReqCallbackPOST() {
    // only if req shows "loaded"
    if (req.readyState == 4) {
        // only if "OK"
        if (req.status == 200) {
            // ...processing statements go here...
			var result_element=document.getElementById(XMLResultElementId);
			var blank_element = document.getElementById(XMLBlankElementId);
			if (req.responseText!='IGNORE.NULL')
			{
		        result_element.innerHTML = req.responseText;
				blank_element.value='';
			}
        } else {
//            alert("There was a problem retrieving the XML data:\n" +
//                req.statusText);
        }
    }
}

function processReqChangePOST() {
    // only if req shows "loaded"
    if (req.readyState == 4) {
        // only if "OK"
        if (req.status == 200) {
            // ...processing statements go here...
			var result_element=document.getElementById(XMLResultElementId);
			var blank_element = document.getElementById(XMLBlankElementId);
			if (req.responseText!='IGNORE.NULL')
			{
		        result_element.innerHTML = req.responseText;
				blank_element.value='';
			}
        } else {
//            alert("There was a problem retrieving the XML data:\n" +
//                req.statusText);
        }
    }
}

function processReqChangeGET() {
 	// only if req shows "loaded"
    if (req.readyState == 4) {
        // only if "OK"
        if (req.status == 200) {
            // ...processing statements go here...
//			if (user == 'jwdavidson') alert(XMLResultElementId);
			if (XMLResultElementId) var result_element = element(XMLResultElementId);
			if (XMLBlankElementId) var blank_element = element(XMLBlankElementId);
            if (req.responseText!='IGNORE.NULL') // && req.responseText.match(XMLRequiredKey)
            {
//            	alert(req.responseText);
				if (result_element)	result_element.innerHTML = req.responseText;
				if (blank_element)	blank_element.value='';
			}
//            alert(req.responseText);
         } else {
//            alert("There was a problem retrieving the XML data:\n" +
//                req.statusText);
        }
    }
}


function processReqAddGET() {
 	// only if req shows "loaded"
    if (req.readyState == 4) {
        // only if "OK"
        if (req.status == 200) {
            // ...processing statements go here...
			var result_element = document.getElementById(XMLResultElementId);
			var blank_element = document.getElementById(XMLBlankElementId);
            result_element.innerHTML = req.responseText + result_element.innerHTML;
			blank_element.value='';
         } else {
//            alert("There was a problem retrieving the XML data:\n" +
//                req.statusText);
        }
    }
}

function processReqAppendGET() {
 	// only if req shows "loaded"
    if (req.readyState == 4) {
        // only if "OK"
        if (req.status == 200) {
            // ...processing statements go here...
			var result_element = document.getElementById(XMLResultElementId);
			var blank_element = document.getElementById(XMLBlankElementId);
            result_element.innerHTML = result_element.innerHTML + req.responseText;
			blank_element.value='';
         } else {
//            alert("There was a problem retrieving the XML data:\n" +
//                req.statusText);
        }
    }
}

function loadXMLDoc(url,postData,resultElementId,processingEngine,blankElementId,requiredKey) {
	if (!processingEngine) processingEngine = 'processReqChangeGET';
	if (resultElementId!=null) XMLResultElementId=resultElementId;
	if (blankElementId!=null) XMLBlankElementId=blankElementId;
	else XMLBlankElementId='hidden_response';
	if (requiredKey!=null) XMLRequiredKey=requiredKey;
	else XMLRequiredKey=".?";

	if (postData)
	{
		var method      = 'POST';
		var contentType = 'application/x-www-form-urlencoded';
		var postData    = postData + '&sid=' + sid;
	}
	else
	{
		var method      = 'GET';
		var contentType = 'text/html';
		url = url + "/sid=" + sid;
	}

	if (req) {
		eval("req.onreadystatechange = " + processingEngine + ";");
		req.open(method, url, true);
		req.setRequestHeader('Content-Type', contentType);
		req.send(postData);
	}
}

function setActiveStyleSheet(title) {
   var i, a, main;
   for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
     if(a.getAttribute("rel").indexOf("style") != -1
        && a.getAttribute("title")) {
       a.disabled = true;
       if(a.getAttribute("title") == title) a.disabled = false;
     }
   }
}

// setStyleByClass: given an element type and a class selector,
// style property and value, apply the style.
// args:
//  t - type of tag to check for (e.g., SPAN)
//  c - class name
//  p - CSS property
//  v - value
// from: http://developer.apple.com/internet/webcontent/styles.html
var ie = (document.all) ? true : false;

function setStyleByClass(t,c,p,v){
	var elements;
	if(t == '*') {
		// '*' not supported by IE/Win 5.5 and below
		elements = (ie) ? document.all : document.getElementsByTagName('*');
	} else {
		elements = document.getElementsByTagName(t);
	}
	for(var i = 0; i < elements.length; i++){
		var node = elements.item(i);
		for(var j = 0; j < node.attributes.length; j++) {
			if(node.attributes.item(j).nodeName == 'class') {
				if(node.attributes.item(j).nodeValue.search(c)!=-1) {
					//alert(node.attributes.item(j).nodeValue.search(c));
					eval('node.style.' + p + " = '" +v + "'");
				}
			}
		}
	}
}

function element(id)
{
	return document.getElementById(id);
}

function displayOptions(itemId)
{
	var layerId = 'layer_' + itemId;
	layerToggle(layerId,itemId);
	return false;
}


function swapContent(firstDivId,secondDivId)
{
	var firstDiv      = element(firstDivId);
	var secondDiv     = element(secondDivId);
	var holdingString = ' ';

	holdingString       = firstDiv.innerHTML;
	firstDiv.innerHTML  = secondDiv.innerHTML;
	secondDiv.innerHTML = holdingString;
}


function layerToggle(layerId,buttonId)
{
		var layer=element(layerId);

		if (layer.style.display=='none')
		{
			layerOn(layerId,buttonId);
		}
		else
		{
			layerOff(layerId);
		}
}

function layerOn(layerid,buttonid)
{
	if (document.getElementById)
	{
		var layer  = element(layerid);
		var button = element(buttonid);

		var buttonLeft = getRealLeft(button);
		var buttonTop  = getRealTop(button);
		var windowW    = document.body.offsetWidth;
		var windowH    = document.body.offsetHeight;

		layer.style.top   = (buttonTop + 15) + 'px';


		if (windowW - buttonLeft > 300)
		{
			layer.style.left = (buttonLeft - 2) + 'px';
		}
		else
		{
			layer.style.right = (windowW - buttonLeft - button.offsetWidth + 2) + 'px';
			layer.style.textAlign = 'right';
		}
		setStyleByClass('div','menubox','display','none');
		setStyleByClass('iframe','menubox','display','none');
		layer.style.display='block';
//		alert(layer.style.display);
	}
}

function layerOff(layerid)
{
	if (document.getElementById)
	{
		element(layerid).style.display='none';
		setStyleByClass('div','menubox','display','none');
		setStyleByClass('iframe','menubox','display','none');
	}
}

function getRealLeft(tempEl)
{
	var xPos = 0;
	while (tempEl!=null)
	{
	  xPos += tempEl.offsetLeft;
	  tempEl = tempEl.offsetParent;
	}
	return xPos;
}

function getRealTop(tempEl)
{
	var yPos = 0;
	while (tempEl!=null)
	{
	  yPos += tempEl.offsetTop;
	  tempEl = tempEl.offsetParent;
	}
	return yPos;
}

function isDefined(variable)
{
return (!(!(document.getElementById(variable))))
}

function constrainImages()
{
	var content_size = document.getElementById('content').offsetWidth;
	var ratio        = 1;
	var i            = 0;

	if(isDefined(document.images[i]))
	for (i = 0; i < 10; i++)
	{
		while ( !document.images[i].complete )
		{
			break;
		}

		if ( document.images[i].width > content_size )
		{
			ratio = (content_size-50) / document.images[i].width;
			document.images[i].width  = content_size - 50;
			document.images[i].height = Math.round(ratio * document.images[i].height,0);
		}
	}
}

function setCurrent(id)
{
	document.getElementById('writetab').className='';
	document.getElementById('sendtab').className='';
	document.getElementById('snitchtab').className='';
	document.getElementById('watchedtab').className='';
	if (id && id!='NULL' && id!='') { document.getElementById(id).className='current'; }
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




//------------------------------------------------------------------------------

function DoPrompt(action,fieldId)
{
	if (!fieldId) fieldId='textbox';

	if (action == "url") {
		var thisURL = prompt("Enter the complete URL for the link you wish to add.", "http://");
		if (thisURL == null){return;}
		var thisText = prompt("Optional descriptive text?", "");
		if(thisText==null) { thisText = thisURL; }

		insertTag(fieldId,"<a href='" + thisURL + "'>",thisText + "</a>");
	}

	if (action == "email") {
		var thisURL = prompt("Enter the email address you wish to add.", "");
		if (thisURL == null){return;}
		var thisText = prompt("Optional descriptive text?", "");
		if(thisText==null) { thisText = thisURL; }

		insertTag(fieldId,"<a href='mailto:" + thisURL + "'>",thisText + "</a>");
	}

	if (action == "snoop") {
		var thisURL = prompt("Enter the user you want to snoop.", "");
		if (thisURL == null){return;}
		var thisText = prompt("Optional descriptive text?", "");
		if(thisText!=null) { thisText = ":" + thisText; }

		insertTag(fieldId,"!" + thisURL,thisText + "!");
	}
}

function saveDraft(draftTimePassed)
{
	if (document.getElementById('textbox').value)
		loadXMLDoc('http://' + http_host + web_root + '/scripts/plan_update.php','draft_time=' + draftTimePassed + '&action=Autosave%20Ajax&newplan=' + escape(document.getElementById('textbox').value),'autosave_alert',processReqChangePOST);

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


