<?php
ob_start();
date_default_timezone_set("Europe/Warsaw");

function logstr_dbg($str){
	global $config;
	$e = new Exception;
	$trace = $e->getTrace();
	$file = $trace[1]["file"];
	$line = $trace[1]["line"];
	$tm = time();
	if (isset($config["appname"])) $fn="cache/log-".$config["appname"].date("Ymd",$tm).".txt";
	else $fn="cache/log".date("Ymd", $tm).".txt";
	$str = "(".$file.":".$line.") ".$str;
	$m=umask(0111);
	$f=@fopen($fn,"ab");
	umask($m);
	if ($f!==false){
		if (flock($f,LOCK_EX)){
			fwrite($f,date("H:i:s", $tm)." ".$str."\n");
			flock($f,LOCK_UN);
		}
		fclose($f);
	}
	else echo "<div class=log>[log] ".$str."\n</div>";
}
function logstr_rel($str){
}
function logstr($str){
	logstr_dbg($str);
}

function array_setval(&$t,$n,&$v=null){
	if (empty($n)) return false;
	$n=explode(".",$n);
	for ($i=0; $i<sizeof($n)-1; $i++){
		$k=$n[$i];
		if ((!is_array($t)&&!is_object($t))||!array_key_exists($k,$t)){
			if (!isset($v)) return true;
			$t[$k]=array();
			//echo "$t[$k]=array()<br>";
		}
		if (is_object($t)) $t=&$t->{$k}; else $t=&$t[$k];
	}
	$k=$n[$i];
	if (isset($v)) {if (is_object($t)) $t->{$k}=$v; else $t[$k]=$v;}
	else {if (is_object($t)) unset($t->{$k}); else unset($t[$k]);}
	//echo "$t[$k]=$v<br>";
	return true;
}
function array_hasval(&$t, $n){
	if (empty($n)) return true;
	$n=explode(".",$n);
	for ($i=0; $i < sizeof($n); $i++){
		$k=$n[$i];
		if ((!is_array($t)&&!is_object($t))||!array_key_exists($k,$t)) return false;
		if (is_object($t)) $t=&$t->{$k}; else $t=&$t[$k];
	}
	return true;
}
function &array_getval(&$t, $n, $def=false){
	if (empty($n)) return $t;
	$n=explode(".",$n);
	for ($i=0; $i < sizeof($n); $i++){
		$k=$n[$i];
		if ((!is_array($t)&&!is_object($t))||!array_key_exists($k,$t)) return $def;
		if (is_object($t)) $t=&$t->{$k}; else $t=&$t[$k];
	}
	return $t;
}
function array_unslash(&$t){
	if (!is_array($t)) return ;
	foreach ($t as $k => $v) {
		if (is_array($v)) array_unslash($t[$k]);
		else $t[$k]=stripslashes($v);
	}
}

class Request{
	private static $instance=null;
	private $vals=array();
	private function __construct() {
		self::Request();
	}
	private function Request(){
		global $_REQUEST,$_FILES,$_SERVER,$_GET,$_POST,$_COOKIE;
		global $config,$text;
		$this->setval("cfg", $config);
		$this->setval("txt", $text);

		$this->setval("cookie",$_COOKIE);
		$this->setval("req",$_REQUEST);
		//propagate get
		if (isset($_GET)){
			foreach ($_GET as $k => $v) {
				$this->setval("req.".$k, $v);
			}
		}
		//propagate cookie
		if (isset($_COOKIE)){
			foreach ($_COOKIE as $k => $v) {
				if ($this->hasval("req.".$k)===false)
					$this->setval("req.".$k, $v);
			}
		}
		array_unslash($this->vals["req"]);

		unset($_COOKIE);unset($_REQUEST);unset($_GET);unset($_POST);

		$this->setval("srv",$_SERVER);
		unset($_SERVER);
		//usefull shortcuts
		$this->setval("method",$this->getval("srv.REQUEST_METHOD"));
		$this->setval("uri",$this->getval("srv.REQUEST_URI"));
		$this->setval("remote-addr",$this->getval("srv.REMOTE_ADDR"));
		$this->setval("remote-port",$this->getval("srv.REMOTE_PORT"));
		$script = $this->getval("srv.SCRIPT_FILENAME");
		$baseurl = "/";
		if (!empty($script)) {
			$root = $this->getval("srv.DOCUMENT_ROOT");
			if (!str_ends_with($root, "/")) $root.="/";
			if (str_starts_with($script, $root)) {
				$i = strrpos($script, "/");
				$baseurl = substr($script, strlen($root)-1, $i-strlen($root)+1)."/";
			}
		}
		$this->setval("rooturl", $baseurl);

		if (isset($_FILES)){
			foreach ($_FILES as $k => $v) {
				//TODO (maybe) copy files from tmp to site location
				foreach ($v as $k1 => $v1) {
					foreach ($v1 as $k2 => $v2) {
						if ($v2=="") $v2=null;
						if ($k1=="name") $this->setval("req.".$k.".".$k2, $v2);
						$this->setval("req.".$k.".".$k2."_file.".$k1, $v2);
					}
				}
			}
			unset($_FILES);
		}
	}
	static function &getInstance() {
		if (self::$instance==null) self::$instance=new Request();
		return self::$instance;
	}

	function hasval($n) {
		return array_hasval($this->vals,$n);
	}
	function &getval($n=null,$v=null) {
		return array_getval($this->vals,$n,$v);
	}
	function setval($n,$v=null) {
		if (is_array($n)) {
			foreach ($n as $k => $v) $this->setval($k,$v);
			return true;
		}
		return array_setval($this->vals,$n,$v);
	}
	function addval($n,$v) {
		if ($n == "error") logstr("add error: $v");
		$a=$this->getval($n);
		if (!$a) $a=array($v);
		else if (!is_array($a)) $a=array($a,$v);
		else $a[]=$v;
		$this->setval($n,$a);
	}
}
?>
