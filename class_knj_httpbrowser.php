<?
	class knj_httpbrowser{
		private $host;
		private $port;
		private $output;
		private $httpauth;
		private $ssl = false;
		private $debug = false;
		private $reconnect_max;
		private $reconnect_count;
		public $fp;
		public $headers_last;
		private $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
		
		/** Connects to a server. */
		function connect($host, $port = 80, $args = array()){
			$this->host = $host;
			$this->port = $port;
			$this->output = false;
			
			if ($port == 443){
				$this->ssl = true;
			}
			
			foreach($args AS $key => $value){
				if ($key == "ssl"){
					$this->$key = $value;
				}else{
					throw new exception("Invalid argument: " . $key);
				}
			}
			
			$this->reconnect();
			
			if (!$this->fp){
				return false;
			}
			
			return true;
		}
		
		function setDebug($value){
			$this->debug = $value;
		}
		
		function setHTTPAuth($user, $passwd){
			$this->httpauth = array(
				"user" => $user,
				"passwd" => $passwd
			);
		}
		
		function setUserAgent($useragent){
			$this->useragent = $useragent;
		}
		
		function setAutoReconnect($max_requests){
			if (!is_numeric($max_requests) || $max_requests <= 0){
				throw new exception("Invalid value given: " . $max_requests);
			}
			
			$this->reconnect_max = $max_requests;
			$this->reconnect_count = 0;
		}
		
		function countAutoReconnect(){
			if ($this->reconnect_max >= 1 && $this->reconnect_count >= $this->reconnect_max){
				$this->reconnect();
				$this->reconnect_count = 0;
			}
			
			$this->checkConnected();
			$this->reconnect_count++;
		}
		
		function checkConnected(){
			if (!$this->host || !$this->fp){
				throw new exception("Not connected.");
			}
		}
		
		/** Reconnects to the host. */
		function reconnect(){
			if ($this->fp){
				$this->disconnect();
			}
			
			if ($this->ssl == true){
				$host = "ssl://" . $this->host;
			}else{
				$host = $this->host;
			}
			
			$this->fp = fsockopen($host, $this->port);
			if (!$this->fp){
				throw new exception("Could not connect.");
			}
		}
		
		/** Posts a message to a page. */
		function post($addr, $post){
			$this->countAutoReconnect();
			
			foreach($post AS $key => $value){
				if ($postdata){
					$postdata .= "&";
				}
				
				$postdata .= urlencode($key) . "=" . urlencode($value);
			}
			
			$headers = 
				"POST /" . $addr . " HTTP/1.1\r\n" .
				"Content-Type: application/x-www-form-urlencoded\r\n" .
				"User-Agent: " . $this->useragent . "\r\n" .
				"Host: " . $this->host . "\r\n" .
				"Content-Length: " . strlen($postdata) . "\r\n" .
				"Connection: Keep-Alive\r\n"
			;
			$headers .= $this->getRestHeaders();
			
			if ($this->cookies[$this->host]){
				foreach($this->cookies[$this->host] AS $key => $value){
					$headers .= "Cookie: " . urlencode($key) . "=" . $value . "\r\n";
				}
			}
			
			$headers .= "\r\n";
			
			if (!fwrite($this->fp, $headers . $postdata)){
				throw new exception("Could not write to socket.");
			}
			
			$this->last_url = "http://" . $this->host . "/" . $addr;
			return $this->readhtml();
		}
		
		function postFormData($addr, $post){
			$this->countAutoReconnect();
			
			$boundary = "---------------------------" . round(mktime(true), 0);
			
			$postdata = "";
			foreach($post AS $key => $value){
				if ($postdata){
					$postdata .= "\r\n";
				}
				
				$postdata .= "--" . $boundary . "\r\n";
				$postdata .= "Content-Disposition: form-data; name=\"" . $key . "\"\r\n";
				$postdata .= "\r\n";
				$postdata .= $value;
			}
			
			$postdata .= "\r\n--" . $boundary . "--";
			
			$headers = 
				"POST /" . $addr . " HTTP/1.1\r\n" . 
				"Host: " . $this->host . "\r\n" . 
				"User-Agent: " . $this->useragent . "\r\n" . 
				"Keep-Alive: 300\r\n" . 
				"Connection: keep-alive\r\n" . 
				"Content-Length: " . strlen($postdata) . "\r\n" .
				"Content-Type: multipart/form-data; boundary=" . $boundary . "\r\n"
			;
			$headers .= $this->getRestHeaders();
			
			if ($this->cookies[$this->host]){
				foreach($this->cookies[$this->host] AS $key => $value){
					$headers .= "Cookie: " . urlencode($key) . "=" . urlencode($value) . "; FService=Password=miden&Fkode=F0623\r\n";
				}
			}
			
			$headers .= "\r\n";
			
			fputs($this->fp, $headers);
			
			$count = 0;
			while($count < strlen($postdata)){
				fputs($this->fp, substr($postdata, $count, 2048));
				$count += 2048;
			}
			
			$this->last_url = "http://" . $this->host . "/" . $addr;
			return $this->readhtml();
		}
		
		/** Posts a file to the server. */
		function postFile($addr, $post, $file){
			$this->countAutoReconnect();
			
			if (is_array($file) && $file["content"] && $file["filename"] && $file["inputname"]){
				$boundary = "---------------------------" . round(mktime(true), 0);
				
				$postdata .= "--" . $boundary . "\r\n";
				$postdata .= "Content-Disposition: form-data; name=\"" . htmlspecialchars($file["inputname"]) . "\"; filename=\"" . htmlspecialchars($file["filename"]) . "\"\r\n";
				$postdata .= "Content-Type: application/octet-stream\r\n";
				$postdata .= "\r\n";
				$postdata .= $file["content"];
				$postdata .= "\r\n--" . $boundary . "--\r\n";
			}else{
				$input_name = $file[0]["input"];
				$file = $file[0]["file"];
				
				$boundary = "---------------------------" . round(mktime(true), 0);
				$cont = file_get_contents($file);
				$info = pathinfo($file);
				
				$postdata .= "--" . $boundary . "\r\n";
				$postdata .= "Content-Disposition: form-data; name=\"" . htmlspecialchars($input_name) . "\"; filename=\"" . htmlspecialchars($info["basename"]) . "\"\r\n";
				$postdata .= "Content-Type: application/octet-stream\r\n";
				$postdata .= "\r\n";
				$postdata .= $cont;
				$postdata .= "\r\n--" . $boundary . "--\r\n";
			}
			
			if (is_array($post)){
				foreach($post AS $key => $value){
					if ($postdata){
						$postdata .= "&";
					}
					
					$postdata .= urlencode($key) . "=" . urlencode($value);
				}
			}
			
			$headers .= "POST /" . $addr . " HTTP/1.1\r\n";
			$headers .= "Host: " . $this->host . "\r\n";
			$headers .= "Content-Type: multipart/form-data; boundary=" . $boundary . "\r\n";
			$headers .= "Content-Length: " . strlen($postdata) . "\r\n";
			$headers .= "Connection: Keep-Alive\r\n";
			$headers .= "User-Agent: " . $this->useragent . "\r\n";
			$headers .= $this->getRestHeaders();
			
			if ($this->cookies[$this->host]){
				foreach($this->cookies[$this->host] AS $key => $value){
					$headers .= "Cookie: " . urlencode($key) . "=" . $value . "\r\n";
				}
			}
			
			$headers .= "\r\n";
			
			
			$sendd = $headers . $postdata;
			$length = strlen($sendd);
			
			while($sendd && $count < ($length + 2048)){
				if (fwrite($this->fp, substr($sendd, $count, 2048)) === false){
					throw new exception("Could not write to socket. Is the connection closed?");
				}
				
				$count += 2048;
			}
			
			return $this->readHTML();
		}
		
		function getRestHeaders(){
			$headers = "";
			
			if ($this->httpauth){
				$auth = base64_encode($this->httpauth["user"] . ":" . $this->httpauth["passwd"]);
				$headers .= "Authorization: Basic " . $auth . "\r\n";
			}
			
			return $headers;
		}
		
		/** Returns the current cookies. */
		function getCookies(){
			return $this->cookies;
		}
		
		function get($addr){
			return $this->getAddr($addr);
		}
		
		/** Reads a page via get. */
		function getAddr($addr, $args = null){
			$this->countAutoReconnect();
			
			if (is_string($args)){
				$host = $args;
			}
			
			if (!$host){
				$host = $this->host;
			}
			
			if (substr($addr, 0, 1) == "/"){
				$addr = substr($addr, 1);
			}
			
			$headers = 
				"GET /" . $addr . " HTTP/1.1\r\n" .
				"Host: " . $host . "\r\n" .
				"User-Agent: " . $this->useragent . "\r\n" .
				"Connection: Keep-Alive\r\n"
			;
			
			if ($args["addheader"]){
				foreach($args["addheader"] AS $header){
					$headers .= $header . "\r\n";
				}
			}
			
			if ($this->cookies[$this->host]){
				foreach($this->cookies[$this->host] AS $key => $value){
					$headers .= "Cookie: " . urlencode($key) . "=" . urlencode($value) . "\r\n";
				}
			}
			
			$headers .= $this->getRestHeaders();
			$headers .= 
				"\r\n"
			;
			
			if ($this->debug){
				echo("getAddr()-headers:\n" . $headers . "\n\n");
			}
			
			//Sometimes trying more times than one fixes the problem.
			$tries = 0;
			$tries_max = 5;
			while(!fwrite($this->fp, $headers)){
				sleep(1);
				$this->reconnect();
				
				$tries++;
				if ($tries >= $tries_max){
					throw new exception("Could not write to socket.");
				}
			}
			
			$this->last_url = "http://" . $this->host . "/" . $addr;
			return $this->readHTML();
		}
		
		/** Read the HTML after sending a request. */
		function readHTML(){
			$chunk = 0;
			$chunked = false;
			$state = "headers";
			$readsize = 1024;
			$first = true;
			
			while(true){
				if ($readsize == 0){
					break;
				}
				
				$line = fgets($this->fp, $readsize);
				if (strlen($line) == 0){
					break;
				}elseif($line === false){
					throw new exception("Could not read from socket.");
				}elseif($first && $line == "\r\n"){
					$line = fgets($this->fp, $readsize); //fixes an error when some servers sometimes sends "\r\n" in the end, if this is a second request.
				}
				
				if ($state == "headers"){
					if ($line == "\r\n"){
						if ($cont100 == true){
							unset($cont100);
						}else{
							$state = "body";
							if ($contentlength == 0 && $contentlength !== null){
								break;
							}
						}
						
						if ($contentlength < 1024 && $contentlength !== null){
							$readsize = $contentlength;
						}
					}else{
						$headers .= $line;
						
						if (preg_match("/^Content-Length: ([0-9]+)\r\n$/", $line, $match)){
							$contentlength = $match[1];
							$contentlength_set = true;
						}elseif(preg_match("/^Transfer-Encoding: chunked\r\n$/", $line, $match)){
							$chunked = true;
						}elseif(preg_match("/^Set-Cookie: (\S+)=(\S+)(;|)( path=\/;| path=\/)\s+/U", $line, $match)){
							$key = urldecode($match[1]);
							$value = urldecode($match[2]);
							
							$this->cookies[$this->host][$key] = $value;
						}elseif(preg_match("/^Set-Cookie: (\S+)=(\S+)\s+/U", $line, $match)){
							$key = urldecode($match[1]);
							$value = urldecode($match[2]);
							
							$this->cookies[$this->host][$key] = $value;
						}elseif(preg_match("/^HTTP\/1\.1 100 Continue\r\n$/", $line, $match)){
							$cont100 = true;
						}elseif(preg_match("/^Location: (.*)\r\n$/", $line, $match)){
							$location = $match[1];
						}else{
							//echo "NU: " . $line;
						}
					}
				}elseif($state == "body"){
					if ($chunked == true){
						if ($line == "0\r\n" || $line == "0\n"){
							break;
						}
						
						//Read body with cunked data.
						if ($chunk == 0){
							$chunk = hexdec($line);
						}else{
							if (strlen($line) > $chunk){
								$html .= $line;
								$chunk = 0;
							}else{
								$html .= $line;
								$chunk -= strlen($line);
							}
						}
					}else{
						$html .= $line;
						
						if ($contentlength !== null){
							//Ellers fuckede det helt, og serveren vil i nogen tilfælde slet ikke svare, før der sendes et nyt request.
							if (($contentlength - strlen($html)) < 1024){
								$readsize = $contentlength - strlen($html) + 1;
								
								if ($readsize <= 0){
									$readsize = 1024;
								}
							}
							
							if (strlen($html) >= $contentlength - 2){
								break;
							}
							
							if ($readsize <= 0){
								break;
							}
						}
					}
				}
				
				$first = false;
			}
			
			if ($this->debug){
				echo("Received headers:\n" . $headers . "\n\n\n");
			}
			
			if ($this->debug){
				echo("Received HTML:\n" . $html . "\n\n\n");
			}
			
			if ($location){
				if ($this->debug){
					echo("Received location-header - trying to follow \"" . $match[1] . "\".\n");
				}
				return $this->getAddr($location);
			}
			
			if (preg_match("/<h2>Object moved to <a href=\"(.*)\">here<\/a>.<\/h2>/", $html, $match)){
				if ($this->debug){
					echo("\"Object moved to\" found in HTML - trying to follow.\n");
				}
				
				return $this->getAddr(urldecode($match[1]));
			}
			
			$this->headers_last = $headers;
			$this->html_last = $html;
			return $html;
		}
		
		function aspxGetViewstate(){
			if (preg_match("/<input type=\"hidden\" name=\"__VIEWSTATE\" id=\"__VIEWSTATE\" value=\"([\S\s]+)\" \/>/U", $this->html_last, $match)){
				return urldecode($match[1]);
			}
			
			return false;
		}
		
		/** Closes the connection. */
		function disconnect(){
			fclose($this->fp);
			unset($this->fp);
		}
	}
?>
