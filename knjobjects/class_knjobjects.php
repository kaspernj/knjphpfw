<?

require_once "knj/knjdb/class_knjdb_row.php";

class knjobjects{
	public $objects;
	public $config;
	
	function __construct($args){
		$this->config = $args;
		$this->args = &$this->config;
		
		if (!array_key_exists("class_sep", $this->config)){
			$this->config["class_sep"] = "_";
		}
		
		if (!array_key_exists("col_id", $this->config)){
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
	
	/** DEPRECATED: Use get_by() instead. */
	function single_by($obj, $args){
		return $this->get_by($obj, $args);
	}
	
	function get_by($obj, $args){
		$args["limit"] = 1;
		
		$objs = $this->list_obs($obj, $args);
		if (!$objs){
			return false;
		}
		
		$data = each($objs);
		return $data[1];
	}
	
	function cleanMemory(){
		$this->clean_memory();
	}
	
	function clean_memory(){
		$usage = (memory_get_usage() / 1024) / 1024;
		if ($usage > 54){
			$this->unset_all();
		}
	}
	
	function unset_all(){
		$this->objects = array();
	}
	
	function unset_class($classname){
		unset($this->objects[$classname]);
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
		}elseif(is_array($data) and $data){
			$rdata = &$data;
		}else{
			$rdata = &$id;
		}
		
		if ($this->config["check_id"] and !is_numeric($id) and $this->args["version"] != 2){
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
			}elseif($this->args["version"] == 2){
				$this->objects[$ob][$id] = new $ob(array(
					"ob" => $this,
					"data" => $rdata
				));
			}else{
				$this->objects[$ob][$id] = new $ob($id, $data);
			}
		}
		
		return $this->objects[$ob][$id];
	}
	
	function get_try($ob, $key, $obname = null){
		$class_name = get_class($ob);
		
		if (!$obname and $this->get_try_cache[$class_name][$key]){
			$obname = $this->get_try_cache[$class_name][$key];
		}elseif(!$obname){
			if (substr($key, -3, 3) == "_id"){
				$obname = substr($key, 0, -3);
				$this->get_try_cache[$class_name][$key] = $obname;
			}elseif(substr($key, -2, 2) == "Id"){
				$obname = substr($key, 0, -2);
				$this->get_try_cache[$class_name][$key] = $obname;
			}else{
				throw new exception("Could not figure out the object name from: " . $key);
			}
		}
		
		$id = intval($ob->g($key));
		if (!$id){
			return false;
		}
		
		try{
			return $this->get($obname, $id);
		}catch(knjdb_rownotfound_exception $e){
			return false;
		}
	}
	
