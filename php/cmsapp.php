<?php
require_once($config["cmslib"]."application.php");
require_once($config["cmslib"]."dbmanager.php");
//require_once("user.php");

define('CMS_VERSION',0);

class Node
{
	var $id;
	var $parentid;
	var $name;
	var $title;
	var $bmflags;//active,inmenu,cachable,...
	var $menutext;
	var $menuimg;
	var $templ;
}
class Content
{
	var $id;
	var $nodeid;
	var $name;
	var $type;
	var $val;
}

class CMSApplication extends Application
{
	var $resp=null;
	var $dbm;
	function CMSApplication(&$resp)
	{
		$this->resp=&$resp;
		db_connect();
		$this->dbm=DBManager::getInstance(db_getdb());
		Application::initialize();
		$this->addval("hdr","Content-Type: text/html; charset=ISO-8859-2");
	}
	function initialize()
	{
		$reqtabs=array(
			//"user"=>"id int primary key auto_increment,name varchar(60),active int(1),pass varchar(32),".
			//	"fullname varchar(255),created datetime,updated datetime,lang char(5),".
			//	"pict varchar(64),unique(name)",
			"session"=>"uid int not null default 0,hash varchar(64) not null,".
				"host varchar(128) not null,tmstamp int not null,sesdata longtext,unique(uid,hash)",
			"node"=>"id int primary key auto_increment,parentid int not null default 0,".
				"name varchar(64) not null,title varchar(255),".
				"bmflags varchar(10) not null default '0000000000',".
				"menutext varchar(64),menuimg varchar(255),".
				"templ varchar(255),unique(name)",
			"content"=>"id int primary key auto_increment,nodeid int not null default 1,".
				"name varchar(64) not null,type int not null default 0,val text,unique(nodeid,name)",
		);
		$r=db_query("show tables");
		if ($r===false) return ;
		$tabs=array();
		while ($row=$r->fetch(FETCH_NUM)) $tabs[]=$row[0];
		foreach ($reqtabs as $t => $v) {
			if (in_array(dbt($t),$tabs)) continue;
			$r=db_create($t,$v);
			if ($r===false) {$this->addval("error",__FILE__."(".__LINE__."): DB: ".db_errors());return false;}
		}

		//if ($this->count(new User())==0)
		//	db_insert("user",array("name"=>"admin","active"=>"1","pass"=>md5("admin")));
		return true;
	}

	function count(&$o,$fmt=null)
		{ $args=func_get_args(); return $this->dbm->count($o,$fmt,args(2,$args)); }
	function add(&$o) { return $this->dbm->add($o); }

	function doCMS()
	{
		$nodeid=$this->getval("req.node");
		if ($nodeid==null) $nodeid=1;
		$n=new Node();
		$r=$this->dbm->find($n,"where id=#0",$nodeid);
		if ($r===false)
		{
			$this->addval("error",__FILE__."(".__LINE__."): DB: ".db_errors());
			return false;
		}
		if (sizeof($r)==0)
		{
			$this->addval("error",$this->getval("txt.err.node")." (".$nodeid.")");
			return false;
		}
		$this->setval("node",$n);
		if ($n->templ) $this->setval("req.view",$n->templ);

		$r=$this->dbm->find(new Node(),"where name='home'");
		$homeid=$r[0]->id;
		$a=array();
		$r=$this->dbm->find(new Node(),"where (id=".$homeid." or parentid=#0) and substr(bmflags,1,2)='00'",$n->parentid?$n->parentid:$homeid);
		//$this->addval("error","Query: ".db_qstr());
		if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB: ".db_errors());
		else $a=array_merge($a,$r);
		$this->setval("menutop",$a);

		$this->setval("menubot",$this->dbm->find(new Node(),"where name='login' and substr(bmflags,1,2)='00'"));

		$a=array();
		$r=$this->dbm->find(new Content(),"where nodeid=#0",$nodeid);
		if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB: ".db_errors());
		else $a=array_merge($a,$r);
		//for ($i=0; $i<sizeof($a); $i++) $a[$i]->val=$this->resp->compile($a[$i]->val);
		$this->setval("content",$a);
	}

