<?

class knj_ftp{
	function __construct($args = array()){
		$this->args = $args;
		
		if (!$this->args["port"]){
			$this->args["port"] = 21;
		}
	}
	
	function connect(){
		$this->ftp = ftp_connect($this->args["host"], $this->args["port"]);
		if (!$this->ftp){
			throw new exception("Could not connect.");
		}
		
		if (!ftp_login($this->ftp, $this->args["user"], $this->args["passwd"])){
			throw new exception("Could not log in.");
		}
		
		if (!array_key_exists("passive", $this->args) or $this->args["passive"]){
			if (!ftp_pasv($this->ftp, true)){
				throw new exception("Could not enable passive mode.");
			}
		}else{
			if (!ftp_pasv($this->ftp, false)){
				throw new exception("Could not disable passive mode.");
			}
		}
	}
	
	function put($args){
		if (!file_exists($args["file"])){
			throw new exception("File does not exist.");
		}
		
		if (!ftp_put($this->ftp, $args["path"], $args["file"], FTP_BINARY)){
			if ($this->args["reconnect_on_error_and_try_again"]){
				$this->connect();
				
				if (!ftp_put($this->ftp, $args["path"], $args["file"], FTP_BINARY)){
					throw new exception("Could not transfer file: " . $err["message"]);
				}
			}else{
				throw new exception("Could not transfer file: " . $err["message"]);
			}
		}
	}
	
	function mkdir($args){
		if (!is_array($args)){
			throw new exception("Argument was not an array.");
		}
		
		if (strlen(trim($args["path"])) <= 0){
			throw new exception("No path was given.");
		}
		
		if (!ftp_mkdir($this->ftp, $args["path"])){
			throw new exception("Could not create dir: " . $args["path"]);
		}
	}
	
	function exists($path){
		$nlist = ftp_nlist($this->ftp, $path);
		
		if (!is_array($nlist)){
			return false;
		}
		
		return true;
	}
}

