<?php
	class knjdb_mssql_columns implements knjdb_driver_columns{
		private $knjdb;
		
		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
		}
		
		function getColumnSQL($column){
			if (!$column["name"]){
				throw new Exception("Invalid name: " . $column["name"]);
			}
			
			$sql = $this->driver->sep_col . $column['name'] . $this->driver->sep_col . " ";
			
			if ($column["type"] == "counter"){
				//This is an Access-primarykey-autoincr-column - convert it!
				$column["type"] = "int";
				$column["primarykey"] = "yes";
				$column["autoincr"] = "yes";
			}elseif($column["type"] == "bit"){
				$column["type"] = "tinyint";
			}elseif($column["type"] == "image"){
				$column["type"] = "blob";
				$column["maxlength"] = "";
			}elseif($column["type"] == "uniqueidentifier"){
				$column["type"] = "int";
				$column["autoincr"] = "yes";
				$column["primarykey"] = "yes";
				$column["default"] = "";
			}
			
			if ($column["type"] == "varchar" && ($column["maxlength"] <= 0 || !$maxlength)){
				$column["maxlength"] = 255;
			}
			
			$sql .= $column['type'];
			
			//Defindes maxlength (and checks if maxlength is allowed on the current database-type).
			if ($column["type"] == "datetime" || $column["type"] == "date" || $column["type"] == "tinytext" || $column["type"] == "text"){
				//maxlength is not allowed in MySQL. So nothing goes here (Access can actually have a maxlength on a datetime).
			}elseif($column["maxlength"] > 0){
				$sql .= "(" . $column["maxlength"] . ")";
			}
			
			//Defines some extras (like primary key, null and default).
			if ($column["primarykey"] == "yes" && !$args["skip_primary"]){
				$sql .= " PRIMARY KEY";
			}
			
			if ($column["autoincr"] == "yes"){
				$sql .= " AUTO_INCREMENT";
			}
			
			if ($column["notnull"] == "yes"){
				$sql .= " NOT NULL";
			}
			
			if (strlen($column["default"]) > 0 && $column["autoincr"] != "yes"){
				$sql .= " DEFAULT " . $this->driver->sep_val . $this->knjdb->sql($column["default"]) . $this->driver->sep_val;
			}
			
			return $sql . $ekstra;
		}
		
		function getColumns(knjdb_table $table){
			if ($table->columns_changed){
				$f_gc = $this->knjdb->query("SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_NAME = '" . $this->knjdb->sql($table->get("name")) . "' ORDER BY ORDINAL_POSITION");
				while($d_gc = $f_gc->fetch()){
					if (!$table->columns[$d_gc["COLUMN_NAME"]]){
						if ($d_gc["IS_NULLABLE"] == "NO"){
							$notnull = "yes";
						}else{
							$notnull = "no";
						}
						
						$default = $d_gc["COLUMN_DEFAULT"];
						if (substr($default, 0, 2) == "('"){
							$default = substr($default, 2, -2);
						}elseif(substr($default, 0, 1) == "("){
							$default = substr($default, 1, -1);
						}
						
						if (substr($default, 0, 1) == "("){ //Fix two times!
							$default = substr($default, 1, -1);
						}
						
						$primarykey = "no";
						$autoincr = "no";
						$type = $d_gc["DATA_TYPE"];
						
						$table->columns[$d_gc["COLUMN_NAME"]] = new knjdb_column($table, array(
								"name" => $d_gc["COLUMN_NAME"],
								"type" => $type,
								"maxlength" => $d_gc["CHARACTER_MAXIMUM_LENGTH"],
								"notnull" => $notnull,
								"default" => $default,
								"primarykey" => $primarykey,
								"autoincr" => 	$autoincr
							)
						);
					}
				}
				
				$table->columns_changed = false;
			}
			
			return $table->columns;
		}
		
		function addColumns(knjdb_table $table, $columns){
			throw new Exception("Not supported.");
			$table->columns_changed = true;
		}
		
		function removeColumn(knjdb_table $table, knjdb_column $col){
			throw new Exception("Not supported.");
			unset($table->columns[$column->get("name")]);
		}
		
		function editColumn(knjdb_column $col, $newdata){
			throw new Exception("Not supported.");
		}
	}

