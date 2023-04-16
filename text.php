<?php
function js_escape($txt){
	if (!isset($txt)) return "";
	return strtr($txt,array("'"=>"\\'","\r\n"=>"\\n","\r"=>"\\n","\n"=>"\\n"));
}
function url_escape($txt){
	if (!isset($txt)) return "";
	return strtr($txt,array("&"=>"&amp;"));
}
function html_escape($txt){
	if (!isset($txt)) return "";
	return strtr($txt,array("<"=>"&lt;",">"=>"&gt;"));
}
function html_fromBB($txt){
	$txt=html_escape($txt);
	$txt=preg_replace("/\\[url=([^] ]*)([^]]*)\\](.*?)\\[\\/url\\]/i","<a\$2 target=\"_blank\" href=\"\$1\">\$3</a>",$txt);
	$txt=preg_replace("/\\[img([^]]*)\\](.*?)\\[\\/img\\]/i","<a target=\"_blank\" href=\"\$2\"><img\$1 src=\"\$2\"/></a>",$txt);
	$txt=preg_replace("/\\[b([^]]*)\\](.*?)\\[\\/b\\]/i","<b\$1>\$2</b>",$txt);
	$txt=preg_replace("/\\[s([^]]*)\\](.*?)\\[\\/s\\]/i","<strike\$1>\$2</strike>",$txt);
	return $txt;
}
function quote_escape($txt){
	if (!isset($txt)) return "";
	return strtr($txt,array("\""=>"&quot;"));
}
static $charset=array(
		"ISO-8859-1" =>"AaCcEeLlNnOoSsZzZz",
		"ISO-8859-2" =>"\xa1\xb1\xc6\xe6\xca\xea\xa3\xb3\xd1\xf1\xd3\xf3\xa6\xb6\xaf\xbf\xac\xbc",
		"WIN-1250"   =>"\xa5\xb9\xc6\xe6\xca\xea\xa3\xb3\xd1\xf1\xd3\xf3\x8c\x9c\xaf\xbf\x8f\x9f",
		"SagemPP"   =>"\x80\xa0\x81\xa1\x82\xa2\x83\xa3\x84\xa4\x85\xa5\x86\xa6\x87\xa7\x88\xa8",
	);

function translate($to,$from,$str) {
	global $charset;
	if ($from=="WIN1250-ms") $_from=$charset["WIN1250"];
	else $_from=$charset[$from];
	$_to=$charset[$to];
	if (!$_from || !$_to) return $str."{can't translate}";
  $str=strtr($str,$_from,$_to);
	if ($from=="WIN1250-ms") $str=strtr($str,"\x84\x93\x94\x96\xa0","\"\"\" \"");
	return $str;
}
function checkEncoding($str,$enc) {
	return mb_detect_encoding($str, $enc, true);
	//return false;
}
function translate_1250tohtml($str) {
//"\xa5"=>"¡","\xb9"=>"±","\x8c"=>"¦","\x9c"=>"¶","\x8f"=>"¬","\x9f"=>"¼",
	$str=strtr($str,array(
		"\x99"=>"&trade;","\xae"=>"&reg;")); //,"\x??"=>"&ndash;"
	return $str;
}
function strvis($s)
{
	$t=""; $l=strlen($s);
  for ($i=0; $i<$l; $i++)
  {
  	$r=unpack("C",substr($s,$i,1)); $r=$r[1];
  	if ($r<=0x20||$r>=0x7f) $t.=sprintf("<%02X>",$r);
  	else $t.=pack("C",$r);
  }
  return $t;
}
function strhex($s)
{
	$t=""; $l=strlen($s);
  for ($i=0; $i<$l; $i++)
  {
  	$r=unpack("C",substr($s,$i,1)); $r=$r[1];
  	$t.=sprintf("%02X",$r);
  }
  return $t;
}
?>
