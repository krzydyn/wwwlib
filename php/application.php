<?php
class Application{
	var $req=null;
	function __construct() {
		self::Application();
	}
	function Application(){
		$this->req=Request::getInstance();
		$charset="utf-8";
		$this->setval("charset",$charset);
		$this->addval("hdr","Content-Type: text/html;charset=\"".$charset."\"");
		$this->addval("hdr","Cache-Control: no-cache, no-store, must-revalidate"); //HTTP 1.1
		$this->addval("hdr","Pragma: no-cache"); //IE6 and HTTP 1.0
		$this->addval("hdr","Expires: 0"); //proxies
	}
	function initialize() { return true; }

	function getval($n,$v=null){return $this->req->getval($n,$v);}
	function setval($n,$v=null){return $this->req->setval($n,$v);}
	function addval($n,$v) {
		return $this->req->addval($n,$v);
	}
	function process() {
		$action="";
		$action=$this->getval("req.act",$action);
		$this->setval("req.act");
		$action=$this->getval("action",$action);
		if ($action && !preg_match("#^[_A-Za-z0-9]+$#",$action)) $action="";
		if (empty($action)) $action="default";
		$this->setval("action",$action);
		$method=$action."Action";
		if (method_exists($this,$method)){
			$this->$method();
		}
		else{
			logmsg("action '".$action."' not supported");
			$this->addval("error","action '".$action."' not supported");
			$this->defaultAction();
		}
	}
	function defaultAction() {}
}
//TODO move to request.php as static func
function uploadfile($src,$dst){
	if (!$src) return true;
	if (!file_exists($src)) return false;
	@unlink($dst);
	//echo "uploading file $src to $dst<br>";
	if (rename($src,$dst)===false){
		//try to copy
		$c=file_get_contents($src);
		if (file_put_contents($c,$dst)===false) return false;
		@unlink($src);
	}
	chmod($dst,0644);
	return true;
}
?>
