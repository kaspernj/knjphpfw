<?

class knjdb_mysql{
	private $args;
	private $knjdb;
	public $tables = array();
	public $sep_col = "`";
	public $sep_val = "'";
	public $sep_table = "`";
	public $sep_index = "`";

	function __construct(knjdb $knjdb, &$args){
		$this->args = $args;
		$this->knjdb = $knjdb;

		require_once("knj/functions_knj_extensions.php");
		knj_dl("mysql");
	}

	function connect(){
		if (array_key_exists("port", $this->args) and $this->args["port"] and $this->args["port"] != 3306){
			$this->conn = mysql_connect($this->args["host"] . ":" . $this->args["port"], $this->args["user"], $this->args["pass"], true);
		}else{
			$this->conn = mysql_connect($this->args["host"], $this->args["user"], $this->args["pass"], true);
		}

		if (!$this->conn){
			throw new Exception("Could not connect: " . mysql_error()); //use mysql_error() here since $this->conn has not been set.
		}

		if ($this->args["db"]){
			if (!mysql_select_db($this->args["db"], $this->conn)){
				throw new Exception("Could not select database: " . $this->error());
			}
		}
	}

	function close(){
		mysql_close($this->conn);
		unset($this->conn);
	}

	function query($query){
		$res = mysql_query($query, $this->conn);
		if (!$res){
			throw new Exception("Query error: " . $this->error() . "\n\nSQL: " . $query);
		}

		return new knjdb_result($this->knjdb, $this, $res);
	}

	function query_ubuf($query){
		$res = mysql_unbuffered_query($query, $this->conn);
		if (!$res){
			throw new Exception("Query error: " . $this->error() . "\n\nSQL: " . $query);
		}

		return new knjdb_result($this->knjdb, $this, $res);
	}

	function fetch($res){
		return mysql_fetch_assoc($res);
	}

	function error(){
		return mysql_error($this->conn);
	}

	function free($res){
		return mysql_free_result($res);
	}

	function getLastID(){
		return mysql_insert_id($this->conn);
	}

	function sql($string){
		if (is_object($string) or is_array($string)){
			throw new exception("Given argument was a valid string.");
		}

		return mysql_real_escape_string($string);
	}

	function escape_table($string){
		if (strpos($string, "`")){
			throw new exception("Tablename contains invalid character.");
		}

		return $string;
	}

	function trans_begin(){
		$this->query("BEGIN");
	}

	function trans_commit(){
		$this->query("COMMIT");
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
			if ($first){
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
			$sql .= $this->makeOrderby($args["orderby"]);
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

	function countRows($res){
		return mysql_num_rows($res);
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

	function makeOrderby($orderby){
		if (is_string($orderby)){
			return " ORDER BY " . $orderby;
		}elseif(is_array($orderby)){
			$sql = " ORDER BY ";

			$first = true;
			foreach($orderby AS $col_name){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}

				$sql .= $this->sep_col . $col_name . $this->sep_col;
			}

			return $sql;
		}
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

