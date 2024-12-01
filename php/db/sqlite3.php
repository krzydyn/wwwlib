<?php
class sqlite3_RecordSet extends RecordSet{
	function setresult($r){
		$this->res=$r;
		$this->rowidx=0;
		$this->numrows=0;
		$this->numfields=$this->res->numColumns();
		if ($this->numfields>0) $this->numrows=1;
	}
	function fetch($mode=0){
		if ($this->rowidx>=$this->numrows) return null;
		if ($mode==FETCH_ASSOC) $mode=SQLITE3_ASSOC;
		else $mode=SQLITE3_NUM;
		$this->row=$this->res->fetchArray($mode);
		if ($this->row) {++$this->rowidx;++$this->numrows;return $this->row;}
		return null;
	}
	function seek($i){
		if ($this->numrows==0) return false;
		if ($i==0) $r=$this->res->reset();
		else $r=$this->res->seek($i); // ?? not in sqlite3
		if ($r===true) $this->rowidx=$i;
		return $r;
	}
}
class sqlite3_DB extends DB{
	function __construct() {$this->dbtype="sqlite3";}
	function connect($h,$u,$p="",$db=""){
		$this->close();
		$this->dbhnd=new SQLite3($db);
		return true;
	}
	function close() {if ($this->dbhnd) {$this->dbhnd->close();$this->dbhnd=null;}}
	function dbselect($db){
		$this->dbhnd->close();
		$this->dbname=$db;
		$this->dbhnd->open($db);
		return true;
	}
	function dbcreate($db){return $this->dbselect($db);}
	function &query($q, $params=array()){
		global $config;
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		$q=trim($q); $r=true;
		if (empty($q)) return $r;

		if (sizeof($params) > 0) {
			$this->sql=$q."('".implode("','",$params)."')";
			$stmt = $this->dbhnd->prepare($q);
			foreach ($params as $k => $v)
				$stmt->bindValue($k, $v);
			unset($k);unset($v);
			$r=$stmt->execute();
		}
		else {
			$this->sql=$q;
			$r=@$this->dbhnd->query($q);
		}
		$this->seterr($r);
		if ($r===false) return $r;
		$rs=new sqlite3_RecordSet();
		if ($r===true) return $rs;
		$rs->setresult($r);
		return $rs;
	}
	function insertid(){
		if (!$this->dbhnd) {$this->_errmsg="not connected";return false;}
		return $this->dbhnd->lastInsertRowID();
	}
	function affected() {
		return $this->dbhnd->changes();
	}
	function tables() {
		$r=$this->query("select tbl_name from sqlite_master where type='table'");
		if ($r===false) return false;
		$tabs=array();
		while ($row=$r->fetch(FETCH_NUM)) $tabs[]=$row[0];
		return $tabs;
	}
	function describe($t) {
		$r=$this->query("select sql from sqlite_master where type='table' and tbl_name='".$t."'");
		if ($r===false) return false;
		$row=$r->fetch(FETCH_NUM);
		$row=$row[0];
		$row=substr($row,strpos($row,"(")+1);
		$row=substr($row,0,strrpos($row,")"));
		$row=explode(",",$row);
		$f=array();
		for ($i=0; $i<sizeof($row); ++$i){
			$l=explode(" ",$row[$i],2);
			$f[$l[0]]=$l[1];
		}
		return $f;
	}
	private function seterr($r){
		if ($r===false){
			$r=$this->dbhnd->lastErrorCode();
			$this->_errmsg=$r.":".$this->dbhnd->lastErrorMsg()." '".$this->qstr()."'";
			logstr($this->_errmsg);
		}
		else $this->_errmsg=null;
	}
}
?>
