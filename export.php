<?php
require_once("../config.php");
require_once($config["lib"]."modules.php");
require_once($config["lib"]."application.php");

$req=Request::getInstance();
if ($req->getval("req.res")===null)
	$req->setval("req.res","user");
if ($req->getval("req.type")===null)
	$req->setval("req.type","txt");
if ($req->getval("req.file")===null)
	$req->setval("req.file",$req->getval("req.res").".".$req->getval("req.type"));

$req->setval("hdr"); //clear hdr
$type=$req->getval("req.type");
$req->setval("tpl","html");
if ($type=="csv") {$req->addval("hdr","Content-type: text/plain");$req->setval("tpl","txt");}
else if ($type=="xls") $req->addval("hdr","Content-type: application/vnd.ms-excel");
else if ($type=="html") $req->addval("hdr","Content-type: text/html");
else $req->addval("hdr","Content-type: text/plain");

if ($req->getval("req.inline")===0)
	$req->addval("hdr","Content-disposition: attachment; filename=".$req->getval("req.file"));
else
	$req->addval("hdr","Content-disposition: filename=".$req->getval("req.file"));

$req->addval("hdr","Expires: Mon, 26 Jul 1997 05:00:00 GMT");
$req->addval("hdr","Pragma: no-store");

db_connect();
$req->setval("result",db_find($req->getval("req.res"),"*"));

$t=new TemplateEngine($req);
$t->load("export.tpl");
?>
