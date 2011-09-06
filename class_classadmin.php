<?
	class ClassAdmin{
		static function getClass($class, $arg1 = null, $arg2 = null){
			global $classes;

			$class_lower = strtolower($class);

			$args = array();
			if ($arg1){
				$args[] = $arg1;
			}
			if ($arg2){
				$args[] = $arg2;
			}

			if (!$classes[$class_lower]){
				$file = ClassAdmin::getPath() . "/class_" . $class_lower . ".php";
				if (!include_once($file)){
					throw new Exception("Could not include file: \"" . $file . "\".");
				}

				$eval = "\$classes[" . $class_lower . "] = new " . $class . "(";
				$first = true;
				foreach($args AS $key => $value){
					if ($first == true){
						$first = false;
					}else{
						$eval .= ", ";
					}

					$eval .= "\$args[" . $key . "]";
				}
				$eval .= ");";

				eval($eval);
			}

			return $classes[$class_lower];
		}

		/** Sets the path where the lib-files are located. */
		static function setPath($path){
			global $knj_classadmin;
			$knj_classadmin["path"] = $path;
		}

		static function getPath(){
			global $knj_classadmin;
			return $knj_classadmin["path"];
		}
	}
?>