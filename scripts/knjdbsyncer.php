#!/usr/bin/php5
<?

for($i = 1; $i < $_SERVER[argc]; $i++){
	$value = $_SERVER[argv][$i];
	
	if ($value == "--db1"){
		$db1 = explode(":", $_SERVER[argv][++$i]);
	}elseif($value == "--db2"){
		$db2 = explode(":", $_SERVER[argv][++$i]);
	}elseif($value == "--outputlevel"){
		$outputlevel = $_SERVER[argv][++$i];
	}elseif($value == "--help"){
		echo("knjDBSyncer - by Kasper Johansen <kaspernj@gmail.com>\n");
		echo("\n");
		echo("Valid arguments:\n");
		echo("   --db1        - Defines how to connect to the first database.\n");
		echo("   --db2        - Defines how to connect to the second database.\n");
		echo("\n");
		echo("Examples:\n");
		echo("   knjdbsyncer --db1 mysql:host:3306:db:user:pass --db2 mysql:host:3306:db:user:pass\n");
	}else{
		die("Unknown argument: \"" . $value . "\"\n");
	}
}

if (!$db1){
	die("Please supply the first database: \"--db1\".\n");
}elseif(!$db2){
	die("Please supply the second database: \"--db2\".\n");
}

if (!$outputlevel){
	$outputlevel = "notice";
}elseif($outputlevel == "notice" || $outputlevel == "warning"){
	//do nothing.
}else{
	die("Invalid outputlevel: \"" . $outputlevel . "\".\n");
}


require_once("knj/dbconn/class_dbconn.php");
$dbconn1 = new DBConn();
if (!$dbconn1->openConn($db1[0], $db1[1], $db1[2], $db1[3], $db1[4], $db1[5])){
	die("Could not make a connection to db1: \"" . $dbconn1->query_error() . "\".\n");
}

$dbconn2 = new DBConn();
if (!$dbconn2->openConn($db2[0], $db2[1], $db2[2], $db2[3], $db2[4], $db2[5])){
	die("Could not make a connection to db2: \"" . $dbconn2->query_error() . "\".\n");
}

$tables1 = $dbconn1->getTables();
$tables2 = $dbconn2->getTables();

foreach($tables1 AS $table1){
	if ($outputlevel == "notice"){
		echo("Notice: Checking table \"" . $table1["name"] . "\".\n");
	}
	
	//Check if the table exist in db2.
	$found = false;
	foreach($tables2 AS $testtable2){
		if ($testtable2["name"] == $table1["name"]){
			$table2 = $testtable2;
			$found = true;
			break;
		}
	}
	
	if (!$found){
		echo("Warning: Table was not found in db2: \"" . $table1["name"] . "\".\n");
	}
	
	
	//Go through columns.
	$columns1 = $dbconn1->getColumns($table1["name"]);
	$columns2 = $dbconn2->getColumns($table1["name"]);
	
	foreach($columns1 AS $column1){
		//Check if all columns exists.
		$column_text = $table1["name"] . "." . $column1["name"] . " " . $column1["type"] . "";
		if ($column1["maxlength"]){
			$column_text .= "(" . $column1["maxlength"] . ")";
		}
		
		$column2 = $columns2[$column1["name"]];
		if (!is_array($column2)){
			echo("Warning: Column does not exist on DB2: \"" . $column_text . "\".\n");
		}else{
			foreach($column1 AS $key => $value){
				if ($column2[$key] != $value){
					echo $column_text . ": " . $key . ":\"" . $value . "\" does not match on column2: " . $key . ":\"" . $column2[$key] . "\".\n";
				}
			}
			
			if ($column1["type"] != $column2["type"]){
				echo("Warning: Column-type doesnt match on \"" . $column_text . "\".\nDB1: \"" . $column1["type"] . "\".\nDB2: \"" . $column2["type"] . "\".\n\n");
			}
		}
	}
	
	//Go through indexes.
	$indexes1 = $dbconn1->getIndexes($table1["name"]);
	$indexes2 = $dbconn2->getIndexes($table2["name"]);
	
	if (count($indexes1) > 0){
		foreach($indexes1 AS $index1){
			$index2 = null;
			
			if (count($indexes2) > 0){
				foreach($indexes2 AS $index2_temp){
					if ($index2_temp["name"] == $index1["name"]){
						$index2 = $index2_temp;
						break;
					}
				}
			}
			
			if (!$index2){
				echo("Warning: Index not found on on db2 \"" . $table1["name"] . "\": \"" . $index1["name"] . "\" with columns: \"" . implode(", ", $index1["columns"]) . "\".\n");
			}
		}
	}
}