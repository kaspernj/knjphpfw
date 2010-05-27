<?php
	/** This class makes it possible to write much shorter code when using the DBConn-framework. */
	class dbconn_fetchresult{
		public $dbconn;
		public $result;
		
		/** The constructor. */
		function __construct(DBConn $dbconn, $result){
			$this->dbconn = $dbconn;
			$this->result = $result;
		}
		
		/** Returns the data as an array. */
		function fetch(){
			return $this->dbconn->fetch($this);
		}
		
		/** Returns the result. */
		function result(){
			return $this->result;
		}
		
		/** Returns the DBConn-object. */
		function getDBConn(){
			return $this->dbconn;
		}
	}
?>