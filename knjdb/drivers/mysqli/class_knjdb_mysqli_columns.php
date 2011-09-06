<?

class knjdb_mysqli_columns implements knjdb_driver_columns{
	private $driver;

	function __construct($knjdb){
		$this->knjdb = $knjdb;
		$this->driver = $knjdb->conn;
	}

	function getColumnSQL($column, $args = null){
		if (!is_array($column)){
			throw new exception("Column is not an array: " . gettype($column));
		}

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

		//Fix MSSQL-datetime-default-crash.
		if ($column["type"] == "datetime" and $column["default"] == "getdate()"){
			$column["default"] = "";
		}

		if ($column["type"] == "varchar" and !intval($column["maxlength"])){
			$column["maxlength"] = 255;
		}

		$sql .= $column['type'];

		//Defindes maxlength (and checks if maxlength is allowed on the current database-type).
		if ($column["type"] == "datetime" || $column["type"] == "date" || $column["type"] == "tinytext" || $column["type"] == "text"){
			//maxlength is not allowed in MySQL. So nothing goes here (Access can actually have a maxlength on a datetime).
		}elseif(array_key_exists("maxlength", $column)){
			$sql .= "(" . $column['maxlength'] . ")";
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
			$return = array();
			$f_gc = $this->knjdb->query("SHOW FULL COLUMNS FROM " . $this->driver->sep_table . $table->get("name") . $this->driver->sep_table);
			while($d_gc = $f_gc->fetch()){
				if (!$table->columns[$d_gc["Field"]]){
					$value = "";

					if ($d_gc["Null"] == "YES"){
						$notnull = "no";
					}else{
						$notnull = "yes";
					}

					if ($d_gc["Key"] == "PRI"){
						$primarykey = "yes";
					}else{
						$primarykey = "no";
					}

					if ($d_gc["Extra"] == "auto_increment"){
						$autoincr = "yes";
					}else{
						$autoincr = "no";
					}

					if ($d_gc['Type'] == "tinytext"){
						$maxlength = 255;
					}else{
						$maxlength = "";
					}

					if (preg_match("/([a-zA-Z]+)\((.+)\)/", $d_gc["Type"], $match)){
						$type = strtolower($match[1]);
						$maxlength = $match[2];
					}else{
						$type = strtolower($d_gc["Type"]);
					}

					$table->columns[$d_gc["Field"]] = new knjdb_column($table, array(
						"name" => $d_gc["Field"],
						"notnull" => $notnull,
						"type" => $type,
						"maxlength" => $maxlength,
						"default" => $d_gc["Default"],
						"primarykey" => $primarykey,
						"value" => $value,
						"input_type" => "mysql",
						"autoincr" => $autoincr,
						"comment" => $d_gc["Comment"]
					));
				}
			}

			$table->columns_changed = false;
		}

		return $table->columns;
	}

	function addColumns(knjdb_table $table, $columns){
		if (!is_array($columns)){
			throw new exception("Second argument wasnt an array of columns.");
		}

		foreach($columns AS $column){
			$this->knjdb->query("ALTER TABLE " . $this->driver->sep_table . $table->get("name") . $this->driver->sep_table . " ADD COLUMN " . $this->knjdb->columns()->getColumnSQL($column) . ";");
			$table->columns_changed = true;
		}
	}

	function removeColumn(knjdb_table $table, knjdb_column $column){
		$sql = "ALTER TABLE " . $this->driver->sep_table . $table->get("name") . $this->driver->sep_table . " DROP COLUMN " . $this->driver->sep_col . $column->get("name") . $this->driver->sep_col;
		$this->knjdb->query($sql);
		unset($table->columns[$column->get("name")]);
	}

	function editColumn(knjdb_column $col, $newdata){
		$table = $col->getTable();
		$sql = "ALTER TABLE " . $this->driver->sep_table . $table->get("name") . $this->driver->sep_table;

		if ($col->get("name") != $newdata["name"]){
			$sql .= " CHANGE " . $this->driver->sep_col . $col->get("name") . $this->driver->sep_col . " " . $this->getColumnSQL($newdata, array("skip_primary" => true));
		}else{
			$sql .= " MODIFY " . $this->getColumnSQL($newdata, array("skip_primary" => true));
		}

		$this->knjdb->query($sql);

		if ($col->get("name") != $newdata["name"]){
			unset($col->getTable()->columns[$col->get("name")]);
			$col->getTable()->columns[$newdata["name"]] = $col;
		}
	}
}
