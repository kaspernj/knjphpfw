<?php
	class knjdb_procedure{
		private $knjdb;
		private $data;

		function __construct(knjdb $knjdb, $data){
			$this->knjdb = $knjdb;
			$this->data = $data;
		}

		function get($key){
			if (!array_key_exists($key, $this->data)){
				throw new Exception("Key does not exist.");
			}

			return $this->data[$key];
		}
	}
?>