// Common functions for strings

String.prototype.trim=function() {
	return this.replace(/^\s*|\s*$/g,'');
}

String.prototype.ltrim=function() {
	return this.replace(/^\s*/g,'');
}

String.prototype.rtrim=function() {
	return this.replace(/\s*$/g,'');
}

String.prototype.htmlspecialchars=function() {
	ch = this;
	ch = ch.replace(/&/g, "&amp;");
	ch = ch.replace(/\"/g, "&quot;");
	ch = ch.replace(/\'/g, "&#039;");
	ch = ch.replace(/</g, "&lt;");
	ch = ch.replace(/>/g, "&gt;");
	return ch;
}

$ = function(param) { return document.getElementById(param); }

// Chat variables
var eventqueue = [];
var eventproc = 0;
var req = null;
var XMLreqpos = 0;

function DoQuery(url, query) {
	if (window.XMLHttpRequest) { // Non-IE browsers
		req = new XMLHttpRequest();
		req.onreadystatechange = DoDataTrigger;
		try {
			req.open("POST", url, true);
			req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		} catch (e) {
			alert(e);
		}
		req.send(query);
	} else if (window.ActiveXObject) { // IE
		req = new ActiveXObject("Microsoft.XMLHTTP");
		if (req) {
			req.onreadystatechange = DoDataTrigger;
			req.open("POST", url, true);
			req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			req.send(query);
		}
	}
}

function DoDataTrigger() {
	if (req.readyState == 2) {
		if (req.status != 200) {
			if (eventqueue.length <= 0) {
				eventproc = 0;
			} else {
				revent = eventqueue.shift();
				RunEvent(revent);
			}
		}
	}
	if (req.readyState == 3) { // partial
		pos2 = XMLreqpos;
		while(pos2 != -1) { pos = pos2; pos2 = req.responseText.indexOf('JSCOMMIT', pos2+1); }
		if (pos == -1) return;
		pos += 8;
		try {
			eval(req.responseText.substr(XMLreqpos, pos-XMLreqpos));
		} catch(e) {
			return; // do not update XMLreqpos
		}
		XMLreqpos = pos;
	}
	if (req.readyState == 4) { // Complete
		if (req.status == 200) { // OK response
			try {
				eval(req.responseText.substr(XMLreqpos));
			} catch(e) {
				alert(e);
				alert(req.responseText);
			}
		}
		if (eventqueue.length <= 0) {
			eventproc = 0;
		} else {
			revent = eventqueue.shift();
			RunEvent(revent);
		}
	}
}

function RunEvent(xevent) {
	// basic idea is : encode the event and pass it to PHP :)
	xevent = xevent.toSource();
	xevent = escape(xevent);
	xevent = xevent.replace(/\+/g, '%2b');
	query = "cmd=" + xevent;
	XMLreqpos = 0;
	DoQuery("test.php", query);
}

window.setTimeout(QueueTimeout, 10000);
function QueueTimeout() {
	window.setTimeout(QueueTimeout, 10000);
	QueueTrigger();
}

function AddEvent(eventname, param) {
	xevent = [ eventname, param ];
	eventqueue.push(xevent);
	QueueTrigger();
}

function QueueTrigger() {
	if (eventproc > 0) {
		return;
	}
	if (eventqueue.length <= 0) {
		return;
	}
	eventproc = 1; // lock to prevent concurrent calls to QueueTrigger
	revent = eventqueue.shift();
	RunEvent(revent);
}