	function checkSession()
	{
		global $_SERVER;
		$user=$this->getval("cfg.class.user");
		$this->setval("info");
		$this->addval("info","You must login to this service");
		$uid=$this->getval("req.uid");
		$hash=$this->getval("req.hash");
		$this->setval("req.uid");
		$this->setval("req.hash");
		$host=$_SERVER["REMOTE_ADDR"];
		if ($uid==null||$hash==null) return false;
		//$r=db_find("session","tmstamp","where uid=#0 and hash=#1",array($uid,$hash));
		$r=$this->dbm->find(new Session(),"where uid=#0 and hash=#1",$uid,$hash);
		if ($r===false)
		{
			$this->addval("error",__FILE__."(".__LINE__."): DB: ".db_errors());
			return false;
		}
		if (sizeof($r)!=1)
		{
			$this->addval("error","Session corrupted");
			return false;
		}
		$s=$r[0];
		if (($s->host!="" && $s->host!=$host) || $s->hash!=md5($s->uid.$s->host.$s->tmstamp.CMS_VERSION))
		{
			//$this->addval("error","host ".$s->host.":".$host."; hash ".$s->hash.":".md5($s->uid.$s->host.$s->tmstamp.CMS_VERSION));
			$this->addval("error",$this->getval("txt.err.seskey"));
			return false;
		}
		$r=$this->dbm->find(new $user(),"where id=#0",$uid);
		if ($r===false)
		{
			$this->addval("error",__FILE__."(".__LINE__."): DB: ".db_errors());
			return false;
		}
		if (sizeof($r)!=1)
		{
			$this->addval("error","Bad login or pssword, try again");
			return false;
		}
		$this->setval("req.uid",$uid);
		$this->setval("req.hash",$hash);
		$this->setval("user",$r[0]);
		$this->setval("msg");
		return true;
	}

