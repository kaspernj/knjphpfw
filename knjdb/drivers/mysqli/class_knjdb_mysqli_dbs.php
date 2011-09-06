<?php
	class knjdb_mysqli_dbs implements knjdb_driver_dbs{
		private $knjdb;
		
		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
		}
		
		function getDBs(){
			$return = array();
			$f_gdbs = $this->knjdb->query("SHOW DATABASES");
			while($d_gdbs = $f_gdbs->fetch()){
				if ($d_gdbs["Database"] != "mysql" && $d_gdbs["Database"] != "information_schema"){
					$return[] = new knjdb_db(array(
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
		}
		
		function createDB($data){
			if (!$data["name"]){
				throw new Exception("No name given.");
			}
			
			$this->knjdb->query("CREATE DATABASE " . $data["name"]);
		}
	}

