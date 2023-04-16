<?php
define('FETCH_ASSOC',0);
define('FETCH_NUM',1);

function sql_escape($s){
	$tr=array("'"=>"''");
	return strtr($s,$tr);
}

abstract class RecordSet{
	var $res=null;
	var $numrows=0;
	var $rowidx=0;
	var $numfields=0;
	abstract function setresult($r);
	abstract function fetch($mode=FETCH_ASSOC);
	function nextrow() { return $this->fetch(); }
}
abstract class DB{
	var $dbtype=null;
	var $dbname=null;
	var $dbhnd=null;
	var $sql=null;//last query
	var $_errmsg;

	abstract function connect($h,$u,$p="",$db="");
	abstract function close();
	abstract function dbselect($db);
	abstract function dbcreate($db);
	abstract function &query($q);
	//if you use a transaction you should use lastInsertId BEFORE you commit otherwise it will return 0
	abstract function insertid();
	abstract function affected();
	abstract function tables();
	abstract function describe($t);

	function errmsg(){return $this->_errmsg;}
	function qstr(){return $this->sql;}
	function script($s){
		$a = explode(";",$s);
		for ($i=0; $i<sizeof($a); ++$i) {
			if ($this->query($a[$i])===false) return false;
		}
		return true;
	}
	function tabcreate($tab,$def){
		return $this->query("CREATE TABLE ".$tab."(".$def.")");
	}
	function tabdrop($tab){
		return $this->query("DROP TABLE ".$tab);
	}
	function tabinsert($tab,$row){
		$fields=array();
		$values=array();
		foreach ($row as $f => $v) {
			if ($v===null) continue;
			if ($v==="") $v="''";
			else if (is_numeric($v)) ;
			else $v="'".sql_escape($v)."'";
			$fields[]=$f; $values[]=$v;
		}
		$r=$this->query("INSERT INTO ".$tab." (".implode(",",$fields).")VALUES(".implode(",",$values).")");
		if ($r===false) return false;
		return $this->insertid();
	}
	function tabupdate($tab,$row,$fmt,$a=null){
		$set="";
		foreach ($row as $f => $v) {
			if ($v===null) continue;
			if ($v==="") $v="''";
			else if (is_numeric($v)) ;
			else $v="'".sql_escape($v)."'";
			$set.=$f."=".$v.",";
		}
		if ($set=="") {echo "set list empyty<br>";return false;}
		$set=substr($set,0,-1);
		if ($this->query("update ".$tab." set ".$set.DB::buildfmt($fmt,$a))===false) return false;
		return $this->affected();
	}
	function tabdelete($tab,$fmt,$a=null){
		if ($this->query("delete from ".$tab.DB::buildfmt($fmt,$a))===false) return false;
		return $this->affected();
	}
	function tabcount($tab,$fmt=null,$a=null){
		$r=$this->query("select count(*) from ".$tab.DB::buildfmt($fmt,$a));
		if ($r==null) return false;
		$row=$r->fetch(FETCH_NUM);
		return $row[0];
	}
	function &tabfind($tab,$fld="*",$fmt=null,$a=null){
		if (!$fld) $fld="*";
		$r=$this->query("select $fld from ".$tab.DB::buildfmt($fmt,$a));
		if ($r==null) {$r=false;return $r;}
		$a=array();
		while ($row=$r->fetch()) $a[]=$row;
		return $a;
	}
	function tabalter($tab,$c){
		return $this->query("alter table ".$tab." ".$c);
	}
	function addcolumn($tab,$cdef,$before=""){
		$desc=$this->describe($tab);
		if ($desc===false) return false;
		$row=explode(",",$cdef);
		$cdef=array();
		foreach ($desc as $f => $v) {
			if ($f==$before){
				for ($i=0; $i<sizeof($row); ++$i)
					$cdef[]=$row[$i];
				$row=null;
			}
			$cdef[]=$f." ".$v;
		}
		if ($row!=null){
			for ($i=0; $i<sizeof($row); ++$i)
				$cdef[]=$row[$i];
		}

		$r=$this->query("select * from ".$tab);
		$a=array();
		while ($row=$r->fetch()) $a[]=$row;
		$this->tabdrop($tab);
		$this->tabcreate($tab,implode(",",$cdef));
		for ($i=0; $i<sizeof($a); ++$i) $this->tabinsert($tab,$a[$i]);
	}
	function tabdump($tab) {
		$desc=$this->describe($tab);
		$s="CREATE TABLE $tab (";
		foreach ($desc as $f => $v) {
			$s.= "$f $v,";
		}
		$s = substr($s,0,-1).");";
		logstr($s);
		$r=$this->query("select * from ".$tab);
		while ($row=$r->fetch()) {
			$s="INSERT INTO $tab VALUES ('";
			foreach ($row as $v) {
				if (checkEncoding($v,"UTF-8")==false)
					$v=iconv("ISO-8859-2","UTF-8",$v);
				$s.=sql_escape($v)."','";
			}
			$s = substr($s,0,-3);
			$s.="');";
			logstr($s);
		}
	}
	function dump() {
		$tabs = $this->tables();
		foreach ($tabs as $t) {
			$this->tabdump($t);
		}
	}
	/*
	 '#fld' in fmt will be replaced with $a[fld]
	*/
	static function buildfmt($fmt,$a){
		if ($fmt==null) return "";
		if ($a==null) return " ".$fmt;
		$tr=array("\\#"=>"#");
		foreach ($a as $f => $v) {
			if ($v=="") $v="''";
			else if (is_numeric($v)) ;
			else $v="'".sql_escape($v)."'";
			$tr["#".$f]=$v;
		}
		return " ".	strtr($fmt,$tr);
	}

	static function &getDriver($driver=null){
		global $config;
		if (empty($driver)) $driver=$config["dbtype"];
		include_once($config["lib"]."db/".$driver.".php");
		$c=$driver."_DB";
		$c=new $c();
		return $c;
	}
	static function &connectDefault(){
		global $config;
		$dbh=DB::getDriver();
		if ($dbh){
			@$host=$config["dbhost"];
			@$user=$config["dbuser"];
			@$pass=$config["dbpasswd"];
			$dbname=$config["dbname"];
			$dbh->connect($host,$user,$pass,$dbname);
		}
		return $dbh;
	}
}
?>
