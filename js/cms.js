//import('/cms/kdcms/js/prototype.js');
//require('/cms/kdcms/js/prototype.js');
/******************************************************
 Usage: $import('../include/mian.js', 'js');
        $import('../style/style.css', 'css');
******************************************************/
function $import(path, type){
	var i,
	base,
	src = "common.js",
	scripts = document.getElementsByTagName("script");

	for (i = 0; i < scripts.length; i++) {
		if (scripts[i].src.match(src)) {
			base = scripts[i].src.replace(src, "");
			break;
		}
	}
 
	if (type == "css") {
		document.write("<" + "link href=\"" + base + path + "\" rel=\"stylesheet\" type=\"text/css\"></" + "link>");
	} else {
		document.write("<" + "script src=\"" + base + path + "\"></" + "script>");
	}
}
function $import2(path,type,title){
	var s,i;
	if(type=="js"){
		var ss=document.getElementsByTagName("script");
		for(i=0;i<ss.length;i++){
			if(ss[i].src && ss[i].src.indexOf(path)!=-1)return;
		}
		s=document.createElement("script");
		s.type="text/javascript";
		s.src=path;
	}else if(type=="css"){
		var ls=document.getElementsByTagName("link");
		for(i=0;i<ls.length;i++){
			if(ls[i].href && ls[i].href.indexOf(path)!=-1)return;
		}
		s=document.createElement("link");
		s.rel="alternate stylesheet";
		s.type="text/css";
		s.href=path;
		s.title=title;
		s.disabled=false;
	}
	else return;
	var head=document.getElementsByTagName("head")[0];
	head.appendChild(s);
}

var getGeoLocation_callback=null;
function getGeoLocation_done(loc){
	alert('geolocation is '+mytoString(loc));
}
function getGeoLocation_error(err){
	alert('geolocation error '+mytoString(err));
	//err.code: 0=unknown, 1=danied, 2=not avail, 3=timeout
}
function getGeoLocation(cbLoc,params){
	if (!navigator.geolocation) {
		alert('geolocation not supported');
		return false;
	}
	var p={ maximumAge: 0, timeout: 30000, enableHighAccuracy: true };
	if (params!=null) p=params;
	navigator.geolocation.getCurrentPosition(getGeoLocation_done,getGeoLocation_error,p);
	return true;
}
function _toString(o,i,t){
	var str='';
	var ind='';
	if (o==null) return 'NULL';
	var tp=typeof o;
	if (i==0) str+=tp+':';
	for (var x=0; x<i; x++) ind+='  ';
	if (tp=='string') str+=o;
	else
	for (var p in o){
		try{
		var v=o[p];
		tp=typeof v;
		if (tp == 'function'){
			//if (t=='f') v=v.toString().gsub('\n','');
			continue;
		}else if (v==null) v='NULL';
		else if (i<1){
			if (tp=='object' && t=='f' && p.indexOf('parent')<0) v='{\n'+_toString(v,i+1,t)+ind+'}';
		}
		str+=ind+tp+':'+p+'='+v+'\n';
		}catch(e){str+='(*Exception* '+o+'.'+p+')'+e+'\n';}
	}
	return str;	
}
function mytoString(o){
	var t='';
	if (arguments.length>1) t=arguments[1];
	return _toString(o,0,t);
}
var g_newform=null;
function packForm(){
	if (!window.opener) return false;
	var e=$('bodytab');
	var w=0,h=0;
	if (!window.opener.packed)
	{
		w+=e.getWidth(); h+=e.getHeight();
		window.opener.packed=true;
	}
	else
	{
		w+=e.getWidth(); h+=e.getHeight();
	}
	//document.write("initial size = <b>"+w+" x "+h+"</b>");
	//if (w<200) w=200;
	//if (h<110) h=110;
	window.moveTo((window.screen.width-w)/2,(window.screen.height-h)/3)
	if (Prototype.Browser.IE)
	{
		w+=30; h+=15;
		//w=Math.floor(w*1.8);
		//h=Math.floor(h*1.5);
		window.resizeTo(w,h+50);
	}
	else window.sizeToContent();
}
//TODO get Elements from one (named) form
function checkall(n,attr){
	var a=document.getElementsByTagName('input');
	//alert('checkall: '+a+' length='+a.length);
	for (var i=0; i<a.length; i++)
	{
		if (a[i].type=='checkbox')
		{
		 	if (a[i].name.substring(0,n.length)==n)
			a[i].checked=attr;
		}
	}
}
function submitForm(){
	var form=document.forms[0];
	for(var i in arguments){
		var a=arguments[i];
		console.log('a['+i+']='+a);
		var fv=a.split('=');
		if (form[fv[0]]) form[fv[0]].value=fv[1];
		else form.action+=a;
	}
	form.submit();
	return false;
}
function formcheck(f,fld){
	var form=document.forms[f];
	var str=form[fld].value;
	var v='';
	for(i = 0; i < str.length; i++){
		if (str.charCodeAt(i)<127) v+=str.substring(i,i+1);
	}
	form['test'].value=md5(v);
	//alert('value=\''+v+'\' : '+form['test'].value);
}
function commit(f,fld){
	formcheck(f,fld);
	document.forms[f].submit();
}
function getCookie(n){
	var cookies = document.cookie.replace(/;\s*/g,';');
	cookies=cookies.split(';');
	for(var i=0; i<cookies.length; i++){
		var nv=cookies[i].split('=');
		if (n==nv[0]) return unescape(nv[1]);
	}
	return null;
}
function page_init(){
	track_anchors();
	//extern_anchors();
}
function extern_anchors(){
	var aobjs=$$('a');
	aobjs.each(function(o,i){
	});
}
function track_anchors(){
	var aobjs=$$('a');
	//console.log('obj='+mytoString(aobjs[0]));
	aobjs.each(function(o,i){
		if (o.onclick == null){
			o.onclick=function(){
			//console.log('clickedOn='+mytoString(o));
			var cat=getCookie('tab');
			/*var lab=o.search;
			if (!lab) {
				lab=o.href;
				if (o.host==document.location.host)
					lab=lab.substring(lab.indexOf(o.host)+o.host.length+1);
				else lab=lab.substring(lab.indexOf('//')+2);
			}
			if (lab.charAt(0)=='?') lab=lab.substring(1);*/
			var lab=o.textContent.strip(); //or o.innetHTML
			if (lab.indexOf(',')>0) lab=lab.substring(0,lab.indexOf(','));
			track(cat,'click',lab,1);
			if (o.getAttribute("class").indexOf('extern')>=0) return !window.open(this.href);
			};
			//console.log('adding onclick for '+o.textContent);
		}
	});
}
function track(cat,act,lab,val){
	console.log('_trackEvent(%s,%s,%s,%d)',cat,act,lab,val);
	if (document.location.host=='localhost') return ;
	var async=false;
	if (async){
		var _gaq = _gaq || [];
		//var a=['_trackEvent'];
		//a.push(arguments);
		_gaq.push(['_trackEvent',cat,act,lab,val]);
	}
	else { //sync
		var tr=_gat._getTracker('UA-34765457-1');
		tr._trackEvent(''+cat,''+act,''+lab,parseInt(val));
	}
}

