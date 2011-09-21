<?
	/** This class is meant to be implemented into the DBConn()-class. */
	class DBConn_tables extends DBConn_columns{
		/** Drops a table from the database. */
		function tableDrop($tablename){
			return $this->query("DROP TABLE " . $tablename);
		}
		
		/** Drops all tables on the database. */
		function tablesDropAll(){
			$tables = $this->getTables();
			foreach($tables AS $table){
				$this->tableDrop($table["name"]);
			}
		}
		
		/** Returns true if the given table exists. */
		function tableExists($table_name){
			$tables = $this->getTables();
			
			foreach($tables AS $table){
				if ($table["name"] == $table_name){
					return true;
				}
			}
			
			return false;
		}
		
		/** Returns a full list of tables in an array. */
		function getTables(){
			if ($this->getConn()){
				$return = array();
				
				if ($this->getType() == "mysql"){
					$f_gt = $this->query("SHOW TABLE STATUS", $this->conn);
					while($d_gt = $this->query_fetch_assoc($f_gt)){
						$return[] = array(
							"name" => $d_gt[Name],
							"engine" => $d_gt[Engine],
							"collation" => $d_gt[Collation],
							"rows" => $d_gt[Rows]
						);
					}
					
					return $return;
				}elseif($this->getType() == "pgsql"){
					$f_gt = $this->query("SELECT relname FROM pg_stat_user_tables ORDER BY relname");
					while($d_gt = $this->query_fetch_assoc($f_gt)){
						$return[] = array(
							"name" => $d_gt[relname],
							"engine" => "pgsql",
							"collation" => "pgsql"
						);
					}
					
					return $return;
				}elseif($this->getType() == "sqlite" || $this->getType() == "sqlite3"){
					$f_gt = $this->query("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name");
					while($d_gt = $this->query_fetch_assoc($f_gt)){
						if ($d_gt["name"] != "sqlite_sequence"){
							$return[] = array(
								"name" => $d_gt[name],
								"engine" => "sqlite",
								"collation" => "sqlite"
							);
						}
					}
					
					return $return;
				}elseif($this->getType() == "access"){
					$f_gt = odbc_tables($this->conn);
					while($d_gt = odbc_fetch_array($f_gt)){
						if ($d_gt[TABLE_TYPE] == "TABLE"){
							$return[] = array(
								"name" => $d_gt[TABLE_NAME],
								"engine" => "access",
								"collation" => "access"
							);
						}
					}
					
					return $return;
				}else{
					throw new Exception("Not a valid type: " . $this->getType());
				}
			}
		}
		
		/**
		 * Renames a table to something else.
		 * 
		 * @param string $oldtable The old table name - the table which should be renamed.
		 * @param string $newtable The new table name - what the table should be renamed to.
		*/
		function RenameTable($oldtable, $newtable){
			$oldtable = trim($oldtable);
			$newtable = trim($newtable);
			
			if ($oldtable == $newtable){
				throw new Exception("The old table-name is the same as the old one.");
			}
			
			if ($this->getType() == "mysql" || $this->getType() == "pgsql" || $this->getType() == "sqlite3"){
				$sql = $this->getSQLC()->tableRename($oldtable, $newtable);
				$result = $this->query($sql);
				
				if (!$result){
					echo "Warning: Could not rename table \"" . $oldtable . "\" to \"" . $newtable . "\".\n";
				}
				
				return $result;
			}elseif($this->getType() == "sqlite" || $this->getType() == "access"){
				//Fuck you very much SQLite. This is just pure pain... No "ALTER TABLE" :'(
				//Generating SQL for the table and replaces it with a new name.
				$indexes = $this->GetIndexes($oldtable);
				$columns = $this->GetColumns($oldtable);
				$sql_new = $this->getSQLC()->convertTable($newtable, $columns);
				
				
				//Executing the creating of the new table.
				if (!$this->query($sql_new)){
					echo "Warning: Could not rename table \"" . $oldtable . "\" to \"" . $newtable . "\" (couldnt create new-table).\n";
					return false;
				}
				
				//Inserting the old data.
				if (!$this->query("INSERT INTO " . $newtable . " SELECT * FROM " . $oldtable)){
					echo "Warning: Could not rename table \"" . $oldtable . "\" to \"" . $newtable . "\" (couldnt copy data to new table).\n";
					return false;
				}
				
				//Recreating indexes for the new table.
				$this->AddIndexFromGet($newtable, $indexes);
				
				//Dropping the old table.
				if (!$this->tableDrop($oldtable)){
					echo "Warning: Could not rename table \"" . $oldtable . "\" to \"" . $newtable . "\" (couldnt drop old table).\n";
					return false;
				}
				
				return true;
			}else{
				throw new Exception("Invalid type: " . $this->getType());
			}
		}
		
		/** Count the rows for a table. */
		function countRows($tablename){
			$f_cr = $this->query("SELECT COUNT(*) AS count FROM " . $tablename);
			if (!$f_cr){
				throw new Exception("Error in DBConnTables(): " . $this->query_error());
			}
			
			$d_cr = $this->query_fetch_assoc($f_cr);
			return $d_cr["count"];
		}
		
		/** Truncates a table on the database. */
		function truncateTable($table){
			if ($this->getType() == "mysql" || $this->getType() == "pgsql"){
				return $this->query("TRUNCATE " . $table);
			}elseif($this->getType() == "sqlite" || $this->getType() == "sqlite3"){
				return $this->query("DELETE FROM " . $table);
			}else{
				throw new Exception("Invalid type: " . $this->getType());
			}
		}
	}

