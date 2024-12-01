function isFunction(functionToCheck) {
   var getType = {};
   return functionToCheck && getType.toString.call(functionToCheck) === '[object Function]';
}

function Ajax() {
	var r=null;
	if (window.XMLHttpRequest)
		// code for IE7+, Firefox, Chrome, Opera, Safari
		r = new XMLHttpRequest();
	else
		// old IE
		r = new ActiveXObject("Microsoft.XMLHTTP");
	r.mozBackgroundRequest = true; //hide dialog window
	this.req=r;
}

Ajax.prototype.async = function(method,url,onResponse,tag) {
	var load_elem = $('loading');
	if (load_elem) {
		load_elem.style.display='inline-block';
	}

	//var that=this;
	method=method.toUpperCase();
	logw('AJAX '+method+':'+url);
	//open(method,url,async,user,passwd)
	this.req.open(method, url, true);

	this.req.onprogress = function (ev) { //XMLHttpRequestProgressEvent
		//ev.loaded, ev.total
		//console.log('onprogress');console.log(ev);
	}
	this.req.onreadystatechange = function (ev) { //Event
    	var r=ev.target;
		//console.log('onreadystatechange '+r.readyState);console.log(ev);
		if (r.readyState==r.HEADERS_RECEIVED) {
			//console.log('headers:'+r.getAllResponseHeaders());
			return ; //continue
		}
		if (r.readyState==r.OPENED || r.readyState==r.LOADING) {
			return ; //continue
		}
		var rc,tx;
		if (r.readyState!=r.DONE) {
			rc=-1; tx='Ajax internal error';
		}
		else if (r.status==0) {
			rc=-1; tx='Network error';
		}
		else {
			rc=r.status; tx=r.responseText;
		}

		if (load_elem) load_elem.style.display='none';
		//r.status==401 - login/password incorrect
		if (isFunction(onResponse)) onResponse(rc, tx, tag);
	};
	//this.req.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	//this.req.setRequestHeader("Authorization", "Basic " + base64(username) + ':' + base64(password));
	try {
		this.req.send();
	} catch(e) {
		if (load_elem) load_elem.style.display='none';
		console.log('exception:'+JSON.stringify(e));
		if (isFunction(onResponse)) onResponse(-1, e.toString(), tag);
	}
}
