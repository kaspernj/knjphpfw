<?

class knj_translations{
	public $number_info, $args;
	
	function __construct($args = array()){
		$this->args = $args;
		$this->db = $this->args["db"];
		if (!$this->db){
			throw new exception("No db given in arguments.");
		}
		
		$this->ob = new knjobjects(array(
			"db" => $this->db,
			"require" => false,
			"extra_args_self" => true,
			"extra_args" => array("db" => $this->db),
			"get_array" => true
		));
		
		$this->set_locale($args["locale"]);
	}
	
	function set_locale($newlocale){
		$this->args["locale"] = $newlocale;
		
    if ($this->args["locale"] == "da_DK"){
      $this->number_info = array(
        "dec_point" => ",",
        "thousands_sep" => ".",
        "csv_delimiter" => ";"
      );
    }else{
      $this->number_info = array(
        "dec_point" => ".",
        "thousands_sep" => ",",
        "csv_delimiter" => ","
      );
    }
	}
	
	function number_out($number, $decimals = 2){
		return number_format($number, $decimals, $this->number_info["dec_point"], $this->number_info["thousands_sep"]);
	}
	
	function number_in($number){
		return floatval(strtr($number, array(
			$this->number_info["thousands_sep"] => "",
			$this->number_info["dec_point"] => "."
		)));
	}
	
	function del($obj){
		$transs = $this->ob->list_obs("knj_translations_translation", array(
			"object" => $obj
		));
		foreach($transs as $trans){
			$trans->delete();
		}
	}
	
	function get($obj, $args = array()){
		if (!$obj){
			return "";
		}
		
		if (is_string($args)){
      $args = array("key" => $args);
		}
		
		if (array_key_exists("locale", $args)){
			$locale = $args["locale"];
		}else{
			$locale = $this->args["locale"];
		}
		
		$list_args = array(
      "object" => $obj,
      "locale" => $locale
    );
    if ($args["key"]){
      $list_args["key"] = $args["key"];
    }
		
		$trans = $this->ob->list_obs("knj_translations_translation", $list_args);
		if (!$trans){
			return "";
		}
		
		return $trans[0]->g("value");
	}
	
	function set($obj, $values, $args = array()){
		if (!is_array($values)){
			throw new exception("Invalid values given - not an array.");
		}
		
		if (!$values){
			return null;
		}
		
		foreach($values AS $key => $value){
			$trans = $this->ob->list_obs("knj_translations_translation", array(
				"object" => $obj,
				"key" => $key,
				"locale" => $this->args["locale"]
			));
			
			if ($trans and $trans[0]){
				$trans[0]->update(array(
					"value" => $value
				));
			}else{
				$trans = $this->ob->add("knj_translations_translation", array(
					"object" => $obj,
					"key" => $key,
					"value" => $value,
					"locale" => $this->args["locale"]
				));
			}
		}
	}
}

class knj_translations_translation extends knjdb_row{
	function __construct($args = array()){
		parent::__construct(array(
			"db" => $args["db"],
			"data" => $args["data"],
			"table" => "translations"
		));
		$this->ob = $args["ob"];
		if (!$this->ob){
      throw new exception("No ob was given.");
		}
	}
	
	static function getList($args = array(), $eargs = array()){
		$db = $eargs["db"];
		if (!$db){
			throw new exception("No extra args was given.");
		}
		
		$sql = "SELECT * FROM translations WHERE 1=1";
		
		$ret = $eargs["ob"]->sqlhelper($args, array(
			"table" => "translations",
			"db" => $eargs["db"],
			"cols_str" => array("object_class", "key", "locale", "value"),
			"cols_num" => array("object_id")
		));
		
		foreach($args AS $key => $value){
			switch($key){
				case "object":
					$sql .= " AND object_class = '" . $db->sql(get_class($value)) . "'";
					$sql .= " AND object_id = '" . $db->sql($value->id()) . "'";
					break;
				case "key":
				case "locale":
				case "value":
					$sql .= " AND `" . $key . "` = '" . $db->sql($value) . "'";
					break;
				default:
					throw new exception(sprintf("Invalid key: %s.", $key));
			}
		}
		
		$sql .= $ret["sql_where"];
		$sql .= $ret["sql_order"];
		$sql .= $ret["sql_limit"];
		
		return $eargs["ob"]->list_bysql("knj_translations_translation", $sql);
	}
	
	static function addNew($data, $args = array()){
		$db = $args["db"];
		$ob = $args["ob"];
		
		if ($data["object"]){
			$data["object_class"] = get_class($data["object"]);
			$data["object_id"] = $data["object"]->id();
			unset($data["object"]);
		}
		
		$db->insert("translations", $data);
		return $ob->get("knj_translations_translation", $db->last_id());
	}
	
	function delete(){
		$this->db->delete("translations", array("id" => $this->id()));
		if ($this->ob){
      $this->ob->unset_ob($this);
    }
	}
}

