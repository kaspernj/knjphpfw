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
		}
	}
	
	function put($args){
		if (!ftp_put($this->ftp, $args["file"], $args["path"], FTP_BINARY)){
			throw new exception("Could not transfer file.");
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