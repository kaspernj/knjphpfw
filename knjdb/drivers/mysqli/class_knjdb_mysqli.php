<?

class knjdb_mysqli{
	private $args;
	private $knjdb;
	public $sep_col = "`";
	public $sep_val = "'";
	public $sep_table = "`";
	public $sep_index = "`";

	function __construct(knjdb $knjdb, &$args){
		$this->args = $args;
		$this->knjdb = $knjdb;

		require_once("knj/functions_knj_extensions.php");
		knj_dl("mysqli");
	}

	static function getArgs(){
		return array(
			"host" => array(
				"type" => "text",
				"title" => "Hostname"
			),
			"user" => array(
				"type" => "text",
				"title" => "Username"
			),
			"pass" => array(
				"type" => "passwd",
				"title" => "Password"
			),
			"db" => array(
				"type" => "text",
				"title" => "Database"
			)
		);
	}

	function connect(){
		$this->conn = new MySQLi($this->args["host"], $this->args["user"], $this->args["pass"], $this->args["db"]);

		if (mysqli_connect_error()){ //do not use the OO-way - it was broken until 5.2.9.
			throw new Exception("Could not connect (" . mysqli_connect_errno() . "): " . mysqli_connect_error());
		}
	}

	function close(){
		$this->conn->close();
		unset($this->conn);
	}

	function query($query){
		$res = $this->conn->query($query);
		if (!$res){
			throw new Exception("Query error: " . $this->error() . "\n\nSQL: " . $query);
		}

		return new knjdb_result($this->knjdb, $this, $res);
	}

	function fetch($res){
		return $res->fetch_assoc();
	}

	function error(){
		return $this->conn->error;
	}

	function free($res){
    return $res->free();
	}

	function getLastID(){
		return $this->conn->insert_id;
	}

	function sql($string){
		return $this->conn->real_escape_string($string);
	}

	function escape_table($string){
		if (strpos($string, "`")){
			throw new exception("Tablename contains invalid character.");
		}

		return $string;
	}

	function trans_begin(){
		$this->conn->autocommit(false); //turn off autocommit.
	}

	function trans_commit(){
		$this->conn->commit();
		$this->conn->autocommit(true); //turn on autocommit.
	}

	function insert($table, $arr){
		$sql = "INSERT INTO " . $this->sep_table . $table . $this->sep_table . " (";

		$first = true;
		foreach($arr AS $key => $value){
			if ($first == true){
				$first = false;
			}else{
				$sql .= ", ";
			}

			$sql .= $this->sep_col . $key . $this->sep_col;
		}

		$sql .= ") VALUES (";
		$first = true;
		foreach($arr AS $key => $value){
			if ($first == true){
				$first = false;
			}else{
				$sql .= ", ";
			}

			$sql .= $this->sep_val . $this->sql($value) . $this->sep_val;
		}
		$sql .= ")";

		$this->query($sql);
	}

	function insert_multi($table, $rows){
		$sql = "INSERT INTO " . $this->sep_table . $table . $this->sep_table . " (";

		$first = true;
		foreach($rows[0] AS $key => $value){
			if ($first == true){
				$first = false;
			}else{
				$sql .= ", ";
			}

			$sql .= $this->sep_col . $key . $this->sep_col;
		}

		$sql .= ") VALUES";

		$first_row = true;
		foreach($rows AS $arr){
			if ($first_row){
				$first_row = false;
			}else{
				$sql .= ",";
			}

			$sql .= " (";
			$first = true;
			foreach($arr AS $key => $value){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}

				$sql .= $this->sep_val . $this->sql($value) . $this->sep_val;
			}
			$sql .= ")";
		}

		$this->query($sql);
	}

	function select($table, $where = null, $args = null){
		$sql = "SELECT";

		$sql .= " * FROM " . $this->sep_table . $table . $this->sep_table;

		if ($where){
			$sql .= " WHERE " . $this->makeWhere($where);
		}

		if ($args["orderby"]){
			$sql .= " ORDER BY " . $args["orderby"];
		}

		if ($args["limit"]){
			$sql .= " LIMIT " . $args["limit"];
		}

		return $this->query($sql);
	}

	function delete($table, $where = null){
		$sql = "DELETE FROM " . $this->sep_table . $table . $this->sep_table;

		if ($where){
			$sql .= " WHERE " . $this->makeWhere($where);
		}

		return $this->query($sql);
	}

	function update($table, $data, $where = null){
		$sql .= "UPDATE " . $this->sep_table . $table . $this->sep_table . " SET ";

		$first = true;
		foreach($data AS $key => $value){
			if ($first == true){
				$first = false;
			}else{
				$sql .= ", ";
			}

			$sql .= $this->sep_col . $key . $this->sep_col . " = " . $this->sep_val . $this->sql($value) . $this->sep_val;
		}

		if ($where){
			$sql .= " WHERE " . $this->makeWhere($where);
		}

		return $this->query($sql);
	}

	function optimize($tables){
		if (!is_array($tables)){
			$tables = array($tables);
		}

		$sql = "OPTIMIZE TABLE ";
		$sql .= knjarray::implode(array(
			"array" => $tables,
			"surr" => "`",
			"impl" => ",",
			"self_callback" => array($this, "escape_table")
		));

		return $this->query($sql);
	}

	function makeWhere($where){
		$first = true;
		foreach($where AS $key => $value){
			if ($first == true){
				$first = false;
			}else{
				$sql .= " AND ";
			}

			if (is_array($value)){
				$sql .= $this->sep_col . $key . $this->sep_col . " IN (" . knjarray::implode(array("array" => $value, "impl" => ",", "surr" => "'", "self_callback" => array($this, "sql"))) . ")";
			}else{
				$sql .= $this->sep_col . $key . $this->sep_col . " = " . $this->sep_val . $this->sql($value) . $this->sep_val;
			}
		}

		return $sql;
	}

	function date_in($str){
		return strtotime($str);
	}

	function date_format($unixt, $args = array()){
		$format = "Y-m-d";

		if (!array_key_exists("time", $args) or $args["time"]){
			$format .= " H:i:s";
		}

		return date($format, $unixt);
	}
}