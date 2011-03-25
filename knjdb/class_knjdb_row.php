<?

/** This class represents a single row in a table. */
class knjdb_row{
	public $dbconn;
	private $table;
	private $id;
	public $data;
	
	/** The constructor. */
	function __construct($dbconn, $table = null, $id = null, $data = null, $args = array()){
		if (is_array($dbconn)){
			$this->row_args = $dbconn;
			$args = &$this->row_args;
			$dbconn = $this->row_args["db"];
			$table = $this->row_args["table"];
			
			if (is_array($this->row_args["data"])){
				$data = $this->row_args["data"];
				$id = $data["id"];
			}else{
				$id = $this->row_args["data"];
				$data = null;
			}
		}
		
		$this->db = $dbconn;
		$this->dbconn = $dbconn;
		$this->table = $table;
		$this->id = $id;
		
		if (!$id){
			throw new exception(_("No ID was given."));
		}elseif(!$this->db){
			throw new exception("No valid db given.");
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
	
	/** Returns the table-object for this row. */
	function getTable(){
		return $this->db->getTable($this->table);
	}
	
	/** Re-loads all the data from the database. */
	function updateData($data = null){
		if (is_null($data)){
			$data = $this->db->selectsingle($this->table, array($this->col_id => $this->id));
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
		return $this->dbconn->date_in($this->g($key));
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
		
		$this->db->update($this->table, $arr, array($this->col_id => $this->id));
		
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