<?php
	class knjsms{
		function __construct($arr_opts){
			$ob_file = "knjphpframework/knjsms/drivers/class_knjsms_" . $arr_opts["type"] . ".php";
			$ob_name = "knjsms_" . $arr_opts["type"];
			
			require_once("knjphpframework/knjsms/interface_knjsms_driver.php");
			require_once($ob_file);
			$this->ob = new $ob_name($arr_opts);
		}
		
		function sendSMS($number, $text){
			$this->ob->sendSMS($number, $text);
		}
	}
?>