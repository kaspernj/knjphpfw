<?

/** This class represents a single row in a table. */
class knjdb_row{
	public $dbconn;
	private $table;
	private $id;
	public $data;

	/** The constructor. */
	function __construct($dbconn, $table = null, $id = null, $data = null, $args = array()){
		if (is_array($dbconn) and $dbconn["ob"]->config["version"] == 2){
			$this->ob = $dbconn["ob"];
			$this->db = $dbconn["ob"]->config["db"];

			if (is_array($dbconn["data"])){
				$data = $dbconn["data"];
				$this->id = $dbconn["data"]["id"];
			}else{
				$this->id = $dbconn["data"];
				$data = null;
			}
		}elseif(is_array($dbconn)){
			$this->row_args = $dbconn;
			$args = &$this->row_args;
			$this->db = $this->row_args["db"];
			$this->table = $this->row_args["table"];

			if ($this->row_args["ob"] and !$dbconn){
				$dbconn = $this->row_args["ob"]->config["db"];
			}

			if (is_array($this->row_args["data"])){
				$data = $this->row_args["data"];
				$this->id = $data["id"];
			}else{
				$this->id = $this->row_args["data"];
				$data = null;
			}
		}else{
			$this->db = $dbconn;
			$this->dbconn = $dbconn;
			$this->id = $id;
			$this->table = $table;
		}

		if (!$this->id){
			throw new exception(_("No ID was given."));
		}

		foreach($args AS $key => $value){
			if ($key == "col_id"){
				$this->$key = $value;
			}elseif($key == "db" or $key == "data" or $key == "table"){
				//do nothing.
			}else{
				throw new Exception("Invalid argument: \"" . $key . "\".");
			}
		}

		if (!$this->col_id){
			$this->col_id = "id";
		}

		$this->updateData($data);
	}

	function ob(){
		if ($this->ob){
			return $this->ob;
		}

		throw new exception("Could not figure out the object handler to use.");
	}

	function db(){
		if ($this->db){
			return $this->db;
		}elseif($this->dbconn){
			return $this->dbconn;
		}elseif($this->ob){
			return $this->ob->config["db"];
		}

		throw new exception("Could not figure out the database to use.");
	}

	function table_name(){
		if ($this->table){
			return $this->table;
		}else{
			return get_class($this);
		}
	}

	/** Returns the table-object for this row. */
	function getTable(){
		return $this->db->getTable($this->table);
	}

	/** Re-loads all the data from the database. */
	function updateData($data = null){
		if (is_null($data)){
			$data = $this->db->selectsingle($this->table_name(), array($this->col_id => $this->id));
			if (!$data){
				throw new knjdb_rownotfound_exception("No row with the specified ID was found: " . $this->id . ".");
			}
		}

		$this->data = $data;
	}

	/** Returns a key from the row. */
	function get($key){
		if (!array_key_exists($key, $this->data)){
			print_r($this->data);
			throw new Exception("The key does not exist: \"" . $key . "\".");
		}

		return $this->data[$key];
	}

	function g($key){
		return $this->get($key);
	}

	function g_date($key){
		return $this->db->date_in($this->g($key));
	}

	/** Returns the row as an array. */
	function getAsArray(){
		return $this->data;
	}

	/** Updates the row. */
	function update($arr, $args = null){
		if (!is_array($arr) or empty($arr)){
			throw new exception("No array given or array was empty.");
		}

		$this->db->update($this->table_name(), $arr, array($this->col_id => $this->id));

		if (!$args or !$args["reload"]){
			$this->updateData();
		}

		return true;
	}

	function setData($arr, $args = null){
		return $this->update($arr, $args);
	}

	function id(){
		return $this->id;
	}
}

class knjdb_rownotfound_exception extends exception{}

