<?php
class Controler
{
	var $dbm;
	var $modelName;
	var $dao;
	function Controler()
	{
		$dbm=DBManager::getInstance();
	}
	function preaction()
	{
		$this->dao=new $this->modelName."DAO"();
	}
	function postaction($r)
	{
	}
	function list_action()
	{
		return $this->dao->getlist();
	}
	function edit_action()
	{
		$o=new $this->modelName();
		if (($id=$this->getval("req.".$this->modelName.".id"))>0)
			$this->dao->readobj($id,$o);
		$o->setValues($this->getval("req.".$this->modelName));
	}
	function save_action()
	{
		$o=new $this->modelName();
		$o->setValues($this->getval("req.".$this->modelName));
		if ($o->isValid()!==true) return false;
		$this->dao->saveobj($o);
	}

	function process()
	{
		$r=$this->preaction();
		if ($r===true)
		{
			$action=$this->getval("action");
			$method=$action."_action";
			if (method_exists($this,$method)) $r=$this->$method();
			else $r=false;
		}
		return $this->postaction($r);
	}
}
?>
