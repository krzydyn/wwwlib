<?php
// from http://artykuly.zyxist.com/czytaj.php/drzewa_w_php_i_mysql

function addNode($id, $title)
{
	// zablokuj innym dostep do tabeli, aby sie nic nie pokopalo
	mysql_query('LOCK TABLES `drzewko` WRITE');
	$r = mysql_query('SELECT `left`, `right`
			FROM drzewko WHERE id=\''.$id.'\'');
	if($row = mysql_fetch_assoc($r))
	{
		$left = $row['left'];
		$right = $row['right'];
	}
	else
	{
		// nie znaleziono elementu
		// nadrzednego, zacznij nowe drzewo
		$left = 0;
		$right = 1;
	}

	// przesun wartosci parametrow nastepnych wezlow o 2
	mysql_query('UPDATE drzewko SET `right`=`right`+2
			WHERE `right` > '.($right-1));
	mysql_query('UPDATE drzewko SET `left`=`left`+2
			WHERE `left` > '.($right-1));

	// dodaj nowy element
	mysql_query('INSERT INTO drzewko (`nazwa`, `left`,
		 `right`) VALUES(\''.$title.'\', \''.$right.'\',
		 \''.($right+1).'\')');
	// zdejmujemy blokade, tabela ponownie nadaje
	// sie do uzytku
	mysql_query('UNLOCK TABLES');
} // end createNode();
function displayTree($root)
	{
		// pobierz parametry glownego wezla
		$r = mysql_query('SELECT `left`, `right` FROM
			drzewko WHERE id=\''.$root.'\'');
		if($row = mysql_fetch_assoc($r))
		{
			$right = array();
			// wyswietl wezly
			$r = mysql_query('SELECT `nazwa`, `left`,
				`right` FROM drzewko WHERE `left`
				BETWEEN \''.$row['left'].'\' AND
				\''.$row['right'].'\' ORDER BY `left`');
			while($row = mysql_fetch_assoc($r))
			{
				// czysc stos
				if(count($right) > 0)
				{
					while($right[count($right)-1] < $row['right'])
					{
						array_pop($right);
					}
				}
				// wyswietl element
				echo str_repeat('| ',count($right))."\n";
				if(count($right) - 1 > 0)
				{
					echo str_repeat('| ',
						count($right) - 1).'+- '.
						$row['nazwa']."\n";
				}
				else
				{
					echo '+- '.$row['nazwa']."\n";
				}
				// zloz jego parametr 'right' na stos
				$right[] = $row['right'];
			}
			// wszystko jest OK
			return 1;
		}
		// tere fere, nie ma takiego wezla
		return 0;
	} // end displayTree();

function demo_creteTree()
{
	CREATE TABLE `drzewko` (
		`id` int(8) NOT NULL AUTO_INCREMENT,
		`nazwa` varchar(32) collate utf8_polish_ci NOT NULL DEFAULT '',
		`left` int(8) NOT NULL DEFAULT '0',
		`right` int(8) NOT NULL DEFAULT '0',
		PRIMARY KEY  (`id`),
		KEY `parent` (`left`),
		KEY `right` (`right`)
	);

	INSERT INTO `drzewko` VALUES (1, 'Budynki', 1, 22);
	INSERT INTO `drzewko` VALUES (2, 'Przemyslowe', 2, 5);
	INSERT INTO `drzewko` VALUES (3, 'Publiczne', 6, 11);
	INSERT INTO `drzewko` VALUES (4, 'Mieszkalne', 12, 21);
	INSERT INTO `drzewko` VALUES (5, 'Fabryka', 3, 4);
	INSERT INTO `drzewko` VALUES (6, 'Biblioteka', 7, 8);
	INSERT INTO `drzewko` VALUES (7, 'Kosciol', 9, 10);
	INSERT INTO `drzewko` VALUES (8, 'Domy', 13, 18);
	INSERT INTO `drzewko` VALUES (9, 'Blok mieszkalny', 19, 20);
	INSERT INTO `drzewko` VALUES (10, 'Dom jednorodzinny', 14, 15);
	INSERT INTO `drzewko` VALUES (11, 'Dom wielorodzinny', 16, 17);
}
?>
