<?php
require_once("modules.php");
require_once("parser_php.php");
require_once("parser_html.php");
class Obfuscator
{
	var $parser=null;
	var $files=array(); //project source files
	function addfile($f) { $this->files[]=$f; }
	function setParser(&$p) { $this->parser=$p; }
	function parse(&$cont)
	{
		$this->parser->parse($cont);
	}
	function build()
	{
		return $this->parser->build();
	}
	function obfuscate($cont=null)
	{
		if ($cont==null)
		{
			for ($i=0; $i < sizeof($this->files); $i++)
			{
				$cont=file_get_contents($this->files[$i]);
				$this->parse($cont);
			}
		}
		else $this->parse($cont);
		return $this->build();
	}
}

$o=null;
//process request & set output variables
if (array_key_exists("cont",$_REQUEST))
{
	$cont=$_REQUEST["cont"];
	$o=new Obfuscator();
	//$o->setParser(new ParserPHP());
	$o->setParser(new ParserHTML());
	//$o->addfile("parser.php");
	$out=$o->obfuscate($cont);
	echo "max_depth=".$o->parser->max_build_depth."<br>";
}
else $cont=null;

echo "drop php code here:";
echo "<form action=\"\" method=\"post\">";
echo "<textarea name=\"cont\" cols=100 rows=12>".strtr($cont,array("&"=>"&amp;","<"=>"&lt;"))."</textarea><br>";
echo "<input type=\"submit\" value=\"obfuscate\">";
echo "</form>";
if (isset($out))
{
	echo "Obfuscated code<br>\n";
	echo "<pre>".strtr($out,array("&"=>"&amp;","<"=>"&lt;"))."</pre>";
}
echo "<br>";
if ($o!==null) printobj("blocks",$o->parser->blocks);
?>
