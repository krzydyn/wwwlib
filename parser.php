<?php
require_once("text.php");

class BlockDef{
	function BlockDef($t=""){if ($t) $this->blocks[]=$t;}
	var $blocks=array();
}
class FuncDef{
	var $name;
	var $type; //return type
	var $mods; //function modifiers
	var $args="";//function args
	var $block=null;
}
class ClassDef{
	var $name;
	var $mods; //class modifiers
	var $parents=null;
	var $interfaces=null;
	var $vars=array();
	var $funcs=array();
}
define('WHITE_IGNORE',0);
define('WHITE_TOKEN',1);
abstract class Parser{
	var $cont;
	var $idx,$len;
	var $ln;
	var $mode=WHITE_IGNORE;
	var $blocks=array();
	var $build_depth;
	var $max_build_depth;

	function iswhite($c){
		//echo "ord($c)=".ord($c)."<br>";
		$c=ord($c);
		if ($this->mode==WHITE_IGNORE) return $c<=0x20;
		return $c<=0x20 && $c!=0x0a && $c!=0xc;
	}
	function isspec($c){
		return strpos("{}[]<>().,;:?'|-+*&^%#@!~`=/\\\n\r\"",$c)!==false;
	}
	function isspec_concat($c){
		//return strpos("{}[]<>().,;:?'|-+*&^%#@!~`=/\\\n\r",$c)!==false;
		return $this->isspec($c);
	}
	function gettoken(){
		$token="";
		for(;$this->idx<$this->len;$this->idx++){
			$c=substr($this->cont,$this->idx,1);
			if ($c=="\n") $this->ln++;
			if (!$this->iswhite($c)) break;
			$token.=$c;
		}
		if ($this->mode==WHITE_TOKEN && !empty($token)) return $token;
		if ($this->idx>=$this->len) return null;
		$this->idx++;
		if ($c=="\\") return $c.substr($this->cont,$this->idx++,1);
		if ($this->isspec($c)) return $c;
		$token=$c;
		for(;$this->idx<$this->len;$this->idx++){
			$c=substr($this->cont,$this->idx,1);
			if ($c=="\n") $this->ln++;
			if ($this->iswhite($c)||$this->isspec($c)) break;
			$token.=$c;
		}
		return $token;
	}
	abstract function parse_impl();
	abstract function build_impl();

	function parse_citation($st,$en=null){
		if ($en==null) $en=$st;
		$m=$this->mode;
		$this->mode=WHITE_TOKEN;
		$tok=$st;
		//echo "Parser::parse_citation(st=$st,en=$en)<br>";
		while (($t=Parser::gettoken())!=null){
			$tok.=$t;
			if ($t==$en) break;
		}
		$this->mode=$m;
		//echo "Parser::parse_citation stopped on $t<br>";
		return $tok;
	}
	function parse($cont){
		//printobj("parse",$cont);
		$this->cont=&$cont;
		$this->idx=0; $this->len=strlen($this->cont);
		$this->ln=0;
		$this->parse_impl();
	}
	function build(){
		$this->len=0;
		$this->build="";
		$this->build_depth=0;
		$this->max_build_depth=0;
		return $this->build_impl();
	}
	function concat($t1,$t2){
		if ($t1=="") return $t2;
		$x1=substr($t1,-1); $x2=substr($t2,0,1);
		if ($this->isspec_concat($x1) || $this->isspec_concat($x2)) return $t1.$t2;
		return $t1." ".$t2;
	}
	function build_block(&$f){
		if (!is_object($f)) return $f;
		if (sizeof($f->blocks)==0) return $b;
		//if (sizeof($f->blocks)==1) return $this->build_block($f->blocks[0]);
		$b.="{";
		$this->build_depth++;
		if ($this->max_build_depth<$this->build_depth) $this->max_build_depth=$this->build_depth;
		//echo "depth=".$this->build_depth."<br>\n";
		for ($i=0; $i<sizeof($f->blocks); $i++){
			$b.=$this->build_block($f->blocks[$i]);
		}
		$b.="}\n";
		$this->build_depth--;
		return $b;
	}
}
?>
