<?php
require_once($config["cmslib"]."application.php");
require_once($config["cmslib"]."model.php");

define('CRM_VERSION',0);

class Session extends ModelObject
{
	var $uid;
	var $hash;
	var $host;
	var $tmses;
	var $tmstamp;
	var $sesdata;
	function getPK(){return array("uid","hash");}
}
class User extends ModelObject
{
	var $id;
	var $name;
	var $passwd;
	var $active=1;
	var $fullname;
	var $email;
	function toString(){return $this->fullname!==null?$this->fullname:$this->name;}
}
class Address extends ModelObject
{
	var $id;
	var $country="Polska";
	var $region="Mazowieckie";
	var $city="Warszawa";
	var $street;

	var $phone;
	var $mobile;
	var $fax;
	function toString()
	{
		if ($this->phone) return $this->street.", ph:".$this->phone;
		if ($this->city) return $this->city.", ".$this->street;
		return $this->street;
	}
}
class Firm extends ModelObject
{
	var $id;
	var $name;
	var $nip;
	var $id_address;//Address
	function toString()
	{
		$s=is_object($this->id_address)?"\n(".$this->id_address->toString().")":"";
		return $this->name.$s;
	}
	function setAddress(&$addrs)
	{
		if (!array_key_exists($this->id_address,$addrs)) return ;
		$this->id_address=$addrs[$this->id_address];
	}
}
class Employee extends ModelObject
{
	var $id;
	var $name; //family name
	var $fstname; //fist name(s)
	var $email;
	var $id_firm; // Firm
	function toString(){return $this->name." ".$this->fstname;}
}

class Project extends ModelObject
{
	var $id;
	var $id_parent; //Project
	var $name;
	var $id_client; //Firm
	var $id_manager;//Employee
	var $budget;
	var $dtbegin;
	var $dtend;
	var $status;
	var $genesis;
	var $target;
	var $risks;
	var $_path;
	function toString()
	{
		$s="";
		if ($this->_path!==null)
		{
			for ($i=0; $i<sizeof($this->_path); $i++)
				$s=$this->_path[$i]->name."/".$s;
		}
		return $s.$this->name;
	}
	function setPath(&$projs)
	{
		$used=array();
		$this->_path=array(); $p=$this;
		while ($p->id_parent>0)
		{
			if (!array_key_exists($p->id_parent,$projs)) break;
			if (in_array($p->id_parent,$used)) // loop detected
				{echo "tree loop detected for projid=".$p->id_parent."n=".$p->toString()."<br>";break;}
			$used[]=$p->id_parent;
			$p=$projs[$p->id_parent];
			$this->_path[]=$p;
		}
	}
}
class Activity extends ModelObject
{
	var $id;
	var $name;
	var $id_parent; //Project (optional)
}
class Workload extends ModelObject
{
	var $id;
	var $id_author;  //Employee
	var $id_project;
	var $id_activity;
	var $tmbegin;
	var $duration;
	var $subject;
	var $details;
}
class xMeeting extends Workload
{
	var $particips;   //Employee[s]
	function getFields()
	{
		$a=parent::getFields();
		$r=array_search("particips",$a);
		if ($r!==false) unset($a[$r]);
		return $a;
	}
}
class Todo extends ModelObject
{
	var $id;
	var $id_project; //Project
	var $id_author;  //User
	var $id_delegate;//User
	var $content;
}
class AccessRight extends ModelObject
{
	var $id;
	var $name;
}

