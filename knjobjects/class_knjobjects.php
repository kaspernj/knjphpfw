<?

require_once "knj/knjdb/class_knjdb_row.php";

class knjobjects{
	private $objects;
	private $config;
	
	function __construct($args){
		$this->config = $args;
		$this->args = &$this->config;
		
		if (!$this->config["class_sep"]){
			$this->config["class_sep"] = "_";
		}
		
		if (!$this->config["col_id"]){
			$this->config["col_id"] = "id";
		}
		
		if (!array_key_exists("check_id", $this->config)){
			$this->config["check_id"] = true;
		}
		
		if (!array_key_exists("require", $this->config)){
			$this->config["require"] = true;
		}
	}
	
	static function array_data($objects, $args = array()){
		$return = array();
		
		foreach($objects AS $object){
			if (!$args or $args["ids"]){
				$return[] = $object->id();
			}elseif($args["data"]){
				$return[] = $object->g($args["data"]);
			}else{
				throw new exception("No data-identifier given.");
			}
		}
		
		return $return;
	}
	
	function single_by($obj, $args){
		$objs = $this->list_obs($obj, $args);
		if (!$objs){
			return false;
		}
		
		$data = each($objs);
		return $data[1];
	}
	
	function cleanMemory(){
		$usage = (memory_get_usage() / 1024) / 1024;
		if ($usage > 54){
			$this->unset_all();
		}
	}
	
	function clean_memory(){
		$this->cleanMemory();
	}
	
	function unset_all(){
		$this->objects = array();
	}
	
	function requirefile($obname){
		if ($this->config["require"]){
			$fn = $this->config["class_path"] . "/class" . $this->config["class_sep"] . $obname . ".php";
			if (!file_exists($fn)){
				throw new exception("File not found: " . $fn);
			}
			
			require_once($fn);
		}
	}
	
	function listObs($ob, $args = array()){
		if (!$this->objects[$ob]){
			$this->requirefile($ob);
		}
		
		$call_args = array(
			$args
		);
		
		if ($this->args["extra_args"]){
			$eargs = $this->args["extra_args"];
			
			if ($this->args["extra_args_self"]){
				$eargs["ob"] = $this;
			}
			
			$call_args[] = $eargs;
		}
		
		return call_user_func_array(array($ob, "getList"), $call_args);
	}
	
	function list_obs($ob, $args = array(), $list_args = array()){
		$return = $this->listObs($ob, $args);
		
		if ($list_args["key"]){
			$newreturn = array();
			foreach($return AS $object){
				$newreturn[$object->g($list_args["key"])] = $object;
			}
			
			return $newreturn;
		}
		
		return $return;
	}
	
	function list_reader($args){
		if (!$args){
			throw new exception("No arguments given.");
		}
		
		if (!$args["ob"]){
			throw new exception("No object name given.");
		}
		
		if (!$args["obargs"]){
			throw new exception("No object-arguments given.");
		}
		
		$this->list_reader_count++;
		$id = $this->list_reader_count;
		$this->list_reader[$id]["from"] = 0;
		$this->list_reader[$id]["add"] = 1000;
		$this->list_reader[$id]["args"] = $args;
		
		return $id;
	}
	
	function list_reader_read($id){
		$data = &$this->list_reader[$id];
		if (!$data){
			return false;
		}
		
		if ($data["obs"]){
			foreach($data["obs"] AS $ob){
				$this->unset_ob($ob);
			}
		}
		
		$args = $data["args"]["obargs"];
		$args["limit_from"] = $data["from"];
		$args["limit_to"] = $data["add"];
		$data["obs"] = $this->list_obs($data["args"]["ob"], $args);
		
		if (!$data["obs"]){
			unset($this->list_reader[$id]);
			return false;
		}
		
		$data["from"] += $data["add"];
		return $data["obs"];
	}
	
	function list_reader_count($id){
		$data = &$this->list_reader[$id];
		if (!$data){
			return false;
		}
		
		$args = $data["args"]["obargs"];
		unset($args["limit_from"], $args["limit_to"]);
		$args["count"] = true;
		$count = $this->list_obs($data["args"]["ob"], $args);
		
		return $count;
	}
	
	function listArr($ob, $args = null){
		$opts = array();
		if ($args["none"]){
			unset($args["none"]);
			
			if (function_exists("gtext")){
				$opts = array(0 => $this->gtext("None"));
			}elseif(function_exists("_")){
				$opts = array(0 => _("None"));
			}elseif(function_exists("gettext")){
				$opts = array(0 => gettext("None"));
			}
		}
		
		$list = $this->listObs($ob, $args);
		foreach($list AS $listitem){
			$opts[$listitem->get($this->config["col_id"])] = $listitem->getTitle();
		}
		
		return $opts;
	}
	
	function sqlargs_orderbylimit($args){
		$sql = "";
		
		if ($args["orderby"] and preg_match("/^[A-z]+$/", $args["orderby"])){
			$sql .= " ORDER BY " . $args["orderby"];
			
			if ($args["ordermode"] == "desc"){
				$sql .= " DESC";
			}
		}
		
		if ($args["limit"] and is_numeric($args["limit"])){
			$sql .= " LIMIT " . $args["limit"];
		}
		
		if ($args["limit_from"] and $args["limit_to"] and is_numeric($args["limit_from"]) and is_numeric($args["limit_to"])){
			$sql .= " LIMIT " . $args["limit_from"] . ", " . $args["limit_to"];
		}
		
		return $sql;
	}
	
