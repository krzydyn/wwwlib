<!-- kdcms menu.tpl -->
<%
function cms_menu($m,$l){
	$mCLASS="menu";
	if (!is_array($m)){
		echo "<!-- kdcms empty menu -->";
		return ;
	}
	echo str_repeat("  ",$l)."<ul class=\"$mCLASS\">\n";
	for($i=0; list($f,$v)=each($m); $i++){
		echo str_repeat("  ",$l+1)."<li class=\"${mCLASS}item\">";
		if (is_array($v)){
			//echo "$f".($l==1?"\n":"<br>\n");
			echo "$f\n";
			cms_menu($v,$l+1);
			echo str_repeat("  ",$l+1);
		}
		else {
			echo "<a href=\"$v\">$f</a>";
		}
		echo "</li>\n";
	}
	echo str_repeat("  ",$l)."</ul>\n";
}
cms_menu(val("menu"),0);
%>
