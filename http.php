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
    public $cookies = array();
    public $useragent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
    public $lastError = '';

    private static $_sockets = array();
    private static $_dns = array();
    private $_redirects = 0;
    private $_host;
    private $_ip;
    private $_port;
    private $_timeout = 0;
    private $_httpauth;
    private $_ssl = false;
    private $_socket;
    private $_responceHeader = '';
    private $_responce = '';

    /**
     * Set up default values.
     *
     * @param string $host    Server to connect to.
     * @param int    $port    Default is 80.
     * @param bool   $ssl     If the connection should use ssl encryption.
     * @param int    $timeout How log before giving up on connecting.
     * @param bool   $debug   Weather to print debug info.
     */
    function __construct(
        $host = '',
        $port = 80,
        $ssl = false,
        $timeout = 0,
        $debug = false
    ) {
        $this->_debug = $debug;
        if (!$timeout) {
            $timeout = ini_get('default_socket_timeout');
        }
        $this->_timeout = $timeout;
        $this->_host = $host;
        if ($host) {
            $this->_ip = $this->_getHost();
        }
        $this->_port = $port;
        $this->_ssl = $ssl ? 1 : 0;
        $this->cookies[$host] = (array) $this->cookies[$host];

        if ($this->_ip) {
            $this->_socket =& self::$_sockets[$this->_ip][$this->_port][$this->_ssl];
        }
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
        if ($this->_debug) {
            echo $msg ."\n";
        }
    }

    /**
     * Resove host to IP and cache results
     *
     * @return null
     */
    private function _getHost()
    {
        if (!self::$_dns[$this->_host]) {
            self::$_dns[$this->_host] = gethostbyname($this->_host);
        }

        return self::$_dns[$this->_host];
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

        $host = $this->_ip;
        if ($this->_ssl) {
            $host = 'ssl://' .$this->_ip;
        }

        $attempts = 0;
        while (!$this->_socket) {
            if ($attempts) {
                usleep(100000);
            }

            $attempts++;

            $this->_debug(_('Connecting to server.'));
            $this->_socket = fsockopen(
                $host,
                $this->_port,
                $errno,
                $errstr,
                $this->_timeout
            );

            if ($attempts > 5) {
                return false;
            }
        }
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
            'user' => $user,
            'passwd' => $passwd
        );
    }

    /**
     * Posts a message to a page.
     *
     * @param string $addr Absolute URI to the desired page
     * @param array  $post Name as key
     *
     * @return string Response as a string.
     */
    public function post($addr, $post)
    {
        $postdata = array();
        foreach ($post as $name => $value) {
            $postdata[] = urlencode($name) .'=' .urlencode($value);
        }

        return $this->request(
            $addr,
            implode('&', $postdata),
            array('Content-Type' => 'application/x-www-form-urlencoded')
        );
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
        $boundary = "---------------------------" .round(mktime(true));

        $postdata = "";
        foreach ($post as $key => $value) {
            $postdata .= "--" .$boundary ."\r\n";
            $postdata .= 'Content-Disposition: form-data; name="' .$key .'"' ."\r\n";
            $postdata .= "\r\n";
            $postdata .= $value;
        }

        $postdata .= "\r\n--" .$boundary ."--\r\n";

        return $this->request(
            $addr,
            $postdata,
            array('Content-Type' => 'multipart/form-data; boundary=' .$boundary)
        );
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
        if (is_array($file)
            && $file["content"]
            && $file["filename"]
            && $file["inputname"]
        ) {
            $boundary = "---------------------------" .round(mktime(true));

            $postdata .= "--" .$boundary ."\r\n";
            $postdata .= 'Content-Disposition: form-data; name="'
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
            $postdata .= 'Content-Disposition: form-data; name="'
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

        return $this->request(
            $addr,
            $postdata,
            array('Content-Type' => 'multipart/form-data; boundary=' .$boundary)
        );
    }

    /**
     * Glue the hader togeather and sent the request
     *
     * @param string $URI          Absolute URI to the desired page
     * @param string $post         Post data
     * @param array  $extraHeaders Key as type
     *
     * @return string Response as a string.
     */
    public function request($URI, $post = '', $extraHeaders =  array())
    {
        $url = parse_url($URI);
        $url['path'] = explode('/', $url['path']);
        $url['path'] = array_map('urldecode', $url['path']);
        $url['path'] = array_map('rawurlencode', $url['path']);
        $url['path'] = implode('/', $url['path']);

        $url['query'] = explode('=', $url['query']);
        foreach ($url['query'] as $key => $var) {
            $var = explode('&', $var);
            $var = array_map('urldecode', $var);
            $var = array_map('rawurlencode', $var);
            $url['query'][$key] = implode('&', $var);
        }
        $url['query'] = implode('=', $url['query']);

        $reconnect = false;

        if ($url['host'] && $url['host'] != $this->_host) {
            $this->_host = $url['host'];
            $this->_ip = $this->_getHost();
            $reconnect = true;
        }

        if ($url['port'] && $url['port'] != $this->_port) {
            $this->_port = $url['port'];
            $reconnect = true;
        }

        if (($url['scheme'] == 'https' && $this->_ssl == false)
            || ($url['scheme'] == 'http' && $this->_ssl == true)
        ) {
            $this->_ssl = $url['scheme'] == 'https' ? true : false;
            $reconnect = true;
        }

        if ($reconnect) {
            $this->_socket =& self::$_sockets[$this->_ip][$this->_port][$this->_ssl];
        }

        unset($url['scheme']);
        unset($url['host']);
        unset($url['port']);

        $URI = $this->unparseUrl($url);

        //URI must be absolute
        if (substr($URI, 0, 1) != '/') {
            $URI = '/' .$URI;
        }

        $headers['Host'] = $this->_host;
        $headers['User-Agent'] = $this->useragent;
        $headers['Accept-Encoding'] = 'gzip, deflate';
        $headers['Connection'] = 'Keep-Alive';

        $header = 'GET';
        if ($post) {
            $header = 'POST';
            $headers['Content-Type'] = 'application/octet-stream';
            $headers['Content-Length'] = mb_strlen($post, '8bit');
        }

        $header .= ' ' .$URI .' HTTP/1.1' ."\r\n";

        $headers = array_merge($headers, $extraHeaders);

        foreach ($headers as $key => $value) {
            $header .= $key . ': ' . $value ."\r\n";
        }

        if ($this->cookies[$this->_host]) {
            $cookies = array();
            foreach ($this->cookies[$this->_host] as $key => $value) {
                $cookies[] = urlencode($key) .'=' .urlencode($value);
            }
            $header = 'Cookie: ' . implode('; ' . $cookies) ."\r\n";
        }

        if ($this->_httpauth) {
            $auth = base64_encode(
                $this->_httpauth['user'] .':' .$this->_httpauth['passwd']
            );
            $header .= 'Authorization: Basic ' .$auth ."\r\n";
        }
        $header .= "\r\n";

        $this->_debug(_('Request header:') ."\n" .$header);

        $data = $header . $post;

        if (!$this->_socket) {
            $this->_reconnect();
        }

        $retry = 1;
        while (!fwrite($this->_socket, $data)) {
            if ($retry > 5) {
                throw new exception(_('Could not write to socket.'));
            }
            $this->_reconnect();
            $retry++;
        }

        $contentlength = null;
        $chunk = 0;
        $chunked = false;
        $state = "headers";
        $readsize = 1024;
        $headers = '';
        $cont100 = false;
        $html = '';
        $location = '';
        $encoding = '';
        $mime = array();
        $charset = '';

        while (true) {
            if ($readsize == 0) {
                break;
            }

            $line = fgets($this->_socket, $readsize);
            if ($headers && mb_strlen($line, '8bit') == 0) {
                break;
            } elseif ($line === false) {
                throw new exception(_('Could not read from socket.'));
            } elseif (!$headers && $line == "\r\n") {
                continue;
            }

            if ($state == "headers") {
                if ($line == "\r\n" || $line == "\n") {
                    if ($cont100 == true) {
                        $cont100 = false;
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

                    if (preg_match('/^Content-Type: (.+)/', $line, $match)) {
                        $match = explode(';', $match[1]);
                        $mime = explode('/', trim($match[0]));

                        if ($match[1]) {
                            $match = explode('=', $match[1]);
                            if (trim($match[0]) == 'charset') {
                                $charset = trim($match[1]);
                            }
                        }
                    } elseif (preg_match("/^Content-Length: ([0-9]+)/", $line, $match)) {
                        $contentlength = $match[1];
                        $contentlength_set = true;
                    } elseif (preg_match("/^Transfer-Encoding: chunked/", $line, $match)) {
                        $chunked = true;
                    } elseif (preg_match("/^Set-Cookie: (\S*?);/", $line, $match)) {
                        $key = urldecode($match[1]);
                        $value = urldecode($match[2]);

                        $this->cookies[$this->_host][$key] = $value;
                    } elseif (preg_match("/^HTTP\/1\.1 100 Continue/", $line, $match)) {
                        $cont100 = true;
                    } elseif (preg_match('/^Content-Encoding: (.+)/', $line, $match)) {
                        $encoding = trim($match[1]);
                    } elseif (preg_match("/^Location: ([\S]*)/", $line, $match)) {
                        $location = $match[1];
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
                        if (mb_strlen($line, '8bit') > $chunk) {
                            $html .= $line;
                            $chunk = 0;
                        } else {
                            $html .= $line;
                            $chunk -= mb_strlen($line, '8bit');
                        }
                    }
                } else {
                    $html .= $line;

                    if ($contentlength !== null) {
                        /*
                         * Ellers fuckede det helt, og serveren vil i nogen
                         * tilfælde slet ikke svare, før der sendes et nyt request.
                         */
                        if (($contentlength - mb_strlen($html, '8bit')) < 1024) {
                            $readsize = $contentlength - mb_strlen($html, '8bit') + 1;

                            if ($readsize <= 0) {
                                $readsize = 1024;
                            }
                        }

                        if (mb_strlen($html, '8bit') >= $contentlength) {
                            break;
                        }

                        if ($readsize <= 0) {
                            break;
                        }
                    }
                }
            }
        }

        if ($encoding == 'deflate') {
            $tmp = @gzuncompress($html);
            if (!$tmp) {
                $tmp = gzinflate($html);
            }
            $html = $tmp;
        } elseif ($encoding == 'gzip') {
            $html = gzinflate(substr($html, 10, -8));
        }

        if ($charset) {
            $html = iconv($charset, 'UTF-8', $html);
        }

        $this->_debug(_('Response headers:') ."\n" .$headers);

        $msg = _('Received content:');
        if ($mime[0] == 'text') {
            $this->_debug($msg ."\n" .$html . "\n");
        } else {
            $this->_debug($msg . ' [BINERY]' . "\n");
        }

        if (preg_match('/<h2>Object moved to <a href="([^"]*)">here<\/a>.<\/h2>/', $html, $match)) {
            $msg = _('Found "Object moved to" in HTML.');
            $this->_debug($msg);
            $location = $match[1];
        }

        if ($location) {
            if ($this->_redirects < 10) {
                $this->_redirects++;
                $msg = _('Redirect attempts %s.');
                $this->_debug(sprintf($msg, $this->_redirects));
                return $this->request($location);
            } else {
                $this->lastError = _('Too many redirects occured.');
                return false;
            }
        }

        $this->_responceHeader = $headers;
        $this->_responce = $html;
        $this->_redirects = 0;
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
        unset(self::$_sockets[$this->_ip][$this->_port][$this->_ssl]);
    }
}

