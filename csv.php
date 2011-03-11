<?php

class knj_csv{
	static function arr_to_csv($arr, $del, $encl){
		$str = "";
		foreach($arr AS $value){
			$value_safe = str_replace($del, "", $value);
			$value_safe = str_replace($encl, "", $value);
			
			if (strlen($str) > 0){
				$str .= $del;
			}
			
			$str .= $encl . $value_safe . $encl;
		}
		
		return $str;
	}
	
	function __construct($args){
		if (!is_array($args)){
			throw new exception("Invalid arguments.");
		}
		
		$this->args = $args;
		$this->read_size = 4096 * 4;
		$this->del = ";";
		$this->encl = "\"";
		$this->lines_count = 0;
		
		if (!array_key_exists("nl", $this->args)){
			$this->args["nl"] = "\n";
		}
		
		$this->fp = fopen($args["path"], "r");
		if (!$this->fp){
			throw new exception("Path could not be opened in read mode.");
		}
	}
	
	function line(){
		$this->lines_count++;
		$this->line = fgets($this->fp, $this->read_size);
		
		$arr = array();
		$this->col_count = 0;
		while(($data = $this->line_new()) !== false){
			$this->col_count++;
			
			if ($this->args["utf8_encode"]){
				$arr[] = utf8_encode($data);
			}else{
				$arr[] = $data;
			}
		}
		
		return $arr;
	}
	
	function line_new(){
		if (strlen($this->line) <= 0){
			return false;
		}
		
		$char = substr($this->line, 0, 1);
		
		if ($char == $this->encl){
			$this->line = substr($this->line, 1);
			
			while(true){
				$next_found = $this->encl . $this->del;
				$next = strpos($this->line, $next_found);
				if ($next !== false){
					break;
				}
				
				$next_found = $this->encl . $this->args["nl"];
				$next = strpos($this->line, $next_found);
				if ($next !== false){
					break;
				}
				
				if ($this->args["multiple_lines"] and !feof($this->fp)){
					$this->lines_count++;
					$this->line .= fgets($this->fp, $this->read_size);
					continue;
				}
				
				$next_found = $this->encl;
				$next = strpos($this->line, $next_found);
				if (feof($this->fp) and $next !== false){
					break;
				}
				
				throw new exception("Could not find the next enclosure on line " . $this->lines_count . ".");
			}
			
			$data = substr($this->line, 0, $next);
			$this->line = substr($this->line, $next + strlen($next_found));
			
			return $data;
		}elseif($char == $this->del){
			$this->line = substr($this->line, 1);
			return "";
		}
		
		$pos_del = strpos($this->line, $this->del);
		if ($pos_del === false){
			$data = $this->line;
			$this->line = "";
			return $data;
		}
		
		$data = substr($this->line, 0, $pos_del);
		$this->line = substr($this->line, $pos_del + 1);
		return $data;
	}
}