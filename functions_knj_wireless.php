<?
	/** These functions are currently Linux-only! */
	function knj_wl_getAdapters($adapter = null){
		require_once "knj/functions_knj_os.php";
		$os = knj_os::getOS();
		
		if ($os["os"] == "linux"){
			$cmd = knj_os::shellCMD("iwconfig " . $adapter);
			$cmd = $cmd["result"];
			
			
			if (preg_match_all("/([a-z]{3,4}[0-9]{1})\s+(AR6000 802.11g|AR6000 802.11b|radio off|unassociated|IEEE 802.11b|IEEE 802.11g)\s+ESSID:(off\/any|\"([\S\s]*)\")([\s\S]+)\n\n/U", $cmd, $matches)){
				$return = array();
				
				foreach($matches[5] AS $key => $string){
					$essid = str_replace("\"", "", $matches[3][$key]);
					$state = $matches[2][$key];
					$adapter = $matches[1][$key];
					
					if ($state == "radio off" || $state == "unassociated"){
						$return[] = array(
							"state" => $state,
							"essid" => "",
							"adapter" => $adapter
						);
					}else{
						$ap = null;
						$quality = null;
						if (preg_match("/Access Point: (\S+)\s+/", $string, $match)){
							$ap = $match[1];
						}
						
						if (preg_match("/Link Quality(:|=)([0-9]{1,3})\/([0-9]{1,3})\s+/", $string, $match)){
							$quality = $match[2];
						}
						
						$return[$adapter] = array(
							"state" => $state,
							"essid" => $essid,
							"adapter" => $adapter,
							"quality" => $quality
						);
					}
				}
				
				return $return;
			}else{
				throw new Exception("No adapters could be matched.");
			}
		}else{
			throw new Exception("The system running is not Linux - sorry.");
		}
	}
	
	function knj_wl_getAPs($adapter){
		if (!$adapter){
			throw new Exception("No adapter was given.");
			return false;
		}
		
		require_once("knj/functions_knj_os.php");
		$aps_cmd = knj_os::shellCMD("iwlist " . $adapter . " scan");
		if (strlen($aps_cmd["error"]) > 0){
			throw new Exception("Could not get aps: " . trim($aps_cmd["error"]));
		}
		$aps_cmd = $aps_cmd["result"];
		
		while(true){
			if (preg_match("/Cell [0-9]+ - [\s\S]+(Cell [0-9]+\s+|$)/U", $aps_cmd, $match)){
				$match[0] = str_replace($match[1], "", $match[0]);
				$aps_strings[] = $match[0];
				$aps_cmd = str_replace($match[0], "", $aps_cmd);
			}else{
				break;
			}
		}
		
		$return = array();
		foreach($aps_strings AS $string){
			$mac = $matches[1][$key];
			$essid = null;
			if (preg_match("/ESSID:\"([\S\s]+)\"/U", $string, $match)){
				$essid = $match[1];
			}
			
			$mac = "";
			if (preg_match("/Address: (\S+)\s+/", $string, $match)){
				$mac = $match[1];
			}
			
			if (preg_match("/Encryption key:(on|off)/", $string, $match)){
				if ($match[1] == "off"){
					$enc = "off";
					$enc_type = "none";
				}else{
					$enc = "on";
					
					if (strpos(strtolower($string), "wpa") !== false){
						$enc_type = "wpa";
					}else{
						$enc_type = "wep";
					}
				}
			}else{
				$enc = "off";
				$enc_type = "none";
			}
			
			$quality = "0";
			if (preg_match("/Quality(:|=)([0-9]{1,3})\/([0-9]{1,3})/", $string, $match)){
				$quality = $match[2];
			}
			
			if ($essid){
				/** NOTE: It is possible that an AP does not have an ESSID. */
				$return[$essid][$mac] = array(
					"essid" => $essid,
					"mac" => $mac,
					"enc" => $enc,
					"enc_type" => $enc_type,
					"quality" => $quality
				);
			}
		}
		
		return $return;
	}

