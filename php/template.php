<?php

function code_start($type="cpp",$id=""){
	if ($id) $id=" id=\"$id\""; else $id="";
	echo "<pre class=\"sh_".$type." code\"".$id.">";
	ob_start();
}
function code_stop(){
	$txt=ob_get_contents();
	ob_end_clean();
	echo strtr($txt,array("<"=>"&lt;",">"=>"&gt;"));
	echo "</pre>";
}
function parseArgs($str){
	$a=array(); $t=array();
	//preg_match_all("#\s*(\w+)\s*=\s*\"([^\"]*)\"#",$str,$t);
	//preg_match_all("#\s*(\w+)\s*=\s*\"([^\"<]*(?:<%(?:.+?)%>)*[^\"]*)\"#s",$t[2],$a);
	preg_match_all("#\s*(\w+)\s*=\s*\"([^\"<]*(?:<%(?:.+?)%>)*[^\"]*)\"#s",$str,$t);
	for ($i=0; $i < sizeof($t[0]); ++$i)
		$a[$t[1][$i]] = $t[2][$i];
	unset($t);
	return $a;
}
function buildArgs($a){
	$str="";
	foreach ($a as $k => $v)
		$str.=" $k=\"".$v."\"";
	return $str;
}
function parseTags($t){
	$c="";
	//printobj("parsetags",$t);
	if (!is_array($t)) return "";
	if (array_key_exists("x",$t)){
		unset($t["x"]);
		for ($i=1; $i<sizeof($t); ++$i)
			$t[$i-1]=$t[$i];
		unset($t[$i]);
	}
	//printobj("parseTags",implode("^",$t));
	//$t[0] - full match
	//$t[1] - tag (type:tagname)
	//$t[2] - args (name="value")
	//$t[3] - text bitween tags
	$args=parseArgs($t[2]);
	if ($t[1]=="sp:"){
		if ($args["n"]) $c.=str_repeat("&nbsp;",$args["n"]);
		else $c.="&nbsp;";
	}
	else if ($t[1]=="br:"){
		if ($args["n"]) $c.=str_repeat("<br/>",$args["n"]);
		else $c.="<br/>";
	}
	else if ($t[1]=="t:list"){
		//printobj("t:list",$args);
		@$prop=$args["property"]; @$id=$args["id"]; @$val=$args["value"];
		unset($args["property"]); unset($args["id"]); unset($args["value"]);
		@$encl=$args["enclose"]; unset($args["enclose"]);

		if ($prop){
			if (!$id) $id="__i";
			if (!$val) $val="__v";
			if (strlen($t[3])==0) $t[3]="<%\$$id%>";
			$t3c=compile_tags($t[3]);
			$pn="\$".strtr($prop,array("."=>"_"));
			$c.="<% $pn=val(\"".$prop."\");if($pn!==false){%>";
			if ($encl) $c.="<$encl".buildArgs($args).">";
			$c.="<%if(is_array($pn)||is_object($pn)){%>";
			$c.="\n<% \$$val"."cnt=0;foreach($pn as \$$id=>\$$val){++\$$val"."cnt;%>";
			$c.=$t3c;
			$c.="<%}}else if (strlen(\$$val=$pn)>0){%>";
			$c.=$t3c;
			$c.="<%}%>";
			if ($encl) $c.="</$encl>";
			$c.="<%}%>";
		}
		else //throw new Exception("no mandatory args for <b>".$t[1]."</b>");
			$c="no mandatory args for <b>".$t[1]."</b>";
		unset($prop);unset($id);unset($val);unset($encl);
	}
	else if ($t[1]=="html:select"){
		//printobj("args",$args);
		@$prop=$args["property"]; @$id=$args["id"]; @$val=$args["value"];
		@$emp=$args["empty"]; @$sel=$args["sel"];
		unset($args["property"]); unset($args["id"]); unset($args["value"]);
		unset($args["empty"]); unset($args["sel"]);
		@$n=$args["name"];
		if (!$n) $n=$prop;
		$args["name"]=$n;
		if ($prop){
			if (!$id) $id="__i";
			if (!$val) $val="__v";
			if (empty($t[3])) $t[3]="<option value=\"<%\$$id%>\"><%\$$val%></option>";
			$aaa=preg_replace("value=(\"[^\"]+\")","\$1",$t[3]);
			$t3c=compile_tags($t[3]);
			$pn="\$".strtr($prop,array("."=>"_"));
			$c.="<% $pn=val(\"".$prop."\");%>";
			$c.="<select".buildArgs($args).">";
			if ($emp) $c.="<option value=\"\">$emp</option>\n";
			$c.="\n<% \$$val"."cnt=0;foreach($pn as \$$id=>\$$val){++\$$val"."cnt;%>";
			$c.=$t3c;
			$c.="\n<%}%>\n</select>";
		}
		else //throw new Exception("no mandatory args for <b>".$t[1]."</b>");
			$c="no mandatory args for <b>".$t[1]."</b>";
		unset($n);unset($id);unset($val);
	}
	else if ($t[1]=="html:checkbox"){
		//printobj("args",$args);
		@$n=$args["name"]; @$idx=$args["index"];
		unset($args["index"]);
		$args["type"]="checkbox";
		//$args["class"]="checkbox";
		if ($n){
			//$args["name"]="$n"."[<%\$$idx%>]";
			$args["name"]="$n";
			$c.="<input".buildArgs($args);
			$c.="<%if(val(\"".$idx."\")!==null)echo \" checked\"%>";
			$c.=">";
		}
		else //throw new Exception("no mandatory args for <b>".$t[1]."</b>");
			$c="no mandatory args for <b>".$t[1]."</b>";
		unset($n);unset($id);unset($val);
	}
	else if ($t[1]=="html:textarea"){
		//printobj("textarea args",$args);
		@$n=$args["property"];
		unset($args["property"]);
		$c.="<textarea".buildArgs($args).">";
		$c.="<%echo html_escape(val(\"$n\"))%>";
		$c.="</textarea>";
		unset($n);
	}
	else if ($t[1]=="t:fckeditor"){
		//printobj("args",$a);
		@$n=$args["name"]; @$sk=$args["skin"]; @$tb=$args["toolbar"];
		@$w=$args["width"]; @$h=$args["height"];
		if (!$w) $w=850;
		if (!$h) $h=400;
		@$cl=$args["class"];
		if ($n){
			$c.="<div class=\"$cl\"";
			if ($w&&$h) $c.="style=\"width:".$w."px;height:".$h."px;\"";
			$c.=">\n";
			$c.="<script type=\"text/javascript\">";
			$vn=strtr($n,array("["=>".","]"=>""));
			$c.="<!--\nvar fck = new FCKeditor('$n','$w','$h','$tb','<%echo js_escape(val(\"$vn\"))%>');\n";
			$c.="fck.BasePath='<%echo js_escape(val(\"cfg.fck\"))%>';\n";
			if ($sk)
				$c.="fck.Config['SkinPath']=fck.BasePath+'editor/skins/$sk/';\n";
			$c.="fck.Config['CustomConfigurationsPath']='<%val(\"rooturl\").val(\"cfg.fckconfig\")%>';
fck.Create();
//-->
</script>
</div>";
		}
		else //throw new Exception("no mandatory args for <b>".$t[1]."</b>");
			$c="no mandatory args for <b>".$t[1]."</b>";
	}
	else if ($t[1]=="gg:status"){
		//styl=2 gives text output
		@$id=$args["id"];unset($args["id"]);
		@$styl=$args["styl"];unset($args["styl"]);
		if ($id){
			$c.="<img src=\"http://www.gadu-gadu.pl/users/status.asp?id=$id";
			if ($styl) $c.="&styl=$styl";
			$c.="\"".buildArgs($args).">";
		}
		unset($id);
	}
	else //throw new Exception("wrong tag <b>".$t[1]."</b>");
		$c="wrong tag <b>".$t[1]."</b>";
	return $c;
}
function parseNoTags($t){
	//$t[0] - full match
	//$t[1..N] - parts of match
	if ($t[3]=="</script") return $t[0];
	$c=$t[1];
	$c.=preg_replace("#([^\w]\w{1,3}) #s","\$1&nbsp;",$t[2]);
	$c.=$t[3];
	return $c;
}
function parseInlines($c){
	if (!is_array($c)) return "";
	$c=$c[1];
	if (substr($c,0,1)=="\$") $c="echo".$c;
	else if (substr($c,0,3)=="val") $c="echo ".$c;
	else if (substr($c,0,4)=="vstr") $c="echo ".$c;
	$c=strtr($c,array(
		">setval"=>">setval",
		//"val"=>"\$this->val",
		"include("=>"\$this->inc("
		));
	$c=preg_replace("#(\\bval[0-9a-zA-z]*)\(#s","\$this->\$1(",$c);
	$c=preg_replace("#(\\bvstr[0-9a-zA-z]*)\(#s","\$this->\$1(",$c);
	return "<?php ".$c."?>";
}
function compile_tags($c){
	//try{
	$x=@preg_replace("#<\\?.*\\?>#s","",$c);
	if ($x!="") $c=$x;
	//$x=@preg_replace("#<>#s","",$c);
	//if ($x!="") $c=$x;
	//printobj("compile_tags",$c);
	$x=@preg_replace_callback("#<(\w+:\w*)\s*(.*?)>(.*?)</\\1>#s","parseTags",$c);
	if ($x!="") $c=$x;
	//$c=@preg_replace_callback("#(?P<x>.?<(\w+:\w*)((.(?!<\\2))|(?P>x))*?.</\\2>)#s","parseTags",$c);
	$x=@preg_replace_callback("#<(\w+:\w*)\s*([^/]*)/>#s","parseTags",$c);
	if ($x!="") $c=$x;
	//$c=@preg_replace_callback("#<(\w+:\w*)\s(.(?!/>))*/>#s","parseTags",$c);
	//}catch(Exception $e) {throw new Exception($e.getMessage());}
	return $c;
}
class TemplateEngine {
	var $req;
	var $cachedir;
	var $compiled=array();
	var $headerdone=false;

