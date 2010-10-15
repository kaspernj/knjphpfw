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
				"knj_date" => "functions_knj_date",
				"knj_browser" => "web",
				"knj_os" => "os",
				"objects" => "objects",
				"knjarray" => "functions_array",
				"knjdb" => "db",
				"knjobjects" => "objects",
				"knj_csv" => "csv",
				"knj_fs" => "functions_knj_filesystem",
				"knj_login" => "web_login",
				"knj_strings" => "strings",
				"knj_ssh2" => "class_knj_ssh2"
			);
			$this->classes = array(
				"net_ftp" => "Net/FTP.php",
				"pclzip" => "libphp-pclzip/pclzip.lib.php"
			);
		}
		
		function load($classname){
			$class = strtolower($classname);
			
			if ($this->classes[$class]){
				require_once($this->classes[$class]);
			}
			
			if ($this->ext[$class]){
				require_once("knj/exts.php");
				knj_dl($this->ext[$classname]);
			}
			
			if ($this->knj[$class]){
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
?>