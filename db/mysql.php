<?php
class mysql_RecordSet extends RecordSet{
	function setresult($r){
		$this->res=$r;
		$this->rowidx=0;
		$this->numrows=mysql_num_rows($this->res);
		$this->numfields=mysql_num_fields($this->res);
	}
	function fetch($mode=0){
		if ($this->rowidx>=$this->numrows) return null;
		if ($mode==0) $mode=MYSQL_ASSOC;
		else $mode=MYSQL_NUM;
		$this->row=mysql_fetch_array($this->res,$mode);
		if ($this->row) {$this->rowidx++;return $this->row;}
		$this->numrows=$this->rowidx;
		return null;
	}
	function seek($i){
		if ($this->numrows==0) return false;
		$r=mysql_data_seek($this->res,$i);
		if ($r===true) $this->rowidx=$i;
		return $r;
	}
}
class mysql_DB extends DB{
	function __construct() {$this->dbtype="mysql";}
	function connect($h,$u,$p="",$db=""){
		$this->close();
		$this->dbhnd=mysql_connect($h,$u,$p);
		$this->seterr($this->dbhnd);
		if (!$this->dbhnd) return false;
		if (!empty($db)) $this->dbselect($db);
		return true;
	}
	function close() {if ($this->dbhnd) {mysql_close($this->dbhnd);$this->dbhnd=null;}}
	function dbselect($db){
		$this->dbname=$db;
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		$r = mysql_select_db($this->dbname,$this->dbhnd);
		$this->seterr($r);
		if (!$r) return false;
		return true;
	}
	function dbcreate($db){
		return $this->query("create database ".$db);
	}
	function &query($q,$f=-1,$n=-1){
		global $config;
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		$limit="";
		if ($n>=0){
			$limit=" limit ";
			if ($f>=0) $limit.=$f;
			if ($n>=0) $limit.=",".$n;
		}
		$this->sql=$q.$limit;
		if (array_getval($config,"debug.query")=="y") printobj("query",$this->sql);
		$r=mysql_query($this->sql,$this->dbhnd);
		$this->seterr($r);
		if ($r===false) return $r;
		$rs=new mysql_RecordSet();
		if ($r===true) return $rs;
		$rs->setresult($r);
		return $rs;
	}
	function insertid(){
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		return mysql_insert_id($this->dbhnd);
	}
	/**
		return number of rows affected by last query
	*/
	function affected(){
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		return mysql_affected_rows($this->dbhnd);
	}
	function tables(){
		$r=$this->query("show tables");
		if ($r===false) return false;
		$tabs=array();
		while ($row=$r->fetch(FETCH_NUM)) $tabs[]=$row[0];
		return $tabs;
	}
	private function seterr($r){
		if ($r===false)
			$this->_errmsg=mysql_errno($this->dbhnd).":".mysql_error($this->dbhnd).
				"<br>".$this->qstr();
		else $this->_errmsg=null;
	}
}
function mysql_password($passStr) {
        $nr=0x50305735;
        $nr2=0x12345671;
        $add=7;
        $charArr = preg_split("//", $passStr);

        foreach ($charArr as $char) {
                if (($char == '') || ($char == ' ') || ($char == '\t')) continue;
                $charVal = ord($char);
                $nr ^= ((($nr & 63) + $add) * $charVal) + ($nr << 8);
                $nr &= 0x7fffffff;
                $nr2 += ($nr2 << 8) ^ $nr;
                $nr2 &= 0x7fffffff;
                $add += $charVal;
        }

        return sprintf("%08x%08x", $nr, $nr2);
}
?>
