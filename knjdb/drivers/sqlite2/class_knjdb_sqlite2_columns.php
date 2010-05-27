<?php
	class knjdb_sqlite2_columns implements knjdb_driver_columns{
		private $knjdb;
		public $columns = array();
		
		function __construct($knjdb){
			$this->knjdb = $knjdb;
		}
		
		function getColumnSQL($column){
			if (!is_array($column)){
				throw new Exception("Column was not an array.");
			}
			
			$sql = $this->knjdb->conn->sep_col . $column["name"] . $this->knjdb->conn->sep_col . " ";
			
			//Follow code checks for un-supported table-types.
			//MySQL-specific columns.
			$type = $column["type"];
			$maxlength = $column["maxlength"];
			$primarykey = $column["primarykey"];
			$autoincr = $column["autoincr"];
			
			if ($type == "tinyint" || $type == "mediumint"){
				$type = "int";
			}elseif($type == "enum"){
				$type = "varchar";
				$maxlength = "";
			}elseif($type == "tinytext"){
				$type = "varchar";
				$maxlength = "255";
			}elseif($type == "counter"){
				//This is an Access-primarykey-autoincr-column - convert it!
				$sql .= "int";
				$type = "int";
				
				$primarykey = "yes";
				$autoincr = "yes";
			}
			
			if ($autoincr == "yes" && $type != "int"){
				$column["type"] = "int";
			}
			
			if ($type == "enum"){
				$type = "varchar";
				$maxlength = "255";
			}
			
			if ($type == "int"){
				$sql .= "integer";
			}elseif($type == "decimal"){
				$sql .= "varchar";
			}else{
				$sql .= $type;
			}
			
			//Defindes maxlength (and checks if maxlength is allowed on the current database-type).
			if ($type == "int" && $primarykey == "yes" && $autoincr == "yes"){
				//maxlength is not allowed when autoincr is true in SQLite (or else the column wont be auto incr).
			}elseif($maxlength){
				$sql .= "(" . $maxlength . ")";
			}
			
			//Defines some extras (like primary key, null and default).
			if ($primarykey == "yes"){
				$sql .= " PRIMARY KEY";
			}
			
			if ($column["notnull"] == "yes"){
				$sql .= " NOT NULL";
			}
			
			if (strlen($column["default"]) > 0){
				$sql .= " DEFAULT " . $this->knjdb->conn->sep_val . $this->knjdb->sql($column["default"]) . $this->knjdb->conn->sep_val;
			}
			
			return $sql . $ekstra;
		}
		
		function getColumns(knjdb_table $table){
			if ($table->columns_changed || count($table->columns) <= 0){
				$f_gc = $this->knjdb->query("PRAGMA table_info('" . $table->get("name") . "')");
				while($d_gc = $f_gc->fetch()){
					if (!$table->columns[$d_gc["name"]]){
						if (!$d_gc["notnull"]){
							$notnull = "no";
						}else{
							$notnull = "yes";
						}
						
						if ($d_gc["pk"] == "1"){
							$primarykey = "yes";
						}else{
							$primarykey = "no";
						}
						
						if (preg_match("/([a-zA-Z]+)\(([0-9]+)\)/", $d_gc["type"], $match)){
							$type = strtolower($match[1]);
							$maxlength = $match[2];
						}else{
							$type = strtolower($d_gc["type"]);
						}
						
						if ($type == "integer"){
							$type = "int";
						}
						
						$table->columns[$d_gc["name"]] = new knjdb_column($table, array(
								"name" => $d_gc["name"],
								"notnull" => $notnull,
								"type" => $type,
								"maxlength" => $maxlength,
								"default" => $d_gc["dflt_value"],
								"primarykey" => $primarykey,
								"input_type" => "sqlite",
								"autoincr" => ""
							)
						);
					}
				}
				
				$table->columns_changed = false;
			}
			
			return $table->columns;
		}
		
		function removeColumn(knjdb_table $table, knjdb_column $column_remove){
			$tablename = $table->get("name");
			
			//Again... SQLite has no "ALTER TABLE".
			$columns = $table->getColumns();
			$indexes = $table->getIndexes();
			$tempname = $tablename . "_temp";
			$table->rename($tempname);
			
			
			//Removing the specific column from the array.
			$cols = array();
			foreach($columns AS $key => $column){
				if ($column->get("name") != $column_remove->get("name")){
					$cols[] = $column->data;
				}
			}
			
			$this->knjdb->tables()->createTable($tablename, $cols);
			$newtable = $this->knjdb->getTable($tablename);
			
			
			$sql_insert = "INSERT INTO " . $this->knjdb->conn->sep_table . $tablename . $this->knjdb->conn->sep_table . " SELECT ";
			$first = true;
			foreach($columns AS $column){
				if ($column->get("name") != $column_remove->get("name")){
					if ($first == true){
						$first = false;
					}else{
						$sql_insert .= ", ";
					}
					
					$sql_insert .= $this->knjdb->conn->sep_col . $column->get("name") . $this->knjdb->conn->sep_col;
					$newcolumns[] = $value;
				}
			}
			
			$sql_insert .= " FROM " . $this->knjdb->conn->sep_table . $tempname . $this->knjdb->conn->sep_table;
			$this->knjdb->query($sql_insert);
			
			
			//Creating indexes again from the array, that we saved at the beginning. In short terms this will rename the columns which have indexes to the new names, so that they wont be removed.
			foreach($indexes AS $index){
				$cols = array();
				foreach($index->getColumns() AS $column){
					$cols[] = $newtable->getColumn($column->get("name"));
				}
			}
			
			
			//Drop the temp-table.
			$table->drop();
			unset($this->columns[$column_remove->get("name")]);
		}
		
		function addColumns(knjdb_table $table, $columns){
			//Again again... SQLite does not have a alter table... Fucking crap.
			//Starting by creating a name for the temp-table.
			$tempname = $table->get("name") . "_temp";
			$tablename = $table->get("name");
			
			//Editing the index-array for renamed columns.
			$indexes = $table->getIndexes();
			
			//Making SQL.
			$oldcolumns = $table->getColumns();
			
			$newcolumns = array();
			foreach($oldcolumns AS $column){
				$newcolumns[] = $column->data;
			}
			foreach($columns AS $column){
				$newcolumns[] = $column;
			}
			
			$table->rename($tempname);
			$this->knjdb->tables()->createTable($tablename, $newcolumns);
			$newtable = $this->knjdb->getTable($tablename);
			
			//If we are adding columns, the new columns are at their defaults, so we just have to add the old data.
			//Making SQL for insert into new table.
			$sql_insert = "INSERT INTO " . $this->knjdb->conn->sep_table . $tablename . $this->knjdb->conn->sep_table . " (";
			
			//Creating the fields that should be insertet into for the SQL.
			$first = true;
			foreach($oldcolumns AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql_insert .= ", ";
				}
				
				$sql_insert .= $this->knjdb->conn->sep_col . $column->get("name") . $this->knjdb->conn->sep_col;
			}
			
			//If a new column has set "notnull" to be true, then we MUST insert into it (thanks evil devil).
			foreach($columns AS $column){
				if ($column['notnull'] && !$column['default']){
					$sql_insert .= ", " . $this->knjdb->conn->sep_col . $column["name"] . $this->knjdb->conn->sep_col;
				}
			}
			
			$sql_insert .= ") SELECT ";
			$first = true;
			foreach($oldcolumns AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql_insert .= ", ";
				}
				
				$sql_insert .= $this->knjdb->conn->sep_col . $column->get("name") . $this->knjdb->conn->sep_col;
			}
			
			//If a new column has set "notnull" to be true, then we MUST insert into it (thanks evil devil). So 
			//we are just emulating an empty string, which will be insertet.
			foreach($columns AS $column){
				if ($column["notnull"] && !$column["default"]){
					$sql_insert .= ", '' AS " . $column["name"];
				}
			}
			
			$sql_insert .= " FROM " . $this->knjdb->conn->sep_col . $tempname . $this->knjdb->conn->sep_col;
			
			//Execute the insert-SQL.
			$this->knjdb->query($sql_insert);
			
			
			//Add indexes.
			foreach($indexes AS $index){
				$cols = array();
				foreach($index->getColumns() AS $col){
					$cols[] = $newtable->getColumn($col->get("name"));
				}
				
				$newtable->addIndex($cols);
			}
			
			
			//Drop the tempoary table.
			$table->drop();
			$newtable->columns_changed = true;
		}
		
		/** NOTE: This is actually just a pure copy from the SQLite3-driver. */
		function editColumn(knjdb_column $col, $newdata){
			$table = $col->getTable();
			$table_name = $table->get("name");
			$tempname = $table->get("name") . "_temp";
			$indexes = $this->knjdb->indexes()->getIndexes($table);
			$table->rename($tempname);
			
			$newcolumns = array();
			foreach($table->getColumns() AS $column){
				if ($column->get("name") == $col->get("name")){
					$newcolumns[] = $newdata;
				}else{
					$newcolumns[] = $column->data;
				}
			}
			
			if (count($newcolumns) <= 0){
				throw new Exception("wtf");
			}
			
			//Makinig SQL for creating the new table with updated columns and executes it.
			$this->knjdb->tables()->createTable($table_name, $newcolumns);
			$table_new = $this->knjdb->getTable($table_name);
			
			//Making SQL for inserting into it from the temp-table.
			$sql_insert = "INSERT INTO '" . $table_name . "' (";
			$sql_select = "SELECT * ";
			
			$count = 0;
			$first = true;
			foreach($table->getColumns() AS $column){
				if ($count > 0){
					$sql_insert .= ", ";
				}
				
				$sql_insert .= $newcolumns[$count]["name"];
				$count++;
			}
			
			$sql_select .= " FROM " . $tempname;
			$sql_insert .= ") " . $sql_select;
			
			$this->knjdb->query($sql_insert);
			$table->drop(); //drop old table which has been renamed.
			
			//Creating indexes again from the array, that we saved at the beginning. In short terms this will 
			//rename the columns which have indexes to the new names, so that they wont be removed.
			$newindexes = array();
			if ($indexes){
				foreach($indexes AS $index_key => $index){
					foreach($index->getColumns() AS $column_key => $column){
						if ($column->get("name") == $col->get("name")){
							$newindexes[$index_key][] = $table_new->getColumn($newdata["name"]);
						}else{
							$newindexes[$index_key][] = $table_new->getColumn($column->get("name"));
						}
					}
				}
			}
			
			foreach($newindexes AS $key => $cols){
				$table_new->addIndex($cols);
			}
			
			$table->data = $table_new->data; //if not it will bug up, if some other code has cached this object.
		}
	}
?>