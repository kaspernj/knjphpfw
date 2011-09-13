<?
	class DBConn_indexes{
		function GetIndexes($tablename){
			if ($this->conn && $tablename){
				if ($this->getType() == "mysql"){
					$f_gi = $this->query("SHOW INDEX FROM " . $tablename) or die($this->query_error());
					while($d_gi = $this->query_fetch_assoc($f_gi)){
						if ($d_gi[Key_name] != "PRIMARY"){
							$key = $d_gi[Key_name];
							
							$index[$key][name] = $d_gi[Key_name];
							$index[$key][columns][] = $d_gi[Column_name];
							
							if ($index[$key][columns_text]){
								$index[$key][columns_text] .= ", ";
							}
							
							$index[$key][columns_text] .= $d_gi[Column_name];
						}
					}
					
					//Making keys to numbers (as in SQLite).
					if ($index){
						foreach($index AS $key => $value){
							$return[] = $value;
						}
					}
					
					return $return;
				}elseif($this->getType() == "pgsql"){
					//Extraction index (fuck you very much PostgreSQL)
					//Read this link for documentation: http://www.postgresql.org/docs/7.4/static/catalog-pg-index.html
					
					$f_gi = $this->query("
						SELECT
							table_data.relname AS table_name,
							index_data.relname AS index_name,
							pg_index.indkey AS column_numbers
						
						FROM
							pg_index
						
						LEFT JOIN pg_class AS index_data ON
							index_data.oid = pg_index.indexrelid
						
						LEFT JOIN pg_class AS table_data ON
							table_data.oid = pg_index.indrelid
						
						WHERE
							table_data.relname = '$tablename'
					") or die($this->query_error());
    				while($d_gi = $this->query_fetch_assoc($f_gi)){
    					$column_numbers = explode(" ", $d_gi[column_numbers]);
    					foreach($column_numbers AS $value){
    						$cn[$value] = true;
    					}
    					
    					$columns = array();
    					
    					$count = 0;
    					$f_gc = $this->query($this->conn, "SELECT column_name FROM information_schema.columns WHERE table_name = '$tablename' ORDER BY ordinal_position") or die($this->query_error());
    					while($d_gc = $this->query_fetch_assoc($f_gc)){
    						$count++;
    						
    						if ($cn[$count]){
    							$columns[] = $d_gc[column_name];
    						}
    					}
    					
    					$return[] = array(
    						"name" => $d_gi[index_name],
    						"columns" => $columns,
    						"columns_text" => implode(", ", $columns)
    					);
    				}
    				
    				return $return;
				}elseif($this->getType() == "sqlite" || $this->getType() == "sqlite3"){
					$f_gi = $this->query("PRAGMA index_list(" . $tablename . ")") or die($this->query_error());
					while($d_gi = $this->query_fetch_assoc($f_gi)){
						if (strpos($d_gi[name], "sqlite") !== false && strpos($d_gi[name], "autoindex") !== false){
							//This is a SQLite-auto-index - do not show or add.
						}else{
							$index = array();
							$index[name] = $d_gi[name];
							
							$first = true;
							$columns_text = "";
							$f_gid = $this->query("PRAGMA index_info('" . $d_gi['name'] . "')");
							while($d_gid = $this->query_fetch_assoc($f_gid)){
								if ($first == true){
									$first = false;
								}else{
									$columns_text .= ", ";
								}
								
								$columns_text .= $d_gid[name];
								$index[columns][] = $d_gid[name];
							}
							
							$index[columns_text] = $columns_text;
							
							$return[] = $index;
						}
					}
					
					return $return;
				}elseif($this->getType() == "access"){
					//Thanks for making it impossible to read indexes (even just to read them) without manually
					//editting it through Microsoft Access. Way to go fucking Microsoft (plz burn somewhere bellow).
					return false;
				}else{
					throw new Exception("Invalid type: " . $this->getType());
				}
			}
		}
		
		function AddIndex($tablename, $columns, $title = false){
			if (!$title){
				$title .= implode("_", $columns);
			}
			
			if ($this->getType() == "sqlite3"){
				$title = $tablename . "_" . $title;
			}
			
			$index[name] = $title;
			$index[columns] = $columns;
			
			$sql = $this->getSQLC()->convertIndex($tablename, $index);
			
			if ($this->query($sql)){
				return true;
			}else{
				return false;
			}
		}
		
		function AddIndexFromGet($tablename, $indexes){
			if ($indexes){
				foreach($indexes AS $index){
					if (!$this->AddIndex($tablename, $index[columns], $index[name])){
						throw new Exception("Could not add index.\n\nTablename: " . $tablename . "\n\nDB-error:\n" . $this->query_error());
					}
				}
			}
			
			return true;
		}
		
		function DropIndex($tablename, $indexname){
			if ($this->getType() == "mysql"){
				return $this->query("DROP INDEX `" . $indexname . "` ON `" . $tablename . "`") or die($this->query_error());
			}elseif($this->getType() == "pgsql"){
				return $this->query("DROP INDEX `" . $indexname . "`") or die($this->query_error());
			}elseif($this->getType() == "sqlite" || $this->getType() == "sqlite3"){
				if (!$this->query("DROP INDEX '" . $indexname . "'")){
					echo "Warning: Could not drop the index.\n";
					return false;
				}
				
				if (!$this->query("VACUUM " . $tablename)){
					echo "Warning: Could not run VACUUM on the table.\n";
					return false;
				}
				
				return true;
			}
		}
	}

