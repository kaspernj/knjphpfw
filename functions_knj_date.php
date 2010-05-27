<?
	function date_MonthNrToStr($in_str){
		if ($in_str == 1){
			$in_date = "Januar";
		}elseif($in_str == 2){
			$in_date = "Februar";
		}elseif($in_str == 3){
			$in_date = "Marts";
		}elseif($in_str == 4){
			$in_date = "April";
		}elseif($in_str == 5){
			$in_date = "Maj";
		}elseif($in_str == 6){
			$in_date = "Juni";
		}elseif($in_str == 7){
			$in_date = "Juli";
		}elseif($in_str == 8){
			$in_date = "August";
		}elseif($in_str == 9){
			$in_date = "September";
		}elseif($in_str == 10){
			$in_date = "Oktober";
		}elseif($in_str == 11){
			$in_date = "November";
		}elseif($in_str == 12){
			$in_date = "December";
		}
		
		return $in_date;
	}
	
	function date_month3($string){
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
	
	function date_DayNrToStr($in_day){
		if ($in_day == 0){
			$in_return = "Søndag";
		}elseif($in_day == 1){
			$in_return = "Mandag";
		}elseif($in_day == 2){
			$in_return = "Tirsdag";
		}elseif($in_day == 3){
			$in_return = "Onsdag";
		}elseif($in_day == 4){
			$in_return = "Torsdag";
		}elseif($in_day == 5){
			$in_return = "Fredag";
		}elseif($in_day == 6){
			$in_return = "Lørdag";
		}
		
		return $in_return;
	}
	
	function date_multi_draw($number, $one, $more){
		if ($number == "1"){
			return $one;
		}else{
			return $more;
		}
	}
	
	function date_secs_drawout($secs){
		$days = floor($secs / 60 / 60 / 24);
		$secs = $secs - ($days * 60 * 60 * 24);
		
		$hours = floor($secs / 60 / 60);
		$secs = $secs - ($hours * 60 * 60);
		
		$minutes = floor($secs / 60);
		$secs = $secs - ($minutes * 60);
		
		$in_online_string = "";
		
		if ($days > 0){
			$in_online_string .= $days . " " . date_multi_draw($days, "dag", "dage");
		}
		
		if ($hours > 0){
			if ($in_online_string){
				$in_online_string .= ", ";
			}
			
			$in_online_string .= $hours . " " . date_multi_draw($hours, "time", "timer");
		}
		
		if ($minutes > 0){
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
?>