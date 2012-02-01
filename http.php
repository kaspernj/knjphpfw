<?php
/**
 * This file contains the Knj_Httpbrowser class
 *
 * PHP version 5
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */

/**
 * This class provides an object capable of doing post and get request
 * over a persistent http(s) connection.
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class Knj_Httpbrowser
{
	public $debug = false;
	public $maxRequests = 0;
	public $cookies = array();
	public $timeout = 0;
	public $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";

	private static $_redirects = 0;
	private $_host;
	private $_port;
	private $_httpauth;
	private $_ssl = false;
	private $_requestCount = 0;
	private $_socket;
	private $_responceHeader = '';
	private $_responce = '';

	/**
	 * Set up default values.
	 */
	function __construct()
	{
		$this->timeout = (int) ini_get("default_socket_timeout");
	}

	/**
	 * Print debugging messages if _debug is set.
	 *
	 * @param string $msg The message that should be printed.
	 *
	 * @return null
	 */
	private function _debug($msg)
	{
		if ($this->debug) {
			echo $msg ."\n";
		}
	}

	/**
	 * Connects to a server.
	 *
	 * @param string $host Server to connect to.
	 * @param int    $port Default is 80.
	 * @param bool   $ssl  If the connection should use ssl encryption.
	 *
	 * @return bool Return true if connection was established.
	 */
	public function connect($host, $port = 80, $ssl = false)
	{
		$this->_host = $host;
		$this->_port = $port;
		$this->_ssl = $ssl;
		$this->cookies[$host] = (array) $this->cookies[$host];

		return $this->_reconnect();
	}

	/**
	 * Reconnects to the host.
	 *
	 * @return null
	 */
	private function _reconnect()
	{
		if ($this->_socket) {
			$this->disconnect();
		}

		$host = $this->_host;
		if ($this->_ssl) {
			$host = "ssl://" .$host;
		}

		$attempts = 0;
		while (!$this->_socket) {
			if ($attempts) {
				usleep(100000);
			}

			$attempts++;

			$this->_socket = fsockopen(
				$host,
				$this->_port,
				$errno,
				$errstr,
				$this->timeout
			);

			if ($attempts > 5) {
				return false;
			}
		}

		$this->_requestCount = 0;
		return true;
	}

	/**
	 * Set connection login info
	 *
	 * @param mixed $user   Username to use.
	 * @param mixed $passwd Password to use.
	 *
	 * @return null
	 */
	public function setHTTPAuth($user, $passwd)
	{
		$this->_httpauth = array(
			"user" => $user,
			"passwd" => $passwd
		);
	}

	/**
	 * Check if the current connection is still valid.
	 *
	 * @return null
	 */
	private function _checkConnected()
	{
		if (!$this->_socket) {
			$this->_reconnect();
		}

		if ($this->maxRequests
			&& $this->_requestCount >= $this->maxRequests
		) {
			$this->_reconnect();
		}
		$this->_requestCount++;
	}

	/**
	 * Posts a message to a page.
	 *
	 * @param string $addr Absolute URI to the desired page
	 * @param array  $post TODO
	 *
	 * @return string Response as a string.
	 */
	public function post($addr, $post)
	{
		$this->_checkConnected();

		$addr = $this->_encodeUrl($addr);

		$postdata = "";
		foreach ($post as $key => $value) {
			if ($postdata) {
				$postdata .= "&";
			}

			$postdata .= urlencode($key) ."=" .urlencode($value);
		}

		//URI must be absolute
		if (substr($addr, 0, 1) != "/") {
			$addr = "/".$addr;
		}

		$headers
			= "POST " .$addr ." HTTP/1.1\r\n"
			."Content-Type: application/x-www-form-urlencoded\r\n"
			."User-Agent: " .$this->useragent ."\r\n"
			."Host: " .$this->_host ."\r\n"
			."Content-Length: " .strlen($postdata) ."\r\n"
			."Connection: Keep-Alive\r\n";

		$headers .= $this->_getAuthHeader();

		if ($this->cookies[$this->_host]) {
			foreach ($this->cookies[$this->_host] as $key => $value) {
				$headers .= "Cookie: " .urlencode($key) ."=" .$value ."\r\n";
			}
		}

		$headers .= "\r\n";

		if (!fwrite($this->_socket, $headers .$postdata)) {
			throw new exception("Could not write to socket.");
		}

		$this->last_url = "http://" .$this->_host .$addr;
		$this->_handleResponce();
		return $this->_responce;
	}

	/**
	 * TODO
	 *
	 * @param string $addr     Absolute URI to the desired page
	 * @param string $postdata TODO
	 *
	 * @return string Response as a string.
	 */
	public function postRaw($addr, $postdata)
	{
		$this->_checkConnected();

		$addr = $this->_encodeUrl($addr);

		//URI must be absolute
		if (substr($addr, 0, 1) != "/") {
			$addr = "/".$addr;
		}

		$headers = "POST " .$addr ." HTTP/1.1\r\n";
		$headers .= "Authorization: Basic "
			.base64_encode("306761540:XXnz*2ms") ."\r\n";
		$headers .= "Host: " .$host ."\r\n";

		$headers .= "Connection: close\r\n";
		$headers .= "Content-Length: " .strlen($postdata) ."\r\n";
		$headers .= "Content-Type: text/xml; charset=\"utf-8\"\r\n";
		$headers .= "\r\n";

		if (!fwrite($this->_socket, $headers .$postdata)) {
			throw new exception("Could not write to socket.");
		}

		$this->last_url = "http://" .$this->_host .$addr;
		$this->_handleResponce();
		return $this->_responce;
	}

	/**
	 * TODO
	 *
	 * @param string $addr Absolute URI to the desired page
	 * @param array  $post TODO
	 *
	 * @return string Response as a string.
	 */
	public function postFormData($addr, $post)
	{
		$this->_checkConnected();

		$addr = $this->_encodeUrl($addr);

		$boundary = "---------------------------" .round(mktime(true));

		$postdata = "";
		foreach ($post as $key => $value) {
			if ($postdata) {
				$postdata .= "\r\n";
			}

			$postdata .= "--" .$boundary ."\r\n";
			$postdata .= 'Content-Disposition: form-data; name="'
				.$key .'"' ."\r\n";
			$postdata .= "\r\n";
			$postdata .= $value;
		}

		$postdata .= "\r\n--" .$boundary ."--";

		//URI must be absolute
		if (substr($addr, 0, 1) != "/") {
			$addr = "/".$addr;
		}

		$headers
			= "POST " .$addr ." HTTP/1.1\r\n"
			."Host: " .$this->_host ."\r\n\r\n"
			."User-Agent: " .$this->useragent ."\r\n"
			."Keep-Alive: 300\r\n"
			."Connection: keep-alive\r\n"
			."Content-Length: " .strlen($postdata) ."\r\n"
			."Content-Type: multipart/form-data; boundary=" .$boundary ."\r\n";

		$headers .= $this->_getAuthHeader();

		if ($this->cookies[$this->_host]) {
			foreach ($this->cookies[$this->_host] as $key => $value) {
				$headers .= "Cookie: " .urlencode($key) ."=" .urlencode($value)
					."; FService=Password=miden&Fkode=F0623\r\n";
			}
		}

		$headers .= "\r\n";

		fputs($this->_socket, $headers);

		$count = 0;
		while ($count < strlen($postdata)) {
			fputs($this->_socket, substr($postdata, $count, 2048));
			$count += 2048;
		}

		$this->last_url = "http://" .$this->_host .$addr;
		$this->_handleResponce();
		return $this->_responce;
	}

	/**
	 * Posts a file to the server.
	 *
	 * @param string $addr Absolute URI to the desired page
	 * @param array  $post TODO
	 * @param array  $file TODO
	 *
	 * @return string Response as a string.
	 */
	public function postFile($addr, $post, $file)
	{
		$this->_checkConnected();

		$addr = $this->_encodeUrl($addr);

		if (is_array($file)
			&& $file["content"]
			&& $file["filename"]
			&& $file["inputname"]
		) {
			$boundary = "---------------------------" .round(mktime(true));

			$postdata .= "--" .$boundary . "\r\n";
			$postdata .= "Content-Disposition: form-data; name=\""
				.htmlspecialchars($file["inputname"]) ."\"; filename=\""
				.htmlspecialchars($file["filename"]) ."\"\r\n";
			$postdata .= "Content-Type: application/octet-stream\r\n";
			$postdata .= "\r\n";
			$postdata .= $file["content"];
			$postdata .= "\r\n-" .$boundary ."--\r\n";
		} else {
			$input_name = $file[0]["input"];
			$file = $file[0]["file"];

			$boundary = "---------------------------" .round(mktime(true));
			$cont = file_get_contents($file);
			$info = pathinfo($file);

			$postdata .= "--" .$boundary ."\r\n";
			$postdata .= "Content-Disposition: form-data; name=\""
				.htmlspecialchars($input_name) ."\"; filename=\""
				.htmlspecialchars($info["basename"]) ."\"\r\n";
			$postdata .= "Content-Type: application/octet-stream\r\n";
			$postdata .= "\r\n";
			$postdata .= $cont;
			$postdata .= "\r\n--" .$boundary ."--\r\n";
		}

		if (is_array($post)) {
			foreach ($post as $key => $value) {
				if ($postdata) {
					$postdata .= "&";
				}

				$postdata .= urlencode($key) ."=" .urlencode($value);
			}
		}

		//URI must be absolute
		if (substr($addr, 0, 1) != "/") {
			$addr = "/".$addr;
		}

		$headers .= "POST " .$addr ." HTTP/1.1\r\n";
		$headers .= "Host: " .$this->_host ."\r\n";
		$headers .= "Content-Type: multipart/form-data; boundary="
			.$boundary ."\r\n";
		$headers .= "Content-Length: " .strlen($postdata) ."\r\n";
		$headers .= "Connection: Keep-Alive\r\n";
		$headers .= "User-Agent: " .$this->useragent ."\r\n";
		$headers .= $this->_getAuthHeader();

		if ($this->cookies[$this->_host]) {
			foreach ($this->cookies[$this->_host] as $key => $value) {
				$headers .= "Cookie: " .urlencode($key) ."=" .$value ."\r\n";
			}
		}

		$headers .= "\r\n";


		$sendd = $headers .$postdata;
		$length = strlen($sendd);

		while ($sendd && $count < ($length + 2048)) {
			if (fwrite($this->_socket, substr($sendd, $count, 2048)) === false) {
				$msg = "Could not write to socket. Is the connection closed?";
				throw new exception($msg);
			}

			$count += 2048;
		}

		$this->_handleResponce();
		return $this->_responce;
	}

	/**
	 * Generate the auth header if needed.
	 *
	 * @return string Header line.
	 */
	private function _getAuthHeader()
	{
		$headers = "";

		if ($this->_httpauth) {
			$auth = base64_encode(
				$this->_httpauth["user"] .":" .$this->_httpauth["passwd"]
			);
			$headers .= "Authorization: Basic " .$auth ."\r\n";
		}

		return $headers;
	}

	/**
	 * Fetch a page via get.
	 *
	 * @param string $addr Absolute URI to the desired page
	 * @param mixed  $args TODO
	 *
	 * @return string Response as a string.
	 */
	public function get($addr, $args = null)
	{
		$this->_checkConnected();

		$addr = $this->_encodeUrl($addr);

		if (is_string($args)) {
			$host = $args;
		}

		if (!$host) {
			$host = $this->_host;
		}

		//URI must be absolute
		if (substr($addr, 0, 1) != "/") {
			$addr = "/".$addr;
		}


		$headers
			= "GET " .$addr ." HTTP/1.1\r\n"
			."Host: " .$host ."\r\n"
			."User-Agent: " .$this->useragent ."\r\n"
			."Connection: Keep-Alive\r\n";

		if ($args["addheader"]) {
			foreach ($args["addheader"] as $header) {
				$headers .= $header ."\r\n";
			}
		}

		if ($this->cookies[$this->_host]) {
			foreach ($this->cookies[$this->_host] as $key => $value) {
				$headers .= "Cookie: " .urlencode($key) ."="
					.urlencode($value) ."\r\n";
			}
		}

		$headers .= $this->_getAuthHeader();
		$headers .= "\r\n";

		$this->_debug(_("Request headers:") ."\n" .$headers);

		//Sometimes trying more times than one fixes the problem.
		$tries = 0;
		$tries_max = 5;
		while (!fwrite($this->_socket, $headers)) {
			sleep(1);
			$this->_reconnect();

			$tries++;
			if ($tries >= $tries_max) {
				throw new exception("Could not write to socket.");
			}
		}

		$this->last_url = "http://" .$this->_host .$addr;
		$this->_handleResponce();
		return $this->_responce;
	}

	/**
	 * Build a url string from an array
	 *
	 * @param array $parsed_url Array as returned by parse_url()
	 *
	 * @return string The URL
	 */
	public function unparseUrl($parsed_url)
	{
		$scheme   = $parsed_url['scheme'] ? $parsed_url['scheme'] .'://' : '';
		$host     = $parsed_url['host'] ? $parsed_url['host'] : '';
		$port     = $parsed_url['port'] ? ':' .$parsed_url['port'] : '';
		$user     = $parsed_url['user'] ? $parsed_url['user'] : '';
		$pass     = $parsed_url['pass'] ? ':' . $parsed_url['pass'] : '';
		$pass     .= ($user || $pass) ? '@' : '';
		$path     = $parsed_url['path'] ? $parsed_url['path'] : '';
		$query    = $parsed_url['query'] ? '?' . $parsed_url['query'] : '';
		$fragment = $parsed_url['fragment'] ? '#' . $parsed_url['fragment'] : '';
		return $scheme .$user .$pass .$host .$port .$path .$query .$fragment;
	}

	/**
	 * Make sure url is properly encoded
	 *
	 * @param string $url The url that must be checked
	 *
	 * @return string Encoded URL
	 */
	private function _encodeUrl($url)
	{
		$url = parse_url($url);
		$url['path'] = explode('/', $url['path']);
		$url['path'] = array_map('urldecode', $url['path']);
		$url['path'] = array_map('rawurlencode', $url['path']);
		$url['path'] = implode('/', $url['path']);

		$url['query'] = explode('=', $url['query']);
		$url['query'] = array_map('urldecode', $url['query']);
		$url['query'] = array_map('rawurlencode', $url['query']);
		$url['query'] = implode('=', $url['query']);

		unset($url['scheme']);
		unset($url['host']);
		unset($url['port']);

		return $this->unparseUrl($url);
	}

	/**
	 * Read the HTML after sending a request and handle any HTTP commands.
	 *
	 * @return string The body of the responce.
	 */
	private function _handleResponce()
	{
		//TODO handle Content-Type and charset
		$chunk = 0;
		$chunked = false;
		$state = "headers";
		$readsize = 1024;
		$first = true;
		$headers = "";
		$cont100 = false;
		$html = '';
		$location = '';

		while (true) {
			if ($readsize == 0) {
				break;
			}

			$line = fgets($this->_socket, $readsize);

			if (strlen($line) == 0) {
				break;
			} elseif ($line === false) {
				throw new exception("Could not read from socket.");
			} elseif ($first && $line == "\r\n") {
				/**
				 * Fixes an error when some servers sometimes sends \r\n in the end,
				 * if this is a second request.
				 */
				$line = fgets($this->_socket, $readsize);
			}

			if ($state == "headers") {
				if ($line == "\r\n" || $line == "\n" || $line == "\r\n") {
					if ($cont100 == true) {
						unset($cont100);
					} else {
						$state = "body";
						if ($contentlength == 0 && $contentlength !== null) {
							break;
						}
					}

					if ($contentlength < 1024 && $contentlength !== null) {
						$readsize = $contentlength;
					}
				} else {
					$headers .= $line;

					if (preg_match("/^Content-Length: ([0-9]+)/", $line, $match)) {
						$contentlength = $match[1];
						$contentlength_set = true;
					} elseif (preg_match("/^Transfer-Encoding: chunked/", $line, $match)) {
						$chunked = true;
					} elseif (preg_match("/^Set-Cookie: (\S+)=(\S+)(;|)( path=\/;| path=\/)\s*/U", $line, $match)) {
						$key = urldecode($match[1]);
						$value = urldecode($match[2]);

						$this->cookies[$this->_host][$key] = $value;
					} elseif (preg_match("/^Set-Cookie: (\S+)=(\S+)\s*$/U", urldecode($line), $match)) {
						$key = urldecode($match[1]);
						$value = urldecode($match[2]);

						$this->cookies[$this->_host][$key] = $value;
					} elseif (preg_match("/^HTTP\/1\.1 100 Continue/", $line, $match)) {
						$cont100 = true;
					} elseif (preg_match("/^Location: ([\S]*)/", $line, $match)) {
						//FIXME If location isn't on same server this will fail!
						$location = $match[1];
					} else {
						//echo "NU: " .$line;
					}
				}
			} elseif ($state == "body") {
				if ($chunked == true) {
					if ($line == "0\r\n" || $line == "0\n") {
						break;
					}

					//Read body with cunked data.
					if ($chunk == 0) {
						$chunk = hexdec($line);
					} else {
						if (strlen($line) > $chunk) {
							$html .= $line;
							$chunk = 0;
						} else {
							$html .= $line;
							$chunk -= strlen($line);
						}
					}
				} else {
					$html .= $line;

					if ($contentlength !== null) {
						/*
						 * Ellers fuckede det helt, og serveren vil i nogen
						 * tilfælde slet ikke svare, før der sendes et nyt request.
						 */
						if (($contentlength - strlen($html)) < 1024) {
							$readsize = $contentlength - strlen($html) + 1;

							if ($readsize <= 0) {
								$readsize = 1024;
							}
						}

						if (strlen($html) >= $contentlength) {
							break;
						}

						if ($readsize <= 0) {
							break;
						}
					}
				}
			}

			$first = false;
		}

		$this->_debug(_("Response headers:") ."\n" .$headers);
		$this->_debug(_("Received HTML:") ."\n" .$html ."\n");

		if (preg_match('/<h2>Object moved to <a href="([^"]*)">here<\/a>.<\/h2>/', $html, $match)) {
			$msg = _('Found "Object moved to" in HTML.');
			$this->_debug($msg);
			//FIXME If location isn't on same server this will fail!
			$location = $match[1];
		}

		if ($location) {
			if (self::$_redirects < 10) {
				self::$_redirects++;
				$msg = _('Redirect attempts %s.');
				$this->_debug(sprintf($msg, self::$_redirects));
				return $this->get($location);
			}
		}

		$this->_responceHeader = $headers;
		$this->_responce = $html;
		self::$_redirects = 0;
		return true;
	}

	/**
	 * Get ASP.NET form viewstate value.
	 *
	 * @return string Viewstate value
	 */
	public function aspxGetViewstate()
	{
		if (preg_match('/<input[^>]*? name="__VIEWSTATE"[^>]*? value="([^"]*)"[^>]*? \/>/', $this->_responce, $match)) {
			return urldecode($match[1]);
		}

		return '';
	}

	/**
	 *  Closes the connection.
	 *
	 * @return null
	 */
	public function disconnect()
	{
		fclose($this->_socket);
		unset($this->_socket);
	}
}

