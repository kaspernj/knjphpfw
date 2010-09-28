<?
	/** Loads a PHP-extension. */
	function knj_dl($extension){
		if (is_array($extension)){
			foreach($extension AS $ext){
				knj_dl($ext);
			}
			
			return true;
		}
		
		require_once "knj/functions_knj_os.php";
		$os = knj_os::getOS();
		
		if (extension_loaded($extension)){
			return true;
		}
		
		if ($extension == "sqlite" && !extension_loaded("php_pdo")){
			knj_dl("pdo");
		}
		
		if ($os["os"] == "windows"){
			$pre = "php_";
			$app = ".dll";
			
			if ($extension == "glade" || $extension == "libglade"){
				$extension = "gtk_libglade2";
			}elseif($extension == "gd"){
				$extension = "gd2";
			}
		}else{
			$app = ".so";
			
			if ($extension == "gtk2"){
				$pre = "php_";
			}
		}
		
		if ($extension == "gtk2" && extension_loaded("php-gtk")){
			return true;
		}
		
		if (!ini_get("enable_dl")){
			throw new Exception("The option \"enable_dl\" is not enabled in \"php.ini\" - cant load extension.");
		}
		
		if (!dl($pre . $extension . $app)){
			throw new Exception("Could not load the extension: " . $extension . ".");
		}
		
		return true;
	}
	
	/** Checks if an extension is loaded or not. */
	function knj_checkLoaded($ext){
		if ($ext == "gtk2"){
			if (extension_loaded("php-gtk")){
				return true;
			}else{
				return false;
			}
		}
		
		if (extension_loaded($ext)){
			return true;
		}
		
		return false;
	}
?>