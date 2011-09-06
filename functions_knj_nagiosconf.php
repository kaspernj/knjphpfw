<?
	/** Parses a nagios-config and returns all relevant options. */
	function nag_parseConf($cont, $path = ""){
		if (!is_string($cont)){
			throw new Exception("The content was not a string.");
		}

		$nagios = array();

		preg_match_all("/define (service|host|hostgroup)\{([\S\s]+)\}/U", $cont, $matches);
		//parse data.
		foreach($matches[2] AS $opt_key => $opt_data){
			$type = $matches[1][$opt_key];
			$opt = array();

			preg_match_all("/([a-z_]+)\s+([ \S]+)/", $opt_data, $data_matches);
			foreach($data_matches[1] AS $key => $data_key){
				$value = $data_matches[2][$key];

				//remove comments from value.
				$pos = strpos($value, ";");
				if ($pos !== false){
					$value = substr($value, 0, $pos);
				}
				$value = trim($value);

				//save the trimmed value.
				if (
					($type == "service" && ($data_key == "hostgroup_name" || $data_key == "host_name")) ||
					($type == "hostgroup" && $data_key == "members")
				){
					$groups_arr = explode(",", $value);
					$groups_return = array();
					foreach($groups_arr AS $group_val){
						$group = array();
						if (substr($group_val, 0, 1) == "!"){
							$group["include"] = false;
						}else{
							$group["include"] = true;
						}

						$group["name"] = trim($group_val);
						$groups_return[] = $group;
					}

					$opt[$data_key] = $groups_return;
				}

				$opt["data"][$data_key] = $value;
			}

			$opt["path"] = $path;
			$nagios[$type][] = $opt;
		}

		return $nagios;
	}

	/** Merges multiple nagios-configs into one. */
	function nag_merge($confs){
		$return = array();
		foreach($confs AS $conf){
			foreach($conf AS $conf_key => $conf_list){
				foreach($conf_list AS $opt_key => $opt){
					$return[$conf_key][] = $opt;
				}
			}
		}

		return $return;
	}

