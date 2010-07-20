<?
	class knj_ssh2{
		private $conn;			//The SSH2-connection.
		private $shell;		//The shell that this class is going to use.
		
		/** The constructor of knj_ssh2. */
		function __construct($args){
			if (!$args["conn"]){
				throw new Exception("No conn supplied in parameters.");
			}
			
			require_once("knjphpframework/functions_knj_filesystem.php");
			$this->conn = $args["conn"];
			
			if (!$args["shell"]){
				$this->shell = ssh2_shell($this->conn);
			}else{
				$this->shell = $args["shell"];
			}
			
			stream_set_blocking($this->shell, true);
		}
		
		function destroy(){
			unset($this->shell, $this->conn);
		}
		
		function getConn(){
			return $this->conn;
		}
		
		function getShell(){
			return $this->shell;
		}
		
		static function quickConnect($host, $user, $pass, $port = 22){
			if (!function_exists("ssh2_connect")){
				throw new exception(_("SSH2 extension is not loaded."));
			}
			
			$conn = ssh2_connect($host, $port);
			if (!$conn){
				throw new Exception("Could not connect to the server.");
			}
			
			if (!ssh2_auth_password($conn, $user, $pass)){
				throw new Exception("Invalid username and/or password.");
			}
			
			$knj_ssh2 = new knj_ssh2(array("conn" => $conn));
			return $knj_ssh2;
		}
		
		/** Returns a list of files in an array. */
		function getDir($dir){
			$stream = ssh2_exec($this->conn, "ls -l " . $dir);
			while(!feof($stream)){
				$string .= fgets($stream, 4096);
			}
			
			return knj_fs::conv_lsl($string);
		}
		
		/** Returns the content of a file. */
		function getFile($path, $args = array()){
			if ($args["readmode"] == "shell"){
				$startstring = md5("[THE START]" . time() . "-" . microtime(true));
				$endstring = md5("[THE END]" . time() . "-" . microtime(true));
				
				$commands = "echo " . $startstring . ";";
				$commands .= "cat " . $path . ";";
				$commands .= "echo " . $endstring . PHP_EOL;
				
				fwrite($this->shell, $commands);
				usleep(450000);
				
				while(true){
					$new = fgets($this->shell, 4096);
					$string .= $new;
					
					if ($new == $endstring . "\r\n"){
						break;
					}
				}
				
				if (!preg_match("/" . knj_strings::regexsafe($startstring) . "\s\s([\s\S]+)\s\s" . knj_strings::regexsafe($endstring) . "/", $string, $match)){
					throw new exception("Could not read result from server.");
				}
				
				$result = $match[1];
				if (strpos($result, "cat: command not found") !== false){
					throw new exception("cat-command is not supported on that server using that user.");
				}
				
				$notfound_string = "cat: " . $path . ": No such file or directory";
				if (strpos($result, $notfound_string) !== false){
					throw new exception("File was not found: " . $path);
				}
				
				return $result;
			}else{
				$stream = ssh2_exec($this->conn, "cat " . $path);
				stream_set_blocking($stream, true);
				$string = stream_get_contents($stream);
			}
			
			return $string;
		}
		
		/** Saves a file on the server. */
		function putFile($path, $content){
			require_once("knjphpframework/functions_knj_strings.php");
			
			$lines = explode("\n", $content);
			$first = true;
			foreach($lines AS $line){
				if ($first){
					$first = false;
					$this->shellCMD("echo " . knj_string_unix_safe($line) . " > " . $path) . "\n";
				}else{
					$this->shellCMD("echo " . knj_string_unix_safe($line) . " >> " . $path) . "\n";
				}
			}
		}
		
		function getHomeDir(){
			//sometimes there is some extra lines - this handles that.
			$lines = explode("\n", $this->shellCMD("echo \$HOME"));
			return $lines[count($lines) - 2];
		}
		
		function sulogin($password){
			fwrite($this->shell, "su -" . PHP_EOL);
			usleep(500000);
			fwrite($this->shell, $password . PHP_EOL);
			usleep(200000);
			fwrite($this->shell, PHP_EOL);
			
			while(true){
				$read = fgets($this->shell, 4096);
				
				if (preg_match("/^Password:(\s*)$/", $read)){
					//do nothing - password already sent.
				}elseif(preg_match("/^su: incorrect password(\s*)$/", $read)){
					throw new Exception("Incorrect root password.");
				}elseif(strpos($read, "root@") !== false){
					break; //logged in as root.
				}elseif(strpos($read, ":~#") !== false){
					break;
				}
			}
		}
		
		function shellCMD($cmd){
			$endstring = md5("[THE END]" . time() . "-" . microtime(true));
			
			fwrite($this->shell, $cmd . PHP_EOL);
			fwrite($this->shell, "echo " . $endstring . PHP_EOL);
			
			$string = "";
			while(true){
				$new = fgets($this->shell, 4096);
				
				if ($new == $endstring . "\r\n"){
					break;
				}elseif(strpos($new, $endstring) !== false){
					//do nothing - this is the echo-command being given to us.
				}else{
					$string .= $new;
				}
			}
			
			return $string;
		}
	}
?>