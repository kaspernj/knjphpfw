<?php
	require_once("knjphpframework/knjdb/class_knjdb_row.php");
	
	class knjobjects{
		private $objects;
		private $config;
		
		function __construct($paras){
			$this->config = $paras;
			
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
		
		function cleanMemory(){
			$usage = (memory_get_usage() / 1024) / 1024;
			if ($usage > 54){
				$this->objects = array();
			}
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
		
		function listObs($ob, $paras = array()){
			if (!$this->objects[$ob]){
				$this->requirefile($ob);
			}
			
			return call_user_func(array($ob, "getList"), $paras);
		}
		
		function list_obs($ob, $paras = array()){
			return $this->listObs($ob, $paras);
		}
		
		function listArr($ob, $paras = null){
			$opts = array();
			if ($paras["none"]){
				unset($paras["none"]);
				$opts = array(0 => gtext("None"));
			}
			
			$list = $this->listObs($ob, $paras);
			foreach($list AS $listitem){
				$opts[$listitem->get($this->config["col_id"])] = $listitem->getTitle();
			}
			
			return $opts;
		}
		
		function listOpts($ob, $getkey, $paras = null){
			$opts = array();
			
			if ($paras["addnew"]){
				unset($paras["addnew"]);
				$opts[0] = gtext("Add new");
			}
			
			if ($paras["none"]){
				unset($paras["none"]);
				$opts[0] = gtext("None");
			}
			
			if ($paras["choose"]){
				unset($paras["choose"]);
				$opts[0] = gtext("Choose") . ":";
			}
			
			if ($paras["all"]){
				unset($paras["all"]);
				$opts[0] = gtext("All");
			}
			
			if (!$paras["col_id"]){
				$paras["col_id"] = "id";
			}
			
			foreach($this->listObs($ob) AS $object){
				if (is_array($getkey) and $getkey["funccall"]){
					$value = call_user_func(array($object, $getkey["funccall"]));
				}else{
					$value = $object->get($getkey);
				}
				
				$opts[$object->get($paras["col_id"])] = $value;
			}
			
			return $opts;
		}
		
		function list_opts($ob, $getkey, $paras = null){
			return $this->listOpts($ob, $getkey, $paras);
		}
		
		function add($ob, $arr){
			if (!$this->objects[$ob]){
				$this->requirefile($ob);
			}
			
			return call_user_func(array($ob, "addNew"), $arr);
		}
		
		function unsetOb($ob, $id = null){
			if (is_object($ob) && is_null($id)){
				$id = $ob->get($this->config["col_id"]);
				
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
		
		function get($ob, $id, $data = null){
			if (is_array($id)){
				$data = $id;
				$id = $data[$this->config["col_id"]];
			}
			
			if ($this->config["check_id"] and !is_numeric($id)){
				throw new exception("Invalid ID: " . gettype($id));
			}
			
			if (!is_string($ob)){
				throw new exception("Invalid object: " . gettype($ob));
			}
			
			if (!$this->objects[$ob][$id]){
				$this->requirefile($ob);
				$this->objects[$ob][$id] = new $ob($id, $data);
			}
			
			return $this->objects[$ob][$id];
		}
	}
?>