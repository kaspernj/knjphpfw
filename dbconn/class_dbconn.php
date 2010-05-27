<?
	require_once("knjphpframework/dbconn/class_dbconn_sqlconverter.php");
	require_once("knjphpframework/dbconn/class_dbconn_indexes.php");
	require_once("knjphpframework/dbconn/class_dbconn_columns.php");
	require_once("knjphpframework/dbconn/class_dbconn_tables.php");
	require_once("knjphpframework/dbconn/class_dbconn_dbs.php");
	require_once("knjphpframework/dbconn/class_dbconn_row.php");
	require_once("knjphpframework/dbconn/class_dbconn_fetchresult.php");
	require_once("knjphpframework/functions_knj_extensions.php");
	require_once("knjphpframework/functions_knj_sql.php");
	require_once("knjphpframework/class_exceptions.php");
	
	/** This class can connect to different type of databases. It can also output data from each database.  */
	class DBConn extends DBConnDBs{
		private $rows;
		private $type_try;
		private $opts = array("idcol" => "id");
		public $lasterror;
		public $conn;
		public $type;
		
		/** The constructor of DBConn. */
		function __construct(){
			$this->sqlc = new SQLConverter();
		}
		
		/** Returns the SQLConverter()-object used by this DBConn (used to make SQL for manipulating with the database). */
		function getSQLC(){
			return $this->sqlc;
		}
		
		/**
		 * Open a new connecting to a database.
		 * 
		 * @param string $type The type of the database (mysql, pgsql, sqlite or access).
		 * @param string $ip The IP og file-location of the database.
		*/
		function openConn($type, $ip, $port = "", $database = "", $username = null, $password = null, WinStatus $win_status = null){
			if ($this->conn){
				$this->CloseConn();
			}
			
			$this->type_try = $type;
			
			if ($type == "mysql"){
				if (!function_exists("mysql_connect") && !knj_dl("mysql")){
					throw new Exception("Could not load the MySQL-extension.");
				}
				
				if (!$port){
					$port = 3306;
				}
				$ip .= ":" . $port;
				
				//updating the status-window.
				if ($win_status){
					$win_status->SetStatus(0, "Connecting...", true);
				}
				
				$this->conn = mysql_connect($ip, $username, $password, true);
				
				//If connection is not set, return false and unset connection.
				if (!$this->conn){
					$this->lasterror = "MySQL connect error: " . mysql_error($this->conn);
					unset($this->conn);
					return false;
				}
				
				//updating the status-window.
				if ($win_status){
					$win_status->SetStatus(0, "Selecting the database...", true);
				}
				
				//If a selection of the default database cant be made, close the connection and return false.
				if (!mysql_select_db($database, $this->conn)){
					$this->lasterror = "MySQL db-select error: " . mysql_error($this->conn);
					mysql_close($this->conn);
					unset($this->conn);
					return false;
				}
				
				$this->type = "mysql";
			}elseif($type == "pgsql"){
				if (!$port){
					$port = "5432";
				}
				
				if (!$this->CheckConnection($ip, $port)){
					$this->lasterror = "Could open a socket to " . $ip . ":" . $port . ".";
					return false;
				}
				
				$this->conn = pg_connect("host=" . $ip . " port=" . $port . " dbname=" . $database . " user=" . $username . " password=" . $password);
				
				$this->pg_ip = $ip;
				$this->pg_port = $port;
				$this->pg_db = $database;
				$this->pg_user = $username;
				$this->pg_pass = $password;
				
				if (!$this->conn){
					$this->lasterror = pg_last_error();
					return false;
				}
				
				$this->type = "pgsql";
				$this->pg_version = pg_version($this->conn);
			}elseif($type == "sqlite"){
				if (!function_exists("sqlite_open") && !knj_dl("sqlite")){
					throw new Exception("Could not load the SQLite-extension.");
				}
				
				$this->conn = sqlite_open($ip);
				
				if ($this->conn){
					$this->type = "sqlite";
				}else{
					$this->lasterror = "The database (" . $ip . ") could not be read.";
					return false;
				}
			}elseif($type == "sqlite3"){
				knj_dl("pdo");
				knj_dl("pdo_sqlite");
				
				try{
					$this->conn = new PDO("sqlite:" . $ip);
					$this->type = "sqlite3";
				}catch(Exception $e){
					echo "Warning: " . $e->getMessage() . "\n";
					$this->lasterror = $e->getMessage();
					return false;
				}
			}elseif($type == "access"){
				if (!file_exists($ip)){
					$this->lasterror = "The file could not be found (" . $ip . ")";
					return false;
				}
				
				$odbc = "Driver={Microsoft Access Driver (*.mdb)};Dbq=" . $ip . ";Uid=Admin;Pwd=;";
				//$odbc = "DRIVER={Microsoft Access Driver (*.mdb)};\r\nDBQ=" . $ip . "\r\n";
				//$odbc = "Driver={MDBToolsODBC};Database=" . $ip . "\r\n";
				//$odbc = "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=" . $ip . ";";
				
				$this->conn = odbc_connect($odbc, "Administrator", "");
				
				if (!$this->conn){
					return false;
				}else{
					$this->type = "access";
				}
			}else{
				throw new Exception("Unsupported type: " . $this->type);
			}
			
			$this->sqlc->setOutputType($this->type);
			return true;
		}
		
		/** Sets the options for the object. */
		function setOpts($arr){
			foreach($arr AS $key => $value){
				if ($key == "idcol"){
					$this->opts[$key] = $value;
				}else{
					throw new Exception("Invalid key: \"" . $key . "\".");
				}
			}
		}
		
		/** Returns a row by its ID and table. */
		function getRow($id, $table, $data = null){
			if (is_array($id)){
				$data = $id;
				$id = $id[$this->opts["idcol"]];
			}
			
			if (!is_numeric($id)){
				throw new Exception("ID was not numeric \"" . $id . "\".");
			}
			
			if ($id < 0){
				throw new Exception("ID was below zero.");
			}
			
			if (!$this->rows[$table][$id]){
				$this->rows[$table][$id] = new dbconn_row($this, $table, $id, $data, array("col_id" => $this->opts["idcol"]));
			}
			
			return $this->rows[$table][$id];
		}
		
		/** Returns the last inserted ID. */
		function getLastInsertedID(){
			if ($this->type == "mysql"){
				return mysql_insert_id($this->conn);
			}elseif($this->type == "sqlite"){
				return sqlite_last_insert_rowid($this->conn);
			}elseif($this->type == "sqlite3"){
				return $this->conn->lastInsertID();
			}else{
				throw new Exception("Unsupported type: " . $this->type);
			}
		}
		
		/** Returns the connecting to the database. */
		function getConn(){
			return $this->conn;
		}
		
		/** Returns the database-type. */
		function getType(){
			return $this->type;
		}
		
		/** Being used when a OpenConn() is called to check MySQL- and PostgreSQL-IP- and ports. */
		function checkConnection($ip, $port){
			$fp = @fsockopen($ip, $port, $err1, $err2, 4);
			
			if (!$fp){
				return false;
			}else{
				return true;
			}
		}
		
		/** Closes the connection to the database. */
		function closeConn(){
			if ($this->conn){
				if ($this->type == "mysql"){
					$state = mysql_close($this->conn);
				}elseif($this->type == "pgsql"){
					$state = pg_close($this->conn);
				}elseif($this->type == "sqlite"){
					$state = sqlite_close($this->conn);
				}elseif($this->type == "sqlite3"){
					//do nothing - connection is closed when conn is set to null later...
				}elseif($this->type == "access"){
					$state = odbc_close($this->conn);
				}else{
					throw new Exception("Unsupported type: " . $this->type);
				}
				
				$this->conn = null;
				return $state;
			}
		}
		
		/**
		 * Executes a query on the database.
		 * @param string $query The query you want to execute.
		*/
		function query($string){
			if (!is_string($string)){
				throw new Exception("The argument for query() has to be a string.");
			}
			
			if ($this->type == "mysql"){
				$res = mysql_query($string, $this->conn);
			}elseif($this->type == "pgsql"){
				$res = pg_query($this->conn, $string);
			}elseif($this->type == "sqlite"){
				$res = sqlite_query($this->conn, $string);
			}elseif($this->type == "sqlite3"){
				$res = $this->conn->query($string);
			}elseif($this->type == "access"){
				$res = odbc_exec($this->conn, $string);
			}else{
				throw new Exception("Not a valid type: " . $this->type);
			}
			
			if (!$res){
				throw new DBConnExc("Database error: " . $this->error());
			}
			
			return new dbconn_fetchresult($this, $res);
		}
		
		/** Executes an unbuffered query on the database (if not possible on the current database-type, then a normal query will be executed). */
		function query_unbuffered($string){
			if ($this->type == "mysql"){
				$res = mysql_unbuffered_query($string, $this->conn);
			}elseif($this->type == "pgsql"){
				$res = pg_query($this->conn, $string);
			}elseif($this->type == "sqlite"){
				$res = sqlite_unbuffered_query($this->conn, $string);
			}elseif($this->type == "sqlite3"){
				$res = $this->conn->query($string);
			}elseif($this->type == "access"){
				$res = odbc_exec($this->conn, $string);
			}else{
				throw new Exception("Not a valid type: " . $this->type);
			}
			
			if (!$res){
				throw new DBConnExc("Database error: " . $this->error());
			}
			
			return new dbconn_fetchresult($this, $res);
		}
		
		/** Returns an array. */
		function query_fetch_assoc($ident){
			if (!$ident){
				throw new Exception("Invalid query-ident.");
			}
			
			if (get_class($ident) != "dbconn_fetchresult"){
				throw new Exception("whuat?");
			}
			
			if ($this->type == "mysql"){
				return mysql_fetch_assoc($ident->result);
			}elseif($this->type == "pgsql"){
				return pg_fetch_assoc($ident->result);
			}elseif($this->type == "sqlite"){
				$data = sqlite_fetch_array($ident->result);
				
				//Makes sqlite_fetch_array() works lige an assoc-function.
				if ($data){
					foreach($data AS $key => $value){
						if (is_numeric($key)){
							unset($data[$key]);
						}
					}
				}
				
				return $data;
			}elseif($this->type == "sqlite3"){
				return $ident->result->fetch(PDO::FETCH_ASSOC);
			}elseif($this->type == "access"){
				return odbc_fetch_array($ident->result);
			}else{
				throw new Exception("Not a valid type: " . $this->type);
			}
		}
		
		/** Another shorter name of query_fetch_assoc(). */
		function fetch($ident){
			return $this->query_fetch_assoc($ident);
		}
		
		/** Performs the num-rows. */
		function numrows($ident){
			if ($this->type == "mysql"){
				return mysql_num_rows($ident);
			}elseif($this->type == "sqlite"){
				return sqlite_num_rows($ident);
			}elseif($this->type == "pgsql"){
				return pg_num_rows($ident);
			}else{
				throw new Exception("Type not supported: \"" . $this->type . "\".");
			}
		}
		
		/** Return an error. */
		function query_error(){
			if ($this->type == "mysql"){
				$tha_error = mysql_error($this->conn);
			}elseif($this->type == "pgsql"){
				$tha_error = "PostgreSQL error: " . pg_last_error($this->conn);
			}elseif($this->type == "sqlite"){
				$tha_error = "SQLite error: " . sqlite_error_string(sqlite_last_error($this->conn));
			}elseif($this->type == "sqlite3"){
				$tha_error = print_r($this->conn->errorInfo(), true);
				if (!$tha_error && $this->lastsqlite3_error){
					$tha_error = $this->lastsqlite3_error;
					unset($this->lastsqlite3_error);
				}
			}elseif($this->type == "access"){
				$tha_error = "Access error: " . odbc_error($this->conn) . ", " . odbc_errormsg($this->conn);
			}elseif($this->lasterror){
				$tha_error = $this->lasterror;
			}else{
				throw new Exception("Not a valid type: " . $this->type);
			}
			
			if (!$tha_error && $this->lasterror){
				$tha_error = $this->lasterror;
			}
			
			return $tha_error;
		}
		
		/** Another shorter name of query_error(). */
		function error(){
			return $this->query_error();
		}
		
		/** A quick way to insert a new row into the database. */
		function insert($table, $arr){
			$sql = sql_parseInsert($arr, $table);
			$result = $this->query($sql);
			
			if (!$result){
				throw new DBConnExc("SQL-error: " . $this->error() . "\n\nSQL: " . $sql);
			}
			
			return $result;
		}
		
		/** A quick way to do a simple select and fetch the result.. */
		function selectfetch($table, $where = null, $args = null){
			$result = $this->select($table, $where, $args);
			if (!$result){
				throw new DBConnExc("SQL-error: " . $this->error() . "\n\nSQL: " . $sql);
			}
			
			$results = array();
			while($data = $this->fetch($result)){
				if ($args["return"] == "array"){
					$results[] = $data;
				}else{
					$results[] = $this->getRow($data, $table);
				}
			}
			
			return $results;
		}
		
		/** Selects a single row and returns it. */
		function selectsingle($table, $where = null, $args = array()){
			$args["limit"] = "1";
			$query = $this->select($table, $where, $args);
			$data = $query->fetch();
			
			return $data;
		}
		
		/** A quick way to do a simple select. */
		function select($table, $where = null, $args = array()){
			$sql = "SELECT * FROM " . $table;
			 
			if ($where){
				$sql .= " WHERE " . $this->makeWhere($where);
			}
			
			if ($args["orderby"]){
				$sql .= " ORDER BY " . $args["orderby"];
			}
			
			if ($args["limit"]){
				$sql .= " LIMIT " . $args["limit"];
			}
			
			$result = $this->query($sql);
			if (!$result){
				throw new DBConnExc("SQL-error: " . $this->error() . "\n\nSQL: " . $sql);
			}
			
			return $result;
		}
		
		/** A quick way to do a simple update. */
		function update($table, $data, $where = null){
			$sql .= "UPDATE " . $table . " SET ";
			
			$first = true;
			foreach($data AS $key => $value){
				if ($first == true){
					$first = false;
				}else{
					$sql .= ", ";
				}
				
				$sql .= $key . " = '" . sql($value) . "'";
			}
			
			if ($where){
				$sql .= " WHERE " . $this->makeWhere($where);
			}
			
			$result = $this->query($sql);
			
			if (!$result){
				throw new DBConnExc("SQL-error: " . $this->error() . "\n\nSQL: " . $sql);
			}
			
			return $result;
		}
		
		/** A quick way to do a simple delete. */
		function delete($table, $where = null){
			$sql = "DELETE FROM " . $table;
			if ($where){
				$sql .= " WHERE " . $this->makeWhere($where);
			}
			
			$result = $this->query($sql);
			
			if (!$result){
				throw new DBConnExc("SQL-error: " . $this->error() . "\n\nSQL: " . $sql);
			}
			
			return $result;
		}
		
		/** Returns the SQL for the query based on an array. */
		function makeWhere($where){
			$first = true;
			foreach($where AS $key => $value){
				if ($first == true){
					$first = false;
				}else{
					$sql .= " AND ";
				}
				
				$sql .= $key . " = '" . sql($value) . "'";
			}
			
			return $sql;
		}
	}
?>