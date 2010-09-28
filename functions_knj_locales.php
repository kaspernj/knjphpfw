<?
	global $functions_knjlocales;
	$functions_knjlocales = array(
		"date_out_format" => "d/m/Y",
		"date_out_short_format" => "d/m/y",
		"date_out_format_time" => "H:i"
	);
	
	/** Initilializes the chosen locales-module. */
	function knjlocales_setmodule($domain, $dir, $module = "ext", $language = "auto"){
		global $functions_knjlocales;
		
		$functions_knjlocales["dir"] = $dir;
		$functions_knjlocales["module"] = $module;
		
		if ($language == "auto"){
			if ($_SERVER["HTTP_ACCEPT_LANGUAGE"]){
				$accept = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
				foreach(explode(",", $accept) AS $value){
					$value = explode(";", $value);
					$language = $value[0];
					break;
				}
			}elseif($_SERVER["LANG"]){
				if (preg_match("/^([a-z]{2}_[A-Z]{2})/", $_SERVER["LANG"], $match)){
				$language = $match[1];
				}else{
					//Language could not be matched - default english.
					$language = "en_GB";
				}	
			}
			
			if ($language == "da"){
				$language = "da_DK";
			}elseif($language == "de"){
				$language = "de_DE";
			}elseif($language == "en"){
				$language = "en_GB";
			}
		}
		
		$language = strtr($language, array(
			"-" => "_"
		));
		if (preg_match("/^([A-z]{2})_([A-z]{2})$/", $language, $match)){
			$language = strtolower($match[1]) . "_" . strtoupper($match[2]);
		}
		
		$functions_knjlocales["language"] = $language;
		
		if (!file_exists($dir)){
			throw new exception("Dir does not exist: " . $dir);
		}
		
		if ($module == "php-gettext"){
			require_once "php-gettext/gettext.inc";
			$functions_knjlocales["module"] = "php-gettext";
			
			_setlocale(LC_ALL, $language);// or die("Locales error 5\n");
			_setlocale(LC_MESSAGES, $language);// or die("Locales error 6\n");
			_bindtextdomain($domain, $dir);
			_bind_textdomain_codeset($domain, "UTF-8");
			_textdomain($domain);
		}elseif($module == "ext"){
			require_once "knjphpframework/functions_knj_extensions.php";
			if (!knj_dl("gettext")){
				throw new exception("gettext-module could not be loaded.");
			}
			
			$functions_knjlocales["module"] = "ext";
			
			putenv("LANGUAGE=" . $language); 
			putenv("LC_ALL=" . $language);
			putenv("LC_MESSAGE=" . $language);
			putenv("LANG=" . $language); 
			
			setlocale(LC_ALL, $language);
			setlocale(LC_MESSAGES, $language);
			
			bindtextdomain($domain, $dir);
			bind_textdomain_codeset($domain, "UTF-8");
			textdomain($domain);
		}else{
			throw new exception("knjlocales (" . __FILE__ . ":" . __LINE__ . "): No such module: " . $module . "\n");
		}
	}
	
	/** Returns the current language in use. */
	function knjlocales_getLanguage(){
		global $functions_knjlocales;
		return $functions_knjlocales["language"];
	}
	
	/** Sets options. */
	function knjlocales_setOptions($args){
		global $functions_knjlocales;
		
		foreach($args AS $key => $value){
			if ($key == "encodeout"){
				$functions_knjlocales["encodeout"] = $value;
			}elseif($key == "date_in_callback" || $key == "date_out_callback"){
				if (!is_callable($value)){
					throw new exception("The given value is not callable.");
				}
				
				$functions_knjlocales[$key] = $value;
			}elseif($key == "date_out_format" || $key == "date_out_format_time" || $key == "date_out_short_format"){
				$functions_knjlocales[$key] = $value;
			}else{
				die("Unknown option: " . $key);
			}
		}
	}
	
	/** Gets the translated string for the chosen locales-module. */
	function knjgettext($msgid){
		global $functions_knjlocales;
		
		if ($functions_knjlocales["module"] == "ext"){
			$return = gettext($msgid);
		}elseif($functions_knjlocales["module"] == "php-gettext"){
			$return = _gettext($msgid);
		}else{
			$return = $msgid;
			#throw new exception("No supported module chosen.");
		}
		
		if ($functions_knjlocales["encodeout"] == "decode_utf8"){
			$return = utf8_decode($return);
		}
		
		return $return;
	}
	
	/** Shorter version of knjgettext(). */
	function gtext($msgid){
		return knjgettext($msgid);
	}
	
	function date_out($unixt = null, $args = null){
		global $functions_knjlocales;
		
		if ($functions_knjlocales["date_out_callback"]){
			return call_user_func($functions_knjlocales["date_out_callback"]);
		}
		
		if (!$unixt){
			$unixt = time();
		}
		
		if ($args["short"]){
			$string = date($functions_knjlocales["date_out_short_format"], $unixt);
		}else{
			$string = date($functions_knjlocales["date_out_format"], $unixt);
		}
		
		if ($args["time"]){
			$string .= " " . date($functions_knjlocales["date_out_format_time"], $unixt);
		}
		
		return $string;
	}
	
	function date_in($date_string){
		global $functions_knjlocales;
		
		if ($functions_knjlocales["date_in_callback"]){
			return call_user_func($functions_knjlocales["date_in_callback"]);
		}
		
		if (preg_match("/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{1,4})(| ([0-9]{1,2}):([0-9]{1,2})(|[0-9]{1,2}))$/", $date_string, $match)){
			$date = $match[1];
			$month = $match[2];
			$year = $match[3];
			
			$hour = $match[5];
			$min = $match[6];
			
			if ($match[7]){
				$sec = $match[7]; //fix notice if empty.
			}
		}
		
		if (!$date || !$month || !$year){
			throw new InvalidDate("Could not understand the date.");
		}
		
		return mktime($hour, $min, $sec, $month, $date, $year);
	}
	
	function knjlocales_localeconv(){
		if ($functions_knjlocales["module"] == "ext"){
			return localeconv();
		}
		
		global $functions_knjlocales;
		$lang = substr($functions_knjlocales["language"], 0, 5);
		
		if ($lang == "da_DK"){
			return array(
				"decimal_point" => ",",
				"thousands_sep" => "."
			);
		}else{
			return array(
				"decimal_point" => ".",
				"thousands_sep" => ","
			);
		}
	}
	
	function number_out($number, $len = 0){
		$moneyformat = knjlocales_localeconv();
		return number_format($number, $len, $moneyformat["decimal_point"], $moneyformat["thousands_sep"]);
	}
	
	function number_in($number){
		$moneyformat = knjlocales_localeconv();
		
		$number = str_replace($moneyformat["thousands_sep"], "", $number);
		if ($moneyformat["decimal_point"] != "."){
			$temp = explode($moneyformat["decimal_point"], $number);
			$number = $temp[0] . "." . $temp[1];
		}
		
		return $number;
	}
	
	class InvalidDate extends exception{}
?>