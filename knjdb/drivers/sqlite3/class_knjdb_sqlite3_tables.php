<?php
	class knjdb_sqlite3_tables implements knjdb_driver_tables{
		public $knjdb;
		public $tables = array();
		private $tables_changed = true;

		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
		}

		function getTables(){
			$return = array();
			$f_gt = $this->knjdb->select("sqlite_master", array("type" => "table"), array("orderby" => "name"));
			while($d_gt = $f_gt->fetch()){
				if ($d_gt["name"] != "sqlite_sequence" and !array_key_exists($d_gt["name"], $this->tables)){
					$this->tables[$d_gt["name"]] = new knjdb_table($this->knjdb, array(
							"name" => $d_gt["name"],
							"engine" => "sqlite3",
							"collation" => "sqlite3"
						)
					);
				}
			}

			return $this->tables;;
		}

		function createTable($tablename, $columns, $args = null){
			$sql = "CREATE TABLE " . $this->knjdb->conn->sep_table . $tablename . $this->knjdb->conn->sep_table . " (";

			$first = true;
			$primary = false;
			foreach($columns AS $column){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}

				/** NOTE: This fixes the limit of SQLite3 is only able to have one primary key - only the first primary key will be marked. */
				if ($primary == true && $column["primarykey"] == "yes"){
					$column["primarykey"] = "no";
				}elseif($column["primarykey"] == "yes"){
					$primary = true;
				}

				/** NOTE: If "not null" is set - then set default value to nothing. */
				if ($column["notnull"] == "yes"){
					$column["default_set"] = true;
				}

				$sql .= $this->knjdb->columns()->getColumnSQL($column);
			}

			$sql .= ")";

			if ($args["returnsql"]){
				return $sql;
			}

			$this->knjdb->query($sql);
		}

		function dropTable(knjdb_table $table){
			unset($this->tables[$table->get("name")]);
			$this->knjdb->query("DROP TABLE " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table);
		}

		function renameTable(knjdb_table $table, $newtable){
			$oldname = $table->get("name");
			$this->knjdb->query("ALTER TABLE " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table . " RENAME TO " . $this->knjdb->conn->sep_table . $newtable . $this->knjdb->conn->sep_table);
			$table->data["name"] = $newtable;
			$this->tables[$newtable] = $table;
			unset($this->tables[$oldname]);
		}

		function truncateTable(knjdb_table $table){
			$this->knjdb->query("DELETE FROM " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table);
		}

		function optimizeTable(knjdb_table $table){
			$this->knjdb->query("VACUUM"); //vacuum the entire database.
		}
	}
?>