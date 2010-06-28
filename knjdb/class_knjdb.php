<?php
	class knjdb{
		public $conn;
		private $args = array(
			"col_id" => "id",
			"autoconnect" => true,
			"stats" => false
		);
		public $stats = array();
		private $drivers = array();
		public $insert_autocommit, $insert_countcommit; //variables used by the transaction-autocommit-feature.
		
		/** The constructor. */
		function __construct($args = null){
			require_once("class_knjdb_result.php");
			
			if ($args){
				$this->setOpts($args);
			}
		}
		
		/** Returns the current type (mysql, sqlite2, pdo...). */
		function getType(){
			return $this->args["type"];
		}
		
		/** Returns a specific table by its name. */
		function getTable($name){
			if ($this->tables()->tables[$name]){
				return $this->tables()->tables[$name];
			}
			
			foreach($this->tables()->getTables() AS $table){
				if ($table->get("name") == $name){
					return $table;
				}
			}
			
			return false;
		}
		
		/** Connects to a database. */
		function connect(){
			require_once("drivers/" . $this->args["type"] . "/class_knjdb_" . $this->args["type"] . ".php");
			$obname = "knjdb_" . $this->args["type"];
			$this->conn = new $obname($this, $this->args);
			$this->conn->connect();
		}
		
		function module($module){
			if ($module == "indexes"){
				$short = "index";
			}else{
				$short = substr($module, 0, -1);
			}
			
			if (!$this->drivers[$module]){
				require_once("interfaces/class_knjdb_driver_" . $module . ".php");
				require_once("class_knjdb_" . $short . ".php");
				
				if (method_exists($this->conn, $module)){
					$this->drivers[$module] = $this->conn->$module();
				}else{
					$obname = "knjdb_" . $this->args["type"] . "_" . $module;
					require_once("drivers/" . $this->args["type"] . "/class_" . $obname . ".php");
					$this->drivers[$module] = new $obname($this);
				}
			}
			
			return $this->drivers[$module];
		}
		
		function rows(){
			return $this->module("rows");
		}
		
		function dbs(){
			return $this->module("dbs");
		}
		
		/** Returns the tables-module. */
		function tables(){
			return $this->module("tables");
		}
		
		/** Returns the columns-module. */
		function columns(){
			return $this->module("columns");
		}
		
		/** Returns the indexes-module. */
		function indexes(){
			return $this->module("indexes");
		}
		
		function procedures(){
			return $this->module("procedures");
		}
		
		/** Returns a row by its ID and table. */
		function getRow($id, $table, $data = null){
			require_once("class_knjdb_row.php");
			
			if (is_array($id)){
				$data = $id;
				$id = $id[$this->args["col_id"]];
			}
			
			if (!is_numeric($id) || $id < 0){
				throw new Exception("ID was not valid \"" . $id . "\".");
			}
			
			if (!$this->rows[$table][$id]){
				$this->rows[$table][$id] = new knjdb_row($this, $table, $id, $data, array("col_id" => $this->args["col_id"]));
			}
			
			return $this->rows[$table][$id];
		}
		
		/** Closes the connection to the database. */
		function close(){
			$this->conn->close();
			unset($this->conn);
		}
		
		/** Returns the last inserted ID. */
		function getLastID(){
			return $this->conn->getLastID();
		}
		
		function last_id(){
			return $this->getLastID();
		}
		
		/** Only has the function because it should be compatible with the DBConn-framework. */
		function getLastInsertedID(){
			return $this->getLastID();
		}
		
		/** Sets the options of the object. */
		function setOpts($args){
			foreach($args AS $key => $value){
				$this->args[$key] = $value;
			}
			
			if ($this->args["autoconnect"] and !$this->conn){
				$this->connect();
			}
		}
		
		/** Performs a query. */
		function query($sql){
			if ($this->args["stats"]){
				$this->stats["query_called"]++;
				
				if ($this->args["debug"]){
					$bt = debug_backtrace();
					
					echo("Query " . $this->stats["query_called"] . "\n");
					echo("File: " . $bt[0]["file"] . ":" . $bt[0]["line"] . "\n");
					echo("File: " . $bt[1]["file"] . ":" . $bt[1]["line"] . "\n");
					echo("SQL: " . $sql . "\n\n");
				}
			}
			
			return $this->conn->query($sql);
		}
		
		/** Fetches a result. */
		function fetch($ident){
			if (is_object($ident) && get_class($ident) == "knjdb_result"){
				$ident = $ident->result;
			}
			
			return $this->conn->fetch($ident);
		}
		
		/** Returns an escape string (mysql_escape_string etc) for the current database-driver. */
		function sql($string){
			return $this->conn->sql($string);
		}
		
		/** Used with transactions. */
		function insert_autocommit($value){
			if (is_numeric($value)){
				$this->trans_begin();
				$this->insert_autocommit = $value;
				$this->insert_countcommit = 0;
			}elseif($value == false){
				if ($this->insert_countcommit > 0){
					$this->trans_commit();
				}
				
				unset($this->insert_autocommit);
				unset($this->insert_countcommit);
			}else{
				throw new Exception("Invalid argument (" . $value . ".");
			}
		}
		
		/** A quick way to do a simple select and fetch the result.. */
		function selectfetch($table, $where = null, $args = null){
			$result = $this->select($table, $where, $args);
			$results = array();
			while($data = $result->fetch($result)){
				if ($args["return"] == "array"){
					$results[] = $data;
				}else{
					$results[] = $this->getRow($data, $table);
				}
			}
			
			return $results;
		}
		
		/** Selects a single row and returns it. */
		function selectsingle($table, $where, $args = array()){
			$args["limit"] = "1";
			return $this->select($table, $where, $args)->fetch();
		}
		
		/** A quick way to do a simple select. */
		function select($table, $where = null, $args = null){
			return $this->conn->select($table, $where, $args);
		}
		
		/** A quick way to insert a new row into the database. */
		function insert($table, $arr){
			$this->conn->insert($table, $arr);
			
			if ($this->insert_autocommit){ //check wherever autocommit is on.
				$this->insert_countcommit++;
				
				if ($this->insert_countcommit >= $this->insert_autocommit){
					$this->trans_commit();
					$this->trans_begin();
					$this->insert_countcommit = 0;
				}
			}
		}
		
		/** A quick way to do a simple update. */
		function update($table, $data, $where = null){
			$this->conn->update($table, $data, $where);
		}
		
		/** A quick way to do a simple delete. */
		function delete($table, $where = null){
			if (!is_null($where) && !is_array($where)){
				throw new exception("The where-parameter was not an array or null.");
			}
			
			$this->conn->delete($table, $where);
		}
		
		function countRows($res){
			return $this->conn->countRows($res->result);
		}
		
		/** Returns the SQL for the query based on an array. */
		function makeWhere($where){
			return $this->conn->makeWhere($where);
		}
		
		function trans_begin(){
			if (method_exists($this->conn, "trans_begin")){
				$this->conn->trans_begin();
			}
		}
		
		function trans_commit(){
			if (method_exists($this->conn, "trans_commit")){
				$this->conn->trans_commit();
			}
		}
		
		function date_format($unixt){
			return $this->conn->date_format($unixt);
		}
	}
?>