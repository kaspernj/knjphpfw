<?

class knj_strings{
	static function substr($string, $len1, $len2){
		return mb_substr($string, $len1, $len2, mb_detect_encoding($string));
	}
	
	static function utf8wrapper($func, $arg1){
		return utf8_encode(call_user_func($func, utf8_decode($arg1)));
	}
	
	static function utf8force($string){
		if (is_array($string)){
			foreach($string as $key => $value){
				$string[$key] = knj_strings::utf8force($value);
			}
			
			return $string;
		}else{
			$values = array();
			$special = array("ø", "æ", "å", "Ø", "Æ", "Å");
			foreach($special AS $value){
				$values[utf8_decode($value)] = $value;
			}
			
			$string = str_replace("Ã¦", "æ", $string);
			
			return strtr($string, $values);
		}
	}
	
	/** Parses a string into an array of strings, which should all be searched for. */
	static function searchstring($string){
		$array = array();
		
		if (preg_match_all("/\"(.*)\"/U", $string, $matches)){
			foreach($matches[1] AS $key => $value){
				$array[] = $value;
				$string = str_replace($matches[0][$key], "", $string);
			}
		}
		
		if (strlen($string) > 0){
			foreach(preg_split("/\s/", $string) AS $value){
				if (strlen(trim($value)) > 0){
					$array[] = $value;
				}
			}
		}
		
		return $array;
	}
	
	static function parseImageHTML($content){
		if (preg_match_all("/<img [\s\S]+ \/>/U", $content, $matches)){
			foreach($matches[0] AS $key => $value){
				$img_html = $value;
				
				if (preg_match("/src=\"([\s\S]+)\"/U", $img_html, $match_src)){
					$src = $match_src[1];
					if (substr($src, 0, 1) == "/"){
						$src = substr($src, 1);
					}
					
					$replace_with = "image.php?picture=" . $src;
					
					if (preg_match("/width: ([0-9]+)(px|)/", $img_html, $match_width)){
						$size["width"] = $match_width[1];
						$replace_with .= "&width=" . $match_width[1];
					}
					
					if (preg_match("/height: ([0-9]+)(px|)/", $img_html, $match_height)){
						$size["height"] = $match_height[1];
						$replace_with .= "&height=" . $match_width[1];
					}
					
					if (preg_match_all("/(width|height)=\"([0-9]+)(px|)\"/", $img_html, $match_sizes)){
						$size = array();
						foreach($match_sizes[1] AS $key => $sizetype){
							if (!$size[$sizetype]){
								$size[$sizetype] = $match_sizes[2][$key];
								$replace_with .= "&" . $sizetype . "=" . $match_sizes[2][$key];
							}
						}
					}
					
					if ($size){
						$img_html = str_replace($src, $replace_with, $img_html);
						$content = str_replace($value, $img_html, $content);
					}
				}
			}
		}
		
		return $content;
	}
	
	static function UnixSafe($string){
		$string = str_replace("\\", "\\\\", $string);
		$string = str_replace(" ", "\\ ", $string);
		$string = str_replace("\$", "\\\$", $string);
		$string = str_replace("(", "\\(", $string);
		$string = str_replace(")", "\\)", $string);
		$string = str_replace(";", "\\;", $string);
		$string = str_replace(",", "\\,", $string);
		$string = str_replace("'", "\\'", $string);
		$string = str_replace(">", "\\>", $string);
		$string = str_replace("<", "\\<", $string);
		$string = str_replace("\"", "\\\"", $string);
		$string = str_replace("&", "\\&", $string);
		
		//Replace the end & - if any.
		//$string = preg_replace("/&\s*$/", "\\&", $string);
		
		return $string;
	}
	
	static function RegexSafe($string){
		return strtr($string, array(
			"/" => "\\/",
			"." => "\\.",
			"(" => "\\(",
			")" => "\\)",
			"[" => "\\[",
			"]" => "\\]",
			"^" => "\\^",
			"\$" => "\\\$",
			"+" => "\\+"
		));
	}
	
	static function HeaderSafe($string){
		return strtr($string, array(
			"\r" => "",
			"\n" => " "
		));
	}
	
	static function csvsafe($string){
		$string = htmlspecialchars($string);
		$string = str_replace("\"", "", $string);
		$string = str_replace("&quot;", "", $string);
		$string = str_replace("&amp;", "&", $string);
		$string = str_replace("\r", "", $string);
		$string = str_replace("\n", "", $string);
		$string = str_replace("&lt;", "<", $string);
		$string = str_replace("&gt;", ">", $string);
		
		return $string;
	}
	
	static function htmlspecialchars_textarea($text){
		return preg_replace("/<\/textarea>/i", "&lt;/textarea>", $text);
	}
	
	static function jsparse($string, $paras = array()){
		$string = strtr($string, array(
			"'" => "\\'",
			"\"" => "&quot;",
			"\r" => "",
			"\n" => "\\n"
		));
		
		if ($paras["parse_quotes"]){
			$string = str_replace("\"", "\\\"", $string);
		}
		
		return $string;
	}
	
	static function tf_str($value, $yesstr, $nostr){
		if ($value){
			return $yesstr;
		}
		
		return $nostr;
	}
	
	static function shorten($text, $maxlength = nil){
		if (!$maxlength or strlen($text) <= $maxlength){
			return $text;
		}
		
		return trim(mb_substr($text, 0, $maxlength, mb_detect_encoding($text))) . "...";
	}
	
	static function is_email($str){
		if (preg_match("/^(.+)@(.+)\.(.+)/", $str)){
			return true;
		}
		
		return false;
	}
	
	static function filename_safe($filename){
    return knj_string_filename($filename, "linux");
	}
}

/**
	* Parses the quotes of a string.
	* 
	* FIXME: DONT USE THIS FUNCTION! It will be removed soon... Look in the SQL-functions instead.
*/
function parse_quotes($string){
	$string = str_replace("'", "\\'", $string);
	
	if (substr($string, -1, 1) == "\\" && substr($string, -2, 2) !== "\\\\"){
		$string = substr($string, 0, -1) . "\\\\";
	}
	
	return $string;
}

/** Parse a string so it fits into the command-line of Linux. */
function knj_string_unix_safe($string){
	return knj_strings::UnixSafe($string);
}

/** Parse a string so it will be a valid filename. */
function knj_string_filename($string, $os = null){
	if (!$os){
		require_once("knj/os.php");
		$os = knj_os::getOS();
		$os = $os["os"];
	}
	
	if ($os == "windows"){
		//parse windows-filename here.
	}elseif($os == "linux"){
		$string = strtr($string, array(
			"å" => "aa",
			"ø" => "oe",
			"æ" => "ae",
			utf8_decode("å") => "aa",
			utf8_decode("æ") => "ae",
			utf8_decode("ø") => "oe",
			"|" => "",
			"&" => "",
			"/" => "",
			"\\" => ""
		));
	}else{
		throw new Exception("Unsupported OS.");
	}
	
	return $string;
}

/** Parse a string to it is safe in a regex-command. */
function knj_string_regex($string){
	return knj_strings::RegexSafe($string);
}

