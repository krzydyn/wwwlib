<?php
function mssql_errno(&$res){
	return get_last_message($res);
}
class mssql_RecordSet extends RecordSet{
	function setresult($r){
		$this->res=$r;
		$this->rowidx=0;
		$this->numrows=mssql_num_rows($this->res);
		$this->numfields=mssql_num_fields($this->res);
	}
	function fetch($mode=0){
		if ($this->rowidx>=$this->numrows) return null;
		if ($mode==0) $mode=MYSQL_ASSOC;
		else $mode=MYSQL_NUM;
		$this->row=mssql_fetch_array($this->res,$mode);
		if ($this->row) {$this->rowidx++;return $this->row;}
		$this->numrows=rowidx;
		return null;
	}
	function seek($i){
		if ($this->numrows==0) return false;
		$r=mssql_data_seek($this->res,$i);
		if ($r===true) $this->rowidx=$i;
		return $r;
	}
}
class mssql_DB extends DB{
	function __construct() {$this->dbtype="mssql";}
	function connect($h,$u,$p="",$db=""){
		$this->close();
		$this->dbhnd=mssql_connect($h,$u,$p);
		$this->seterr($this->dbhnd);
		if (!$this->dbhnd) return false;
		if (!empty($db)) $this->dbselect($db);
		return true;
	}
	function close() {if ($this->dbhnd) {mssql_close($this->dbhnd);$this->dbhnd=null;}}
	function dbselect($db){
		$this->dbname=$db;
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		$r = mssql_select_db($this->dbname,$this->dbhnd);
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
		$r=mssql_query($this->sql,$this->dbhnd);
		$this->seterr($r);
		if ($r===false) return $r;
		$rs=new mssql_RecordSet();
		if ($r===true) return $rs;
		$rs->setresult($r);
		return $rs;
	}
	function insertid(){
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		return mssql_insert_id($this->dbhnd);
	}
	/**
		return number of rows affected by last query
	*/
	function affected(){
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		return mssql_affected_rows($this->dbhnd);
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
			$this->_errmsg=mssql_errno($this->dbhnd).":".mssql_error($this->dbhnd).
				"<br>".$this->qstr();
		else $this->_errmsg=null;
	}
}
?>
