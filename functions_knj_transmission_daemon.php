<?
	/** Parses a list of downloads. */
	function transdaemon_getList($cont){
		require_once("knjphpframework/functions_knj_strings.php");
		
		if (!preg_match_all("/(.+) \(([0-9.]+) (\S+)\) - ([0-9.]+)% .*/", $cont, $matches)){
			throw new Exception("Could not parse list.\n\n" . "Content:\n" . $cont);
		}
		
		$return = array();
		foreach($matches[1] AS $key => $name){
			$hash = "[unknown]";
			
			if (preg_match("/\n(\S+) " . knj_string_regex($name) . "\n/", $cont, $match)){
				$hash = $match[1];
			}
			
			$size = $matches[2][$key];
			$size_mult = $matches[3][$key];
			
			if ($size_mult == "GiB"){
				$size_bytes = (($size * 1024) * 1024) * 1024;
			}elseif($size_mult == "MiB"){
				$size_bytes = ($size * 1024) * 1024;
			}else{
				$size_bytes = "[unknown]";
			}
			
			$return[] = array(
				"name" => $name,
				"hash" => $hash,
				"percent" => $matches[4][$key],
				"size" => $size,
				"size_mult" => $size_mult,
				"size_bytes" => $size_bytes
			);
		}
		
		return $return;
	}
?>