	function gtext($string){
		if (function_exists("gtext")){
			return gtext($string);
		}elseif(function_exists("_")){
			return _($string);
		}elseif(function_exists("gettext")){
			return gettext($string);
		}else{
			return $string;
		}
	}
	
	function listOpts($ob, $getkey, $args = array()){
		$opts = array();
		
		if ($args["addnew"]){
			unset($args["addnew"]);
			$opts[0] = $this->gtext("Add new");
		}
		
		if ($args["none"]){
			unset($args["none"]);
			$opts[0] = $this->gtext("None");
		}
		
		if ($args["choose"]){
			unset($args["choose"]);
			$opts[0] = $this->gtext("Choose") . ":";
		}
		
		if ($args["all"]){
			unset($args["all"]);
			$opts[0] = $this->gtext("All");
		}
		
		if (!$args["col_id"]){
			$args["col_id"] = "id";
		}
		
		if (!$args["list_args"]){
			$args["list_args"] = array();
		}
		
		foreach($this->list_obs($ob, $args["list_args"]) AS $object){
			if (is_array($getkey) and $getkey["funccall"]){
				$value = call_user_func(array($object, $getkey["funccall"]));
			}else{
				$value = $object->get($getkey);
			}
			
			$opts[$object->get($args["col_id"])] = $value;
		}
		
		return $opts;
	}
	
	function list_opts($ob, $getkey, $args = null){
		return $this->listOpts($ob, $getkey, $args);
	}
	
	function list_bysql($ob, $sql, $args = array()){
		$ret = array();
		$q_obs = $this->config["db"]->query($sql);
		while($d_obs = $q_obs->fetch()){
			if ($args["col_id"]){
				$ret[] = $this->get($ob, $d_obs[$args["col_id"]], $d_obs);
			}else{
				$ret[] = $this->get($ob, $d_obs);
			}
		}
		
		return $ret;
	}
	
	function add($ob, $arr){
		if (!$this->objects[$ob]){
			$this->requirefile($ob);
		}
		
		$call_args = array(
			$arr
		);
		
		if ($this->args["extra_args"]){
			$eargs = $this->args["extra_args"];
			
			if ($this->args["extra_args_self"]){
				$eargs["ob"] = $this;
			}
			
			$call_args[] = $eargs;
		}
		
		return call_user_func_array(array($ob, "addNew"), $call_args);
	}
	
	function unsetOb($ob, $id = null){
		if (is_object($ob) and is_null($id)){
			$id = $ob->id();
			
			if ($this->objects[get_class($ob)][$id]){
				unset($this->objects[get_class($ob)][$id]);
			}
		}else{
			if ($this->objects[$ob][$id]){
				unset($this->objects[$ob][$id]);
			}
		}
	}
	
	function unset_ob($ob, $id = null){
		return $this->unsetOb($ob, $id);
	}
	
	function unset_obs($obs){
		foreach($obs AS $ob){
			$this->unset_ob($ob);
		}
	}
	
	function get($ob, $id, $data = null){
		if (is_array($id)){
			$data = $id;
			$rdata = &$data;
			$id = $data[$this->config["col_id"]];
		}else{
			$rdata = &$id;
		}
		
		if ($this->config["check_id"] and !is_numeric($id)){
			if (is_object($id)){
				throw new exception("Invalid ID: \"" . get_class($id) . "\", \"" . gettype($id) . "\".");
			}else{
				throw new exception("Invalid ID: \"" . $id . "\", \"" . gettype($id) . "\".");
			}
		}
		
		if (!is_string($ob)){
			throw new exception("Invalid object: " . gettype($ob));
		}
		
		if (is_object($id)){
			throw new exception("Invalid object: " . get_class($id));
		}
		
		if (!$this->objects[$ob][$id]){
			if (!$this->objects[$ob]){
				$this->requirefile($ob);
			}
			
			if ($this->args["get_array"]){
				$this->objects[$ob][$id] = new $ob(array(
					"data" => $rdata,
					"db" => $this->args["db"],
					"ob" => $this
				));
			}else{
				$this->objects[$ob][$id] = new $ob($id, $data);
			}
		}
		
		return $this->objects[$ob][$id];
	}
	
	function sqlhelper(&$list_args, $args){
		if ($args["table"]){
			$table = $this->config["db"]->conn->sep_table . $this->config["db"]->escape_table($args["table"]) . $this->config["db"]->conn->sep_table . ".";
		}else{
			$table = "";
		}
		
		$colsep = $this->config["db"]->conn->sep_col;
		
		if (!is_array($list_args)){
			throw new exception("The arguments given was not an array.");
		}
		
		foreach($list_args as $list_key => $list_val){
			$found = false;
			
			if (array_key_exists("cols_str", $args) and in_array($list_key, $args["cols_str"])){
				$sql_where .= " AND " . $table . $colsep . $this->config["db"]->escape_column($list_key) . $colsep . " = '" . $this->config["db"]->sql($list_val) . "'";
				$found = true;
			}elseif(array_key_exists("cols_dbrows", $args) and in_array($list_key . "_id", $args["cols_dbrows"])){
				$sql_where .= " AND " . $table . $colsep . $this->config["db"]->escape_column($list_key . "_id") . $colsep . " = '" . $this->config["db"]->sql($list_val->id()) . "'";
				$found = true;
			}
			
			if ($found){
				unset($list_args[$list_key]);
			}
		}
		
		return array(
			"sql_where" => $sql_where
		);
	}
}