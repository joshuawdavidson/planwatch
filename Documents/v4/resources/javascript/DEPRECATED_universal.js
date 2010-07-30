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