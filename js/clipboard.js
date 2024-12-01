// Copyright (C) krikkit - krikkit@gmx.net
// --> http://www.krikkit.net/
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

//http://www.krikkit.net/howto_javascript_copy_clipboard.html

function copyText(theId) {
   var obj=document.getElementById(theId);
   obj.focus();
   //obj.select();
   copyToClipboard(obj.value);
}
function copyObj(id){
	id=$(id);
	if (document.createRange) {
	    // IE9 and modern browsers
	    var r = document.createRange();
	    r.setStartBefore(id);
	    r.setEndAfter(id);
	    r.selectNode(id);
	    var sel = window.getSelection();
	    sel.addRange(r);
	    document.execCommand('Copy');  // does nothing on FF
	} else {
	   alert('old browser');
	    // IE 8 and earlier.  This stuff won't work on IE9.
	    // (unless forced into a backward compatibility mode,
	    // or selecting plain divs, not img or table).
	    var r = document.body.createTextRange();
	    r.moveToElementText(id);
	    r.select()
	    r.execCommand('Copy');
	}
}
function copyToClipboard(txt) {
 if (window.clipboardData) {
   window.clipboardData.setData("Text", txt);
 }
 else if (window.netscape) {
   netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');

   var clip = Components.classes['@mozilla.org/widget/clipboard;1']
                 .createInstance(Components.interfaces.nsIClipboard);
   if (!clip) return;

   var trans = Components.classes['@mozilla.org/widget/transferable;1']
                  .createInstance(Components.interfaces.nsITransferable);
   if (!trans) return;
   trans.addDataFlavor('text/unicode');
   var str = new Object();
   //var len = new Object();

   var str = Components.classes["@mozilla.org/supports-string;1"]
                .createInstance(Components.interfaces.nsISupportsString);

   var copytext=txt;
   str.data=copytext;
   trans.setTransferData("text/unicode",str,copytext.length*2);
   var clipid=Components.interfaces.nsIClipboard;
   if (!clip) return false;
   clip.setData(trans,null,clipid.kGlobalClipboard);
  }
  alert("Following info was copied to your clipboard:\n\n" + txt);
  return false;
}
