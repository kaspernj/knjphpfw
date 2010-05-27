#!/usr/bin/php
<?php
	require_once("knjphpframework/functions_knj_filesystem.php");
	
	function killSVN($dir){
		$fp = opendir($dir);
		while(($file = readdir($fp)) !== false){
			if ($file != "." && $file != ".."){
				if ($file == ".svn" || $file == "CVS"){
					fs_cleanDir($dir . "/" . $file, true);
					echo "Removed \"" . $dir . "/" . $file.  "\".\n";
				}elseif(is_dir($dir . "/" . $file)){
					killSVN($dir . "/" . $file);
				}
			}
		}
	}
	
	killSVN($_SERVER["argv"][1]);
?>