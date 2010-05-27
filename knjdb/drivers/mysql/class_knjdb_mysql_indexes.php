<?php
	require_once("knjphpframework/knjdb/interfaces/class_knjdb_driver_indexes.php");
	
	class knjdb_mysql_indexes implements knjdb_driver_indexes{
		public $knjdb;
		
		function __construct($knjdb){
			$this->knjdb = $knjdb;
		}
		
		function getIndexSQL(knjdb_index $index){
			$sql = "CREATE INDEX " . $this->knjdb->connob->sep_col . $index->get("name") . $this->knjdb->connob->sep_col . " ON " . $this->knjdb->connob->sep_table . $index->getTable()->get("name") . $this->knjdb->connob->sep_table . " (";
			$first = true;
			foreach($index->getColumns() AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= $this->knjdb->connob->sep_col . $column->get("name") . $this->knjdb->connob->sep_col;
			}
			$sql .= ");\n";
			
			return $sql;
		}
		
		function addIndex(knjdb_table $table, $cols, $name = null, $args = null){
			if (!$name){
				$name = "index";
				foreach($cols AS $col){
					$name .= "_" . $col->get("name");
				}
			}
			
			$sql = "CREATE INDEX " . $this->knjdb->connob->sep_table . $name . $this->knjdb->connob->sep_table . " ON " . $this->knjdb->connob->sep_table . $table->get("name") . $this->knjdb->connob->sep_table . " (";
			
			$first = true;
			foreach($cols AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= $this->knjdb->connob->sep_column . $column->get("name") . $this->knjdb->connob->sep_column;
			}
			
			$sql .= ")";
			
			if ($args["returnsql"]){
				return $sql;
			}
			
			$this->knjdb->query($sql);
			$table->indexes_changed = true;
		}
		
		function removeIndex(knjdb_table $table, knjdb_index $index){
			$sql = "DROP INDEX " . $this->knjdb->conn->sep_index . $index->get("name") . $this->knjdb->conn->sep_index . " ON " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table;
			$this->knjdb->query($sql);
			unset($table->indexes[$index->get("name")]);
		}
		
		function getIndexes(knjdb_table $table){
			if ($table->indexes_changed){
				$f_gi = $this->knjdb->query("SHOW INDEX FROM " . $this->knjdb->connob->sep_table . $table->get("name") . $this->knjdb->connob->sep_table);
				while($d_gi = $f_gi->fetch()){
					if ($d_gi["Key_name"] != "PRIMARY"){
						$key = $d_gi["Key_name"];
						$index[$key]["name"] = $d_gi["Key_name"];
						$index[$key]["columns"][] = $table->getColumn($d_gi["Column_name"]);
					}
				}
				
				//Making keys to numbers (as in SQLite).
				$return = array();
				if ($index){
					foreach($index AS $name => $value){
						if (!$this->indexes[$name]){
							$table->indexes[$name] = new knjdb_index($table, $value);
						}
					}
				}
				
				$table->indexes_changed = false;
			}
			
			return $table->indexes;
		}
	}
?>