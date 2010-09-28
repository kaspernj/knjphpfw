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
}