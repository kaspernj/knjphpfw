<?php
class Knjsms_Smsd_DB implements knjsms_driver
{
	private $_db;

	function __construct(knjdb $knjdb)
	{
		$this->_db = $knjdb;
	}

	function sendSMS($number, $text)
	{
		$this->_db->insert(
			"outbox",
			array(
				"number" => $number,
				"text" => $text
			)
		);
	}
}

