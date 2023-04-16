<?php
//Serach: \n[ \t]*{

class ModelObject{
	function __construct() {
		self::ModelObject();
	}
	function ModelObject(){
		$cl=get_class($this);
		$fld=get_class_vars($cl);
		foreach ($fld as $f => $v) {
			if (!isset($this->{$f})) $this->{$f}=null;
		}
	}
	function defaultOrder(){return "";}
	function clear(){
		$cl=get_class($this);
		$fld=get_class_vars($cl);
		foreach ($fld as $f => $v) $this->{$f}=null;
	}
	function &copy(){
		$cl=get_class($this);
		$o=new $cl();
		$fld=get_object_vars($o);
		foreach ($fld as $f => $v) {
			if (array_key_exists($f,$this)) $o->{$f}=$this->{$f};
			else unset($o->{$f});
		}
		return $o;
	}
	function isValid(){
		return $this->getID()!="";
	}
	function toString() {return $this->getID();}
	function getClass() {return get_class($this);}
	function tabname() {return strtolower(get_class($this));}
	function setValues(&$a){
		if(is_array($a)){
			$fld=get_class_vars(get_class($this));
			foreach ($fld as $f => $v) {
				if (array_key_exists($f,$a)) $this->{$f}=$a[$f];
			}
			//printobj("setValues",$this);
		}
	}
	/**
		get assoc array(F=>V) for dao representaion
	*/
	function &getValues(&$a=null){
		$fld=get_object_vars($this);
		if (is_array($a)){
			foreach ($fld as $f => $v) {
				if (array_key_exists($f,$a)) $a[$f]=$this->{$f};
			}
		}
		else{
			$a=array();
			foreach ($fld as $f => $v) {
				if (substr($f,0,1)=="_") continue; //internal field
				$a[$f]=$v;
			}
		}
		//printobj("getValues",$a);
		return $a;
	}

	/**
		get fields array for dao representaion
	*/
	function &getFields(){
		$a=array();
		$fld=get_object_vars($this);
		foreach ($fld as $f => $v) {
			if (substr($f,0,1)=="_") continue; //internal field
			$a[]=$f;
		}
		return $a;
	}
	/**
		get array of primary key fields
	*/
	function getPK(){return array("id"=>null);}
	/**
		get [concatenated] value of primary key fields
	*/
	function getID(){
		$pk=$this->getPK();
		$this->getValues($pk);
		return implode(":",$pk);
	}
}
class Criteria {
	var $crit;
	var $val=array();
	var $limit;
	var $order;
	var $groupby;
	function __construct($f=null,$v=null,$op="=") {
		self::Criteria($f,$v,$op);
	}
	function Criteria($f=null,$v=null,$op="="){
		if (!empty($f)) $this->addop($f,$v,$op);
	}
	function setLimit($l){$this->limit=$l;}
	function setOrder($o){$this->order=$o;}
	function setGroup($g){$this->groupby=$g;}
	function clear() { $this->crit=$this->limit=$this->order=""; $this->val=array();}
	function hasValue($v){
		return in_array($v,$this->val);
	}
	function addfv($fld,$v=null){
		if (is_array($fld)){
			foreach ($fld as $f => $v)
				$this->val[$f]=$v;
		}
		else $this->val[$fld]=$v;
	}
	function addop($fld,$v=null,$op="=",$type="and"){
		if (is_array($fld)){
			$op=$v?$v:"=";
			foreach ($fld as $f => $v) {
				$this->val[$f]=$v;
				$this->add($f.$op."#".$f, $type);
			}
		}
		else {
			$f = $fld;
			$this->val[$f]=$v;
			$this->add($f.$op."#".$f,$type);
		}
	}
	function add($expr,$type="and"){
		if (!$expr) return $this;
		if ($this->crit=="") $this->crit=$expr;
		else $this->crit.=" ".$type." ".$expr;
		return $this;
	}
	function get(){
		$s=$this->crit?"where ".$this->crit:"";
		if ($this->groupby) $s.=" group by ".$this->groupby;
		if ($this->order) $s.=" order by ".$this->order;
		if ($this->limit) $s.=" limit ".$this->limit;
		return DB::buildfmt($s,$this->val);
	}
}
class ObjectDB {
	private $_errmsg;
	var $db;
	function __construct(&$db) {
		self::ObjectDB($db);
	}
	function ObjectDB(&$db){$this->db=$db; $this->_errmsg=false;}

	function save(&$o){
		if (!($o instanceof ModelObject)) {$this->_errmsg="ModelObject required";return false;}
		if (!$o->getID()) return $this->insert($o);
		return $this->update($o);
	}
	function insert(&$o){
		$this->_errmsg=false;
		if (!($o instanceof ModelObject)) {$this->_errmsg="ModelObject required";return false;}
		$tab=strtolower(get_class($o));
		$row=$o->getValues();
		$r=$this->db->tabinsert($tab,$row);
		if ($r===false) return $r;
		if (array_key_exists("id",$o)) $o->id=$r;
		return $r;
	}
	function update(&$o){
		$this->_errmsg=false;
		if (!($o instanceof ModelObject)) {$this->_errmsg="ModelObject required";return false;}
		$tab=strtolower(get_class($o));
		$row=$o->getValues();
		$fld=$o->getPK(); $o->getValues($fld);
		$crit=new Criteria($fld);
		return $this->db->tabupdate($tab,$row,$crit->get());
	}
	function del(&$o,&$crit=null){
		$this->_errmsg=false;
		if (!($o instanceof ModelObject)) {$this->_errmsg="ModelObject required";return false;}
		if ($crit && !($crit instanceof Criteria)) {$this->_errmsg="Criteria required";return false;}
		if ($crit==null) {
			$crit=new Criteria();
			$fld=$o->getPK();$o->getValues($fld);
			$crit->addop($fld,"=");
		}
		$cl=get_class($o);
		$tab=strtolower($cl);
		return $this->db->tabdelete($tab,$crit->get());
	}
	function count(&$o,$crit=null){
		$this->_errmsg=false;
		if (!($o instanceof ModelObject)) {$this->_errmsg="ModelObject required";return false;}
		if ($crit && !($crit instanceof Criteria)) {$this->_errmsg="Criteria required";return false;}
		$tab=strtolower(get_class($o));
		return $this->db->tabcount($tab,$crit?$crit->get():null);
	}
	function &find($o,$fld=null,$crit=null){
		$fal=false;
		$this->_errmsg=false;
		if (!($o instanceof ModelObject)) {$this->_errmsg="ModelObject required";return $fal;}
		if ($crit && !($crit instanceof Criteria)) {$this->_errmsg="Criteria required";return $fal;}
		$tab=strtolower(get_class($o));
		if ($fld==null) $fld=array("*");
		//else $fld=array_merge(array("*"),$fld); // concat
		$r=$this->db->tabfind($tab,implode(",",$fld),$crit?$crit->get():null);
		return ObjectDB::toObjectList($o,$r);
	}
	static function &toObjectList($o,&$r){
		if ($r===false) return $r;
		if (sizeof($r)>0){
			$tmp=array(); $n=sizeof($r);
			for ($i=0; $i<$n; ++$i){
				$o=$o->copy(); $o->setValues($r[$i]);
				$tmp[]=$o;
			}
			$r=$tmp; unset($tmp);
		}
		return $r;
	}
	function errmsg(){return $this->_errmsg!==false?$this->_errmsg:$this->db->errmsg();}
	function addconstraint($tab,$c) {return $this->db->addconstraint($tab,$c);}
	function qstr() {return $this->db->qstr();}
}
?>
