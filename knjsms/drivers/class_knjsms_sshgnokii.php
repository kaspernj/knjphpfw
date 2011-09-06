<?php
	class knjsms_sshgnokii implements knjsms_driver{
		function __construct($arr_opts){
			require_once("knj/functions_knj_extensions.php");
			knj_dl("ssh2");

			require_once("knj/class_knj_ssh2.php");
			$this->ssh = knj_ssh2::quickConnect($arr_opts["host"], $arr_opts["user"], $arr_opts["passwd"], $arr_opts["port"]);
		}

		function sendSMS($number, $text){
			$res = $this->ssh->shellCMD("echo \"This is a test message\" | gnokii --sendsms " . $number);
			print_r($res);
		}
	}

