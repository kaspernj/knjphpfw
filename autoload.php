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
				"knj_os" => "os",
				"objects" => "objects",
				"knjdb" => "db",
				"knjobjects" => "objects",
				"knj_login" => "web_login",
				"knj_strings" => "strings"
			);
			$this->classes = array();
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