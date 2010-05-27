<?php
	/** This class represents a single row in a table. */
	class knjdb_row{
		public $dbconn;
		private $table;
		private $id;
		private $data;
		
		/** The constructor. */
		function __construct($dbconn, $table, $id, $data = null, $args = array()){
			$this->dbconn = $dbconn;
			$this->db = $dbconn;
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
		
		/** Returns the table-object for this row. */
		function getTable(){
			return $this->dbconn->getTable($this->table);
		}
		
		/** Re-loads all the data from the database. */
		function updateData($data = null){
			if (is_null($data)){
				$data = $this->dbconn->selectsingle($this->table, array($this->col_id => $this->id));
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
		
		function update($arr){
			return $this->setData($arr);
		}
		
		function id(){
			return $this->get($this->col_id);
		}
	}
	
	class knjdb_rownotfound_exception extends exception{}
?>