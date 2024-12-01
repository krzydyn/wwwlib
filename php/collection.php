<?php
class Collection
{
	var $arr;
	function Collection(&$arr){$this->arr=&$arr;}
	function size(){return sizeof($this->arr);}
	function &toArray(){return $this->arr;}
	function add($o){$this->arr[]=$o;}
	function clear()
	{
		//TODO unset all elements ?
		$this->arr=array();
	}
	function iterator(){return new KIterator($this);}

	function toHtmlString()
	{
		for ($i=0; $i<sizeof($this->arr); $i++)
		{
			$r=&$this->arr[$i];
			$s="<tr>";
			foreach ($r as $f=>$v) $s.="<td>".$v."</td>";
			$s.="</tr>\n";
			echo $s;
		}
	}
}
?>
