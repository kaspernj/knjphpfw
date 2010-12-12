<?

class knj_autoload{
	function __construct(){
		$this->exts = array(
			"gtk" => "gtk2",
			"mysql" => "mysql",
			"sqlite3" => "sqlite"
		);
		$this->knj = array(
			"web" => "web",
			"knj_browser" => "web",
			"knj_date" => "date",
			"knj_ftp" => "ftp",
			"knj_os" => "os",
			"objects" => "objects",
			"knjarray" => "functions_array",
			"knjdb" => "db",
			"knjobjects" => "objects",
			"knj_csv" => "csv",
			"knj_login" => "web_login",
			"knj_mail" => "mail",
			"knj_strings" => "strings",
			"knj_ssh2" => "class_knj_ssh2",
			"knj_fs" => "functions_knj_filesystem",
			"knj_date" => "date",
			"knj_translations" => "translations"
		);
		$this->classes = array(
			"net_ftp" => "Net/FTP.php",
			"pclzip" => "libphp-pclzip/pclzip.lib.php"
		);
	}
	
	function load($classname){
		$class = strtolower($classname);
		
		if (array_key_exists($class, $this->classes)){
			require_once($this->classes[$class]);
		}
		
		if (array_key_exists($class, $this->exts)){
			require_once("knj/exts.php");
			knj_dl($this->ext[$classname]);
		}
		
		if (array_key_exists($class, $this->knj)){
			require_once("knj/" . $this->knj[$class] . ".php");
		}
	}
	
	function add($class, $file = null){
		if (is_array($class)){
			foreach($class AS $key => $value){
				$this->add($key, $value);
			}
		}else{
			$this->classes[strtolower($class)] = $file;
		}
	}
}
