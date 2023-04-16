<?php
require_once($config["cmslib"]."application.php");

class AdmNode extends Node
{}
class AdmContent extends Content
{}

class AdminApp extends Application
{
	function AdminApp(&$t)
	{
		$t->cachedir=null;
		parent::Application($t);
		$this->setval("cfg.templatedir",array($this->getval("cfg.cmslib")."templates/"));
		AdminApp::initialize();
	}
	function initialize()
	{
		$n=$this->count(new AdmNode());
		if ($n<1) db_insert("admnode",array("name"=>"main","title"=>"Main"));
		if ($n<2) db_insert("admnode",array("parentid"=>"1","name"=>"content","title"=>"Content"));
		if ($n<3) db_insert("admnode",array("parentid"=>"1","name"=>"site","title"=>"Site"));

		$c=new AdmContent();
		$n=$this->count($c,"where nodeid=#0",1);
		if ($n<1)
		{
			$r=db_insert("admcontent",array("nodeid"=>"1","name"=>"p1",
				"val"=>"node name is {val(\"node.name\")}"
				));
			if ($r===false) {$this->setval("errors",__FILE__."(".__LINE__."): DB: ".db_errors());return;}
		}
	}
	function doAdmin()
	{
		$nodeid=$this->getval("node");
		if ($nodeid==null) $nodeid=1;
		$n=new AdmNode();
		$r=$this->dbm->find($n,"where id=#0",$nodeid);
		if ($r===false)
		{
			$this->setval("errors",__FILE__."(".__LINE__."): DB: ".db_errors());
			return false;
		}
		if (sizeof($r)==0)
		{
			$this->setval("errors",$this->getval("txt.err.node"));
			return false;
		}
		$this->setval("node",$n);
		if ($n->templ) $this->setval("view",$n->templ);

		$a=array();
		$r=$this->dbm->find(new AdmNode(),"where (id=1 or parentid=#0) and substr(bmflags,1,2)='00'",$n->parentid?$n->parentid:$n->id);
		if ($r===false) $this->setval("errors",__FILE__."(".__LINE__."): DB: ".db_errors());
		else $a=array_merge($a,$r);
		$this->setval("menu",$a);
		//echo "sql:".$this->dbm->qstr()."<br>";

		$a=array();
		$r=$this->dbm->find(new AdmContent(),"where nodeid=#0",$nodeid);
		if ($r===false) $this->setval("errors",__FILE__."(".__LINE__."): DB: ".db_errors());
		else $a=array_merge($a,$r);
		for ($i=0; $i<sizeof($a); $i++) $a[$i]->val=$this->templ->compile($a[$i]->val);
		$this->setval("content",$a);
	}
}
?>
