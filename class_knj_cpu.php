<?
	/** This class handels CPU-information. */
	class knj_cpu{
		/** Returns information about the CPU-frequency. */
		static function getInfo(){
			//Read info about freq.
			$freq["freq_cur"] = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq"));
			$freq["freq_min"] = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq"));
			$freq["freq_max"] = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq"));

			$avails = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_available_frequencies"));
			$avails = preg_split("/\s+/", $avails);
			$freq["freq_available"] = $avails;


			//Read info about gov.
			$freq["gov_cur"] = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_governor"));

			$govs = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_available_governors"));
			$govs = preg_split("/\s+/", $govs);
			$freq["gov_avaiable"] = $govs;


			//Read driver.
			$freq["driver"] = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_driver"));


			return $freq;
		}
	}
?>