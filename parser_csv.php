<?php
require_once("parser.php");
class ParserCSV extends Parser{
	function gettoken(){
		$t=parent::gettoken();
		if ($t==null) return null;
		if ($t=="\"") return $this->parse_citation($t);
		return $t;
	}
	function parse_impl(){
		//echo "ParserCSV::parse_impl()<br>";
		$this->mode=WHITE_TOKEN;
		$rec=array();
		$v=null;
		while (($t=$this->gettoken())!==null){
			if ($t=="\n"){
				//echo "Token is EOL<br>";
				$rec[]=$v;$v=null;
				$this->blocks[]=$rec;
				$rec=array();
			}
			else if ($t==","){
				//echo "Token is COMA<br>";
				$rec[]=$v;$v=null;
			}
			else if ($v===null) $v=$t;
			else $v.=$t;
		}
		if ($v!==null) $rec[]=$v;
		if(sizeof($rec)>0) $this->blocks[]=$rec;
	}
	function build_impl(){
	}
}
?>
