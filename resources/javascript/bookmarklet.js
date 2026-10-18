/*
 * javascript:void((function(){var%20e=document.createElement('script');e.setAttribute('type','text/javascript');e.setAttribute('src','http://planwatch.org/includes/'+new%20Date().getTime+'bookmarklet.js'());document.body.appendChild(e)})())
 */
(function(){
	var host = "http://planwatch.org";
	var options;
	var findImgs = function(offset){
		offset = offset||0;
		//no youbute imgs, thx
		if(window.location.href.match(/^http:\/\/www.youtube.com/) && window.location.href.match(/\?/)) {
			sendFrameMessage({youtube: true});
			return;	
		}
		var imgs = document.getElementsByTagName('img');
		var tosend = [];
		var logo;
		for (var i=offset;i<imgs.length;i++){
			img = imgs[i];
			if (img.offsetHeight > 20 && img.offsetWidth > 20 && tosend.length < offset+20) {
				var ratio = img.offsetWidth/img.offsetHeight;
				if (ratio < 0) ratio = img.offsetHeight/img.offsetWidth;
				if (ratio < 3) tosend.push(img.src);
				else if (img.src.match("logo")&&!logo){
					tosend.push(img.src);
					logo = true;
				}
			}
		}
		sendFrameMessage({imgs: tosend.join(";;")});
	};
	var fetchSelection = function(){
				var selection;
				if (window.getSelection) selection = '' + window.getSelection();
				else if (document.selection) selection = document.selection.createRange().text;
		if (!selection && typeof getElementsByTagNameAndClass != "undefined" && getElementsByTagNameAndClass('div', 'watch-video-desc')[1]) {
			var container = getElementsByTagNameAndClass('div', 'watch-video-desc')[1];
			if (container) {
				for(var i=0;i<container.childNodes.length;i++) {
					var child = container.childNodes[i];
					if (child.tagName && child.tagName.toLowerCase() == 'span') selection = child.innerHTML;
				}
			}
		}
		return selection || '';
	};
	var embeds = document.getElementsByTagName('embed');
	var objs = document.getElementsByTagName('object');

	var toggle = function(els, hide) {
		for (var i=0; i < els.length; i++) els[i].style.visibility = hide?'hidden':'visible';
	};
	toggle(embeds, true);
	toggle(objs, true);

		var bookmarklet = function() {
				if ($("bookmarklet_body")) return;

		options = {
			iframeUrl: host+'/add_feed/v2',
			offsets: {
				x: 0,
				y: -100
			},
			containerStyles: {
				position: 'absolute',
				top: scrollPos().y+'px',
				border: "3px solid #ccc",
				background: '#ccc',
				zIndex: 100000,
				'text-align': 'left'
			},
			foregroundStyles: {
				backgroundColor: "white",
				zIndex: 2,
				width: "600px",
				height: "325px"
			}
		};

		var selection = fetchSelection();
		var container = createElement();
		container.id = "bookmarklet_body";
		setStyles(container, options.containerStyles);

		var foreground = createElement("div", container);
		foreground.id = "bookmarklet_fg";
		setStyles(foreground, options.foregroundStyles);

		foreground.innerHTML = '<iframe frameborder="0" id="bookmarklet_iframe" scrolling="no" style="position:relative;top:0px;left:0px;width:100%;height:100%;border:0px;padding:0px;margin:0px"></iframe>';

		var closeLink = createElement('img');
		closeLink.src = host+'/images/tiny_cancel.gif';
		setStyles(closeLink, {
			position: 'relative',
			left: '554px',
			cursor: 'pointer',
			margin:"2px 0px 0px 0px",
			width: "33px",
			height: "5px"
		});
		
		container.appendChild(closeLink);
		document.body.appendChild(container);
		
				
		var msg = {
						title: document.title,
						link: location.href.split("#")[0],
						description: trim(tidy(selection || ''))
				};
		if (window.location.href.match(/^http:\/\/www.youtube.com/) && window.location.href.match(/\?/)) {
			var vals = parseQuery(window.location.href.split("?")[1].split("#")[0], false, false);
			if (vals['v']) {
				msg.link = "http://www.youtube.com/watch?v=" + vals["v"];
				msg.type = 5
				msg.title = document.title.replace("YouTube - ", "")
			}
		}
		sendFrameMessage(msg, true);
		window.onscroll = function() {
			center(container);
		};
		window.onresize = function(){
			center(container);
		};
		center(container);
		addEvent(window, "keydown", function(e){
			if (!e.keyCode == 27) return;
			close();
		});
		addEvent(closeLink, 'click', close);
	};
	var close = function(){
		remove($("bookmarklet_body"));
		toggle(embeds, false);
		toggle(objs, false);
	};
	var parseQuery = function(str, encodeKeys, encodeValues) {
		encodeKeys = encodeKeys=="undefined"?true:encodeKeys;
		encodeValues = encodeValues=="undefined"?true:encodeValues;
		var vars = str.split(/[&;]/);
		var rs = {};
		for (var i=0;i<vars.length;i++) {
			val = vars[i];
			var keys = val.split('=');
			if (keys.length && keys.length == 2) {
				rs[(encodeKeys) ? encodeURIComponent(keys[0]) : keys[0]] = (encodeValues) ? encodeURIComponent(keys[1]) : keys[1];
			}
		};
		return rs;
	};
	var trim = function(str) {
		return str.replace(/^\s+|\s+$/g, '')
	};
		var tidy = function(str) {
		var txt = str.toString();
		var chars = {
			"[\xa0\u2002\u2003\u2009]": " ",
			"\xb7": "*",
			"[\u2018\u2019]": "'",
			"[\u201c\u201d]": '"',
			"\u2026": "...",
			"\u2013": "-",
			"\u2014": "--",
			"\uFFFD": "&raquo;"
		};
		for(var i in chars) {
			if (!chars.hasOwnProperty(i)) continue;
			txt = txt.replace(new RegExp(i, 'g'), chars[i]);
		}
		return txt;
	};
	var addEvent = function(element, name, fn) {
				var fnCopy = fn;
				if (element.addEventListener) {
						element.addEventListener(name, fnCopy, false);
				} else if (element.attachEvent) {
						fnCopy = function() {
								fn(window.event);
						};
						element.attachEvent("on" + name, fnCopy);
				} else {
						throw new Error("Event registration not supported");
				}
				return {
						element: element,
						name: name,
						fn: fnCopy
				};
		};
	
		var removeEvent = function(event) {
				var element = event.element;
			if (element.removeEventListener) element.removeEventListener(event.name, event.fn, false);
				else if (element.detachEvent) element.detachEvent("on" + event.name, event.fn);
		};
		
	var cancelEvent = function(e) {
				if (!e) e = window.event;
			if (e.preventDefault) e.preventDefault();
				else e.returnValue = false;
	 	};

	var scrollPos = function() {
				if (self.pageYOffset !== undefined) {
						return {
								x: self.pageXOffset,
								y: self.pageYOffset
						};
				}
				var d = document.documentElement;
				return {
						x: d.scrollLeft,
						y: d.scrollTop
				};
	};
		var setScrollPos = function(pos) {
				var e = document.documentElement,
				b = document.body;
				e.scrollLeft = b.scrollLeft = pos.x;
				e.scrollTop = b.scrollTop = pos.y;
		};
	var getOffset = function(obj) {
				var curleft = 0;
				var curtop = 0;
				if (obj.offsetParent) {
						curleft = obj.offsetLeft;
						curtop = obj.offsetTop;
						while (obj = obj.offsetParent) {
								curleft += obj.offsetLeft;
								curtop += obj.offsetTop;
						}
				}
				return {
						left: curleft,
						top: curtop
				};
		};
	var windowDim = function(){
		var w = 0, h = 0;
		if( typeof( window.innerWidth ) == 'number' ) {
			//Non-IE
			w = window.innerWidth;
			h = window.innerHeight;
		} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
			//IE 6+ in 'standards compliant mode'
			w = document.documentElement.clientWidth;
			h = document.documentElement.clientHeight;
		} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
			//IE 4 compatible
			w = document.body.clientWidth;
			h = document.body.clientHeight;
		}
		return {width: w, height: h};
	};
	var winScroll = function(){
		function f_filterResults(n_win, n_docel, n_body) {
			var n_result = n_win ? n_win : 0;
			if (n_docel && (!n_result || (n_result > n_docel)))
				n_result = n_docel;
			return n_body && (!n_result || (n_result > n_body)) ? n_body : n_result;
		};
		return {
			left: f_filterResults (
				window.pageXOffset ? window.pageXOffset : 0,
				document.documentElement ? document.documentElement.scrollLeft : 0,
				document.body ? document.body.scrollLeft : 0
			),
			top: f_filterResults (
				window.pageYOffset ? window.pageYOffset : 0,
				document.documentElement ? document.documentElement.scrollTop : 0,
				document.body ? document.body.scrollTop : 0
			)
		};
	};
	var center = function(element, offsets) {
		offsets = offsets||options.offsets||{x: 0, y: 0};
		var pos = getOffset(element);
		var win = windowDim();
		var scroll = winScroll();
		var dim = {
			width: element.offsetWidth,
			height: element.offsetHeight
		};
		setStyles(element, {
			top: (scroll.top + win.height/2 - dim.height/2 + offsets.y) + "px",
			left: (scroll.left + win.width/2 - dim.width/2 + offsets.x) + "px"
		});
	};
		var empty = function(node) {
				while (node.firstChild) {
			node.removeChild(node.firstChild);
				}
	};
		var remove = function(node) {
				if (node && node.parentNode) node.parentNode.removeChild(node);
		};
	var createElement = function(tagName, injectInside) {
				var e = document.createElement(tagName||"div");
				e.style.padding = "0";
				e.style.margin = "0";
				e.style.border = "0";
				e.style.position = "relative";
				if (injectInside) injectInside.appendChild(e);
				return e;
	};
	var setOpacity = function(element, opacity) {
				if (navigator.userAgent.indexOf("MSIE") != -1) {
						var normalized = Math.round(opacity * 100);
						element.style.filter = "alpha(opacity=" + normalized + ")";
				} else {
						element.style.opacity = opacity;
				}
		};
		var $ = function(id){
				return document.getElementById(id);
		};
		var setStyles = function(element, styles) {
			for(var i in styles){
				if (!styles.hasOwnProperty(i)) continue;
				element.style[i] = styles[i];
			}
		};
		var sendFrameMessage = function(m, load) {
				var p = "";
				for (var i in m) {
						if (!m.hasOwnProperty(i)) continue;
						p += (p.length ? '&': '');
						p += encodeURIComponent(i) + '=' + encodeURIComponent(m[i]);
				}
				var iframe;
				if (navigator.userAgent.indexOf("Safari") != -1) {
						iframe = frames["bookmarklet_iframe"];
				} else {
						iframe = $("bookmarklet_iframe").contentWindow;
				}
				if (!iframe) return;
				url = options.iframeUrl;
		if (!url.match(/^http/)) url = options.iframeUrl;
		if (load && url.indexOf("?")>=0) url += "&"+p;
		else if (load) url += "?"+p;
		else url += "#"+p;
		if (load) options.iframeUrl = url;
				try {
						iframe.location.replace(url);
				} catch(e) {
						iframe.location = url; // safari
				}
		};
		var gCurScroll = scrollPos();
		var checkForFrameMessage = function() {
				var prefix = "Bookmarklet-";
				var hash = location.href.split('#')[1]; // location.hash is decoded
				if (!hash || hash.substring(0, prefix.length) != prefix) {
						gCurScroll = scrollPos(); // save pos
						return;
				}
				var msg = hash.split('-');
				window.location.replace(window.location.href.split("#")[0] + "#");
				msgMethods[msg[1]](msg[2]);
				var pos = gCurScroll;
				setScrollPos(pos);
				setTimeout(function() { setScrollPos(pos); },10);
		};
		setInterval(checkForFrameMessage, 25);
		var msgMethods = {
			close: function(){
				close();
			},
			resize: function(h){
				$("bookmarklet_fg").style.height = h + "px";
			},
			fetchImgs: function(offset) {
				findImgs(offset);
			},
			fetchSelection: function(){
				sendFrameMessage({
					description: trim(tidy(fetchSelection()))
				});
			}
		};
	
		bookmarklet();
})();