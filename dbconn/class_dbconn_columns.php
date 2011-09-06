<?
	/** This class is meant to be implemented into the DBConn()-class. It contains functions to manipulate columns. */
	class DBConn_columns extends DBConn_indexes{
		/** Returns the columns of a specified table-name. */
		function getColumns($tablename){
			if (!$this->conn){
				throw new Exception("No connection to a database.");
			}

			if (!trim($tablename)){
				throw new Exception("Not a valid table-name.");
			}

			if ($this->getType() == "mysql"){
				$f_gc = $this->query("SHOW FULL COLUMNS FROM " . $tablename) or die($this->query_error());
				while($d_gc = $this->query_fetch_assoc($f_gc)){
					$value = "";

					if ($d_gc['Null'] == "YES"){
						$notnull = "no";
					}else{
						$notnull = "yes";
					}

					if ($d_gc['Key'] == "PRI"){
						$primarykey = "yes";
					}else{
						$primarykey = "no";
					}

					if ($d_gc['Extra'] == "auto_increment"){
						$autoincr = "yes";
					}else{
						$autoincr = "no";
					}

					if ($d_gc['Type'] == "tinytext"){
						$maxlength = 255;
					}else{
						$maxlength = "";
					}

					$columns[$d_gc['Field']] = array(
						"name" => $d_gc['Field'],
						"notnull" => $notnull,
						"type" => $d_gc['Type'],
						"maxlength" => $maxlength,
						"default" => $d_gc['Default'],
						"primarykey" => $primarykey,
						"value" => $value,
						"input_type" => "mysql",
						"autoincr" => $autoincr,
						"comment" => $d_gc["Comment"]
					);
				}
			}elseif($this->getType() == "pgsql"){
				$f_gc = $this->query("
					SELECT
						pg_attribute.attname AS fieldname,
						pg_attribute.atttypmod AS maxlength,
						pg_attribute.attnotnull AS notnull,
						pg_type.typname AS fieldtype,
						pg_attribute.atthasdef,
						pg_class.oid

					FROM
						pg_attribute,
						pg_class,
						pg_type

					WHERE
						pg_class.oid = pg_attribute.attrelid AND
						pg_class.relname = '$tablename' AND
						pg_attribute.attnum > 0 AND
						pg_type.oid = pg_attribute.atttypid
				");
				while($d_gc = $this->query_fetch_assoc($f_gc)){
					if ($d_gc[atthasdef] == "t"){
						//The column has a default value, which we have to look up.
						$f_gdv = $this->query("SELECT * FROM pg_attrdef WHERE adrelid = '$d_gc[oid]'");
						$d_gdv = $this->query_fetch_assoc($f_gdv);

						$default = substr($d_gdv[adsrc], 1, -1);
					}else{
						$default = "";
					}

					if ($d_gc[notnull] == "t"){
						$notnull = "yes";
					}else{
						$notnull = "no";
					}

					if (preg_match("/^int[0-9]$/", $d_gc[fieldtype])){
						$type = "int";
					}else{
						$type = $d_gc[fieldtype];
					}

					if ($d_gc[maxlength] == -1){
						$maxlength = "";
					}else{
						$maxlength = $d_gc[maxlength] - 4;
					}

					$primarykey = "no";

					$columns[] = array(
						"name" => $d_gc['fieldname'],
						"notnull" => $notnull,
						"type" => $type,
						"maxlength" => $maxlength,
						"default" => $default,
						"primarykey" => $primarykey,
						"input_type" => "pgsql"
					);
				}
			}elseif($this->getType() == "sqlite"){
				$f_gc = $this->query("PRAGMA table_info(" . $tablename . ")") or die($this->query_error());
				while($d_gc = sqlite_fetch_array($f_gc)){
					if (!$d_gc['notnull']){
						$notnull = "no";
					}else{
						$notnull = "yes";
					}

					if ($d_gc['pk'] == "1"){
						$primarykey = "yes";
					}else{
						$primarykey = "no";
					}

					$columns[$d_gc['name']] = array(
						"name" => $d_gc['name'],
						"notnull" => $notnull,
						"type" => $d_gc['type'],
						"maxlength" => $maxlength,
						"default" => $d_gc['dflt_value'],
						"primarykey" => $primarykey,
						"input_type" => "sqlite"
					);
				}
			}elseif($this->getType() == "sqlite3"){
				$f_gc = $this->query("PRAGMA table_info(" . $tablename . ")") or die($this->query_error());
				while($d_gc = $this->query_fetch_assoc($f_gc)){
					if (!$d_gc['notnull']){
						$notnull = "no";
					}else{
						$notnull = "yes";
					}

					if ($d_gc['pk'] == "1"){
						$primarykey = "yes";
					}else{
						$primarykey = "no";
					}

					$columns[$d_gc['name']] = array(
						"name" => $d_gc['name'],
						"notnull" => $notnull,
						"type" => $d_gc[type],
						"maxlength" => $maxlength,
						"default" => $d_gc['dflt_value'],
						"primarykey" => $primarykey,
						"input_type" => "sqlite"
					);
				}
			}elseif($this->getType() == "access"){
				$f_gc = odbc_columns($this->conn);
				while($d_gc = odbc_fetch_array($f_gc)){
					if ($d_gc[TABLE_NAME] == $tablename){
						if ($d_gc[IS_NULLABLE] == "YES"){
							$notnull = "no";
						}else{
							$notnull = "yes";
						}

						$columns[$d_gc[COLUMN_NAME]] = array(
							"name" => $d_gc[COLUMN_NAME],
							"type" => $d_gc[TYPE_NAME],
							"maxlength" => $d_gc[COLUMN_SIZE],
							"notnull" => $notnull,
							"input_type" => "access"
						);
					}
				}
			}else{
				throw new Exception("Not a valid type: " . $this->getType());
			}

			//So that all types seems the same to the program.
			if (!$columns){
				return array();
			}

			foreach($columns AS $key => $value){
				/** NOTE: Fix bug when decimal- and enum-columns hadnt their maxlength set. */
				$type = $columns[$key]["type"];
				if (preg_match("/^decimal\(([0-9]+),([0-9]+)\)$/", $type, $match)){
					//this is a decimal-field.
					$columns[$key]["type"] = "decimal";
					$columns[$key]["maxlength"] = $match[1] . "," . $match[2];
				}elseif(preg_match("/^enum\((.+)\)$/", $type, $match)){
					//this is a enum-field.
					$columns[$key]["type"] = "enum";
					$columns[$key]["maxlength"] = $match[1];
				}elseif(preg_match("/([a-zA-Z]+)\(([0-9]+)\)/", $type, $match)){
					$columns[$key]["type"] = $match[1];
					$columns[$key]["maxlength"] = $match[2];
				}

				/** NOTE: Standardlize the column-types. */
				$columns[$key]["type"] = strtolower(trim($columns[$key]["type"]));
				if ($columns[$key]["type"] == "integer"){
					$columns[$key]["type"] = "int";
				}elseif($value["type"] == "counter"){
					$columns[$key]["type"] = "counter";
					$columns[$key]["primarykey"] = "yes";
					$columns[$key]["autoincr"] = "yes";
				}

				/** NOTE: Fix bug with quotes on default values (when saved they would have double quotes). */
				if ($columns[$key]["default"]){
					if (substr($columns[$key]["default"], 0, 1) == "'" && substr($columns[$key]["default"], -1, 1) == "'"){
						$columns[$key]["default"] = substr($columns[$key]["default"], 1, -1);
					}
				}
			}

			return $columns;
		}

		/** Adds new columns to a table. */
		function addColumns($table, $columns, $oldcolumns = false){
			if ($this->getType() == "mysql" || $this->getType() == "pgsql"){
				$sql = makesql_addColumns($this->getType(), $table, $columns, $oldcolumns);
				return $this->query($sql);
			}elseif($this->getType() == "sqlite3"){
				/** NOTE: SQLite3 can only add one column per query. */
				foreach($columns AS $column){
					$sql = makesql_addColumns($this->getType(), $table, array($column));
					if (!$this->query($sql)){
						echo "Warning: Could not add column.\n";
						return false;
					}
				}

				return true;
			}elseif($this->getType() == "sqlite"){
				//Again again... SQLite does not have a alter table... Fucking crap.
				//Starting by creating a name for the temp-table.
				$tempname = $table . "_temp";

				//Editing the index-array for renamed columns.
				$indexes = $this->GetIndexes($table);

				//Making SQL.
				$oldcolumns = $this->GetColumns($table);
				$actual_columns = array_merge($oldcolumns, $columns);
				$sql_createtable = $this->getSQLC()->convertTable($table, $actual_columns);

				//Renaming the table to the temp-name.
				if (!$this->RenameTable($table, $tempname)){
					return false;
				}

				//Creating the new table.
				if (!$this->query($sql_createtable)){
					return false;
				}

				//If we are adding columns, the new columns are at their defaults, so we just have to add the old data.
				//Making SQL for insert into new table.
				$sql_insert = "INSERT INTO " . $table . " (";

				//Creating the fields that should be insertet into for the SQL.
				$first = true;
				foreach($oldcolumns AS $column){
					if ($first == true){
						$first = false;
					}else{
						$sql_insert .= ", ";
					}

					$sql_insert .= $column['name'];
				}

				//If a new column has set "notnull" to be true, then we MUST insert into it (thanks evil devil).
				foreach($columns AS $column){
					if ($column['notnull'] && !$column['default']){
						$sql_insert .= ", " . $column['name'];
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

					$sql_insert .= $column[name];
				}

				//If a new column has set "notnull" to be true, then we MUST insert into it (thanks evil devil). So
				//we are just emulating an empty string, which will be insertet.
				foreach($columns AS $column){
					if ($column[notnull] && !$column['default']){
						$sql_insert .= ", '' AS " . $column[name];
					}
				}

				$sql_insert .= " FROM " . $tempname;

				//Execute the insert-SQL.
				if (!$this->query($sql_insert)){
					return false;
				}

				//Drop the tempoary table.
				if (!$this->query("DROP TABLE " . $tempname)){
					return false;
				}

				if (!$this->AddIndexFromGet($table, $indexes)){
					return false;
				}

				return true;
			}
		}

		/** Modifies columns. */
		function editColumns($table, $oldcolumns, $newcolumns){
			if ($this->getType() == "mysql" || $this->getType() == "pgsql"){
				$sql = makesql_editColumns($this->getType(), $table, $oldcolumns, $newcolumns);

				//It will return false, if nothing is changed.
				if ($sql){
					if (!$this->query($sql)){
						echo "Warning: SQL failed: " . $sql . "\n";
						return false;
					}
				}

				return true;
			}elseif($this->getType() == "sqlite" || $this->getType() == "sqlite3"){
				//Setting the temp-name for a temp-table.
				$tempname = $table . "_temp";

				//Getting the indexes for later use.
				$indexes = $this->GetIndexes($table);

				//Rename the current table to the temp-name.
				if (!$this->RenameTable($table, $tempname)){
					echo "Warning: Could not rename the table.\n";
					return false;
				}

				//Makinig SQL for creating the new table with updated columns and executes it.
				$sql_createtable = $this->getSQLC()->convertTable($table, $newcolumns);
				if (!$this->query($sql_createtable)){
					echo "Warning: Could not create new table while trying to edit the columns.\n";
					return false;
				}

				//Making SQL for inserting into it from the temp-table.
				$sql_insert = "INSERT INTO '" . $table . "' (";
				$sql_select = "SELECT ";

				$count = 0;   //FIX: Used to determine, where we are in the $newcolumns-array.
				$first = true;
				foreach($oldcolumns AS $key => $value){
					if ($first == true){
						$first = false;
					}else{
						$sql_insert .= ", ";
						$sql_select .= ", ";
					}

					$sql_insert .= $newcolumns[$count][name];
					$sql_select .= $value[name] . " AS " . $value[name];

					$count++;   //FIXED.
				}

				$sql_select .= " FROM " . $tempname;
				$sql_insert .= ") " . $sql_select;

				if (!$this->query($sql_insert)){
					echo "Warning: Could not copy data from the old table to the temp one while trying to edit the columns.\n";
					return false;
				}

				//Dropping the temp-table. This must be done before re-creating the indexes. If not we will
				//try to create a index, with a index-id which already exists (we will therefore fail).
				if (!$this->tableDrop($tempname)){
					echo "Warning: Could not drop the temp table: \"" . $tempname . "\".\n";
					return false;
				}

				//Creating indexes again from the array, that we saved at the beginning. In short terms this will
				//rename the columns which have indexes to the new names, so that they wont be removed.
				if ($indexes){
					foreach($indexes AS $index_key => $index){
						foreach($index[columns] AS $column_key => $column){
							foreach($oldcolumns AS $ocolumn_key => $ocolumn){
								if ($column == $ocolumn[name]){
									//Updating index-array.
									$indexes[$index_key][columns][$column_key] = $newcolumns[$ocolumn_key][name];
								}
							}
						}
					}
				}

				if (!$this->AddIndexFromGet($table, $indexes)){
					echo "Warning: Could not add indexes to the table.\n";
					return false;
				}

				return true;
			}else{
				throw new Exception("Invalid type: " . $this->getType());
			}
		}

		/** Removes a specified column from a table. */
		function removeColumn($table, $column){
			if ($this->getType() == "mysql" || $this->getType() == "pgsql"){
				return $this->query("ALTER TABLE " . $table . " DROP COLUMN " . $column, $this->conn);
			}elseif($this->getType() == "sqlite" || $this->getType() == "sqlite3"){
				//Getting the indexes for later use.
				$indexes = $this->GetIndexes($table);
				$oldcolumns = $this->getColumns($table);

				//Again... SQLite has no "ALTER TABLE".
				$columns = $this->GetColumns($table);
				$indexes = $this->GetIndexes($table);
				$tempname = $table . "_temp";

				if (!$this->RenameTable($table, $tempname)){
					echo "Warning: Could not rename \"" . $table . "\" to \"" . $tempname . "\".\n";
					return false;
				}

				//Removing the specifik removing column from the array.
				foreach($columns AS $key => $value){
					if ($value[name] == $column){
						unset($columns[$key]);
						break;
					}
				}

				$sql = makesql_table($this->getType(), $table, $columns);
				if (!$this->query($sql)){
					echo "Warning: Could not create new table: \"" . $table . "\".\n";
					return false;
				}

				$sql_insert = "INSERT INTO " . $table . " SELECT ";
				$first = true;
				foreach($columns AS $value){
					if ($value["name"] != $column){
						$sql_insert .= $value["name"];
						$newcolumns[] = $value;
					}
				}

				$sql_insert .= " FROM " . $tempname;
				$this->query($sql_insert);


				//Creating indexes again from the array, that we saved at the beginning. In short terms this will rename the columns which have indexes to the new names, so that they wont be removed.
				if (!$this->AddIndexFromGet($table, $indexes)){
					echo "Warning: Could not add indexes to the table.\n";
					return false;
				}


				//Drop the temp-table.
				if (!$this->tableDrop($tempname)){
					echo "Warning: Could not drop the temp-table: \"" . $tempname . "\".\n";
					return false;
				}

				return true;
			}else{
				throw new Exception("Not a valid type: " . $this->getType());
			}
		}
	}

