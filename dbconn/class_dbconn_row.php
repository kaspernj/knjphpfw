<?
	require_once("knjphpframework/functions_knj_sql.php");
	
	/** This class represents a single row in a table. */
	class dbconn_row{
		private $dbconn;
		private $table;
		private $id;
		private $data;
		
		/** The constructor. */
		function __construct($dbconn, $table, $id, $data = null, $args = array()){
			$this->dbconn = $dbconn;
			$this->table = $table;
			$this->id = $id;
			
			foreach($args AS $key => $value){
				if ($key == "col_id"){
					$this->$key = $value;
				}else{
					throw new Exception("Invalid argument: \"" . $key . "\".");
				}
			}
			
			if (!$this->col_id){
				$this->col_id = "id";
			}
			
			$this->updateData($data);
		}
		
		/** Re-loads all the data from the database. */
		function updateData($data = null){
			if (is_null($data)){
				$f_gdata = $this->dbconn->query("SELECT * FROM " . $this->table . " WHERE " . $this->col_id . " = '" . sql($this->id) . "'") or die($this->dbconn->query_error());
				$data = $this->dbconn->query_fetch_assoc($f_gdata);
				
				if (!$data){
					throw new Exception("No row with the specified ID was found.");
				}
			}
			
			$this->data = $data;
		}
		
		/** Returns a key from the row. */
		function get($key){
			if (!array_key_exists($key, $this->data)){
				throw new Exception("The key does not exist: \"" . $key . "\".");
			}
			
			return $this->data[$key];
		}
		
		/** Returns the row as an array. */
		function getAsArray(){
			return $this->data;
		}
		
		/** Updates the row. */
		function setData($arr){
			$this->dbconn->update($this->table, $arr, array($this->col_id => $this->id));
			$this->updateData();
			return true;
		}
	}
?>