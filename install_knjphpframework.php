#!/usr/bin/php5
<?
	if (trim(system("whoami")) != "root"){
		die("You have to run this script as root.\n");
	}
	
	$dirname = dirname(__FILE__);
	$dirunix = $dirname;
	
	$dirunix = str_replace("\\", "\\\\", $dirunix);
	$dirunix = str_replace(" ", "\\ ", $dirunix);
	
	$testdirs = array(
		"/usr/share/php",
		"/usr/share/php5",
		"/usr/share/php4"
	);
	foreach($testdirs AS $dir){
		if (!file_exists($dir)){
			mkdir($dir);
		}
		
		chdir($dir);
		echo("Found PHP extensions-dir: " . $dir . ".\n");
		
		if (is_link($dir . "/knjphpframework")){
			echo("Its already there - unlinking.\n");
			unlink($dir . "/knjphpframework");
		}
		
		echo "Making symlink.\n";
		system("ln -s " . $dirunix . " knjphpframework");
	}
	
	echo "\n\nDone.\n";
?>