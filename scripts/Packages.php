#!/usr/bin/php
<?php
	/** NOTE: This script is used to generate the "Packages" and "Packages.gz" files that Debian-based repositories uses. It can also be used to make OPKG-repositories. */
	
	function ipkg_parse($file){
		require_once("knjphpframework/functions_knj_os.php");
		require_once("knjphpframework/functions_knj_filesystem.php");
		require_once("knjphpframework/functions_knj_strings.php");
		
		$fileinfo = fileinfo($file);
		if (strpos($fileinfo, "gzip compressed data") !== false){
			$format = "tar.gz";
		}else{
			$format = "debian";
		}
		
		$old_dir = getcwd();
		$tmpdir = "./generate_packages_list_" . microtime(true);
		while(true){
			if (file_exists($tmpdir)){
				$tmpdir .= "1";
			}else{
				break;
			}
		}
		
		if (!mkdir($tmpdir)){
			throw new Exception("Could not create temp-dir: " . $tmpdir);
		}
		
		$finfo = pathinfo($file);
		
		$cmd = "cd " . knj_string_unix_safe($tmpdir) . ";";
		if ($format == "tar.gz"){
			$cmd .= "tar -zxvf ../" . knj_string_unix_safe($fino["basename"]);
		}else{
			$cmd .= "ar -x ../" . knj_string_unix_safe($finfo["basename"]) . " control.tar.gz";
		}
		
		$res = knj_os::shellCMD($cmd);
		if (strlen($res["error"]) > 0){
			throw new Exception(trim($res["error"]));
		}
		
		$res = knj_os::shellCMD("cd " . knj_string_unix_safe($tmpdir) . "; tar -zxvf control.tar.gz");
		if (strlen($res["error"]) > 0){
			throw new Exception(trim($res["error"]));
		}
		
		$res = knj_os::shellCMD("cd " . knj_string_unix_safe($tmpdir) . "; cat control");
		if (strlen($res["error"]) > 0){
			throw new Exception(trim($res["error"]));
		}
		
		$control = substr($res["result"], 0, -1);
		$return = array();
		foreach(explode("\n", $control) AS $line){
			if (preg_match("/^(\S+):\s+([\s\S]+)$/", $line, $match)){
				if (strlen(trim($match[2])) > 0){
					$return["control"][$match[1]] = $match[2];
				}
			}
		}
		
		knj_os::shellCMD("cd " . knj_string_unix_safe($old_dir));
		fs_cleanDir($tmpdir, true);
		if (file_exists($tmpdir)){
			if (!rmdir($tmpdir)){
				throw new Exception("Could not remove tmp-dir.");
			}
		}
		
		return $return;
	}
	
	function md5sum($file){
		require_once("knjphpframework/functions_knj_os.php");
		$res = knj_os::shellCMD("md5sum " . $file);
		if (strlen($res["error"]) > 0){
			throw new Exception($res["error"]);
		}
		
		$result = explode(" ", $res["result"]);
		return $result[0];
	}
	
	function fileinfo($file){
		require_once("knjphpframework/functions_knj_os.php");
		require_once("knjphpframework/functions_knj_strings.php");
		
		$res = knj_os::shellCMD("file " . knj_string_unix_safe($file));
		if (strlen($res["error"]) > 0){
			throw new Exception(trim($res["error"]));
		}
		
		$res = substr($res["result"], strlen($file) + 2, -1);
		return $res;
	}
	
	function writeout($line){
		global $fp1, $fp2;
		gzwrite($fp1, $line);
		fwrite($fp2, $line);
	}
	
	$fp1 = gzopen("Packages.gz", "w");
	$fp2 = fopen("Packages", "w");
	$od = opendir("./") or die("Could not dir.\n");
	
	$first = true;
	while(($file = readdir($od)) !== false){
		if ($file != "." && $file != ".."){
			$ext = substr($file, -4, 4);
			if ($ext == ".ipk" || $ext == ".deb"){
				echo "Reading \"" . $file . "\".\n";
				
				$result = ipkg_parse($file);
				$md5sum = md5sum($file);
				
				if ($first == true){
					$first = false;
				}else{
					writeout("\n");
				}
				
				foreach($result["control"] AS $key => $value){
					$keyl = strtolower($key);
					
					if (strlen($key) > 0 && strlen($value) > 0 && $keyl != "filename" && $keyl != "size" && $keyl != "md5sum"){
						writeout($key . ": " . $value . "\n");
					}
				}
				
				writeout("Filename: " . $file . "\n");
				writeout("Size: " . filesize($file) . "\n");
				writeout("MD5Sum: " . $md5sum . "\n");
			}
		}
	}
	gzclose($fp1);
	gzclose($fp2);
?>
