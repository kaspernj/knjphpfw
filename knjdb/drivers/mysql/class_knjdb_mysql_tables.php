<?

class knjdb_mysql_tables implements knjdb_driver_tables{
	public $knjdb;
	public $tables = array();
	public $tables_changed = true;

	function __construct(knjdb $knjdb){
		$this->knjdb = $knjdb;
	}

	function getTables(){
		if ($this->tables_changed){
			$f_gt = $this->knjdb->query("SHOW TABLE STATUS");
			while($d_gt = $f_gt->fetch()){
				if (!$this->tables[$d_gt["Name"]]){
					$this->tables[$d_gt["Name"]] = new knjdb_table($this->knjdb, array(
						"name" => $d_gt["Name"],
						"engine" => $d_gt["Engine"],
						"collation" => $d_gt["Collation"],
						"rows" => $d_gt["Rows"]
					));
				}
			}

			$this->tables_changed = false;
		}

		return $this->tables;
	}

	function renameTable(knjdb_table $table, $newname){
		$this->knjdb->query("ALTER TABLE " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table . " RENAME TO " . $this->knjdb->conn->sep_table . $newname . $this->knjdb->conn->sep_table);

		unset($this->tables[$table->get("name")]);
		$table->data["name"] = $newname;
		$this->tables[$newname] = $table;
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

	function dropTable(knjdb_table $table){
		$this->knjdb->query("DROP TABLE " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table);
		unset($this->tables[$table->get("name")]);
	}

	function truncateTable(knjdb_table $table){
		$this->knjdb->query("TRUNCATE " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table);
	}

	function optimizeTable(knjdb_table $table){
		$this->knjdb->query("OPTIMIZE TABLE " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table); //vacuum the entire database.
	}
}