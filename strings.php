<?php

class knj_strings{
	static function utf8wrapper($func, $arg1)
	{
		return utf8_encode(call_user_func($func, utf8_decode($arg1)));
	}

	/**
	 * Parses a string into an array of strings, which should all be searched for.
	 */
	static function searchstring($string)
	{
		$array = array();

		if (preg_match_all("/\"(.*)\"/U", $string, $matches)) {
			foreach($matches[1] as $key => $value) {
				$array[] = $value;
				$string = str_replace($matches[0][$key], "", $string);
			}
		}

		if (mb_strlen($string) > 0) {
			foreach(preg_split("/\s/", $string) as $value) {
				if (mb_strlen(trim($value)) > 0) {
					$array[] = $value;
				}
			}
		}

		return $array;
	}

	static function parseImageHTML($content)
	{
		if (preg_match_all("/<img [\s\S]+ \/>/U", $content, $matches)) {
			foreach ($matches[0] as $key => $value) {
				$img_html = $value;

				if (preg_match("/src=\"([\s\S]+)\"/U", $img_html, $match_src)) {
					$src = $match_src[1];
					if (mb_substr($src, 0, 1) == "/") {
						$src = mb_substr($src, 1);
					}

					$replace_with = "image.php?picture=" .$src;

					if (preg_match("/width: ([0-9]+)(px|)/", $img_html, $match_width)) {
						$size["width"] = $match_width[1];
						$replace_with .= "&width=" .$match_width[1];
					}

					if (preg_match("/height: ([0-9]+)(px|)/", $img_html, $match_height)) {
						$size["height"] = $match_height[1];
						$replace_with .= "&height=" .$match_width[1];
					}

					if (preg_match_all("/(width|height)=\"([0-9]+)(px|)\"/", $img_html, $match_sizes)) {
						$size = array();
						foreach($match_sizes[1] as $key => $sizetype) {
							if (!$size[$sizetype]){
								$size[$sizetype] = $match_sizes[2][$key];
								$replace_with .= "&" .$sizetype ."=" .$match_sizes[2][$key];
							}
						}
					}

					if ($size) {
						$img_html = str_replace($src, $replace_with, $img_html);
						$content = str_replace($value, $img_html, $content);
					}
				}
			}
		}

		return $content;
	}

	static function UnixSafe($string)
	{
		return addcslashes($string, "\\ \$();,'><\"&");
	}

	static function RegexSafe($string)
	{
		return addcslashes($string, '\\/.()[]^\$+');
	}

	static function HeaderSafe($string)
	{
		$replace = array(
			"\r\n" => " ",
			"\n" => " ",
			"\r" => " "
		);
		return strtr($string, $replace);
	}

	static function jsparse($string, $quotes = false)
	{
		$replace = array(
			"'" => "\\'",
			"\r\n" => "\\n",
			"\n" => "\\n",
			"\r" => "\\n"
		);
		$string = strtr($string, $replace);

		if ($quotes) {
			$string = str_replace('"', '\\"', $string);
		}

		return $string;
	}

	static function tf_str($value, $yesstr, $nostr)
	{
		if ($value) {
			return $yesstr;
		}

		return $nostr;
	}

	static function shorten($text, $maxlength = 0)
	{
		if (!$maxlength or mb_strlen($text) <= $maxlength) {
			return $text;
		}

		return trim(mb_substr($text, 0, $maxlength-1)) ."…";
	}

	static function is_email($str)
	{
		if (preg_match("/^(.+)@(.+)\.(.+)/", $str)) {
			return true;
		}

		return false;
	}

	static function filename_safe($filename)
	{
		return knj_string_filename($filename, "posix");
	}
}

/**
 * Parse a string so it fits into the command-line of Linux.
 */
function knj_string_unix_safe($string)
{
	return knj_strings::UnixSafe($string);
}

/**
 * Parse a string so it will be a valid filename.
 */
function knj_string_filename($string, $os = null)
{
	if (!$os){
		require_once("knj/os.php");
		$os = knj_os::getOS();
		$os = $os["os"];
	}

	if ($os == "windows") {
		$search  = '/["*:<>?\|]+/u';
		$replace = '';
	} elseif ($os == "linux") {
		$search  = '#/#u';
		$replace = '';
	} elseif ($os == "posix") {
		$search  = '/[^A-z0-9._-]+/u';
		$replace = '';
	} else {
		throw new Exception("Unsupported OS.");
	}

	return preg_replace($search, $replace, $string);
}

/**
 * Parse a string to it is safe in a regex-command.
 */
function knj_string_regex($string)
{
	return knj_strings::RegexSafe($string);
}

