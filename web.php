<?php

/** This file contains functions that may come in handy when building web-applications. */
global $knj_web;
$knj_web = array(
	"col_id_name" => "id"
);

class web
{
	function inputs($args)
	{
		$html = "";
		foreach ($args as $arg) {
			$html .= web::input($arg);
		}
		
		return $html;
	}
	
	function input($args)
	{
		ob_start();
		form_drawInput($args);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
	function drawInput($args)
	{
		return form_drawInput($args);
	}
	
	function drawOpts($opts, $selected = null)
	{
		return select_drawOpts($opts, $selected);
	}
	
	function checkVal($val, $opt1 = "1", $opt2 = "0")
	{
		if ($val == "on") {
			return $opt1;
		} else {
			return $opt2;
		}
	}
	
	function alert($msg)
	{
		return alert($msg);
	}
	
	function redirect($url)
	{
		return redirect($url);
	}
	
	function back()
	{
		return jsback();
	}
	
	function rewrite_replaces()
	{
		return array(
			"&" => "",
			"æ" => "ae",
			"ø" => "oe",
			"å" => "aa",
			"Æ" => "AE",
			"Å" => "AA",
			"Ø" => "OE",
			"é" => "e",
			"\"" => "",
			"(" => "",
			")" => "",
			"*" => "",
			":" => "-",
			"+" => "_",
			"." => "-",
			"," => "-",
			"®" => "",
			"▒" => "",
			"┬" => "",
			"?" => "-"
		);
	}
	
	function rewritesafe($string)
	{
		$string = strtr($string, Web::rewrite_replaces());
		$string = preg_replace("/\s+/", "_", $string);
		
		return $string;
	}
	
	function rewritesafe_removeothers($str)
	{
		$str = Web::rewritesafe($str);
		preg_match_all("/[\/A-z_-\d]+/", $str, $matches);
		$newstr = implode("_", $matches[0]);
		return $newstr;
	}
	
	function rewriteback($string)
	{
		return strtr($string, array(
			"_" => " "
		));
	}
	
	static function current_url()
	{
		if ($_SERVER["HTTPS"] == "on") {
			$url = "https://";
		} else {
			$url = "http://";
		}
		
		$url .= $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		
		return $url;
	}
	
	static function sxml_esc($str)
	{
		return strtr($str, array(
			"&" => "&amp;"
		));
	}
}

function secCheckInclude($file)
{
	if (strpos($file, "..") !== false) {
		throw new exception("Possible hack.");
	}
}

/** Function to redirect. You can use this instead of using the header()-function. */
function redirect($url, $exit = true)
{
	global $knj_web;
	
	if (!headers_sent() and !$knj_web["alert_sent"]) {
		header("Location: " . $url);
	} else {
		jsredirect($url);
	}
	
	if ($exit == true) {
		exit;
	}
}

/** Function to show a message through the JS-alert-function. */
function alert($msg)
{
	global $knj_web;
	
	$msg = knj_strings::jsparse($msg);
	
	echo '<script type="text/javascript">alert("' .$msg .'");</script>';
	$knj_web["alert_sent"] = true;
}

function jsback()
{
	?>
		<script type="text/javascript">
			history.back(-1);
		</script>
	<?
	exit();
}

function jsredirect($url)
{
	?><script type="text/javascript">location.href="<?=$url?>";</script><?
	exit();
}

function select_drawOpts($opts, $selected = null)
{
	if (is_object($selected)) {
		$selected = $selected->id();
	} elseif (is_array($selected) && is_object($selected[0])) {
		$selected = call_user_func(array($selected[0], $selected[1]), $selected[2]);
	}
	
	$html = "";
	foreach ($opts as $key => $value) {
		$html .= "<option";
		
		$is_selected = false;
		if (is_array($selected) and in_array($key, $selected)) {
			$is_selected = true;
		} elseif (is_array($selected) and ($selected["type"] == "arr_rows" || $selected["type"] == "arr_values")) {
			if (is_array($selected["values"])) {
				foreach ($selected["values"] as $sel_key => $sel_val) {
					if (is_a($sel_val, "knjdb_row")) {
						if ($key == $sel_val->id()) {
							$is_selected = true;
						}
					} else {
						if ($selected["type"] == "arr_values") {
							if ($key == $sel_val) {
								$is_selected = true;
							}
						} else {
							if ($key == $sel_key) {
								$is_selected = true;
							}
						}
					}
				}
			}
		} elseif ($key == $selected) {
			if (!is_numeric($key) || intval($key) != 0) {
				$is_selected = true;
			}
		}
		
		if ($is_selected) {
			$html .= " selected=\"selected\"";
		}
		
		$html .= " value=\"" . htmlspecialchars($key) . "\">" . htmlspecialchars($value) . "</option>\n";
	}
	
	return $html;
}

function form_drawInput($args)
{
	if (is_array($args["value"]) && is_callable($args["value"])) {
		$value = call_user_func($args["value"]);
	} elseif (is_array($args["value"]) && ($args["value"]["type"] == "arr_rows" || $args["value"]["type"] == "arr_values")) {
		//do nothing.
	} elseif (is_array($args["value"]) && is_object($args["value"][0])) {
		$value = $args["value"][0]->$args["value"][1]($args["value"][2]);
	} else {
		if ($args["value"] === null && array_key_exists("default", $args)) {
			$value = $args["default"];
		} else {
			$value = $args["value"];
		}
	}
	
	if (is_array($value)) {
		$value = null;
	}
	
	if (is_null($value) and array_key_exists("default", $args)) {
		$value = $args["default"];
	}
	
	if ($value and $args["value_callback"]) {
		if (array_key_exists(1, $args["value_callback"])) {
			$value = call_user_func($args["value_callback"][0], $value, $args["value_callback"][1]);
		} else {
			$value = call_user_func($args["value_callback"][0], $value);
		}
	}
	
	if (!$args["type"]) {
		$f3 = substr($args["name"], 0, 3);
		if ($f3 == "che") {
			$args["type"] = "checkbox";
		} elseif ($f3 == "tex") {
			$args["type"] = "text";
		} elseif ($f3 == "sel" || array_key_exists("opts", $args)) {
			$args["type"] = "select";
		} elseif ($f3 == "fil") {
			$args["type"] = "file";
		} elseif ($f3 == "rad") {
			$args["type"] = "radio";
		}
	}
	
	if (!$args["id"]) {
		$id = $args["name"];
	} else {
		$id = $args["id"];
	}
	
	if (!$args["type"]) {
		$args["type"] = "text";
	}
	
	if ($args["type"] == "password" and !$args["class"]) {
		$args["class"] = "input_text";
	}
	
	if (!$args["class"]) {
		$args["class"] = "input_" . $args["type"];
	}
	
	if ($args["colspan"]) {
		$colspan_cont = $args["colspan"] - 1;
	}
	
	if (!array_key_exists("tr", $args) || $args["tr"]) {
		?><tr><?
	}
	
	if ($args["title"]) {
		$title_html = htmlspecialchars($args["title"]);
	} elseif ($args["title_html"]) {
		$title_html = $args["title_html"];
	}
	
	if ($args["div"]) {
    $title_html = "<div>" . $title_html . "</div>";
	}
	
	$css = array();
	$td_html = "<td class=\"tdc\"";
	if ($args["td_width"]) {
    $css["width"] = $args["td_width"];
	}
	if ($args["align"]) {
    $css["text-align"] = $args["align"];
  }
  
  if (!empty($css)) {
    $td_html .= " style=\"";
    foreach ($css as $key => $val) {
      $td_html .= $key . ": " . $val . ";";
    }
    $td_html .= "\"";
  }
  
	if ($colspan_cont > 1) {
		$td_html .= " colspan=\"" . $colspan_cont . "\"";
	}
	$td_html .= ">";
	
	if ($args["div"]) {
    $td_end_html = "</div></td>";
    $td_html .= "<div>";
  } else {
    $td_end_html = "</td>";
	}
	
	$js_tags = "";
	$js_tags_arr = array("onkeyup", "onkeydown", "onchange");
	foreach ($js_tags_arr AS $js_tag) {
		if ($args[$js_tag]) {
			$js_tags .= " " . $js_tag . "=\"" . $args[$js_tag] . "\"";
		}
	}
	
	if (array_key_exists("autocomplete", $args) and !$args["autocomplete"]) {
		$js_tags .= " autocomplete=\"off\"";
	}
	
	if ($args["type"] == "numeric") {
		$value = number_out($value, $args["decis"]);
	}
	
	if ($args["classes"]) {
		$classes = $args["classes"];
	} else {
		$classes = array();
	}
	
	$classes[] = $args["class"];
	$args["class"] = implode(" ", $classes);
	
	if ($args["type"] == "spacer") {
		?><td colspan="2">&nbsp;</td><?
	} elseif ($args["type"] == "checkbox") {
		?>
			<td colspan="2" class="tdcheck">
				<input<?if ($args["disabled"]) {?> disabled<?}?> type="<?=$args["type"]?>" name="<?=$args["name"]?>" id="<?=$id?>"<?if ($value) {?> checked="checked"<?}?><?=$js_tags?> />
				<label for="<?=$id?>"><?=$title_html?></label>
			</td>
		<?
	} elseif ($args["type"] == "select") {
		$etags = "";
		if ($args["multiple"]) {
			$etags .= " multiple=\"multiple\"";
		}
		
		if ($args["height"]) {
			$etags .= " height=\"" . htmlspecialchars($args["height"]) . "\"";
		}
		
		if (is_null($value) and is_array($args["value"])) {
      $value = $args["value"];
    }
		
		?>
			<td class="tdt">
				<?=$title_html?>
			</td>
			<?=$td_html?>
				<select<?=$etags?><?if ($args["size"]) {?> size="<?=htmlspecialchars($args["size"])?>"<?}?> name="<?=htmlspecialchars($args["name"])?>" id="<?=htmlspecialchars($id)?>" class="<?=$args["class"]?>"<?=$js_tags?>>
				<?=select_drawOpts($args["opts"], $value)?>
				</select>
				<?if ($args["moveable"]) {?>
					<div style="padding-top: 3px;">
						<input type="button" value="<?=_("Up")?>" onclick="select_moveup($('#<?=$id?>'));" />
						<input type="button" value="<?=_("Down")?>" onclick="select_movedown($('#<?=$id?>'));" />
					</div>
				<?}?>
			<?=$td_end_html?>
		<?
	} elseif ($args["type"] == "imageupload") {
		if ($args["filetype"]) {
			$ftype = $args["filetype"];
		} else {
			$ftype = "jpg";
		}
		
		if (!$value) {
			$fn = null;
		} else {
			$fn = $args["path"] . "/" . $value . "." . $ftype;
		}
		
		if (!$fn || !file_exists($fn)) {
			$found = false;
			$fn_nopic = "images/nopicture.jpg";
			$fn = null;
			
			if (file_exists($fn_nopic)) {
				$fn = $fn_nopic;
			}
		} else {
			$found = true;
		}
		
		if ($args["dellink"]) {
			$args["dellink"] = str_replace("%value%", $value, $args["dellink"]);
		}
		
		?>
			<td class="tdt">
				<?=$title_html?>
			</td>
			<?=$td_html?>
				<table class="designtable">
					<tr>
						<td style="width: 100%;">
							<input type="file" name="<?=htmlspecialchars($args["name"])?>" id="<?=htmlspecialchars($id)?>" class="<?=htmlspecialchars($args["class"])?>" />
						</td>
						<td>
							<?if ($fn) {?>
								<img src="image.php?picture=<?=urlencode($fn)?>&amp;smartsize=80&amp;edgesize=20&amp;equaldim=true" alt="Preview" />
							<?}?>
							<?if ($found and $args["dellink"]) {?>
								<div style="text-align: center;">
									<?if (function_exists("gtext")) {?>
										(<a href="javascript: if (confirm('<?=gtext("Do you want to delete the picture?")?>')) {location.href='<?=$args["dellink"]?>';}"><?=gtext("delete")?></a>)
									<?} elseif (function_exists("_")) {?>
										(<a href="javascript: if (confirm('<?=_("Do you want to delete the picture?")?>')) {location.href='<?=$args["dellink"]?>';}"><?=_("delete")?></a>)
									<?} else {?>
										(<a href="javascript: if (confirm('Do you want to delete the picture?')) {location.href='<?=$args["dellink"]?>';}"><?=_("delete")?></a>)
									<?}?>
								</div>
							<?}?>
						</td>
					</tr>
				</table>
			<?=$td_end_html?>
		<?
	} elseif ($args["type"] == "file") {
		?>
			<td class="tdt">
				<?=$title_html?>
			</td>
			<?=$td_html?>
				<input type="file" class="input_<?=$args["type"]?>" name="<?=htmlspecialchars($args["name"])?>" id="<?=htmlspecialchars($id)?>"<?=$js_tags?> />
			<?=$td_end_html?>
		<?
	} elseif ($args["type"] == "textarea") {
		?>
			<td class="tdt">
				<?=$title_html?>
			</td>
			<?=$td_html?>
				<textarea name="<?=htmlspecialchars($args["name"])?>" id="<?=htmlspecialchars($id)?>" class="<?=htmlspecialchars($args["class"])?>"<?if ($args["height"]) {?> style="height: <?=$args["height"]?>;"<?}?><?=$js_tags?>><?=htmlspecialchars($value, NULL, 'UTF-8')?></textarea>
			<?=$td_end_html?>
		<?
	} elseif ($args["type"] == "fckeditor") {
		?>
			<td class="tdt">
				<?=$title_html?>
			</td>
			<?=$td_html?>
				<?
					$fck = new fckeditor($args["name"]);
					
					if ($args["height"]) {
						$fck->Height = $args["height"];
					} else {
						$fck->Height = 300;
					}
					
					$fck->Value = $value;
					$fck->Create();
				?>
			<?=$td_end_html?>
		<?
	} elseif ($args["type"] == "radio") {
		$id = $id . "_" . $value;
		?>
			<td class="tdt" colspan="2">
				<input type="radio" id="<?=htmlspecialchars($id)?>" name="<?=htmlspecialchars($args["name"])?>" value="<?=htmlspecialchars($args["value"])?>"<?if ($args["checked"]) {?> checked="checked"<?}?><?=$js_tags?> />
				<label for="<?=htmlspecialchars($id)?>">
					<?=$title_html?>
				</label>
			</td>
		<?
	} elseif ($args["type"] == "info") {
		?>
			<td class="tdt">
				<?=$title_html?>
			</td>
			<?=$td_html?>
				<?=$value?>
			<?=$td_end_html?>
		<?
	} elseif ($args["type"] == "plain") {
		?>
			<td class="tdt">
				<?=$title_html?>
			</td>
			<?=$td_html?>
				<?=htmlspecialchars($value)?>
			<?=$td_end_html?>
		<?
	} elseif ($args["type"] == "headline") {
		?>
			<td class="tdheadline" colspan="2">
				<?=$title_html?>
			</td>
		<?
	} else {
		?>
			<td class="tdt">
				<?=$title_html?>
			</td>
			<?=$td_html?>
				<input type="<?=htmlspecialchars($args["type"])?>"<?if ($args["disabled"]) {?> disabled<?}?><?if ($args["maxlength"]) {?> maxlength="<?=$args["maxlength"]?>"<?}?> class="<?=$args["class"]?>" id="<?=htmlspecialchars($id)?>" name="<?=htmlspecialchars($args["name"])?>" value="<?=htmlspecialchars($value)?>"<?=$js_tags?> />
			<?=$td_end_html?>
		<?
	}
	
	if (!array_key_exists("tr", $args) || $args["tr"]) {
		?></tr><?
	}
	
	if ($args["descr"]) {
    $descr = $args["descr"];
    
    if ($args["div"]) {
      $descr = "<div class=\"tdd\">" . $descr . "</div>";
    }
    
		?>
			<tr>
				<td colspan="2"<?if (!$args["div"]) {?> class="tdd"<?}?>>
					<?=$descr?>
				</td>
			</tr>
		<?
	}
}

/** A shortcut-function to get data from a database through a ID-column. */
function GOne($in_id, $in_db, $in_fields)
{
	global $knj_web;
	
	if ($knj_web["dbconn"]) {
		return $knj_web["dbconn"]->query("SELECT " . $in_fields . " FROM " . $in_db . " WHERE " . $knj_web["col_id_name"] . " = '" . $knj_web["dbconn"]->sql($in_id) . "' LIMIT 1")->fetch();
	} else {
		$f_gone = mysql_query("SELECT " . $in_fields . " FROM " . $in_db . " WHERE " . $knj_web["col_id_name"] . " = '" . mysql_escape_string($in_id) . "' LIMIT 1") || die("MySQL-error: " . mysql_error());
		$d_gone = mysql_fetch_array($f_gone);
	}
	
	return $d_gone;
}

/** A shortcut-function to get data from a database through a ID-column. */
function GID($in_id, $in_db)
{
	global $knj_web;
	
	if ($knj_web["dbconn"]) {
		return $knj_web["dbconn"]->selectsingle($in_db, array($knj_web["col_id_name"] => (int) $in_id));
	} else {
		$sql = "SELECT * FROM " . sql($in_db) . " WHERE " . $knj_web["col_id_name"] . " = '" . sql($in_id) . "' LIMIT 1";
		$f_gid = mysql_query($sql) || die("MySQL-error: " . mysql_error() . "\nSQL: " . $sql);
		$d_gid = mysql_fetch_array($f_gid);
	}
	
	return $d_gid;
}

/** This class handels code for the users browser. */
class knj_browser{
	/** Returns the browser. */
	static function getBrowser()
	{
		global $knj_web;
		
		$uagent = "";
		if (array_key_exists("HTTP_USER_AGENT", $_SERVER)) {
			$uagent = $_SERVER["HTTP_USER_AGENT"];
		}
		
		if (strpos($uagent, "MSIE") !== false) {
			return "ie";
		} elseif (strpos($uagent, "Chrome") !== false) {
			return "chrome";
		} elseif (strpos($uagent, "Safari") !== false) {
			return "safari";
		} elseif (strpos($uagent, "Konqueror") !== false) {
			return "konqueror";
		} elseif (strpos($uagent, "Opera") !== false) {
			return "opera";
		} else {
			if ($knj_web and array_key_exists("return_mozilla", $knj_web) and $knj_web["return_mozilla"] == true) {
				return "mozilla";
			} else {
				return "firefox";
			}
		}
	}
	
