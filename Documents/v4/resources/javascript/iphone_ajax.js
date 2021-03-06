var req;var XMLResultElementId='';var XMLBlankElementId='';var XMLEditBoxId='';var XMLRequiredKey='';var redirectLocation='';var d=new Date();setInterval("watched_list_refresh();",120001);try{req=new XMLHttpRequest();}catch(trymicrosoft){try{req=new ActiveXObject("Msxml2.XMLHTTP");}catch(othermicrosoft){try{req=new ActiveXObject("Microsoft.XMLHTTP");}catch(failed){req=false;}}}

function watched_list_refresh()
{
	if (document.getElementById('key'))
		var key=document.getElementById('key').innerHTML;
	else var key='1';
	loadXMLDoc('http://' + http_host + '/' + web_root + 'watched/' + user + '/ajax/' + key + '/' + d.getTime(),null,'planwatch','processReqChangeGET',null,'Watched Plans');
	setTimeout("watched_list_refresh();",120001);
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
 //               req.statusText);
        }
    }
}

function processReqChangeGET() {
 	// only if req shows "loaded"
    if (req.readyState == 4) {
        // only if "OK"
        if (req.status == 200) {
            // ...processing statements go here...
//			alert(XMLResultElementId);
			var result_element = document.getElementById(XMLResultElementId);
//			var blank_element = document.getElementById(XMLBlankElementId);
            if (req.responseText!='IGNORE.NULL' && req.responseText.match(XMLRequiredKey))
            {
	            result_element.innerHTML = req.responseText;
//				blank_element.value='';
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

function setCurrent(id)
{
	document.getElementById('writetab').className='';
	document.getElementById('sendtab').className='';
	document.getElementById('snitchtab').className='';
	document.getElementById('watchedtab').className='';
	document.getElementById('toolstab').className='';
	document.getElementById('viewtab').className='';
	if (id && id!='NULL' && id!='') { document.getElementById(id).className='current'; }
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

(function() {

var slideSpeed = 20;
var slideInterval = 0;

var currentPage = null;
var currentDialog = null;
var currentWidth = 0;
var currentHash = location.hash;
var hashPrefix = "#_";
var pageHistory = [];
var newPageCount = 0;
var checkTimer;

// *************************************************************************************************

window.iui =
{
    showPage: function(page, backwards)
    {
        if (page)
        {
            if (currentDialog)
            {
                currentDialog.removeAttribute("selected");
                currentDialog = null;
            }

            if (hasClass(page, "dialog"))
                showDialog(page);
            else
            {
                var fromPage = currentPage;
                currentPage = page;

                if (fromPage)
                    setTimeout(slidePages, 0, fromPage, page, backwards);
                else
                    updatePage(page, fromPage);
            }
        }
    },

    showPageById: function(pageId)
    {
        var page = $(pageId);
        if (page)
        {
            var index = pageHistory.indexOf(pageId);
            var backwards = index != -1;
            if (backwards)
                pageHistory.splice(index, pageHistory.length);

            iui.showPage(page, backwards);
        }
    },

    showPageByHref: function(href, args, method, replace, cb)
    {
        var req = new XMLHttpRequest();
        req.onerror = function()
        {
            if (cb)
                cb(false);
        };
        
        req.onreadystatechange = function()
        {
            if (req.readyState == 4)
            {
                if (replace)
                    replaceElementWithSource(replace, req.responseText);
                else
                {
                    var frag = document.createElement("div");
                    frag.innerHTML = req.responseText;
                    iui.insertPages(frag.childNodes);
                }
                if (cb)
                    setTimeout(cb, 1000, true);
            }
        };

        if (args)
        {
            req.open(method || "GET", href, true);
            req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            req.setRequestHeader("Content-Length", args.length);
            req.send(args.join("&"));
        }
        else
        {
            req.open(method || "GET", href, true);
            req.send(null);
        }
    },
    
    insertPages: function(nodes)
    {
        var targetPage;
        for (var i = 0; i < nodes.length; ++i)
        {
            var child = nodes[i];
            if (child.nodeType == 1)
            {
                if (!child.id)
                    child.id = "__" + (++newPageCount) + "__";

                var clone = $(child.id);
                if (clone)
                    clone.parentNode.replaceChild(child, clone);
                else
                    document.body.appendChild(child);

                if (child.getAttribute("selected") == "true" || !targetPage)
                    targetPage = child;
                
                --i;
            }
        }

        if (targetPage)
            iui.showPage(targetPage);    
    },

    getSelectedPage: function()
    {
        for (var child = document.body.firstChild; child; child = child.nextSibling)
        {
            if (child.nodeType == 1 && child.getAttribute("selected") == "true")
                return child;
        }    
    }    
};

// *************************************************************************************************

addEventListener("load", function(event)
{
    var page = iui.getSelectedPage();
    if (page)
        iui.showPage(page);

    setTimeout(preloadImages, 0);
    setTimeout(checkOrientAndLocation, 0);
    checkTimer = setInterval(checkOrientAndLocation, 300);
}, false);
    
addEventListener("click", function(event)
{
    var link = findParent(event.target, "a");
    if (link)
    {
        function unselect() { link.removeAttribute("selected"); }
        
        if (link.href && link.hash && link.hash != "#")
        {
            link.setAttribute("selected", "true");
            iui.showPage($(link.hash.substr(1)));
            setTimeout(unselect, 500);
        }
        else if (link == $("backButton"))
            history.back();
        else if (link.getAttribute("type") == "submit")
            submitForm(findParent(link, "form"));
        else if (link.getAttribute("type") == "cancel")
            cancelDialog(findParent(link, "form"));
        else if (link.target == "_replace")
        {
            link.setAttribute("selected", "progress");
            iui.showPageByHref(link.href, null, null, link, unselect);
        }
        else if (!link.target)
        {
            link.setAttribute("selected", "progress");
            iui.showPageByHref(link.href, null, null, null, unselect);
        }
        else
            return;
        
        event.preventDefault();        
    }
}, true);

addEventListener("click", function(event)
{
    var div = findParent(event.target, "div");
    if (div && hasClass(div, "toggle"))
    {
        div.setAttribute("toggled", div.getAttribute("toggled") != "true");
        event.preventDefault();        
    }
}, true);

function checkOrientAndLocation()
{
    if (window.innerWidth != currentWidth)
    {   
        currentWidth = window.innerWidth;
        if (currentWidth==320) var orient="profile"; else var orient="landscape";
        document.body.setAttribute("orient", orient);
        setTimeout(scrollTo, 100, 0, 1);
    }

    if (location.hash != currentHash)
    {
        var pageId = location.hash.substr(hashPrefix.length);
        iui.showPageById(pageId);
    }
}

function showDialog(page)
{
    currentDialog = page;
    page.setAttribute("selected", "true");
    
    if (hasClass(page, "dialog") && !page.target)
        showForm(page);
}

function showForm(form)
{
    form.onsubmit = function(event)
    {
        event.preventDefault();
        submitForm(form);
    };
    
    form.onclick = function(event)
    {
        if (event.target == form && hasClass(form, "dialog"))
            cancelDialog(form);
    };
}

function cancelDialog(form)
{
    form.removeAttribute("selected");
}

function updatePage(page, fromPage)
{
    if (!page.id)
        page.id = "__" + (++newPageCount) + "__";

    location.href = currentHash = hashPrefix + page.id;
    pageHistory.push(page.id);

    var pageTitle = $("pageTitle");
    if (page.title)
        pageTitle.innerHTML = page.title;

    if (page.localName.toLowerCase() == "form" && !page.target)
        showForm(page);
        
    var backButton = $("backButton");
    if (backButton)
    {
        var prevPage = $(pageHistory[pageHistory.length-2]);
        if (prevPage && !page.getAttribute("hideBackButton"))
        {
            backButton.style.display = "inline";
            backButton.innerHTML = prevPage.title ? prevPage.title : "Back";
        }
        else
            backButton.style.display = "none";
    }    
}

function slidePages(fromPage, toPage, backwards)
{        
    var axis = (backwards ? fromPage : toPage).getAttribute("axis");
    if (axis == "y")
        (backwards ? fromPage : toPage).style.top = "100%";
    else
        toPage.style.left = "100%";

    toPage.setAttribute("selected", "true");
    scrollTo(0, 1);
    clearInterval(checkTimer);
    
    var percent = 100;
    slide();
    var timer = setInterval(slide, slideInterval);

    function slide()
    {
        percent -= slideSpeed;
        if (percent <= 0)
        {
            percent = 0;
            if (!hasClass(toPage, "dialog"))
                fromPage.removeAttribute("selected");
            clearInterval(timer);
            checkTimer = setInterval(checkOrientAndLocation, 300);
            setTimeout(updatePage, 0, toPage, fromPage);
        }
    
        if (axis == "y")
        {
            backwards
                ? fromPage.style.top = (100-percent) + "%"
                : toPage.style.top = percent + "%";
        }
        else
        {
            fromPage.style.left = (backwards ? (100-percent) : (percent-100)) + "%"; 
            toPage.style.left = (backwards ? -percent : percent) + "%"; 
        }
    }
}

function preloadImages()
{
    var preloader = document.createElement("div");
    preloader.id = "preloader";
    document.body.appendChild(preloader);
}

function submitForm(form)
{
    iui.showPageByHref(form.action || "POST", encodeForm(form), form.method);
}

function encodeForm(form)
{
    function encode(inputs)
    {
        for (var i = 0; i < inputs.length; ++i)
        {
            if (inputs[i].name)
                args.push(inputs[i].name + "=" + escape(inputs[i].value));
        }
    }

    var args = [];
    encode(form.getElementsByTagName("input"));
    encode(form.getElementsByTagName("select"));
    return args;    
}

function findParent(node, localName)
{
    while (node && (node.nodeType != 1 || node.localName.toLowerCase() != localName))
        node = node.parentNode;
    return node;
}

function hasClass(self, name)
{
    var re = new RegExp("(^|\\s)"+name+"($|\\s)");
    return re.exec(self.getAttribute("class")) != null;
}

function replaceElementWithSource(replace, source)
{
    var page = replace.parentNode;
    var parent = replace;
    while (page.parentNode != document.body)
    {
        page = page.parentNode;
        parent = parent.parentNode;
    }

    var frag = document.createElement(parent.localName);
    frag.innerHTML = source;

    page.removeChild(parent);

    while (frag.firstChild)
        page.appendChild(frag.firstChild);
}

function $(id) { return document.getElementById(id); }
function ddd() { console.log.apply(console, arguments); }

})();