	function doLogin()
	{
		global $_SERVER;
		$n=$this->getval("req.user");
		if ($n==null) {return false;}
		$p=md5($this->getval("req.passwd"));
		$this->setval("req.passwd");//remove from workspace
		//$r=db_find("user","id,name","where name=#0 and pass=#1",array($n,$p));
		$r=$this->dbm->find(new User(),"where name=#0 and pass=#1",$n,$p);
		if ($r===false)
		{
			$this->addval("error",__FILE__."(".__LINE__."): DB: ".db_errors());
			return false;
		}
		if (sizeof($r)!=1)
		{
			$this->addval("error","Bad login or pssword, try again");
			return false;
		}
		if (!$r[0]->active)
		{
			$this->addval("error","Account is not active");
			return false;
		}

		$s=new Session();
		$s->uid=$r[0]->id;
		$s->host=$_SERVER["REMOTE_ADDR"];
		$s->tmstamp=time();
		$s->hash=md5($s->uid.$s->host.$s->tmstamp.CMS_VERSION);
		$s->sesdata=null;
		$this->dbm->add($s);
		if (db_errors())
		{
			$this->addval("error",__FILE__."(".__LINE__."): DB: ".db_errors());
			return false;
		}
		$this->setval("req.hash",$s->hash);
		$this->setval("req.uid",$s->uid);
		$this->setval("msg","Login successful");
		return true;
	}
	function doLogout()
	{
		$user=$this->getval("user","User");
		$r=db_delete("session","where uid=#0 and hash=#1",$user->id,$this->getval("hash"));
		if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
		db_delete("session","where tmstamp<#0",array(time()-24*3600));
		db_delete("session","where hash!=md5(concat(uid,host,tmstamp,'".CMS_VERSION."'))");
		$this->setval("req.uid");
		$this->setval("req.hash");
		$this->setval("req.user",$user->name);
		$this->setval("req.view","login");
		$this->setval("user");
		$this->setval("msg","You must login to this service");
	}
	function doSession()
	{
		$cmd=$this->getval("cmd");
		if ($cmd=="clean")
		{
			$r=db_delete("session",null);
			if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
			else $this->setval("msg","all entries deleted");
		}
		$r=$this->dbm->find(new Session);
		if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
		$this->setval("result",$r);
	}
	function doUser()
	{
		$user=$this->getval("cfg.class.user");
		$cmd=$this->getval("req.cmd");
		if ($cmd=="")
		{
			$view=$this->getval("req.view");
			if ($view=="useredit"||$view=="userpasswd") $cmd=$view;
		}
		if ($cmd=="useredit" || $cmd=="userpasswd")
		{
			$id=$this->getval("req.user.id");
			if ($id)
			{
				$r=$this->dbm->find(new $user(),"where id=#0",$id);
				if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
				else
				{
					//echo $this->dbm->qstr()."<br>";
					$this->setval("req.user",$r[0]);
					$s=new Session();
					$r=$this->dbm->find($s,"where uid=#0 and host=''",$id);
					if ($r!=null)
						$this->setval("newuser",array("uid"=>$id,"hash"=>$s->hash,"view"=>"userpasswd","user[id]"=>$id));
				}
			}
			return ;
		}
 		else if ($cmd=="delete")
		{
			$userid=$this->getval("req.userid");
			if (!is_array($userid)) $this->setval("msg","nothing to delete");
			else
			{
				$userid=array_keys($userid);
				$r=db_delete("user","where id in (".implode(",",$userid).")");
				if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
				else $this->setval("msg","selected entries deleted");
			}
		}
		else if ($cmd=="add")
		{
			$this->setval("req.user.active",$this->getval("req.user.active")==null?0:1);
			$pict=$this->getval("req.user.pict");
			if ($pict)
			{
				$file=$this->getval("req.user.pict_file");
				if ($file["name"] && $file["error"]==0)
				{
					if (!uploadfile($file["tmp_name"],"uploads/".translate("ISO8859-1","ISO8859-2",$file["name"])))
						$file["error"]=5;
				}
				if ($r=$file["error"])
				{
					$this->addval("error","Upload (".$pict.") = ".$r);
					$this->setval("req.user.pict");
				}
			}
			$u=new $user();
			setfields($u,$this->getval("req.user"));
			$u->id=null;
			$u->updated=date("Y-m-d H:i:s");
			$u->created=$u->updated;
			//printobj("class fields",$u);
			if ($u->name=="") $this->addval("error",$this->getval("txt.err.nologin"));
			else
			{
				$r=$this->dbm->add($u);
				if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
				else
				{
					$s=new Session();
					$s->uid=$r;
					$s->host="";
					$s->tmstamp="0";
					$s->hash=md5($s->uid.$s->host.$s->tmstamp.CMS_VERSION);
					$s->sesdata=null;
					$r=$this->dbm->add($s);
					if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
				}
			}
		}
		else if ($cmd=="save")
		{
			$this->setval("req.user.active",$this->getval("req.user.active")==null?0:1);
			$pict=$this->getval("req.user.pict");
			if ($pict)
			{
				$file=$this->getval("req.user.pict_file");
				if ($file["name"] && $file["error"]==0)
				{
					if (!uploadfile($file["tmp_name"],"uploads/".translate("ISO8859-1","ISO8859-2",$file["name"])))
						$file["error"]=5;
				}
				if ($r=$file["error"])
				{
					$this->addval("error","Upload (".$pict.") = ".$r);
					$this->setval("req.user.pict");
				}
			}
			$u=new $user();
			if (!$this->getval("req.user.id")) $r=true;
			else
			{
				$r=$this->dbm->find($u,"where id=#0",$this->getval("req.user.id"));
				if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
				if (sizeof($r)!=1) $r=false; else $r=true;
			}
			setfields($u,$this->getval("req.user"));
			$u->updated=date("Y-m-d H:i:s");
			if (!$u->created) $u->created=$u->updated;
			if ($u->name=="") $this->addval("error",$this->getval("txt.err.nologin"));
			$this->setval("req.user",$u);
			if ($r===true)
			{
				if ($u->id==0)
				{
					$r=$this->dbm->add($u);
					if ($r!==false)
					{
						$u->id=$r;
						$s=new Session();
						$s->uid=$r;
						$s->host="";
						$s->tmstamp="0";
						$s->hash=md5($s->uid.$s->host.$s->tmstamp.CMS_VERSION);
						$s->sesdata=null;
						$r=$this->dbm->add($s);
						if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
					}
				}
				else
				{
					$r=$this->dbm->update($u,"where id=#0",$u->id);
				}
				if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
			}
			else $this->addval("error","wrong user ID");
			if ($r===true) $this->setval("req.view","userlist");
		}
		else if ($cmd=="chpass")
		{
			if ($this->getval("req.user.passwd1")!=$this->getval("req.user.passwd2"))
				{$this->addval("error","Password not match");return;}
			if ($this->getval("user.id")!=1 && $this->getval("user.id")!=$this->getval("req.user.id"))
				{$this->addval("error","Oparation not allowed");return;}
			if ($this->getval("user.id")==1||$this->getval("req.user.active")==0)
				$r=db_update("user",array("active"=>1,"pass"=>md5($this->getval("req.user.passwd2"))),"where id=#0",$this->getval("req.user.id"));
			else
				$r=db_update("user",array("pass"=>md5($this->getval("req.user.passwd2"))),"where id=#0 and pass=#1",$this->getval("req.user.id"),md5($this->getval("req.user.passwd0")));
			if ($r===false) {$this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());return;}
			if ($r==0) {$this->addval("error","Password incorrect");return;}
			if ($this->getval("error")===null)
			{
				$this->setval("req.view");
				if ($this->getval("req.user.id")==$this->getval("req.uid"))
				{
					$r=$this->dbm->find(new $user(),"where id=#0",$this->getval("req.uid"));
					$this->setval("user",$r[0]);
				}
			}
		}
		//echo "setting result for ".$user;
		$r=$this->dbm->find(new $user());
		if ($r===false) $this->addval("error",__FILE__."(".__LINE__."): DB:".db_errors());
		//printobj("result",$r);
		$this->setval("result",$r);
	}
}
?>
