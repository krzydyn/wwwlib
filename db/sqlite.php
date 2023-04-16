<?php
class sqlite_RecordSet extends RecordSet{
	function setresult($r){
		$this->res=$r;
		$this->rowidx=0;
		$this->numrows=sqlite_num_rows($this->res);
		$this->numfields=sqlite_num_fields($this->res);
	}
	function fetch($mode=0){
		if ($this->rowidx>=$this->numrows) return null;
		if ($mode==0) $mode=SQLITE_ASSOC;
		else $mode=SQLITE_NUM;
		$this->row=sqlite_fetch_array($this->res,$mode);
		if ($this->row) {$this->rowidx++;return $this->row;}
		$this->numrows=$this->rowidx;
		return null;
	}
	function seek($i){
		if ($this->numrows==0) return false;
		if ($i==0) $r=sqlite_rewind($this->res);
		else $r=sqlite_seek($this->res,$i);
		if ($r===true) $this->rowidx=$i;
		return $r;
	}
}
class sqlite_DB extends DB{
	function __construct() {
		$this->dbtype="sqlite";
		if (strncmp(phpversion(),"7.",2)>=0) {
			throw new Exception("use sqlite3");
		}
	}
	function connect($h,$u,$p="",$db=""){
		if (!empty($db)) $this->dbselect($db);
		return true;
	}
	function close() {if ($this->dbhnd) {sqlite_close($this->dbhnd);$this->dbhnd=null;}}
	function dbselect($db){
		$this->close();
		$this->dbname=$db;
		$this->dbhnd=sqlite_open($db,0666);
		$this->seterr($this->dbhnd);
		if (!$this->dbhnd) return false;
		return true;
	}
	function dbcreate($db){return $this->dbselect($db);}
	function &query($q){
		global $config;
		$q=trim($q);
		if (empty($q)) return true;
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		$this->sql=$q;
		if (array_getval($config,"debug.query")=="y") printobj("query",$this->sql);
		$r=@sqlite_query($this->sql,$this->dbhnd);
		$this->seterr($r);
		if ($r===false) return $r;
		$rs=new sqlite_RecordSet();
		if ($r===true) return $rs;
		$rs->setresult($r);
		return $rs;
	}
	function insertid(){
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		return sqlite_last_insert_rowid($this->dbhnd);
	}
	/**
		return number of rows affected by last query
	*/
	function affected(){
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		return sqlite_changes($this->dbhnd);
	}
	function tables(){
		$r=$this->query("select tbl_name from sqlite_master where type='table'");
		if ($r===false) return false;
		$tabs=array();
		while ($row=$r->fetch(FETCH_NUM)) $tabs[]=$row[0];
		return $tabs;
	}
	function describe($t){
		$r=$this->query("select sql from sqlite_master where type='table' and tbl_name='".$t."'");
		if ($r===false) return false;
		$row=$r->fetch(FETCH_NUM);
		$row=$row[0];
		$row=substr($row,strpos($row,"(")+1);
		$row=substr($row,0,strrpos($row,")"));
		$row=explode(",",$row);
		$f=array();
		for ($i=0; $i<sizeof($row); ++$i){
			$l=explode(" ",trim($row[$i]),2);
			if (sizeof($l) == 1) $l[1]="";
			$f[trim($l[0])]=trim($l[1]);
		}
		return $f;
	}
	private function seterr($r){
		if ($r===false){
			$r=sqlite_last_error($this->dbhnd);
			$this->_errmsg=$r.":".sqlite_error_string($r).
				"<br>".$this->qstr();
		}
		else $this->_errmsg=null;
	}
}
?>
