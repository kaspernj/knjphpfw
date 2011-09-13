<?php
	class knjdb_mssql_tables implements knjdb_driver_tables{
		public $knjdb;
		public $tables = array();
		private $tables_changed = true;
		
		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
		}
		
		function getTables(){
			if ($this->tables_changed){
				$f_gt = $this->knjdb->query("SELECT * FROM INFORMATION_SCHEMA.Tables WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
				while($d_gt = $f_gt->fetch()){
					$this->tables[$d_gt["TABLE_NAME"]] = new knjdb_table($this->knjdb, array(
							"name" => $d_gt["TABLE_NAME"]
						)
					);
				}
				
				$this->tables_changed = false;
			}
			
			return $this->tables;
		}
		
		function createTable($tablename, $cols, $args = null){
			$sql = "CREATE TABLE " . $this->knjdb->conn->sep_table . $tablename . $this->knjdb->conn->sep_table . " (";
			$prim_keys = array();
			
			$first = true;
			foreach($cols AS $col){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= $this->knjdb->columns()->getColumnSQL($col);
			}
			$sql .= ")";
			
			if ($args["returnsql"]){
				return $sql;
			}
			
			$this->knjdb->query($sql);
			$this->tables_changed = true;
		}
		
		function renameTable(knjdb_table $table, $newname){
			throw new Exception("Not supported.");
		}
		
		function dropTable(knjdb_table $table){
			throw new Exception("Not supported.");
		}
		
		function truncateTable(knjdb_table $table){
			throw new Exception("Not supported.");
		}
	}

