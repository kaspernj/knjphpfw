<?
	function date_month_str_to_no($string){
		if ($string == "jan"){
			return 1;
		}elseif($string == "feb"){
			return 2;
		}elseif($string == "mar"){
			return 3;
		}elseif($string == "apr"){
			return 4;
		}elseif($string == "maj"){
			return 5;
		}elseif($string == "jun"){
			return 6;
		}elseif($string == "jul"){
			return 7;
		}elseif($string == "aug"){
			return 8;
		}elseif($string == "okt"){
			return 9;
		}elseif($string == "sep"){
			return 10;
		}elseif($string == "nov"){
			return 11;
		}elseif($string == "dec"){
			return 12;
		}else{
			return false;
		}
	}
	
	function date_multi_draw($number, $one, $more){
		if ($number == "1"){
			return $one;
		}else{
			return $more;
		}
	}
	
	function date_secs_drawout($secs, $paras = array()){
		$in_online_string = "";
		
		$days = floor($secs / 60 / 60 / 24);
		if ($days > 0 && !$paras["not_days"]){
			$secs = $secs - ($days * 60 * 60 * 24);
			$in_online_string .= $days . " " . date_multi_draw($days, "dag", "dage");
		}
		
		$hours = floor($secs / 60 / 60);
		if ($hours > 0){
			$secs = $secs - ($hours * 60 * 60);
			
			if ($in_online_string){
				$in_online_string .= ", ";
			}
			
			$in_online_string .= $hours . " " . date_multi_draw($hours, "time", "timer");
		}
		
		$minutes = floor($secs / 60);
		if ($minutes > 0){
			$secs = $secs - ($minutes * 60);
			
			if ($in_online_string){
				$in_online_string .= ", ";
			}
			
			$in_online_string .= $minutes . " " . date_multi_draw($minutes, "minut", "minutter");
		}
		
		if ($secs > 0){
			if ($in_online_string){
				$in_online_string .= ", ";
			}
			
			$in_online_string .= $secs . " " . date_multi_draw($secs, "sekund", "sekunder");
		}
		
		return $in_online_string;
	}
	
	function timestr_to_secs($timestr){
		if (!preg_match("/^([0-9]+):([0-9]+):([0-9]+)$/", $timestr, $match)){
			throw new exception("Could not match time-string.");
		}
		
		$secs_hours = $match[1] * 3600;
		$secs_mins = $match[2] * 60;
		$total = $match[3] + $secs_hours + $secs_mins;
		return $total;
	}
?>