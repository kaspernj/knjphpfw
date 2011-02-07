<?

/** This class has functions which handels OS-specific functions. */
class knj_os{
	/** Runs a command as a pipe and returns the output. */
	static function shellCMD($cmd){
		//Send command to Unix-prompt.
		$descriptorspec = array(
			0 => array("pipe", "r"),	// stdin is a pipe that the child will read from
			1 => array("pipe", "w"),	// stdout is a pipe that the child will write to
			2 => array("pipe", "w")		// stderr is a file to write to
		);
		$process = proc_open($cmd, $descriptorspec, $pipes);
		
		//Read result-
		$result = "";
		while(!feof($pipes[1])){
			$result .= fread($pipes[1], 4096);
		}
		
		//Read errors.
		$error = "";
		while(!feof($pipes[2])){
			$error .= fread($pipes[2], 4096);
		}
		
		return array(
			"result" => $result,
			"error" => $error
		);
	}
	
	/** Returns runnning processes. */
	static function getProcs($args = null){
		if (is_array($args) && $args["grep"]){
			$grep = $args["grep"];
			$command = "ps aux | " . $grep;
		}elseif(is_string($args) && strlen($args) > 0){
			require_once "knj/functions_knj_strings.php";
			$grep = "grep " . knj_string_unix_safe($args);
			$command = "ps aux | " . $grep;
		}else{
			$command = "ps aux";
		}
		$command .= " | grep -vir grep";
		
		$psaux = knj_os::shellCMD($command);
		$procs = explode("\n", $psaux["result"]);
		$return = array();
		
		foreach($procs AS $proc){
			$proc = trim($proc);
			
			if (strlen($proc) > 0 && substr($proc, 0, 4) != "USER"){
				if (preg_match("/^(\S+)\s+([0-9]+)\s+([0-9.]+)\s+([0-9.]+)\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+ ([\s\S]+)$/", $proc, $match)){
					$cmd = $match[5];
					
					if ($cmd != $command && $cmd != $grep && $cmd != "sh -c " . $command){
						$user = $match[1];
						$pid = $match[2];
						$cpu = $match[3];
						$ram = $match[4];
						
						$return[] = array(
							"user" => $user,
							"pid" => $pid,
							"cpu" => $cpu,
							"ram" => $ram,
							"cmd" => $cmd
						);
					}
				}else{
					echo "One of the processes wasnt not read: \"" . $proc . "\".\n";
				}
			}
		}
		
		return $return;
	}
	
	/** Runs a command with system() and returns the output. */
	static function systemCMD($cmd){
		ob_start();
		system($cmd, $return);
		if (!$return){
			$return = ob_get_contents();
		}
		ob_end_clean();
		
		return $return;
	}
	
	/** Returns the path of the a graphical sudo, if installed. */
	static function getGraphicalSudo($cmd){
		$tests = array(
			"/usr/bin/gksu",
			"/usr/bin/gksudo"
		);
		foreach($tests AS $value){
			if (file_exists($value)){
				$sudo = $value;
				break;
			}
		}
		
		if ($cmd){
			$sudo .= " \"" . $cmd . "\"";
		}
		
		return $sudo;
	}
	
	/** Returns the type of running client-browser. */
	static function getBrowser(){
		$useragent = $_SERVER[HTTP_USER_AGENT];
		if (strpos($_SERVER[HTTP_USER_AGENT], "MSIE") !== false){
			return "ie";
		}elseif(strpos($_SERVER[HTTP_USER_AGENT], "Opera") !== false){
			return "opera";
		}elseif(strpos($_SERVER[HTTP_USER_AGENT], "Firefox") !== false){
			return "mozilla";
		}else{
			return "unknown";
		}
	}
	
	/** Returns the user, which is running the script. */
	static function whoAmI(){
		global $knj_whoami;
		
		if ($knj_whoami){
			return $knj_whoami;
		}
		
		$os = knj_os::getOS();
		if ($os["os"] == "linux"){
			$knj_whoami = trim(knj_os::systemCMD("whoami"));
		}else{
			throw new Exception("Unsupported OS: " . $os["os"]);
		}
		
		return $knj_whoami;
	}
	