	function sqlhelper(&$list_args, $args){
		if ($args["db"]){
			$db = $args["db"];
		}else{
			$db = $this->config["db"];
		}
		
		if ($args["table"]){
			$table = $db->conn->sep_table . $db->escape_table($args["table"]) . $db->conn->sep_table . ".";
		}else{
			$table = "";
		}
		
		$colsep = $db->conn->sep_col;
		if (!is_array($list_args)){
			throw new exception("The arguments given was not an array.");
		}
		
		$sql_limit = "";
		$sql_order = "";
		$dbrows_exist = array_key_exists("cols_dbrows", $args);
		$num_exists = array_key_exists("cols_num", $args);
		$str_exists = array_key_exists("cols_str", $args);
		
		foreach($list_args as $list_key => $list_val){
			$found = false;
			
			if ($args["utf8_decode"]){
				$list_val = utf8_decode($list_val);
			}
			
			if (($str_exists and in_array($list_key, $args["cols_str"]) or ($num_exists and in_array($list_key, $args["cols_num"])))){
				if (is_array($list_val)){
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key) . $colsep . " IN (" . knjarray::implode(array("array" => $list_val, "impl" => ",", "surr" => "'", "self_callback" => array($db, "sql"))) . ")";
				}else{
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key) . $colsep . " = '" . $db->sql($list_val) . "'";
				}
				
				$found = true;
			}elseif($str_exists and preg_match("/^(.+)_(has|not)$/", $list_key, $match) and in_array($match[1], $args["cols_str"])){
				if ($match[2] == "has"){
					if ($list_val){
						$sql_where .= " AND " . $table . $colsep . $db->escape_column($match[1]) . $colsep . " != ''";
					}else{
						$sql_where .= " AND " . $table . $colsep . $db->escape_column($match[1]) . $colsep . " = ''";
					}
					$found = true;
				}elseif($match[2] == "not"){
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($match[1]) . $colsep . " != '" . $db->sql($list_val) . "'";
					$found = true;
				}
			}elseif($dbrows_exist and in_array($list_key . "_id", $args["cols_dbrows"])){
				if (!is_object($list_val) and !is_bool($list_val) and !is_array($list_val)){
					throw new exception("Unknown type: " . gettype($list_val));
				}elseif(is_object($list_val) and !method_exists($list_val, "id")){
					throw new exception("Unknown method on object: " . get_class($list_val) . "->id().");
				}
				
				if ($list_val === true){
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key . "_id") . $colsep . " != '0'";
				}elseif($list_val === false){
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key . "_id") . $colsep . " = '0'";
				}elseif(is_array($list_val)){
					if (empty($list_val)){
						$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key . "_id") . $colsep . " = '-1'";
					}else{
						$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key . "_id") . $colsep . " IN (" . knjarray::implode(array("array" => $list_val, "impl" => ",", "surr" => "'", "func_callback" => "id")) . ")";
					}
				}else{
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key . "_id") . $colsep . " = '" . $db->sql($list_val->id()) . "'";
				}
				
				$found = true;
			}elseif($dbrows_exist and in_array($list_key . "Id", $args["cols_dbrows"])){
				if (!is_object($list_val) and !is_bool($list_val)){
					throw new exception("Unknown type: " . gettype($list_val));
				}elseif(is_object($list_val) and !method_exists($list_val, "id")){
					throw new exception("Unknown method on object: " . get_class($list_val) . "->id().");
				}
				
				if ($list_val === true){
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key . "Id") . $colsep . " != '0'";
				}elseif($list_val === false){
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key . "Id") . $colsep . " = '0'";
				}else{
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key . "Id") . $colsep . " = '" . $db->sql($list_val->id()) . "'";
				}
				
				$found = true;
			}elseif($dbrows_exist and in_array($list_key, $args["cols_dbrows"])){
				if (is_array($list_val)){
					if (empty($list_val)) throw new exception("No elements was given in array.");
					
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key) . $colsep . " IN (" . knjarray::implode(array("array" => $list_val, "impl" => ",", "surr" => "'", "self_callback" => array($db, "sql"))) . ")";
				}else{
					$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key) . $colsep . " = '" . $db->sql($list_val) . "'";
				}
				
				$found = true;
			}elseif(array_key_exists("cols_bool", $args) and in_array($list_key, $args["cols_bool"])){
				if ($list_val){
					$list_val = "1";
				}else{
					$list_val = "0";
				}
				$sql_where .= " AND " . $table . $colsep . $db->escape_column($list_key) . $colsep . " = '" . $db->sql($list_val) . "'";
				$found = true;
			}elseif(substr($list_key, -7, 7) == "_search" and preg_match("/^(.+)_search$/", $list_key, $match) and (($str_exists and in_array($match[1], $args["cols_str"])) or ($dbrows_exist and in_array($match[1], $args["cols_dbrows"])) or ($num_exists and in_array($match[1], $args["cols_num"])))){
				$sql_where .= " AND " . $table . $colsep . $db->escape_column($match[1]) . $colsep . " LIKE '%" . $db->sql($list_val) . "%'";
				$found = true;
			}elseif(substr($list_key, -6, 6) == "_lower" and preg_match("/^(.+)_lower$/", $list_key, $match) and in_array($match[1], $args["cols_str"])){
				$sql_where .= " AND LOWER(" . $table . $colsep . $db->escape_column($match[1]) . $colsep . ") = LOWER('" . $db->sql($list_val) . "')";
				$found = true;
			}elseif(array_key_exists("cols_num", $args) and preg_match("/^(.+)_(from|to)/", $list_key, $match) and in_array($match[1], $args["cols_num"])){
				$sql_where .= " AND " . $table . $colsep . $db->escape_column($match[1]) . $colsep;
				$found = true;
				
				switch($match[2]){
					case "from":
						$sql_where .= " >= '" . $db->sql($list_val) . "'";
						break;
					case "to":
						$sql_where .= " <= '" . $db->sql($list_val) . "'";
						break;
					default:
						throw new exception("Invalid mode: " . $match[2]);
				}
			}elseif(array_key_exists("cols_dates", $args) and preg_match("/^(.+)_(date|time|from|to)/", $list_key, $match) and in_array($match[1], $args["cols_dates"])){
				$found = true;
				
				switch($match[2]){
					case "date":
						$sql_where .= " AND DATE(" . $table . $colsep . $db->escape_column($match[1]) . $colsep . ")";
						$sql_where .= " = '" . $db->sql($db->date_format($list_val, array("time" => false))) . "'";
						break;
					case "time":
						$sql_where .= " AND " . $table . $colsep . $db->escape_column($match[1]) . $colsep;
						$sql_where .= " = '" . $db->sql($db->date_format($list_val, array("time" => true))) . "'";
					case "from":
						$sql_where .= " AND " . $table . $colsep . $db->escape_column($match[1]) . $colsep;
						$sql_where .= " >= '" . $db->sql($db->date_format($list_val, array("time" => true))) . "'";
						break;
					case "to":
						$sql_where .= " AND " . $table . $colsep . $db->escape_column($match[1]) . $colsep;
						$sql_where .= " <= '" . $db->sql($db->date_format($list_val, array("time" => true))) . "'";
						break;
					default:
						throw new exception("Invalid mode: " . $match[2]);
				}
			}elseif($list_key == "limit"){
				$sql_limit .= " LIMIT " . intval($list_val);
				$found = true;
			}elseif($list_key == "limit_from" and $list_args["limit_to"]){
				$sql_limit .= " LIMIT " . intval($list_val) . ", " . intval($list_args["limit_to"]);
				$found = true;
			}elseif($list_key == "limit_to"){
				$found = true;
			}elseif($list_key == "orderby"){
				if (is_string($list_val)){
					if ($args["orderby_callbacks"][$list_val]){
						$orderby_res = $args["orderby_callbacks"][$list_val]();
						if ($orderby_res){
							$sql_order .= " ORDER BY " . $db->escape_column($orderby_res);
							$found = true;
						}
					}else{
						$sql_order .= " ORDER BY " . $table . $colsep . $db->escape_column($list_val) . $colsep;
						$found = true;
					}
				}elseif(is_array($list_val)){
					$found = true;
					$sql_order .= " ORDER BY ";
					
					$first = true;
					foreach($list_val as $val_ele){
						if ($first){
							$first = false;
						}else{
							$sql_order .= ", ";
						}
						
						if (is_array($val_ele)){
							$ordermode = strtolower($val_ele[1]);
							$val_ele = $val_ele[0];
						}
						
						$sql_order .= $table . $colsep . $db->escape_column($val_ele) . $colsep;
						
						if ($ordermode == "desc"){
							$sql_order .= " DESC";
						}elseif($ordermode == "asc"){
							$sql_order .= " ASC";
						}elseif($ordermode){
							throw new exception("Invalid order-mode: " . $ordermode);
						}
					}
				}
			}
			
			if ($found){
				unset($list_args[$list_key]);
			}
		}
		
		return array(
			"sql_where" => $sql_where,
			"sql_limit" => $sql_limit,
			"sql_order" => $sql_order
		);
	}
}