<?php
	require_once("knj/knjdb/interfaces/class_knjdb_driver_rows.php");
	
	class knjdb_sqlite2_rows implements knjdb_driver_rows{
		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
			$this->driver = $this->knjdb->conn;
		}
		
		function getObInsertSQL(knjdb_row $row){
			$data = $row->getAsArray();
			$table = $row->getTable();
			
			return $this->getArrInsertSQL($table->get("name"), $data);
		}
		
		function getArrInsertSQL($tablename, $data){
			if (!is_array($data)){
				throw new Exception("This function only accepts an array.");
			}
			
			$sql = "INSERT INTO " . $this->driver->sep_table . $tablename . $this->driver->sep_table . " (";
			
			$count = 0;
			foreach($data AS $key => $value){
				if ($count > 0){
					$sql .= ", ";
					$sql_vals .= ", ";
				}
				
				$sql .= $this->driver->sep_col . $key . $this->driver->sep_col;
				$sql_vals .= $this->driver->sep_val . $this->knjdb->sql($value) . $this->driver->sep_val;
				
				$count++;
			}
			
			$sql .= ") VALUES (" . $sql_vals . ");";
			
			return $sql;
		}
	}
?>