class CRMApplication extends Application
{
	var $dao;
	function CRMApplication(&$resp)
	{
		Application::Application($resp);
		db_connect();
		$this->dao=new ObjectManager(db_getdb());
		CRMApplication::initialize();
	}
	function initialize()
	{
		$reqtabs=array(
			"session"=>"uid int not null,hash varchar(80) not null,host varchar(63),tmses int,tmstamp int,".
				"sesdata text,unique(uid,hash)",
			"user"=>"id int primary key auto_increment,name varchar(63) not null,active int(1),passwd varchar(32),".
				"fullname varchar(255),email varchar(255),unique(name)",
			"employee"=>"id int primary key auto_increment,name varchar(63),fstname varchar(127),".
				"email varchar(255),id_firm int,unique(email)",
			"project"=>"id int primary key auto_increment,id_parent int not null default 0,".
				"name varchar(63) not null,id_client int,id_manager int,".
				"budget int,dtbegin date,dtend date,status int,genesis text,target text,risks text",
			"activity"=>"id int primary key auto_increment,name varchar(63),id_project int", //task
			"address"=>"id int primary key auto_increment,country varchar(63),region varchar(63),city varchar(63),street varchar(127),".
				"phone varchar(255),mobile varchar(255),fax varchar(255)" ,
			"firm"=>"id int primary key auto_increment,name varchar(127),nip varchar(31),type int,id_address int,unique(name)",
			"workload"=>"id int primary key auto_increment,id_author int,".
				"id_project int,id_activity int,tmbegin datetime,duration int,".
				"subject varchar(255),details text",

			"accessright"=>"id int primary key,name varchar(31) not null",
			"accessprofile"=>"id int primary key,name varchar(31) not null",
		);
		$tabs=$this->dao->tables();
		if ($tabs===false) return ;
		foreach ($reqtabs as $t => $v) {
			if (in_array(dbt($t),$tabs)) continue;
			$r=$this->dao->db->tabcreate(dbt($t),$v);
			if ($r===false) {$this->addval("error","DB:".$this->dao->errmsg());return false;}
		}

		// add constraints
		/*
		if ($this->dao->db->tabcount("information_schema.table_constraints","where table_name='meeting'")==1)
		{
			$r=$this->dao->addconstraint("meeting","foreign key (id_project) references project(id)");
			if ($r===false) {$this->addval("error","DB:".$this->dao->errmsg());return false;}
			$r=$this->dao->addconstraint("meeting","foreign key (id_moderator) references employee(id)");
			if ($r===false) {$this->addval("error","DB:".$this->dao->errmsg());return false;}
			$r=$this->dao->addconstraint("meeting","foreign key (id_address) references address(id)");
			if ($r===false) {$this->addval("error","DB:".$this->dao->errmsg());return false;}
		}
		*/
		if (db_count("user")==0)
			db_insert("user",array("name"=>"admin","active"=>"1","passwd"=>md5("admin")));

		return true;
	}

	function mapFields($fld,&$res)
	{
		foreach ($res as $i => $ri) {
			foreach ($fld as $f => $cl) {
				if (!array_key_exists($f,$ri))
				{
					$this->addval("warn",get_class($ri)." has no field ".$f);
					continue;
				}
				$o=new $cl();
				$id=$ri->{$f};
				$r=$this->dao->find($o,"concat(".implode(",':',",$o->getPK()).")='".$id."'");
				if ($r!==false) $ri->{$f}=&$o;
				else $this->addval("warn","DB".$this->dao->errmsg());
			}
		}
	}

	function process()
	{
		if ($this->auth()===false)
		{
			$this->setval("req.act","login");
			$this->setval("info","You must login to this service");
			$this->setval("view","login");
		}
		parent::process();
		if ($this->getval("req.act")!="login")
			$this->setval("pagetitle",$this->getval("req.act"));
	}

	function auth()
	{
		if ($this->getval("session")!==null)
		{
			$this->addval("debug","session exists");
			return true;
		}

		$this->addval("debug","creating session ...");
		$sesid=$this->getval("req.sesid");
		if (!$sesid)
		{
			$this->addval("debug","sesid not found");
			return false;
		}
		$sesid=explode(":",$sesid);
		$ses=new Session();
		$ses->uid=$sesid[0];
		$ses->hash=$sesid[1];
		$this->setval("req.sesid");

		$r=$this->dao->find($ses,$ses->getPK());
		if ($r===false) { $this->addval("error","DB:".$this->dao->errmsg()); return false; }
		if (sizeof($r)!=1)
		{
			if ($this->getval("req.act")=="logout") return false;

			$this->addval("error","Session corrupted (n=".sizeof($r).") ID=".$ses->getID());
			$this->addval("error",$this->dao->qstr());
			return false;
		}
		$host=$this->getval("server.remote.ip");
		if (($ses->host!="" && $ses->host!=$host) || $ses->hash!=md5($ses->uid.$ses->host.$ses->tmses.CRM_VERSION))
		{
			$this->addval("error",$this->getval("txt.err.seskey","/wrong session key"));
			return false;
		}
		$user=new User(); $user->id=$ses->uid;
		$r=$this->dao->find($user,$user->getPK());
		if ($r===false) { $this->addval("error","DB:".$this->dao->errmsg()); return false; }
		if (sizeof($r)!=1)
		{
			$this->addval("error",$this->getval("txt.err.nouser","/no such user"));
			return false;
		}
		$ses->tmstamp=time();
		$this->dao->update($ses,$ses->getPK());
		$this->setval("req.sesid",$ses->getID());
		$this->setval("session",$ses);
		$this->setval("user",$user);
		$this->addval("debug","session created");
		return true;
	}

