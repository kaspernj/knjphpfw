<?php
	class knjdb_mssql_indexes implements knjdb_driver_indexes{
		private $knjdb;
		
		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
		}
		
		function addIndex(knjdb_table $table, $cols, $name = null, $args = null){
			throw new Exception("Not supported.");
		}
		
		function getIndexSQL(knjdb_index $index){
			throw new Exception("Not supported.");
		}
		
		function getIndexes(knjdb_table $table){
			if ($table->indexes_changed){
				$f_gi = $this->knjdb->query("
					SELECT
						id,
						name,
						indid,
						OBJECT_NAME(id) AS TableName
					
					FROM
						sysindexes
					
					WHERE
						OBJECT_NAME(sysindexes.id) = '" . $this->knjdb->sql($table->get("name")) . "'
				");
				while($d_gi = $f_gi->fetch()){
					$columns = array();
					$f_gik = $this->knjdb->query("
						SELECT
							syscolumns.name,
							OBJECT_NAME(syscolumns.id) AS TableName
						
						FROM
							sysindexkeys,
							syscolumns
						
						WHERE
							sysindexkeys.id = '" . $this->knjdb->sql($d_gi["id"]) . "' AND
							sysindexkeys.indid = '" . $this->knjdb->sql($d_gi["indid"]) . "' AND
							syscolumns.id = '" . $this->knjdb->sql($d_gi["id"]) . "' AND
							syscolumns.colid = sysindexkeys.colid
						
						ORDER BY
							syscolumns.name
					");
					while($d_gik = $f_gik->fetch()){
						$columns[$d_gik["name"]] = $table->getColumn($d_gik["name"]);
					}
					
					if (count($columns) > 0){ //to avoid the actual system indexes with no columns which confuses...
						$table->indexes[$d_gi["name"]] = new knjdb_index($table, array(
								"name" => $d_gi["name"],
								"columns" => $columns
							)
						);
					}
				}
				
				$table->indexes_changed = false;
			}
			
			return $table->indexes;
		}
		
		function removeIndex(knjdb_table $table, knjdb_index $index){
			throw new Exception("Not supported.");
		}
	}