	static function getHomeDir(){
		if ($_SERVER["HOME"]){ //linux
			return $_SERVER["HOME"];
		}elseif($_SERVER["USERPROFILE"]){ //windows
			return $_SERVER["USERPROFILE"];
		}
		
		$os = knj_os::getOS();
		if ($os == "linux"){
			$res = knj_os::shellCMD("echo \$HOME");
			return trim($res["result"]);
		}
		
		throw new Exception("Could not find out home-dir.");
	}
	
	/** Returns the type of running OS ("windows", "linux"...). */
	static function getOS(){
		global $knj_getos;
		
		if (!$knj_getos){
			if (array_key_exists("OS", $_SERVER) and strpos(strtolower($_SERVER["OS"]), "windows") !== false){
				$knj_getos["os"] = "windows";
			}else{
				$knj_getos["os"] = "linux";
			}
		}
		
		return $knj_getos;
	}
	
	/** Returns the hosts-file. */
	static function getHosts(){
		$os = knj_os::getOS();
		
		if ($os == "windows"){
			$sysroot = str_replace("\\", "/", $_SERVER["SystemRoot"]);
			return $sysroot . "/system32/drivers/etc/hosts";
		}else{
			return "/etc/hosts";
		}
	}
	
	/** Returns the supported newline string. */
	static function getNewLine(){
		$os = knj_os::getOS();
		
		if ($os == "windows"){
			return "\r\n";
		}else{
			return "\n";
		}
	}
	
	/** Returns the PATH-dirs for the CLI as string in an array. */
	static function getPaths(){
		$os = knj_os::getOS();
		if ($os[os] != "linux"){
			throw new Exception("This function only works on Linux.");
		}
		
		$paths = knj_os::shellCMD("echo \$PATH");
		$dirs = explode(":", $paths["result"]);
		
		return $dirs;
	}
	
	/** Checks if a command is registered in PATH. */
	static function checkCmd($cmd){
		$paths = knj_os::getPaths();
		
		foreach($paths AS $dir){
			if (file_exists($dir . "/" . $cmd)){
				return array("status" => true, "filepath" => $dir . "/" . $cmd);
			}
		}
		
		return array("status" => false);
	}
	
	/** Adds a new path to the PHP-include_path. */
	static function phpPathAdd($path){
		$os = knj_os::getOS();
		$oldpath = ini_get("include_path");
		
		if ($os[os] == "windows"){
			$betw = ";";
		}else{
			$betw = ":";
		}
		
		$paths = explode($betw, $oldpath);
		
		//Check if it already exists.
		foreach($paths AS $oldpath){
			if (strtolower($oldpath) == strtolower($path)){
				return true;
			}
		}
		
		//The path does not exist - add it.
		$paths[] = $path;
		$newpaths = implode($betw, $paths);
		if (!ini_set("include_path", $newpaths)){
			throw new Exception("Could not set the include_path.");
		}
		
		return true;
	}
	
	/** Fixes "knj FrameWork" to the php-include_path. */
	static function phpPathAddKnjFrameWork(){
		$dirname = dirname(__FILE__);
		$dirname = str_replace("\\", "/", $dirname);
		$dirname = explode("/", $dirname);
		unset($dirname[count($dirname) - 1]);
		$dirname = implode("/", $dirname);
		
		knj_os::phpPathAdd($dirname);
	}
	
	/** Returns the path to the PHP-executable, which you need if you want to start new processes. */
	static function getPHPExec($version = 5){
		$os = knj_os::getOS();
		
		if ($os["os"] == "linux"){
			$test_paths = array(
				"/usr/bin/php" . $version,
				"/usr/local/bin/php" . $version,
				"/usr/bin/php",
				"/usr/local/bin/php"
			);
			foreach($test_paths AS $path){
				if (file_exists($path)){
					return $path;
				}
			}
			
			if ($_SERVER["_"] && file_exists($_SERVER["_"])){
				return $_SERVER["_"];
			}
		}elseif($os["os"] == "windows"){
			//A hack to make this function work with packages created with knjPackageCreater.
			if (file_exists("../php5gtk2/php.exe")){
				return realpath("../php5gtk2/php.exe");
			}
		}else{
			throw new Exception("Unsupported OS: \"" . $os["os"] . "\".");
		}
		
		throw new Exception("Could not find the PHP-executable.");
	}
}