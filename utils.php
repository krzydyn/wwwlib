<html>
<title>Utils</title>
<body>
<?php
$hex=$bin="";
if (array_key_exists("hex",$_REQUEST)) $hex=$_REQUEST["hex"];
if (array_key_exists("bin",$_REQUEST)) $bin=$_REQUEST["bin"];
if ($hex=="") {$hex=unpack("H*",$bin);$hex=$hex[1];}
else $bin=pack("H*",$hex);
//print_r($hex);
?>
<form action="">
HEX: <input name="hex" value="<?echo "$hex" ?>">
<input type="submit" value="OK"><br></form>
<form action="">
BIN: <input name="bin" value="<?echo $bin.?>">
<input type="submit" value="OK"><br></form>
</body></html>
