<?php
	class knjdb_column{
		public $knjdb;
		public $table;
		public $data;
		
		function __construct(knjdb_table $table, $data){
			$this->knjdb = $table->knjdb;
			$this->table = $table;
			$this->data = $data;
		}
		
		function getTable(){
			return $this->table;
		}
		
		function setData($arr){
			$changed = false;
			foreach($this->data AS $key => $value){
				if ($key != "input_type" && $arr[$key] != $value){
					$changed = true;
					break;
				}
			}
			
			if (!$changed){
				return null; //abort if the data is the same.
			}
			
			$this->knjdb->columns()->editColumn($this, $arr);
			foreach($arr AS $key => $value){
				$this->data[$key] = $value;
			}
		}
		
		/** Returns a key from the row. */
		function get($key){
			if (!array_key_exists($key, $this->data)){
				throw new Exception("The key does not exist: \"" . $key . "\".");
			}
			
			return $this->data[$key];
		}
	}

