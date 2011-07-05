<?

class knj_powerset{
	public $arr;
	
	function __construct($args = array()){
		$this->args = $args;
		$this->res = array();
		$this->skips = array();
		$this->arr = $this->args["arr"];
		$this->length = count($this->arr);
		$this->maxlength = $this->args["maxlength"];
		$this->minlength = $this->args["minlength"];
		$this->res_count = 0;
		$this->res_count_lastrun = 0;
		$this->abort = false;
		$this->perc = 0;
		$this->depth = 0;
		
		$this->callback_count_ret = $this->args["callback_count_ret"];
		if (!$this->callback_count_ret){
			$this->callback_count_ret = 50000;
		}
	}
	
	function skip_value($value){
		$this->skips[$value] = true;
	}
	
	function remove_value($value){
		foreach($this->arr as $key => $val){
			if ($val == $value){
				$this->skip_value($value);
				unset($this->arr[$key]);
				break;
			}
		}
	}
	
	function run(){
		for($i = 0; $i < $this->length; $i++){
			$val = $this->arr[$i];
			
			if (is_null($val)){
				continue;
			}elseif($this->skips[$val]){
				continue;
			}
			
			$this->perc = $i / $this->length;
			
			if ($this->abort){
				print "Powerset: Break!\n";
				break;
			}
			
			if ($this->minlength and $this->minlength <= 1){
				try{
					$this->res_add(array($val));
				}catch(knj_powerset_skipids_exception $e){
					if (in_array($val, $this->remove)){
						$this->remove = null;
						continue;
					}else{
						print_r($this->remove);
						print "Value: " . $val . "\n";
						throw $e;
					}
				}
			}
			
			if ($this->maxlength > 1){
				try{
					$this->add_combinations(array($val), $i + 1);
				}catch(knj_powerset_skipids_exception $e){
					if (in_array($val, $this->remove)){
						$this->remove = null;
						continue;
					}else{
						print_r($this->remove);
						print "Value: " . $val . "\n";
						throw $e;
					}
				}
			}
		}
		
		if ($this->res_count_lastrun > 0 and array_key_exists("callback", $this->args)){
			try{
				$this->callback();
			}catch(knj_powerset_skipids_exception $e){
				//ignore - we are done.
			}
		}
	}
	
	function add_combinations($arr_val, $pos){
		for($i = $pos; $i < $this->length; $i++){
			$val = $this->arr[$i];
			
			if (is_null($val)){
				continue;
			}
			
			$new = $arr_val;
			$new[] = $val;
			$count_new = count($new);
			
			if (!$this->minlength or $this->minlength <= $count_new){
				try{
					$this->res_add($new);
				}catch(knj_powerset_skipids_exception $e){
					foreach($arr_val as $parent_val){
						if (in_array($parent_val, $this->remove)){
							throw $e;
						}
					}
					
					if (in_array($val, $this->remove)){
						$this->remove = array();
						continue;
					}else{
						throw new exception("SkipIDs should not have reached this?");
					}
				}
			}
			
			if ($this->maxlength and $count_new < $this->maxlength){
				try{
					$this->add_combinations($new, $i + 1);
				}catch(knj_powerset_skipids_exception $e){
					print_r($this->remove);
					print "Value: " . $val . "\n";
					
					foreach($arr_val as $parent_val){
						if (in_array($parent_val, $this->remove)){
							throw $e;
						}
					}
					
					if (in_array($val, $this->remove)){
						$this->remove = array();
						continue;
					}else{
						throw new exception("SkipIDs should not have reached this?");
					}
				}
			}
		}
	}
	
	function res_add($ele){
		$this->res[] = $ele;
		$this->res_count_lastrun++;
		
		if ($this->res_count_lastrun >= $this->callback_count_ret and array_key_exists("callback", $this->args)){
			$this->callback();
		}
	}
	
	function callback(){
		$call_res = call_user_func($this->args["callback"], &$this->res);
		if (is_array($call_res)){
			if ($call_res["type"] == "abort"){
				print "ABORT!\n";
				$this->abort = true;
			}elseif($call_res["type"] == "remove" and $call_res["ids"]){
				$this->remove = $call_res["ids"];
				foreach($this->remove as $val){
					$this->remove_value($val);
				}
				throw new knj_powerset_skipids_exception();
			}
		}
		
		$this->res = array();
		$this->res_count_lastrun = 0;
	}
	
	function result(){
		return $this->res;
	}
}

class knj_powerset_skipids_exception extends exception{}