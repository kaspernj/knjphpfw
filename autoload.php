<?
	class knj_autoload{
		function __construct(){
			$this->exts = array(
				"gtk" => "gtk2",
				"mysql" => "mysql",
				"sqlite3" => "sqlite"
			);
			$this->knj = array(
				"knj_web" => "web",
				"knj_os" => "os",
				"objects" => "objects",
				"knjdb" => "db",
				"knjobjects" => "objects"
			);
		}
		
		function load($classname){
			$class = strtolower($classname);
			
			if ($this->ext[$class]){
				require_once("knj/exts.php");
				knj_dl($this->ext[$classname]);
			}
			
			if ($this->knj[$class]){
				require_once("knj/" . $this->knj[$class] . ".php");
			}
		}
	}
?>