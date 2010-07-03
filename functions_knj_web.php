<?
	/** This file contains functions that may come in handy when building web-applications. */
	//require_once("knjphpframework/functions_knj_sql.php");
	global $knj_web;
	$knj_web = array(
		"col_id_name" => "id"
	);
	
	class web{
		function input($paras){
			ob_start();
			form_drawInput($paras);
			$html = ob_get_contents();
			ob_end_clean();
			return $html;
		}
		
		function drawInput($paras){
			return form_drawInput($paras);
		}
		
		function drawOpts($opts, $selected = null){
			return select_drawOpts($opts, $selected);
		}
		
		function checkVal($val, $opt1 = "1", $opt2 = "0"){
			if ($val == "on"){
				return $opt1;
			}else{
				return $opt2;
			}
		}
		
		function alert($msg){
			return alert($msg);
		}
		
		function redirect($url){
			return redirect($url);
		}
		
		function back(){
			return jsback();
		}
		
		function rewritesafe($string){
			$string = strtr($string, array(
				"&" => "",
				"æ" => "ae",
				"ø" => "oe",
				"å" => "aa",
				"Æ" => "AE",
				"Å" => "AA",
				"Ø" => "OE",
				"\"" => "",
				"/" => "_",
				"(" => "",
				")" => "",
				"*" => "",
				":" => "-",
				"+" => "_",
				"." => "-"
			));
			$string = preg_replace("/\s+/", "_", $string);
			
			return $string;
		}
		
		function rewriteback($string){
			return strtr($string, array(
				"_" => " "
			));
		}
		
		function htmlspecialchars_textarea($input){
			return htmlspecialchars_textarea($input);
		}
	}
	
	function secCheckInclude($file){
		if (strpos($file, "..") !== false){
			throw new exception("Possible hack.");
		}
	}
	
	/** Function to redirect. You can use this instead of using the header()-function. */
	function redirect($url, $exit = true){
		global $knj_web;
		
		if (!headers_sent() && !$knj_web[alert_sent]){
			header("Location: " . $url);
		}else{
			jsredirect($url);
		}
		
		if ($exit == true){
			exit();
		}
	}
	
	/** Function to show a message through the JS-alert-function. */
	function alert($msg){
		global $knj_web;
		
		$msg = strtr($msg, array(
			"\n" => "\\n",
			"\t" => "\\t",
			"\r" => "",
			"\"" => "\\\""
		));
		
		$knj_web["alert_sent"] = true;
		?><script type="text/javascript">alert("<?=$msg?>");</script><?
	}
	
	function jsback(){
		?>
			<script type="text/javascript">
				history.back(-1);
			</script>
		<?
		exit();
	}
	
	function jsredirect($url){
		?><script type="text/javascript">location.href="<?=$url?>";</script><?
		exit();
	}
	
	function htmlspecialchars_textarea($input){
		$input = preg_replace("/<\/textarea>/", "&lt;/textarea>", $input);
		return $input;
	}
	
	function select_drawOpts($opts, $selected = null){
		if (is_object($selected)){
			$selected = $selected->get("id");
		}elseif(is_array($selected) && is_object($selected[0])){
			$selected = call_user_func(array($selected[0], $selected[1]), $selected[2]);
		}
		
		$html = "";
		foreach($opts AS $key => $value){
			$html .= "<option";
			
			$is_selected = false;
			if (is_array($selected) && in_array($key, $selected)){
				$is_selected = true;
			}elseif($key == $selected){
				$is_selected = true;
			}
			
			if ($is_selected){
				$html .= " selected=\"selected\"";
			}
			
			$html .= " value=\"" . htmlspecialchars($key) . "\">" . htmlspecialchars($value) . "</option>";
		}
		
		return $html;
	}
	
	function form_drawInput($paras){
		if (is_array($paras["value"]) && is_callable($paras["value"])){
			$value = call_user_func($paras["value"]);
		}elseif(is_array($paras["value"]) && is_object($paras["value"][0])){
			$value = $paras["value"][0]->$paras["value"][1]($paras["value"][2]);
		}else{
			if ($paras["value"] === null && array_key_exists("default", $paras)){
				$value = $paras["default"];
			}else{
				$value = $paras["value"];
			}
		}
		
		if (is_array($value)){
			$value = null;
		}
		
		if ($value and $paras["value_callback"]){
			if ($paras["value_callback"][1]){
				$value = call_user_func($paras["value_callback"][0], $value, $paras["value_callback"][1]);
			}else{
				$value = call_user_func($paras["value_callback"][0], $value);
			}
		}
		
		if (is_null($value) and array_key_exists("default", $paras)){
			$value = $paras["default"];
		}
		
		if (!$paras["type"]){
			$f3 = substr($paras["name"], 0, 3);
			if ($f3 == "che"){
				$paras["type"] = "checkbox";
			}elseif($f3 == "tex"){
				$paras["type"] = "text";
			}elseif($f3 == "sel" or $paras["opts"]){
				$paras["type"] = "select";
			}
		}
		
		if (!$paras["id"]){
			$id = $paras["name"];
		}else{
			$id = $paras["id"];
		}
		
		if (!$paras["type"]){
			$paras["type"] = "text";
		}
		
		if ($paras["type"] == "password" and !$paras["class"]){
			$paras["class"] = "input_text";
		}
		
		if (!$paras["class"]){
			$paras["class"] = "input_" . $paras["type"];
		}
		
		if ($paras["colspan"]){
			$colspan_cont = $paras["colspan"] - 1;
		}
		
		if (!array_key_exists("tr", $paras) or $paras["tr"]){
			?><tr><?
		}
		
		$td_html = "<td class=\"tdc\"";
		if ($paras["td_width"]){
			$td_html .= " style=\"width: " . $paras["td_width"] . ";\"";
		}
		if ($colspan_cont > 1){
			$td_html .= " colspan=\"" . $colspan_cont . "\"";
		}
		$td_html .= ">";
		
		if ($paras["type"] == "checkbox"){
			?>
				<td colspan="2" class="tdcheck">
					<input type="<?=$paras["type"]?>" name="<?=$paras["name"]?>" id="<?=$id?>"<?if ($value){?> checked="checked"<?}?> />
					<label for="<?=$id?>"><?=htmlspecialchars($paras["title"])?></label>
				</td>
			<?
		}elseif($paras["type"] == "select"){
			?>
				<td class="tdt">
					<?=htmlspecialchars($paras["title"])?>
				</td>
				<?=$td_html?>
					<select<?if ($paras["size"]){?> size="<?=htmlspecialchars($paras["size"])?>"<?}?> name="<?=htmlspecialchars($paras["name"])?>" id="<?=htmlspecialchars($id)?>" class="<?=$paras["class"]?>"<?if ($paras["onchange"]){?> onchange="<?=$paras["onchange"]?>"<?}?>>
					<?=select_drawOpts($paras["opts"], $paras["value"])?>
					</select>
				</td>
			<?
		}elseif($paras["type"] == "imageupload"){
			if (!$value){
				$fn = null;
			}else{
				$fn = $paras["path"] . "/" . $value . ".jpg";
			}
			
			if (!$fn or !file_exists($fn)){
				$found = false;
				$fn_nopic = "images/nopicture.jpg";
				$fn = null;
				
				if (file_exists($fn_nopic)){
					$fn = $fn_nopic;
				}
			}else{
				$found = true;
			}
			
			if ($paras["dellink"]){
				$paras["dellink"] = str_replace("%value%", $value, $paras["dellink"]);
			}
			
			?>
				<td class="tdt">
					<?=htmlspecialchars($paras["title"])?>
				</td>
				<?=$td_html?>
					<table class="designtable">
						<tr>
							<td style="width: 100%;">
								<input type="file" name="<?=htmlspecialchars($paras["name"])?>" id="<?=htmlspecialchars($id)?>" class="<?=htmlspecialchars($paras["class"])?>" />
							</td>
							<td>
								<?if ($fn){?>
									<img src="image.php?picture=<?=urlencode($fn)?>&amp;smartsize=80&amp;edgesize=20&amp;equaldim=true" alt="Preview" />
								<?}?>
								<?if ($found and $paras["dellink"]){?>
									<div style="text-align: center;">
										(<a href="javascript: if (confirm('<?=gtext("Do you want to delete the picture?")?>')){location.href='<?=$paras["dellink"]?>';}"><?=gtext("delete")?></a>)
									</div>
								<?}?>
							</td>
						</tr>
					</table>
				</td>
			<?
		}elseif($paras["type"] == "file"){
			?>
				<td class="tdt">
					<?=htmlspecialchars($paras["title"])?>
				</td>
				<?=$td_html?>
					<input type="file" class="input_<?=$paras["type"]?>" name="<?=htmlspecialchars($paras["name"])?>" id="<?=htmlspecialchars($id)?>" />
				</td>
			<?
		}elseif($paras["type"] == "textarea"){
			?>
				<td class="tdt">
					<?=htmlspecialchars($paras["title"])?>
				</td>
				<?=$td_html?>
					<textarea name="<?=htmlspecialchars($paras["name"])?>" class="<?=htmlspecialchars($paras["class"])?>"<?if ($paras["height"]){?> style="height: <?=$paras["height"]?>;"<?}?>><?=htmlspecialchars_textarea($value)?></textarea>
				</td>
			<?
		}elseif($paras["type"] == "fckeditor"){
			?>
				<td class="tdt">
					<?=htmlspecialchars($paras["title"])?>
				</td>
				<?=$td_html?>
					<?
						$fck = new fckeditor($paras["name"]);
						
						if ($paras["height"]){
							$fck->Height = $paras["height"];
						}else{
							$fck->Height = 300;
						}
						
						$fck->Value = $value;
						$fck->Create();
					?>
				</td>
			<?
		}else{
			?>
				<td class="tdt">
					<?=htmlspecialchars($paras["title"])?>
				</td>
				<?=$td_html?>
					<input type="<?=htmlspecialchars($paras["type"])?>"<?if ($paras["disabled"]){?> disabled<?}?><?if ($paras["maxlength"]){?> maxlength="<?=$paras["maxlength"]?>"<?}?> class="<?=$paras["class"]?>" id="<?=htmlspecialchars($id)?>" name="<?=htmlspecialchars($paras["name"])?>" value="<?=htmlspecialchars($value)?>" />
				</td>
			<?
		}
		
		if (!array_key_exists("tr", $paras) or $paras["tr"]){
			?><tr><?
		}
		
		if ($paras["descr"]){
			?>
				<tr>
					<td colspan="2" class="tdd">
						<?=$paras["descr"]?>
					</td>
				</tr>
			<?
		}
	}
	
	/** A shortcut-function to get data from a database through a ID-column. */
	function GOne($in_id, $in_db, $in_fields){
		global $knj_web;
		
		if ($knj_web["dbconn"]){
			return $knj_web["dbconn"]->query("SELECT " . $in_fields . " FROM " . $in_db . " WHERE " . $knj_web["col_id_name"] . " = '" . $knj_web["dbconn"]->sql($in_id) . "' LIMIT 1")->fetch();
		}else{
			$f_gone = mysql_query("SELECT " . $in_fields . " FROM " . $in_db . " WHERE " . $knj_web["col_id_name"] . " = '" . mysql_escape_string($in_id) . "' LIMIT 1") or die("MySQL-error: " . mysql_error());
			$d_gone = mysql_fetch_array($f_gone);
		}
		
		return $d_gone;
	}
	
	/** A shortcut-function to get data from a database through a ID-column. */
	function GID($in_id, $in_db){
		global $knj_web;
		
		if ($knj_web["dbconn"]){
			return $knj_web["dbconn"]->selectsingle($in_db, array($knj_web["col_id_name"] => $in_id));
		}else{
			$sql = "SELECT * FROM " . $in_db . " WHERE " . $knj_web["col_id_name"] . " = '" . sql($in_id) . "' LIMIT 1";
			$f_gid = mysql_query($sql) or die("MySQL-error: " . mysql_error() . "\nSQL: " . $sql);
			$d_gid = mysql_fetch_array($f_gid);
		}
		
		return $d_gid;
	}
	
	/** This class handels code for the users browser. */
	class knj_browser{
		/** Returns the browser. */
		static function getBrowser(){
			global $knj_web;
			
			if (strpos($_SERVER["HTTP_USER_AGENT"], "MSIE") !== false){
				return "ie";
			}elseif(strpos($_SERVER["HTTP_USER_AGENT"], "Chrome") !== false){
				return "chrome";
			}elseif(strpos($_SERVER["HTTP_USER_AGENT"], "Safari") !== false){
				return "safari";
			}elseif(strpos($_SERVER["HTTP_USER_AGENT"], "Konqueror") !== false){
				return "konqueror";
			}elseif(strpos($_SERVER["HTTP_USER_AGENT"], "Opera") !== false){
				return "opera";
			}else{
				if ($knj_web["return_mozilla"] == true){
					return "mozilla";
				}else{
					return "firefox";
				}
			}
		}
		
		/** Returns the major version of the browser. */
		static function getVersion(){
			if (knj_browser::getBrowser() == "ie"){
				if (strpos($_SERVER["HTTP_USER_AGENT"], "MSIE 8") !== false){
					return 8;
				}elseif(strpos($_SERVER["HTTP_USER_AGENT"], "7.0") !== false){
					return 7;
				}else{
					return 6;
				}
			}elseif(knj_browser::getBrowser() == "safari"){
				if (strpos($_SERVER["HTTP_USER_AGENT"], "Version/4.0") !== false){
					return 4;
				}
			}elseif(knj_browser::getBrowser() == "konqueror"){
				if (strpos($_SERVER["HTTP_USER_AGENT"], "Konqueror/3") !== false){
					return 3;
				}elseif (strpos($_SERVER["HTTP_USER_AGENT"], "Konqueror/4") !== false){
					return 4;
				}
			}elseif(knj_browser::getBrowser() == "mozilla" or knj_browser::getBrowser() == "firefox"){
				if (strpos($_SERVER["HTTP_USER_AGENT"], "Firefox/3") !== false){
					return 3;
				}elseif(strpos($_SERVER["HTTP_USER_AGENT"], "Firefox/2") !== false){
					return 2;
				}
			}elseif(knj_browser::getBrowser() == "chrome"){
				if (strpos($_SERVER["HTTP_USER_AGENT"], "Chrome/4") !== false){
					return 4;
				}
			}
			
			return 0;
		}
		
		/** Returns the registered operating-system - "windows", "linux", "mac" or "bot". */
		static function getOS(){
			require_once("knjphpframework/functions_array.php");
			$bots = array(
				"yahoo! slurp",
				"msnbot",
				"googlebot",
				"adsbot",
				"ask jeeves",
				"conpilot crawler",
				"yandex",
				"exabot",
				"hostharvest",
				"dotbot",
				"ia_archiver",
				"httpclient",
				"spider.html",
				"comodo-certificates-spider",
				"sbider",
				"speedy spider",
				"spbot",
				"aihitbot",
				"scoutjet",
				"com_bot",
				"aihitbot",
				"robot.html",
				"robot.htm",
				"catchbot",
				"baiduspider",
				"setoozbot",
				"sslbot",
				"browsershots",
				"perl",
				"wget"
			);
			
			$ua = strtolower($_SERVER["HTTP_USER_AGENT"]);
			
			if (strpos($ua, "windows") !== false){
				return "windows";
			}elseif(strpos($ua, "linux") !== false){
				return "linux";
			}elseif(strpos($ua, "mac") !== false){
				return "mac";
			}elseif(strpos($ua, "playstation") !== false){
				return "playstation";
			}elseif(strpos($ua, "nintendo wii") !== false){
				return "wii";
			}elseif(knjarray::stringsearch($ua, $bots)){
				return "bot";
			}elseif(strpos($ua, "sunos") !== false){
				return "sun";
			}elseif(trim($ua) == ""){
				return false;
			}else{
				return "unknown";
			}
		}
	}
?>
