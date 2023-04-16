<%
function cms_tabs($m,$sel,$uri){
	if (!is_array($m)) return ;
	echo "<ul class=\"tabs\">\n";
	foreach ($m as $f => $v) {
		$cl="";
		if (strpos($sel,$f)!==false) $cl=" class=\"sel\"";
		echo "  <li class=\"tabitem\"><a$cl href=\"?tab=$f\">$v</a></li>\n";
	}
	echo "</ul>\n";
}
cms_tabs(val("tabs"),val("req.tab"),val("server.script"));
%>
