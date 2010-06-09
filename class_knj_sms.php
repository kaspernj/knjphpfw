<?
	/** This class can send SMS through the danish companies: CBB (www.cbb.dk) and Bibob (www.bibob.dk). */
	class knj_SMS{
		private $opts = array("connected" => false);
		private $http;
		private $soap_client;
		
		/** Sets options. */
		function setOpts($arr){
			if (!$this->opts["mode"] && !$arr["mode"]){
				$arr["mode"] = "cbb";
			}
			
			foreach($arr AS $key => $value){
				if ($key == "mobilenumber" || $key == "password" || $key == "gnokiiexe" || $key == "gnokiiconf" || $key == "host" || $key == "port" || $key == "username"){
					//do nothing.
				}elseif($key == "mode"){
					if ($value == "bibob"){
						require_once("knjphpframework/functions_knj_extensions.php");
						knj_dl(array("soap", "openssl", "xml"));
					}elseif($value == "happii"){
						require_once("knjphpframework/class_knj_httpbrowser.php");
						$this->http = new knj_httpbrowser();
					}elseif($value == "cbb"){
						require_once("knjphpframework/class_knj_httpbrowser.php");
						$this->http = new knj_httpbrowser();
					}elseif($value == "gnokii"){
						//valid.
						if (!$this->opts["gnokiiexe"]){
							$this->opts["gnokiiexe"] = "/usr/bin/gnokii";
						}
						
						if (!$this->opts["gnokiiconf"]){
							$this->opts["gnokiiconf"] = "/etc/gnokiirc";
						}
					}elseif($value == "knjsmsgateway"){
						//valid.
					}else{
						throw new Exception("Invalid value for \"mode\": \"" . $value . "\".");
					}
				}else{
					throw new Exception("Invalid option: " . $key);
				}
				
				$this->opts[$key] = $value;
			}
		}
		
		/** Connects to CBB. */
		function connect(){
			if ($this->opts["mode"] == "cbb" || $this->opts["mode"] == "bibob"){
				if (!$this->opts["mobilenumber"] || !$this->opts["password"]){
					throw new Exception("Invalid phonenumber or password.");
				}
			}
			
			if ($this->opts["mode"] == "cbb"){
				$this->http->connect("cbb.dk");
				$html = $this->http->post("cbb?cmd=login", array(
					"mobilenumber" => $this->opts["mobilenumber"],
					"password" => $this->opts["password"]
				));
			}elseif($this->opts["mode"] == "bibob"){
				$this->soap_client = new SoapClient("https://www.bibob.dk/SmsSender.asmx?WSDL", array(
					"verify_peer" => false,
					"allow_self_signed" => true
				));
			}elseif($this->opts["mode"] == "happii"){
				$this->http->connect("www.happiimobil.dk", 443);
				$this->http->post("login/check.asp", array(
					"username" => $this->opts["mobilenumber"],
					"password" => $this->opts["password"]
				));
				$html = $this->http->getAddr("login/");
				
				if (strpos($html, "<div class=\"login_menu_txt\"><a href=\"/login/websms/\">WebSMS</a></div>") === false){
					throw new Exception("Could not log in.");
				}
			}elseif($this->opts["mode"] == "gnokii"){
				require_once("knjphpframework/functions_knj_os.php");
				$cmd = $this->opts["gnokiiexe"] . " --config " . $this->opts["gnokiiconf"] . " --identify";
				$res = knj_os::shellCMD($cmd);
				
				if (strpos($res["result"], "Model") !== false && strpos($res["result"], "Manufacturer") != false){
					//valid.
				}else{
					throw new Exception("Gnokii is not connected.");
				}
			}elseif($this->opts["mode"] == "knjsmsgateway"){
				$this->fp = fsockopen($this->opts["host"], $this->opts["port"]);
				if (!$this->fp){
					throw new Exception("Could not open connection to server.");
				}
				
				fwrite($this->fp, "login;" . $this->opts["username"] . ";" . $this->opts["password"] . "\n");
				$status = fread($this->fp, 4096);
				
				if ($status == "login;false\n"){
					throw new Exception("Invalid username and/or password.");
				}elseif($status != "login;true\n"){
					throw new Exception("Error when logging in: " . $status);
				}
			}else{
				throw new Exception("Invalid mode: " . $this->opts["mode"]);
			}
			
			$this->opts["connected"] = true;
		}
		
		/** Closes the connection to CBB. */
		function disconnect(){
			if ($this->opts["mode"] == "cbb"){
				if (!$this->http){
					throw new Exception("Not connected.");
				}
				
				$this->http->disconnect();
				unset($this->http);
			}elseif($this->opts["mode"] == "bibob"){
				unset($this->soap_client);
			}elseif($this->opts["mode"] == "happii"){
				//do nothing.
			}elseif($this->opts["mode"] == "gnokii"){
				//do nothing.
			}elseif($this->opts["mode"] == "knjsmsgateway"){
				fclose($this->fp);
			}else{
				throw new Exception("Invalid mode: \"" . $this->opts["mode"] . "\".");
			}
		}
		
		/** Returns the maxlength for a message, based on the type of mode the object is set to. */
		static function getMaxLength($mode = null){
			if (!$mode){
				$mode = $this->opts["mode"];
			}
			
			if ($mode == "bibob"){
				return 320;
			}elseif($mode == "cbb"){
				return 1206;
			}elseif($mode == "gnokii"){
				return 160;
			}elseif($mode == "knjsmsgateway"){
				return 160;
			}else{
				throw new Exception("Invalid mode: \"" . $mode . "\".");
			}
		}
		
		/** Checks a given number or array if it is valid. */
		function checkNumber($number, $stripmode = null){
			if (is_array($number)){
				foreach($number AS $key => $num){
					$number[$key] = $this->checkNumber($num, $stripmode);
				}
			}else{
				if (!preg_match("/^\+([0-9]{2})([0-9]+)$/", $number, $match_number)){
					throw new Exception("Invalid number (" . $number . ") - correct format is: \"+4512312312\".");
				}
				
				if ($this->opts["mode"] == "bibob"){
					$number = substr($number, 3);
				}
			}
			
			return $number;
		}
		
		/** Sends a SMS. */
		function sendSMS($number, $msg){
			if (!$this->opts["connected"]){
				$this->connect();
			}
			
			$number = $this->checkNumber($number);
			if ($this->opts["mode"] != "bibob" && is_array($number)){
				foreach($number AS $num){
					$this->sendSMS($num, $msg);
				}
				return true;
			}
			
			if ($this->opts["mode"] == "cbb"){
				if (!$this->http){
					$this->connect();
				}
				
				$html = $this->http->post("cbb?cmd=websmssend", array(
					"newentry" => "",
					"receivers" => $number,
					"message" => $msg,
					"smssize" => strlen($msg),
					"smsprice" => "0.19",
					"sendDate" => "",
					"sendDateHour" => "",
					"sendDateMinute" => ""
				));
				
				if (strpos($html, "<td>" . $number . "</td>") !== false){
					//do nothing.
				}else{
					throw new Exception("Could not send SMS.");
				}
			}elseif($this->opts["mode"] == "happii"){
				$html = $this->http->getAddr("login/websms/");
				
				if (!preg_match("/<form name=\"WebSMSForm\" method=\"post\" action=\"\/(\S+)\">/", $html, $match)){
					throw new Exception("Could not match PID.");
				}
				$action = $match[1];
				
				$html = $this->http->post($action, array(
					"sender" => "",
					"Recipient" => substr($number, 3),
					"sMessage" => $msg,
					"sCharsLeft" => 960 - strlen($msg),
					"sSmsCount" => strlen($msg)
				));
				if (strpos($html, "This object may be found") === false){
					throw new Exception("Could not send SMS.");
				}
			}elseif($this->opts["mode"] == "bibob"){
				if (!$this->soap_client){
					$this->connect();
				}
				
				$status_ob = $this->soap_client->__soapCall("SendMessage", array("parameters" => array(
					"cellphone" => $this->opts["mobilenumber"],
					"password" => md5($this->opts["password"]),
					"smsTo" => array("string" => $number),
					"smscontents" => $msg,
					"sendDate" => date("Y-m-d"),
					"deliveryReport" => "0",
					"fromNumber" => $this->opts["mobilenumber"]
				)));
				if ($status_ob->SendMessageResult->ErrorString != "Ingen fejl."){
					throw new Exception("Could not send SMS (" . $status_ob->SendMessageResult->ErrorString . ").");
				}
			}elseif($this->opts["mode"] == "gnokii"){
				$msg = str_replace("\"", "\\\"", $msg);
				$msg = str_replace("!", "\\!", $msg);
				
				$cmd = "echo \"" . $msg . "\" | " . $this->opts["gnokiiexe"] . " --config " . $this->opts["gnokiiconf"] . " --sendsms " . $number;
				$res = knj_os::shellCMD($cmd);
				
				if (strpos($res["error"], "Send succeeded!") !== false){
					//success!
				}else{
					throw new Exception("Could not send SMS.");
				}
			}elseif($this->opts["mode"] == "knjsmsgateway"){
				fwrite($this->fp, "sendsms;" . $number . ";" . $msg . "\n");
				$status = fread($this->fp, 4096);
				
				if ($status != "sendsms;true\n"){
					throw new Exception("Error when sending SMS: \"" . $status . "\".");
				}
			}else{
				throw new Exception("Invalid mode: \"" . $this->opts["mode"] . "\".");
			}
			
			return true;
		}
	}
?>