	function __construct() {
		self::TemplateEngine();
	}
	function TemplateEngine(){
		global $config;
		$this->req=Request::getInstance();
		$this->cachedir=$config["cachedir"];
		if ($this->cachedir===false) $this->cachedir="cache"; //default
		else if (empty($this->cachedir)) $this->cachedir=null;//no cache
		if (!empty($this->cachedir)){
			if (strpos($this->cachedir,'/') != 0)
				$this->cachedir=$config["rootdir"].$this->cachedir;
		}
	}

	function valExist($n,$def=null) {return $this->req->hasval($n);}
	function val($n,$def=null) {return $this->req->getval($n,$def);}
	function val2class($n,$def=null) {
		return strtr($this->req->getval($n,$def),array("/"=>"_",));
	}
	function vstr2link($v,$def=null) {
		return strtr($v,array("+"=>"%2B",));
	}

	function compile($c){
		$c=strtr($c,array("\n\r"=>"\n","\r\n"=>"\n",
			"<br>"=>"<br/>",
			"<hr>"=>"<hr/>",
			"<table cell"=>"<table cell",
			"<table"=>"<table cellspacing=\"0\" cellpadding=\"0\"" //IE compability
			));
		$c=compile_tags($c);
		$c=preg_replace_callback('#<%(.+?)%>#s', 'parseInlines', $c);
		return $c;
	}
	function compare_moddate($fn1,$fn2){
		return filemtime($fn1)-filemtime($fn2);
	}
	function inline($c){
		ob_start();
		eval("?>".$this->compile($c));
		$c = ob_get_contents();
		ob_end_clean();
		echo $c;
	}
	function inc($fn,$dfn=""){
		global $config;
		if (!($src=searchdir($config["templatedir"],$fn))){
			if (!empty($dfn) && $src=searchdir($config["templatedir"],$dfn)){
				$fn=$dfn;
			}
			else {
				//echo "inc:file $fn not found in any of ".a2str($config["templatedir"])."<br>";
				echo "file $fn not found\n";
				return ;
			}
		}
		//echo "include ".$src."<br>";
		if (is_dir($this->cachedir)){
			$compiled=$this->cachedir.strtr($fn,array("/"=>"_"));
			if (!file_exists($compiled) || $this->compare_moddate($compiled,$src)<0) {
				@unlink($compiled);
				$c=$this->compile(file_get_contents($src));
				//printobj("compilation result",$c);
				file_put_contents($compiled,$c);
			}
			include($compiled);
		}
		else{
			$key=md5($src);
			if (array_key_exists($key,$this->compiled)) $c=$this->compiled[$key];
			else $c=$this->compile(file_get_contents($src));
			eval("?>".$c);
		}
	}
	function headers(){
		if ($this->headerdone) return ;
		$h=$this->val("hdr");
		if (is_array($h)) {
			foreach ($h as $k => $v)
				header($v);
		}
		$this->headerdone=true;
	}
	function load($fn){
		global $config;
		$this->req->setval("srv",null);
		$this->headers();

		if (!searchdir($config["templatedir"],$fn)){
			echo "load:file $fn not found in any of ".a2str($config["templatedir"])."\n";
			//echo "file $fn not found";
			return ;
		}
		$c = ob_get_clean();
		if (!empty($c)) {
			logstr("[len=".strlen($c)."] v='".$c."'");
		}

		logstr("loading template $fn");
		ob_start();
		$this->inc($fn);
		$c = ob_get_clean();

		//$c=preg_replace_callback('#([^/]>)\s*([^<]+)(</)#s','parseNoTags',$c);
		//$c=preg_replace_callback('#(</\w>)([^<]+)(<)#s','parseNoTags',$c);
		//TODO don't process <script>...</script>
		//$c=preg_replace_callback('#(>)([^<]+)(<)#s','parseNoTags',$c);
		//$c=preg_replace_callback('#(<\w[^>]*>)([^<]+)(<[^>]*\w>)#s','parseNoTags',$c);
		echo $c;
		logstr("load template $fn fin");

		//to strip html tags:
		// 1. strip_tags($c);
		/* 2. preg_replace("/<.*?>/", "", $c);*/
		// 2. preg_replace("/<.*?
	}
}
?>
