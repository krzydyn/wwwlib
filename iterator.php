<?php
include_once($config["cmslib"]."collection.php");
class KIterator
{
	var $arr;
	var $cp;
	function KIterator(&$c)
	{
		if ($c instanceof Collection) $this->arr=$c->toArray();
		else if (is_array($c)) $this->arr=&$c;
		else throw new Exception("not a Collection");
		$this->cp=0;
	}
	function hasNext()
	{
		return $this->cp<sizeof($this->arr);
	}
	function &next()
	{
		if ($this->cp>=sizeof($this->arr)) return null;
		return $this->arr[$this->cp++];
	}
}
?>