	/** Returns the major version of the browser. */
	static function getVersion()
	{
		$uagent = "";
		if (array_key_exists("HTTP_USER_AGENT", $_SERVER)) {
			$uagent = $_SERVER["HTTP_USER_AGENT"];
		}
		
		if (knj_browser::getBrowser() == "ie") {
			if (preg_match("/MSIE (\d+)/", $uagent, $match)) {
				return $match[1];
			} elseif (strpos($uagent, "7.0") !== false) {
				return 7;
			} else {
				return 6;
			}
		} elseif (knj_browser::getBrowser() == "safari") {
			if (strpos($uagent, "Version/4.0") !== false) {
				return 4;
			}
		} elseif (knj_browser::getBrowser() == "konqueror") {
			if (strpos($uagent, "Konqueror/3") !== false) {
				return 3;
			} elseif (strpos($uagent, "Konqueror/4") !== false) {
				return 4;
			}
		} elseif (knj_browser::getBrowser() == "mozilla" || knj_browser::getBrowser() == "firefox") {
			if (strpos($uagent, "Firefox/3") !== false) {
				return 3;
			} elseif (strpos($uagent, "Firefox/2") !== false) {
				return 2;
			}
		} elseif (knj_browser::getBrowser() == "chrome") {
			if (strpos($uagent, "Chrome/4") !== false) {
				return 4;
			}
		}
		
		return 0;
	}
	
