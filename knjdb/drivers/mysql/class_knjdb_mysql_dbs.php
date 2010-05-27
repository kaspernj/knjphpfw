<?php
	class knjdb_mysql_dbs implements knjdb_driver_dbs{
		private $knjdb;
		
		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
		}
		
		function getCurrentDB(){
			$db = $this->knjdb->query("SELECT DATABASE() AS 'database'")->fetch();
			if (!$db["database"]){
				throw new Exception("No database is selected.");
			}
			
			return $this->getDB($db["database"]);
		}
		
		function getDBs(){
			$return = array();
			$f_gdbs = $this->knjdb->query("SHOW DATABASES");
			while($d_gdbs = $f_gdbs->fetch()){
				if ($d_gdbs["Database"] != "mysql" && $d_gdbs["Database"] != "information_schema"){
					$return[] = new knjdb_db($this->knjdb, array(
							"name" => $d_gdbs["Database"]
						)
					);
				}
			}
			
			return $return;
		}
		
		function getDB($name){
			foreach($this->getDBs() AS $db){
				if ($db->getName() == $name){
					return $db;
				}
			}
			
			throw new Exception("Could not find the database.");
		}
		
		function chooseDB(knjdb_db $db){
			$this->knjdb->query("USE " . $db->getName());
			
			//reset tables-cache.
			$this->knjdb->tables()->tables = array();
			$this->knjdb->tables()->tables_changed = true;
		}
		
		function createDB($data){
			if (!$data["name"]){
				throw new Exception("No name given.");
			}
			
			$this->knjdb->query("CREATE DATABASE " . $data["name"]);
		}
		
		function getTablesForDB(knjdb_db $db){
			/** FIXME: This should only return tables for the current database. */
			return $this->knjdb->tables()->getTables();
		}
	}
?>