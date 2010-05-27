<?
	class SQLConverter{
		public $type_output;		//The SQL-language.
		public $sep;				//The seperator, which should be used.
		
		/** Sets the current output-type. */
		function SetOutputType($type){
			if ($type != "mysql" && $type != "pgsql" && $type != "sqlite" && $type != "access" && $type != "mssql" && $type != "sqlite3"){
				throw new Exception($type . " is not supported.");
			}else{
				$this->type_output = $type;
				$this->SetSep();
			}
		}
		
		/** Returns the current output-type. */
		function getType(){
			return $this->type_output;
		}
		
		function SetSep(){
			if ($this->getType() == "mysql"){
				$this->sep = "`";
			}elseif($this->getType() == "pgsql"){
				$this->sep = "";
			}elseif(($this->getType() == "sqlite" || $this->getType() == "sqlite3")){
				$this->sep = "'";
			}elseif($this->getType() == "access"){
				$this->sep = "`";
			}else{
				throw new Exception("Invalid type: " . $this->getType());
			}
		}
		
		/** Returns the SQL for a table. */
		function ConvertTable($tablename, $columns){
			$sql = "CREATE TABLE " . $this->sep . $tablename . $this->sep . " (";
			$prim_keys = array();
			
			$first = true;
			foreach($columns AS $tha_column){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				//Only one primary key - this fixes it by setting only the first key registered as primary.
				if ($tha_column['primarykey'] == "yes"){
					if (($this->getType() == "sqlite" || $this->getType() == "sqlite3") && $tha_column["autoincr"] == "yes"){
						//Dont do this, since it will make the column non-autoincr!!
					}else{
						//Diable primary per column.
						$tha_column['primarykey'] = "no";
						
						//Remember which keys for end-primary-key set.
						$prim_keys[] = $tha_column['name'];
					}
				}
				
				$sql .= $this->ConvertColumn($tha_column);
			}
			
			//Make primary keys in the end to support joined-primary-keys.
			if ($prim_keys){
				$sql .= ", PRIMARY KEY (";
				
				$first = true;
				foreach($prim_keys AS $column_name){
					if ($first == true){
						$first = false;
					}else{
						$sql .= ", ";
					}
					
					$sql .= $this->sep . $column_name . $this->sep;
				}
				
				$sql .= ")";
			}
			
			$sql .= ");\n";
			
			return $sql;
		}
		
		function ConvertColumn($column){
			$sql = $this->sep . $column['name'] . $this->sep . " ";
			
			//Follow code checks for un-supported table-types.
			//MySQL-specific columns.
			if ($this->getType() != "mysql" && $this->getType() != "sqlite3" && ($column[type] == "tinyint" || $column[type] == "mediumint")){
				$column[type] = "int";
			}elseif($this->getType() != "mysql" && $this->getType() != "sqlite3" && $column[type] == "enum"){
				$column[type] = "varchar";
				$column[maxlength] = "";
			}elseif($this->getType() == "pgsql" && $column[type] == "tinytext"){
				$column[type] = "varchar";
				$column[maxlength] = "255";
			}
			
			if ($column[type] == "counter" && $this->getType() != "access"){
				//This is an Access-primarykey-autoincr-column - convert it!
				$sql .= "int";
				$col[type] = "int";
				
				$column["primarykey"] = "yes";
				$column["autoincr"] = "yes";
			}
			
			if ($this->getType() == "sqlite3" && $column[autoincr] == "yes" && $column[type] != "int"){
				$column["type"] = "int";
			}
			
			if ($this->getType() == "mysql" && $column[type] == "varchar" && ($column[maxlength] <= 0 || !$maxlength)){
				$column["maxlength"] = 255;
			}
			
			if ($column["type"] == "enum" && ($this->getType() == "sqlite" || $this->getType() == "sqlite3")){
				$column["type"] = "varchar";
				$column["maxlength"] = "255";
			}
			
			if ($column[type] == "int"){
				if ($this->getType() == "access" && $column[autoincr] == "yes" && $column[primarykey] == "yes"){
					$sql .= "counter";
					$col[type] = "counter";
				}elseif($this->getType() == "postgresql" || $this->getType() == "sqlite" || $this->getType() == "sqlite3" || $this->getType() == "access"){
					$sql .= "integer";
					$col[type] = "int";
				}else{
					$sql .= "int";
					$col[type] = "int";
				}
			}elseif($this->getType() == "access" && $column[type] == "tinytext"){
				//Access does not support tinytext.
				$sql .= "text";
			}elseif($this->getType() != "mysql" && $this->getType() != "sqlite3" && $column[type] == "decimal"){
				$sql .= "varchar";
			}else{
				$sql .= $column['type'];
			}
			
			//Defindes maxlength (and checks if maxlength is allowed on the current database-type).
			if ($this->getType() == "mysql" && ($column[type] == "datetime" || $column[type] == "tinytext" || $column[type] == "text")){
				//maxlength is not allowed in MySQL. So nothing goes here (Access can actually have a maxlength on a datetime).
			}elseif($this->getType() == "pgsql" && ($column[type] == "int")){
				//maxlength is not allowed on integers in PostgreSQL.
			}elseif($this->getType() == "access" && ($col[type] == "int" || $col[type] == "counter" || $column[type] == "int" || $column[type] == "counter")){
				//maxlength is not allowed in Access on an integer or a counter-type.
			}elseif(($this->getType() == "sqlite" || $this->getType() == "sqlite3") && $col["type"] == "int" && $column["primarykey"] == "yes" && $column["autoincr"] == "yes"){
				//maxlength is not allowed when autoincr is true in SQLite (or else the column wont be auto incr).
			}elseif($column['maxlength']){
				$sql .= "(" . $column['maxlength'] . ")";
			}
			
			//Defines some extras (like primary key, null and default).
			if ($column['primarykey'] == "yes"){
				if ($this->getType() == "mysql" || ($this->getType() == "sqlite" || $this->getType() == "sqlite3")){
					$sql .= " PRIMARY KEY";
				}
			}
			
			if ($column["autoincr"] == "yes"){
				if ($this->getType() == "mysql"){
					$sql .= " AUTO_INCREMENT";
				}elseif($this->getType() == "sqlite"){
					//SQLite2 does not support autoincrement.
				}else{
					$sql .= " AUTOINCREMENT";
				}
			}
			
			if ($column['notnull'] == "yes"){
				$sql .= " NOT NULL";
			}
			
			if (isset($column['default']) && $this->getType() != "access"){
				$sql .= " DEFAULT '" . $this->ParseQuotes($column['default']) . "'";
			}
			
			return $sql . $ekstra;
		}
		
		function ConvertInsert($tablename, $data, $columns_input){
			$sql = "INSERT INTO " . $this->sep . $tablename . $this->sep . " (";
			
			$first = true;
			foreach($data AS $key => $value){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= $this->sep . $key . $this->sep;
			}
			
			$sql .= ") VALUES (";
			
			$first = true;
			foreach($data AS $key => $value){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				if ($this->getType() == "access" && is_numeric($value) && ($columns[$key][type] == "int" || $columns[$key][type] == "counter")){
					$sql .= $value;
				//date-converter
				}elseif($columns[$key][input_type] == "mysql" && $columns[$key][type] == "datetime"){
					$sql .= $this->ConvertInsert_date($columns[$key], $value);
				}else{
					$sql .= "'" . $this->ParseQuotes($value) . "'";
				}
			}
			
			$sql .= ");\n";
			
			return $sql;
		}
		
		/** Returns the SQL for renaming a table based on the dbtype. */
		function tableRename($oldtable, $newtable){
			$sep = $this->sep;
			
			if ($this->getType() == "mysql"){
				return "ALTER TABLE " . $sep . $oldtable . $sep . " RENAME TO " . $sep . $newtable . $sep;
			}elseif($this->getType() == "pgsql" || $this->getType() == "sqlite3"){
				return "ALTER TABLE " . $oldtable . " RENAME TO " . $newtable;
			}else{
				throw new Exception("Invalid type: " . $this->getType());
			}
		}
		
		function ConvertInsert_date(&$column, &$value){
			if ($column[input_type] == "mysql" && ($column[type] == "datetime" || $column[type] == "timestamp")){
				//0000-00-00 00:00:00
				if (($this->getType() == "sqlite" || $this->getType() == "sqlite3") || $this->getType() == "mysql"){
					//No reason to convert.
					return "'" . $value . "'";
				}
				
				if ($this->getType() == "pgsql" && $value != "0000-00-00 00:00:00"){
					return "'" . $value . "'";
				}elseif($this->getType() == "pgsql" && $value == "0000-00-00 00:00:00" && $column[notnull] == "no"){
					//A NULL is acceptable, and since PostgreSQL doesnt support "0000-00-00 00:00:00" as a date, NULL must replace it.
					return "NULL";
				}elseif($this->getType() == "pgsql" && $value == "0000-00-00 00:00:00" && $column[notnull] == "yes"){
					//Worst case. We cant insert the date, so we actually have to change it to something close and valid. PostgreSQL should be shot.
					return "'0000-01-01'";
				}
				
				if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/", $value, $match)){
					$unix_time = mktime($match[4], $match[5], $match[6], $match[2], $match[3], $match[1]);
					
					if (($this->getType() == "sqlite" || $this->getType() == "sqlite3") || $this->getType() == "mysql" || $this->getType() == "pgsql"){
						return "'" . date("Y-m-d H:i:s", $unix_time) . "'";
					}elseif($this->getType() == "access"){
						return "";
					}elseif($this->getType() == "mssql"){
						return "";
					}
				}else{
					die("Could not match datetime: " . $value . "\n");
					return "";
				}
			}elseif($column[input_type] == "mysql" && $column[type] == "time"){
				//00:00:00
				if (($this->getType() == "sqlite" || $this->getType() == "sqlite3") || $this->getType() == "mysql" || $this->getType() == "pgsql"){
					//No reason to convert.
					return $value;
				}
				
				if (preg_match("/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/", $value, $match)){
					$unix_time = mktime($match[1], $match[2], $match[3]);
					
					if ($this->getType() == "access"){
						return "";
					}elseif($this->getType() == "mssql"){
						return "";
					}
				}
			}
			
			if (!$unix_time){
				die("No unix time convert.\n");
			}
		}
		
		/** Returns the SQL of an index as a string. */
		function convertIndex($tha_table, $index){
			$sql = "CREATE INDEX " . $this->sep . $index[name] . $this->sep . " ON " . $this->sep . $tha_table . $this->sep . " (";
			 
			$first = true;
			foreach($index[columns] AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= $this->sep . $column . $this->sep;
			}
			
			$sql .= ");\n";
			
			return $sql;
		}
		
		function ParseQuotes($string){
			if ($this->getType() == "access"){
				$string = str_replace("'", "''", $string);
				$string = str_replace("\r\n", "' & CHR(10) & CHR(13) & '", $string);
				$string = str_replace("\r", "' & CHR(10) & '", $string);
				$string = str_replace("\n", "' & CHR(13) & '", $string);
			}elseif(($this->getType() == "sqlite" || $this->getType() == "sqlite3")){
				$string = str_replace("'", "''", $string);
				$string = str_replace("\\", "\\\\", $string);
				$string = str_replace("\r", "\\r", $string);
				$string = str_replace("\n", "\\n", $string);
				
				if (substr($string, -1, 1) == "\\" && substr($string, -2, 2) !== "\\\\"){
					$string = substr($string, 0, -1) . "\\\\";
				}
			}else{
				$string = str_replace("\\", "\\\\", $string);
				$string = str_replace("'", "\'", $string);
				$string = str_replace("\r", "\\r", $string);
				$string = str_replace("\n", "\\n", $string);
				
				if (substr($string, -1, 1) == "\\" && substr($string, -2, 2) !== "\\\\"){
					$string = substr($string, 0, -1) . "\\\\";
				}
			}
			
			return $string;
		}
	}
?>