<?
	/** Returns a complete line from a opened file. */
	function knj_freadline($fp, $mode = "plain", $end = "\n"){
		global $temp;
		$pos = strpos($temp, $end);

		if ($pos !== false){
			$return = substr($temp, 0, $pos);
			$temp = substr($temp, $pos + strlen($end));
			return $return;
		}else{
			if ($mode == "plain"){
				if (feof($fp)){
					$return = $temp;
					$temp = "";
					return $return;
				}else{
					$temp = $temp . fread($fp, 2048);
				}
			}elseif($mode == "gz"){
				if (gzeof($fp)){
					$return = $temp;
					$temp = "";
					return $return;
				}

				$temp = $temp . gzread($fp, 2048);
			}

			return knj_freadline($fp, $mode, $end);
		}
	}

	/** Used to check if the end of the file has been reach when using knj_freadline(). */
	function knj_freadline_eof($fp, $mode = "plain"){
		global $temp;

		if ($mode == "plain"){
			if (!$temp && feof($fp)){
				return true;
			}else{
				return false;
			}
		}elseif($mode == "gz"){
			if (!$temp && gzeof($fp)){
				return true;
			}else{
				return false;
			}
		}
	}

	function knj_fwrite($fp, $content, $mode){
		if ($mode == "plain"){
			return fwrite($fp, $content);
		}elseif($mode == "gz"){
			return gzwrite($fp, $content);
		}

		throw new exception("Unknown mode: " . $mode);
	}

