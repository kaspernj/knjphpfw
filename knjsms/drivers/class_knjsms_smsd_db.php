<?php
	class knjsms_smsd_db implements knjsms_driver{
		public $db;

		function __construct($args){
			require_once("knj/knjdb/class_knjdb.php");
			$this->db = new knjdb($args["knjdb_args"]);
		}

		function sendSMS($number, $text){
			$this->db->insert("outbox", array(
				"number" => $number,
				"text" => utf8_decode($text)
			));
		}
	}
?>