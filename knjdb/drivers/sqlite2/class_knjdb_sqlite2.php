<?php
	/** This class replaces the old DBConn-class. It aims to be much faster by not including so much code by default, and load much less libs. */
	class knjdb_sqlite2{
		protected $args;
		protected $knjdb;
		public $conn;
		public $sep_col = "";
		public $sep_val = "'";
		public $sep_table = "";

		function __construct($knjdb, $args){
			$this->args = $args;
			$this->knjdb = $knjdb;

			require_once("knj/functions_knj_extensions.php");
			knj_dl("sqlite");
		}

		function connect(){
			$this->conn = sqlite_open($this->args["path"]);
			if (!$this->conn){
				throw new Exception("Could not open SQLite database.");
			}
		}

		function close(){
			sqlite_close($this->conn);
		}

		function query($query){
			echo $query . "\n";

			$res = sqlite_query($query, $this->conn);
			if (!$res){
				echo $query . "\n";
				throw new Exception("Query error: " . $this->error() . ", SQL: " . $query);
			}

			return new knjdb_result($this->knjdb, $this, $res);
		}

		function fetch($res){
			if (!is_resource($res)){
				throw new Exception("Failure");
			}
			$data = sqlite_fetch_array($res);

			//Makes sqlite_fetch_array() works lige an assoc-function.
			if ($data){
				foreach($data AS $key => $value){
					if (is_numeric($key)){
						unset($data[$key]);
					}
				}
			}

			return $data;
		}

		function error(){
			return sqlite_error_string(sqlite_last_error($this->conn));
		}

		function getLastID(){
			return sqlite_last_insert_rowid($this->conn);
		}

		function sql($string){
			return sqlite_escape_string($string);
		}
	}

