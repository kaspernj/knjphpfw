<?php
	class knjdb_mssql_procedures implements knjdb_driver_procedures{
		private $knjdb;
		public $procedures = array();
		
		function __construct(knjdb $knjdb){
			$this->knjdb = $knjdb;
		}
		
		function getProcedures(){
			$f_gp = $this->knjdb->query("SELECT * FROM sysobjects WHERE type = 'P' AND category = 0");
			while($d_gp = $f_gp->fetch()){
				$this->procedures = new knjdb_procedure($this->knjdb, array(
						"name" => $d_gp["name"]
					)
				);
			}
			
			return $this->procedures;
		}
		
		function getProcedure($name){
			if (count($this->procedures) <= 0){
				$this->getProcedures(); //creates cache.
			}
			
			return $this->procedures[$name];
		}
	}

