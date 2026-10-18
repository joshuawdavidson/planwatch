var req;var XMLResultElementId='';var XMLBlankElementId='';var XMLEditBoxId='';var XMLRequiredKey='';var redirectLocation='';var d=new Date();setInterval("watched_list_refresh();",120001);try{req=new XMLHttpRequest();}catch(trymicrosoft){try{req=new ActiveXObject("Msxml2.XMLHTTP");}catch(othermicrosoft){try{req=new ActiveXObject("Microsoft.XMLHTTP");}catch(failed){req=false;}}}

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
	
	if (element(itemId) == null)
	{
//		alert("no " + itemId);
	}

	if (element(layerId) == null)
	{
		var parentDiv = element('menu_holder');
		parentDiv.innerHTML = "<div http://www.tvtorrents.com/TorrentLoaderServlet?info_hash=class='menubox' id='" + layerId + "'>Option 1</div>";
	}

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

		if (layer.style.display=='block')
		{
			layerOff(layerId);
		}
		else
		{
			layerOn(layerId,buttonId);
		}
}

function refresh(id,uri)
{
	if (element(id)) loadXMLDoc('http://' + http_host + web_root + uri,null,id,'processReqChangeGET',null,' ');
}

function sendMessage()
{
	var sender = element('sender').value;
	var recipient = element('recipient').value;
	var sendmessage = element('sendmessage').value;
	var requestData = "action=send&ajax=1&sender=" + sender + "&recipient=" + recipient + "&sendmessage=" + escape(sendmessage);
	
	loadXMLDoc('http://' + http_host + web_root + '/scripts/send.php',requestData,'send_div','processReqChangePOST','sendmessage');
	
//	setTimeout("element('sendmessage').value='';",1250);
}

function addNewEntry()
{
	var date     = new Date();
	var timecode = Math.round(date.getTime() / 1000);
	loadXMLDoc('http://' + http_host + web_root + "/entry/ajax_new/" + timecode,null,'plan_body','processReqAddGET',null);
	setTimeout("swapContent('editLayer_' + " + timecode + ",'entry_content_' + " + timecode + ");",500);
}


function displayEditLayer(timecode)
{
	resultId           = 'editLayer_' + timecode;
//	document.getElementById(XMLEditBoxId).innerHTML = 'http://' + http_host + web_root + "/entry/editbox/." + timecode;
	loadXMLDoc('http://' + http_host + web_root + "/entry/editbox/." + timecode,null,resultId,null,processReqChangeGET,"textarea");
	swapContent('editLayer_' + timecode,'entry_content_' + timecode);
}

function sendToFullEditor(timecode,privacy,nolinebreaks,writer)
{
	XMLEditBoxId           = 'editBox_' + timecode;
	XMLResultElementId     = 'entry_content_' + timecode;

	var box                = document.getElementById(XMLEditBoxId);
	var planData           = box.value;
	var entryDiv           = document.getElementById(XMLResultElementId);
	var requestData        = "action=Save%20Draft%20Ajax&private=" + privacy + "&nolinebreaks=" + nolinebreaks + "&edit=." + timecode + "&writer=" + writer + "&newplan=" + escape(planData);

	redirectLocation       = 'http://' + http_host + web_root + '/write/draft/.' + timecode + '/' + privacy + '/' + nolinebreaks + '/' + writer;

//	alert(editorLocation);
	swapContent(XMLResultElementId, 'editLayer_' + timecode);
	entryDiv.innerHTML = "Saving Draft... ";
	loadXMLDoc('http://' + http_host + web_root + "/scripts/plan_update.php", requestData,'entry_content_' + timecode,'processReqChangePOST',null);
	setTimeout("window.location.href=redirectLocation;",500);
}

function updatePlan(timecode,privacy,nolinebreaks,writer)
{
	XMLEditBoxId           = 'editBox_' + timecode;
	XMLResultElementId     = 'entry_content_' + timecode;

	var box                = document.getElementById(XMLEditBoxId);
	var planData           = box.value;
	var entryDiv           = document.getElementById(XMLResultElementId);
	var requestData        = "action=Update%20Journaling%20Plan%20Ajax&private=" + privacy + "&nolinebreaks=" + nolinebreaks + "&edit=." + timecode + "&writer=" + writer + "&newplan=" + escape(planData);

	swapContent(XMLResultElementId, 'editLayer_' + timecode);
	entryDiv.innerHTML = "Processing Update... ";
	loadXMLDoc('http://' + http_host + web_root + "/scripts/plan_update.php", requestData,'entry_content_' + timecode,'processReqChangePOST',null);
}

function watched_list_refresh()
{
	if (document.getElementById('key'))
		var key=document.getElementById('key').innerHTML;
	else var key='1';
	loadXMLDoc('http://' + http_host + '/' + web_root + 'watched/' + user + '/ajax/' + key + '/' + d.getTime(),null,'planwatch','processReqChangeGET',null,'Watched Plans');
//	setTimeout("watched_list_refresh();",120001);
}

function send_refresh()
{
	var sender = element('sender').value;
	var recipient = element('recipient').value;
	var sendmessage = element('sendmessage').value;
	var requestData = "action=refresh&ajax=1&sender=" + sender + "&recipient=" + recipient;
	
	loadXMLDoc('http://' + http_host + web_root + '/scripts/send.php',requestData,'send_div','processReqChangePOST',null);

//	setTimeout("send_refresh();",9757);
}

function list_move(wlpos)
{
//	alert("list_move " + wlpos);
//	loadXMLDoc('http://' + http_host + web_root + "/lists/move/watched/" + wlpos,null,'hidden_response');
	var planwatch = document.getElementById('planwatch');
	var content   = document.getElementById('content');

	if (wlpos=='top')
	{
		planwatch.style.position = 'relative';
		planwatch.style.width    = 'auto';

		content.style.position   = 'relative';
		content.style.width      = 'auto';
	}

	if (wlpos=='left')
	{
		planwatch.style.position = 'absolute';
		planwatch.style.left     = '0px';
		planwatch.style.width    = '170px';

		content.style.position   = 'absolute';
		content.style.left       = '175px';
		content.style.right      = '0px';
	}

	if (wlpos=='right')
	{
		planwatch.style.position = 'absolute';
		planwatch.style.right    = '0px';
		planwatch.style.width    = '170px';

		content.style.position   = 'absolute';
		content.style.right      = '175px';
		content.style.left       = '0px';
	}

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
		loadXMLDoc('http://' + http_host + web_root + "/slogans/write_ajax/" + currentId + '/' + slogan,null,XMLResultElementId,'',null);
	}
}


function slogans_modify_one_rating(slogan_index,direction)
{
	loadXMLDoc('http://' + http_host + web_root + "/slogans/mod/" + slogan_index + "/" + direction,null,'slogan_rating','',null);
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
	}
	else
	{
		var method      = 'GET';
		var contentType = 'text/html';
	}

	if (req) {
		eval("req.onreadystatechange = " + processingEngine + ";");
		req.open(method, url, true);
		req.setRequestHeader('Content-Type', contentType);
		req.send(postData);
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

function constrainImages()
{
	var content_size = document.getElementById('content').offsetWidth;
	var ratio        = 1;
	var i            = 0;

	if(document.images[i])
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