	/** Returns the registered operating-system - "windows", "linux", "mac" or "bot". */
	static function getOS()
	{
		require_once("knj/functions_array.php");
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
			"wget",
			"w3c_validator"
		);
		
		if (array_key_exists("HTTP_USER_AGENT", $_SERVER)) {
			$ua = strtolower($_SERVER["HTTP_USER_AGENT"]);
		} else {
			return "unknown";
		}
		
		if (strpos($ua, "windows") !== false) {
			return "windows";
		} elseif (strpos($ua, "linux") !== false) {
			return "linux";
		} elseif (strpos($ua, "mac") !== false) {
			return "mac";
		} elseif (strpos($ua, "playstation") !== false) {
			return "playstation";
		} elseif (strpos($ua, "nintendo wii") !== false) {
			return "wii";
		} elseif (knjarray::stringsearch($ua, $bots)) {
			return "bot";
		} elseif (strpos($ua, "sunos") !== false) {
			return "sun";
		} elseif (trim($ua) == "") {
			return false;
		} else {
			return "unknown";
		}
	}
	
	static function getOSVersion()
	{
		$version = "unknown";
		
		if (knj_browser::getOS() == "windows") {
			if (preg_match("/Windows\s+NT\s+([\d\.]+)/", $_SERVER["HTTP_USER_AGENT"], $match)) {
				if ($match[1] == 6.0) {
					$version = "vista";
				} elseif ($match[1] == 5.1) {
					$version = "xp";
				}
			} else {
				throw new exception("Could not match version.");
			}
		} elseif (knj_browser::getOS() == "linux") {
			if (preg_match("/Ubuntu\/([\d+\.]+)/", $_SERVER["HTTP_USER_AGENT"], $match)) {
				$version = "ubuntu_" . str_replace(".", "_", $match[1]);
			} else {
				throw new exception("Unknown user-agent for OS '" . knj_browser::getOS() . "': " . $_SERVER["HTTP_USER_AGENT"]);
			}
		} else {
			throw new exception("Unknown user-agent for OS '" . knj_browser::getOS() . "': " . $_SERVER["HTTP_USER_AGENT"]);
		}
		
		return array(
			"version" => $version
		);
	}
	
	static function locale($servervar = null)
	{
		if (!$servervar) {
			$servervar = $_SERVER;
		}
		
		$arr = array();
		$locale = explode(",", $servervar["HTTP_ACCEPT_LANGUAGE"]);
		if (preg_match("/^([a-z]{2})(_|-)[A-Z]{2}/i", $locale[0], $match)) {
			$arr["locale"] = $match[1];
		} elseif (preg_match("/^([a-z]{2})$/", $locale[0], $match)) {
			$arr["locale"] = $match[1];
		}
		
		return $arr;
	}
}