function setbg(o,c) { $(o).style.backgroundColor=c; }
function setcur(o,c) { $(o).style.cursor=c; }
function getPosition(ev) {
	if (!ev) ev = window.event;
	var posx = Event.pointerX(ev);
	var posy = Event.pointerY(ev);
	//now posx and posy contain the mouse position relative to the document
	return [posx,posy];
}
function containsEvent(element,ev){
	element=$(element);
	var p1=getPosition(ev);
	var p2=Position.cumulativeOffset(element);
	//console.log(mytoString(p1[0]+','+p1[1])+':'+mytoString(p2[0]+','+p2[1]));
	if (p1[0]<p2[0] || p1[1]<p2[1]) return false;
	p2[0]+=element.getWidth();
	p2[1]+=element.getHeight();
	//console.log(mytoString(p1[0]+','+p1[1])+':'+mytoString(p2[0]+','+p2[1]));
	if (p1[0]>p2[0] || p1[1]>p2[1]) return false;
	//console.log(mytoString($(element).firstChild));
	//console.log('true');
	return true;
}
/*
Note also that the parameters of resizeTo() have different meaning in different browsers:
in Internet Explorer the parameters specify the outer size of the window,
while in Netscape Navigator they refer to the inner size (which does not include the window borders,
toolbar, status bar, title bar, and the address line). To resize the window to the same outer size
in both browsers, you can use this function:
*/
function resizeOuterTo(w,h) {
 if (parseInt(navigator.appVersion)>3) {
   if (navigator.appName=="Netscape") {
    top.outerWidth=w;
    top.outerHeight=h;
   }
   else top.resizeTo(w,h);
 }
}