	function defaultAction()
	{
		$this->setval("req.act","session");
		return $this->sessionAction();
	}
	function loginAction()
	{
		$user=new User();
		$user->name=$this->getval("req.crmname");
		$user->passwd=md5($this->getval("req.passwd"));
		$this->setval("req.passwd");//remove from workspace
		if ($user->name===null) {return false;}
		$r=$this->dao->find($user,array("name","passwd"));
		if ($r===false) { $this->addval("error","DB:".$this->dao->errmsg()); return ; }
		//printobj("user",$user);
		if (sizeof($r)!=1)
		{
			$this->addval("error","Bad login or pssword, try again");
			return ;
		}
		if ($user->active!="1")
		{
			$this->addval("error","Account is not active");
			return ;
		}

		$ses=new Session();
		$ses->uid=$user->id;
		$ses->host=$this->getval("server.remote.ip");
		$ses->tmses=time();
		$ses->tmstamp=$ses->tmses;
		$ses->hash=md5($ses->uid.$ses->host.$ses->tmses.CRM_VERSION);
		$ses->sesdata=null;
		$r=$this->dao->find($ses->copy(),$ses->getPK());
		if ($r===false) { $this->addval("error","DB:".$this->dao->errmsg()); return ; }
		if (sizeof($r)!=0)
		{
			$this->addval("error","session locked");
			return ;
		}
		$this->dao->insert($ses);
		$this->setval("req.sesid",$ses->getID());
		$this->setval("req.act");
		$this->setval("user",$user);
		$this->setval("info","Login successful");
		$this->defaultAction();
	}
	function logoutAction()
	{
		$ses=$this->getval("session");
		$user=$this->getval("user");
		$r=$this->dao->del($ses);
		if ($r===false) $this->addval("error","DB:".$this->dao->errmsg());
		db_delete("session","where tmstamp<#0",time()-24*3600);
		db_delete("session","where hash!=md5(concat(uid,host,tmses,'".CRM_VERSION."'))");
		$this->setval("req.sesid");
		$this->setval("req.act","login");
		$this->setval("req.crmname",$user->name);
		$this->setval("view","login");
		$this->setval("user");
		$this->setval("info","You must login to this service");
	}
	function sessionAction()
	{
		$this->_daoAction(new Session());
	}
	function addressAction()
	{
		$this->_daoAction(new Address());
	}
	function userAction()
	{
		$obj=new User();
		$cmd=$this->getval("req.cmd");
		if (!$cmd)
		{
			unset($obj->passwd);
			$r=$this->dao->find($obj);
			$this->setval("view","reclist");
		}
		else if ($cmd=="edit")
		{
			$obj->id=$this->getval("req.recid",0);
			if ($obj->id) $r=$this->dao->find($obj,$obj->getPK());
			if ($r!==false) $r=true;
			$obj->passwd=null;
			$this->setval("view","useredit");
		}
		else if ($cmd=="save")
		{
			$res=$this->getval("req.rec");
			$obj->setValues($res); $res["passwd"]=null;
			if ($obj->passwd) $obj->passwd=md5($obj->passwd);
			else unset($obj->passwd);
			$r=$obj->save($this->dao);
			unset($obj->passwd);
			if ($r===false)
			{
				$obj->setValues($res);
				$this->setval("view","useredit");
			}
			else
			{
				$r=$this->dao->find($obj);
				$this->setval("view","reclist");
			}
		}
		else if ($cmd=="delete")
		{
			$checkid=$this->getval("req.checkid");
			if (!is_array($checkid)) $this->setval("info","nothing to delete");
			else
			{
				$checkid=array_keys($checkid);
				$r=$this->dao->del($obj,"concat(".implode(",",$obj->getPK()).") in ('".implode("','",$checkid)."')");
				if ($r===false) $this->addval("error","DB:".$this->dao->errmsg());
				else $this->setval("info","selected items deleted");
			}
			unset($obj->passwd);
			$r=$this->dao->find($obj);
			$this->setval("view","reclist");
		}
		else if ($cmd=="chgpasswd")
		{
			if ($obj->id)
			{
				$obj->passwd=""; //erase passwd
				if ($res["passwd"]) //passwd change req.
				{
					$obj->passwd=$res["passwd0"];
					$pk=$obj->getPK(); $pk[]="passwd";
					$r=$this->dao->find($obj,$pk);
					$obj->passwd="";
					if ($r===false) $this->addval("error","DB:".$this->dao->errmsg());
					else if (sizeof($r)!=1) $this->addval("error","wrong password");
					else if ($res["passwd1"]!=$res["passwd2"]) $this->addval("error","password mismatch");
					else $obj->passwd=$res["passwd2"];
				}
			}
		}
		else
		{
			$this->addval("error","wrong command");
		}
		$this->setval("rec",$obj);
		if ($r===false) $this->addval("error","DB:".$this->dao->errmsg());
		else $this->setval("result",$r);
	}
	function employeeAction()
	{
		$this->_daoAction(new Employee());
		$cmd=$this->getval("req.cmd");
		if ($cmd=="list" && is_array($res=$this->getval("result")))
		{
			//printobj("employees",$res);
			$fld=array("id_firm"=>"Firm");
			$this->mapFields($fld,$res);
		}
	}
	function projectAction()
	{
		$this->_daoAction(new Project());
		$cmd=$this->getval("req.cmd");
		if ($cmd=="edit")
		{
			$a=$this->dao->find(new Project());
			//printobj("projs",$a);
			foreach ($a as $i => $o) $o->setPath($a);
			$this->setval("projects",$a);
			$x=new Employee();
			$a=$this->dao->find($x);
			$this->setval("managers",$a);
			$a=$this->dao->find(new Firm());
			$this->setval("firms",$a);

			$this->setval("view","projectedit");
		}
		else if ($cmd=="list" && is_array($res=$this->getval("result")))
		{
			// map field to class name
			$fld=array("id_parent"=>"Project","id_client"=>"Firm","id_manager"=>"Employee");
			$this->mapFields($fld,$res);
		}
	}
	function firmAction()
	{
		$this->_daoAction(new Firm());
		$cmd=$this->getval("req.cmd");
		if ($cmd=="list" && is_array($res=$this->getval("result")))
		{
			// map field to class name
			$fld=array("id_address"=>"Address");
			$this->mapFields($fld,$res);
		}
	}
	function workloadAction()
	{
		$obj=new Workload();
		$this->_daoAction($obj);
		$cmd=$this->getval("req.cmd");
		//printobj("meeeting",$this->getval("rec"));

		if ($cmd=="edit")
		{
			$a=$this->dao->find(new Project());
			//printobj("projs",$a);
			foreach ($a as $i => $o) $o->setPath($a);
			$this->setval("projects",$a);

			$a=$this->dao->find(new Firm());
			$addrs=$this->dao->find(new Address());
			if ($addrs!==false) {
				foreach ($a as $i => $o) $o->setAddress($addrs);
			}
			else $this->addval("warn","DB:".$this->dao->errmsg());
			$this->setval("firms",$a);

			$a=$this->dao->find(new Employee());
			$this->setval("employees",$a);

			$this->setval("view","workloadedit");
		}
		else if ($cmd=="list" && is_array($res=$this->getval("result")))
		{
			// map field to class objects
			$fld=array("id_project"=>"Project","id_author"=>"Employee");
			$this->mapFields($fld,$res);

			$addrs=$this->dao->find(new Address());
			$activities=$this->getval("activities");
			if ($addrs!==false)
			{
				foreach ($res as $i => $ri) {
					if (is_object($ri->id_firm)) $ri->id_firm->setAddress($addrs);
					$ri->id_activity=$activities[$ri->id_activity];
				}
			}
			else $this->addval("warn","DB:".$this->dao->errmsg());
		}
	}
	function _daoAction(&$obj)
	{
		$cmd=$this->getval("req.cmd");
		$this->setval("req.cmd");
		if (!$cmd)
		{
			$r=$this->dao->find($obj);
			$this->setval("view","reclist");
			$cmd="list";
		}
		else if ($cmd=="view")
		{
			$this->setval("view","recview");
		}
		else if ($cmd=="edit")
		{
			$id=$this->getval("req.recid",0);
			if ($id)
			{
				$r=$this->dao->find($obj,"concat(".implode(",':',",$obj->getPK()).")='".$id."'");
				//printobj("find",$this->dao->qstr());
			}
			if ($r!==false) $r=true;
			$this->setval("view","recedit");
		}
		else if ($cmd=="save")
		{
			$obj->setValues($this->getval("req.rec"));
			$r=$obj->save($this->dao);
			if ($r===true)
			{
				$r=$this->dao->find($obj);
				$this->setval("view","reclist");
				$cmd="list";
			}
			else
			{
				$this->setval("view","recedit");
				$cmd="edit";
			}
		}
		else if ($cmd=="delete")
		{
			$checkid=$this->getval("req.checkid");
			if (!$checkid)
			{
				$obj->setValues($this->getval("req.rec"));
				if ($obj->isValid()) $checkid=array($obj->getID()=>"on");
			}
			if (!is_array($checkid)) $this->setval("info","nothing to delete");
			else
			{
				printobj("checkid",$checkid);
				$checkid=array_keys($checkid);
				$r=$this->dao->del($obj,"concat(".implode(",':',",$obj->getPK()).") in ('".implode("','",$checkid)."')");
				if ($r===false) $this->addval("error","DB:".$this->dao->errmsg());
				else $this->setval("info","selected items deleted ($r)");
			}
			$r=$this->dao->find($obj);
			$this->setval("view","reclist");
			$cmd="list";
		}
		else
		{
			$this->addval("warn","wrong command '".$cmd."'");
			$r=$this->dao->find($obj);
			$this->setval("view","reclist");
			$cmd="list";
		}
		$this->setval("rec",$obj);
		if ($r===false) $this->addval("error","DB:".$this->dao->errmsg());
		else $this->setval("result",$r);
		$this->setval("req.cmd",$cmd);
	}
}
?>
