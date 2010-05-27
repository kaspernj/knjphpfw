<?php
	require_once("knjphpframework/functions_knj_strings.php");
	require_once("knjphpframework/functions_knj_os.php");
	
	/** This is a PHP-framework for the transmission-remote. */
	class transmissionremote{
		function setArgs($args){
			foreach($args AS $key => $value){
				if ($key == "user" || $key == "pass"){
					$this->args[$key] = $value;
				}else{
					throw new Exception("Invalid argument: \"" . $key . "\".");
				}
			}
		}
		
		/** Returns the auth-string which should be used with transmission-remote. */
		function getLoginString(){
			if ($this->args["user"] && $this->args["pass"]){
				return " --auth " . knj_string_unix_safe($this->args["user"]) . ":" . knj_string_unix_safe($this->args["pass"]);
			}
			
			return "";
		}
		
		/** Parses a list of downloads. */
		function getList(){
			$cmd = "transmission-remote" . $this->getLoginString() . " --list";
			$exec = knj_os::shellCMD($cmd);
			$this->errorCmd($exec);
			
			if (preg_match("/Sum:\s+None/", $exec["result"], $match)){
				return array();
			}
			
			if (!preg_match_all("/\s*([0-9]+)\s+([0-9]+)%\s+(Done|None|[0-9\.]+ \S+)\s+(Unknown|Done|[0-9.]+ (min|hrs|days))\s+([0-9.]+)\s+([0-9.]+)\s+(\S+)\s+(Seeding|Up & Down|\S+)\s+(.+)\n/", $exec["result"], $matches)){	
				throw new Exception("Could not parse list.\n\n" . "Content:\n" . $exec["result"]);
			}
			
			$return = array();
			foreach($matches[0] AS $key => $value){
				$hash = $matches[1][$key];
				$name = $matches[10][$key];
				$percent = $matches[2][$key];
				$done = $matches[4][$key];
				
				/*$size = $matches[2][$key];
				$size_mult = $matches[3][$key];
				
				if ($size_mult == "GiB"){
					$size_bytes = (($size * 1024) * 1024) * 1024;
				}elseif($size_mult == "MiB"){
					$size_bytes = ($size * 1024) * 1024;
				}else{
					$size_bytes = "[unknown]";
				}
				*/
				
				$return[] = array(
					"name" => $name,
					"id" => $hash,
					"percent" => $percent,
					"done" => $done,
					"size" => $size,
					"size_mult" => $size_mult,
					"size_bytes" => $size_bytes
				);
			}
			
			return $return;
		}
		
		function getFilesForID($id){
			$cmd = "transmission-remote" . $this->getLoginString() . " --torrent " . $id . " --files";
			$res = knj_os::shellCMD($cmd);
			
			if (!preg_match_all("/\s+([0-9]+): ([0-9]+%)\s+(Low|Normal|High)\s+(Yes|No)\s+([0-9\.]+) (\S{2,6})\s+(.*)\n/U", $res["result"], $matches)){
				throw new exception("Could not match files.");
			}
			
			$return = array();
			foreach($matches[0] AS $key => $value){
				$return[] = array(
					"filename" => trim($matches[7][$key])
				);
			}
			
			return $return;
		}
		
		/** Sets the limit of download and upload. */
		function setLimit($mode, $limit = null){
			$cmd = "transmission-remote" . $this->getLoginString();
			
			if ($mode == "down" || $mode == "up"){
				$cmd .= " --" . $mode . "limit " . knj_string_unix_safe($limit);
			}elseif($mode == "nodown"){
				$cmd .= " --no-downlimit";
			}elseif($mode == "noup"){
				$cmd .= " --no-uplimit";
			}else{
				throw new Exception("Invalid mode: \"" . $mode . "\".");
			}
			
			$exec = knj_os::shellCMD($cmd);
			$this->errorCmd($exec);
			
			return true;
		}
		
		/** Stops a torrent by its hash. */
		function torrAction($id, $action){
			$cmd = "transmission-remote" . $this->getLoginString() . " --torrent " . $id;
			
			if ($action == "stop" || $action == "start" || $action == "remove"){
				$cmd .= " --" . $action;
			}else{
				throw new Exception("Invalid action: \"" . $action . "\".");
			}
			
			$exec = knj_os::shellCMD($cmd);
			$this->errorCmd($exec);
			
			return true;
		}
		
		/** Throws exceptions based on output from knj_os::shellCMD(). */
		function errorCmd($exec){
			if (strlen(trim($exec["error"])) > 0){
				throw new Exception("Transmission-error: " . trim($exec["error"]));
			}
		}
	}
?>