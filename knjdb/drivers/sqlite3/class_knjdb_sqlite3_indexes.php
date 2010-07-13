<?php
	require_once("knj/knjdb/interfaces/class_knjdb_driver_indexes.php");
	
	class knjdb_sqlite3_indexes implements knjdb_driver_indexes{
		private $knjdb;
		
		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
		}
		
		function addIndex(knjdb_table $table, $cols, $name = null, $args = null){
			if (!$name){
				$name = "index";
				foreach($cols AS $col){
					$name .= "_" . $col->get("name");
				}
			}
			
			$sql = "CREATE INDEX " . $this->knjdb->conn->sep_col . $name . $this->knjdb->conn->sep_col . " ON " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table . " (";
			 
			$first = true;
			foreach($cols AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= $this->knjdb->conn->sep_col . $column->get("name") . $this->knjdb->conn->sep_col;
			}
			
			$sql .= ")";
			
			if ($args["returnsql"]){
				return $sql;
			}
			
			$this->knjdb->query($sql);
			$table->indexes_changed = true;
		}
		
		function getIndexSQL(knjdb_index $index){
			$sql = "CREATE INDEX " . $this->knjdb->conn->sep_col . $index->get("name") . $this->knjdb->conn->sep_col . " ON " . $this->knjdb->conn->sep_table . $index->table->get("name") . $this->knjdb->conn->sep_table . " (";
			 
			$first = true;
			foreach($index->getColumns() AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= $this->knjdb->conn->sep_col . $column->get("name") . $this->knjdb->conn->sep_col;
			}
			
			$sql .= ")";
			
			return $sql;
		}
		
		function getIndexes(knjdb_table $table){
			if ($table->indexes_changed){
				$f_gi = $this->knjdb->query("PRAGMA index_list(" . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table . ")");
				while($d_gi = $f_gi->fetch()){
					if (strpos($d_gi["name"], "sqlite") !== false && strpos($d_gi["name"], "autoindex") !== false){
						//This is a SQLite-auto-index - do not show or add.
					}elseif(!$table->indexes[$d_gi["name"]]){
						$index = array();
						$index["name"] = $d_gi["name"];
						
						$first = true;
						$columns_text = "";
						$f_gid = $this->knjdb->query("PRAGMA index_info('" . $d_gi["name"] . "')");
						while($d_gid = $f_gid->fetch()){
							if ($first == true){
								$first = false;
							}else{
								$columns_text .= ", ";
							}
							
							$index["columns"][] = $table->getColumn($d_gid["name"]);
						}
						
						$table->indexes[$index["name"]] = new knjdb_index($table, $index);
					}
				}
				
				$table->indexes_changed = false;
			}
			
			return $table->indexes;
		}
		
		function removeIndex(knjdb_table $table, knjdb_index $index){
			$sql = "DROP INDEX " . $this->knjdb->conn->sep_index . $index->get("name") . $this->knjdb->conn->sep_index;
			$this->knjdb->query($sql);
			$this->knjdb->query("VACUUM " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table);
			unset($table->indexes[$index->get("name")]);
		}
	}
?>