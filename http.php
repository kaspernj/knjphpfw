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
	private $nl = "\r\n";
	public $fp;
	public $headers_last;
	private $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
	
	function __construct($args = array()){
		$this->args = array(
			"timeout" => ini_get("default_socket_timeout")
		);
		$this->cookies = array();
		
		foreach($args AS $key => $value){
			if ($key == "ssl" or $key == "nl" or $key == "debug" or $key == "force_connection"){
				$this->$key = $value;
			}
			
			$this->args[$key] = $value;
		}
	}
	
	function debug($msg){
		if ($this->debug == "kasper"){
			echo $msg;
		}
	}
	
	/** Connects to a server. */
	function connect($host, $port = 80, $args = array()){
		$this->host = $host;
		$this->port = $port;
		$this->output = false;
		
		if (!array_key_exists($host, $this->cookies)){
      $this->cookies[$host] = array();
    }
		
		if ($port == 443){
			$this->ssl = true;
		}
		
		foreach($args AS $key => $value){
			if ($key == "ssl" or $key == "nl" or $key == "debug" or $key == "force_connection"){
				$this->$key = $value;
			}
			
			$this->args[$key] = $value;
		}
		
		$this->reconnect();
		
		if (!$this->fp){
			return false;
		}
		
		return true;
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
		
		$this->fp = fsockopen($host, $this->port, $errno, $errstr, $this->args["timeout"]);
		if (!$this->fp){
			throw new exception("Could not connect.");
		}
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
		while(true){
			if (!$this->host or !$this->fp){
				if ($this->force_connection){
					usleep(100000);
					$this->reconnect();
				}else{
					throw new exception("Not connected.");
				}
			}else{
				break;
			}
		}
	}
	
	/** Posts a message to a page. */
	function post($addr, $post){
		$this->countAutoReconnect();
		
		$postdata = "";
		foreach($post AS $key => $value){
			if ($postdata){
				$postdata .= "&";
			}
			
			$postdata .= urlencode($key) . "=" . urlencode($value);
		}
		
		$headers = 
			"POST /" . $addr . " HTTP/1.1" . $this->nl .
			"Content-Type: application/x-www-form-urlencoded" . $this->nl .
			"User-Agent: " . $this->useragent . $this->nl .
			"Host: " . $this->host . $this->nl .
			"Content-Length: " . strlen($postdata) . $this->nl .
			"Connection: Keep-Alive" . $this->nl
		;
		$headers .= $this->getRestHeaders();
		
		if ($this->cookies[$this->host]){
			foreach($this->cookies[$this->host] AS $key => $value){
				$headers .= "Cookie: " . urlencode($key) . "=" . $value . $this->nl;
			}
		}
		
		$headers .= "" . $this->nl;
		
		if (!fwrite($this->fp, $headers . $postdata)){
			throw new exception("Could not write to socket.");
		}
		
		$this->last_url = "http://" . $this->host . "/" . $addr;
		return $this->readhtml();
	}
	
	function post_raw($addr, $postdata){
		$this->countAutoReconnect();
		
		$headers = "POST /" . $addr . " HTTP/1.1" . $this->nl;
		
		if ($this->auth_basic){
      $headers .= "Authorization: Basic " . base64_encode($this->auth_basic["user"] . ":" . $this->auth_basic["passwd"]) . $this->nl;
    }
    
		$headers .= "Host: " . $host . $this->nl;
		$headers .= "Connection: close" . $this->nl;
		$headers .= "Content-Length: " . strlen($postdata) . $this->nl;
		$headers .= "Content-Type: text/xml; charset=\"utf-8\"" . $this->nl;
		$headers .= $this->nl;
		
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
				$postdata .= "" . $this->nl;
			}
			
			$postdata .= "--" . $boundary . $this->nl;
			$postdata .= "Content-Disposition: form-data; name=\"" . $key . "\"" . $this->nl;
			$postdata .= "" . $this->nl;
			$postdata .= $value;
		}
		
		$postdata .= $this->nl . "--" . $boundary . "--";
		
		$headers =
			"POST /" . $addr . " HTTP/1.1" . $this->nl .
			"Host: " . $this->host . $this->nl .  $this->nl .
			"User-Agent: " . $this->useragent . $this->nl .
			"Keep-Alive: 300" .  $this->nl .
			"Connection: keep-alive" .  $this->nl .
			"Content-Length: " . strlen($postdata) . $this->nl .
			"Content-Type: multipart/form-data; boundary=" . $boundary . $this->nl
		;
		$headers .= $this->getRestHeaders();
		
		if ($this->cookies[$this->host]){
			foreach($this->cookies[$this->host] AS $key => $value){
				$headers .= "Cookie: " . urlencode($key) . "=" . urlencode($value) . "; FService=Password=miden&Fkode=F0623" .  $this->nl;
			}
		}
		
		$headers .= $this->nl;
		
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
			
			$postdata .= "--" . $boundary .  $this->nl;
			$postdata .= "Content-Disposition: form-data; name=\"" . htmlspecialchars($file["inputname"]) . "\"; filename=\"" . htmlspecialchars($file["filename"]) . "\"" . $this->nl;
			$postdata .= "Content-Type: application/octet-stream" .  $this->nl;
			$postdata .= $this->nl;
			$postdata .= $file["content"];
			$postdata .= $this->nl . "-" . $boundary . "--" .  $this->nl;
		}else{
			$input_name = $file[0]["input"];
			$file = $file[0]["file"];
			
			$boundary = "---------------------------" . round(mktime(true), 0);
			$cont = file_get_contents($file);
			$info = pathinfo($file);
			
			$postdata .= "--" . $boundary . $this->nl;
			$postdata .= "Content-Disposition: form-data; name=\"" . htmlspecialchars($input_name) . "\"; filename=\"" . htmlspecialchars($info["basename"]) . "\"" . $this->nl;
			$postdata .= "Content-Type: application/octet-stream" . $this->nl;
			$postdata .= $this->nl;
			$postdata .= $cont;
			$postdata .= $this->nl . "--" . $boundary . "--" . $this->nl;
		}
		
		if (is_array($post)){
			foreach($post AS $key => $value){
				if ($postdata){
					$postdata .= "&";
				}
				
				$postdata .= urlencode($key) . "=" . urlencode($value);
			}
		}
		
		$headers .= "POST /" . $addr . " HTTP/1.1" . $this->nl;
		$headers .= "Host: " . $this->host . $this->nl;
		$headers .= "Content-Type: multipart/form-data; boundary=" . $boundary . $this->nl;
		$headers .= "Content-Length: " . strlen($postdata) . $this->nl;
		$headers .= "Connection: Keep-Alive" . $this->nl;
		$headers .= "User-Agent: " . $this->useragent . $this->nl;
		$headers .= $this->getRestHeaders();
		
		if ($this->cookies[$this->host]){
			foreach($this->cookies[$this->host] AS $key => $value){
				$headers .= "Cookie: " . urlencode($key) . "=" . $value . $this->nl;
			}
		}
		
		$headers .= "" . $this->nl;
		
		
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
			$headers .= "Authorization: Basic " . $auth . $this->nl;
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
			"GET /" . $addr . " HTTP/1.1" . $this->nl .
			"Host: " . $host . $this->nl .
			"User-Agent: " . $this->useragent . $this->nl .
			"Connection: Keep-Alive" . $this->nl
		;
		
		if ($args["addheader"]){
			foreach($args["addheader"] AS $header){
				$headers .= $header . $this->nl;
			}
		}
		
		if ($this->cookies[$this->host]){
			foreach($this->cookies[$this->host] AS $key => $value){
				$headers .= "Cookie: " . urlencode($key) . "=" . urlencode($value) . $this->nl;
			}
		}
		
		$headers .= $this->getRestHeaders();
		$headers .= $this->nl;
		
		$this->debug("getAddr()-headers:\n" . $headers);
		
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
		$headers = "";
		$cont100 = null;
		$html = "";
		$location = null;
		
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
				$line = fgets($this->fp, $readsize); //fixes an error when some servers sometimes sends \r\n in the end, if this is a second request.
			}
			
			if ($state == "headers"){
				if ($line == "\r\n" or $line == "\n" or $line == $this->nl){
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
					
					if (preg_match("/^Content-Length: ([0-9]+)\s*$/", $line, $match)){
						$contentlength = $match[1];
						$contentlength_set = true;
					}elseif(preg_match("/^Transfer-Encoding: chunked\s*$/", $line, $match)){
						$chunked = true;
					}elseif(preg_match("/^Set-Cookie: (\S+)=(\S+)(;|)( path=\/;| path=\/)\s*/U", $line, $match)){
						$key = urldecode($match[1]);
						$value = urldecode($match[2]);
						
						$this->cookies[$this->host][$key] = $value;
					}elseif(preg_match("/^Set-Cookie: (\S+)=(\S+)\s*$/U", urldecode($line), $match)){
						$key = urldecode($match[1]);
						$value = urldecode($match[2]);
						
						$this->cookies[$this->host][$key] = $value;
					}elseif(preg_match("/^HTTP\/1\.1 100 Continue\s*$/", $line, $match)){
						$cont100 = true;
					}elseif(preg_match("/^Location: (.*)\s*$/", $line, $match)){
						$location = trim($match[1]);
					}else{
						//echo "NU: " . $line;
					}
				}
			}elseif($state == "body"){
				if ($chunked == true){
					if ($line == "0\r\n" or $line == "0\n"){
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
						
						if (strlen($html) >= $contentlength){
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
		
		$this->debug("Received headers:\n" . $headers . "\n\n\n");
		$this->debug("Received HTML:\n" . $html . "\n\n\n");
		
		if ($location){
			$this->debug("Received location-header - trying to follow \"" . $match[1] . "\".\n");
			return $this->getAddr($location);
		}
		
		if (preg_match("/<h2>Object moved to <a href=\"(.*)\">here<\/a>.<\/h2>/", $html, $match)){
			$this->debug("\"Object moved to\" found in HTML - trying to follow.\n");
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

