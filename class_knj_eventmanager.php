<?
	/** This class handels callbacks. */
	class EventManager{
		private $events = array();
		private $events_count = 0;
		private $checkevent = true;

		/** Destroys the object. */
		function destroy(){
			unset($this->events);
			unset($this->events_count);
			unset($this->checkevent);
		}

		/** Sets wherever that should be checked that the event has been added with addEvent when connecting to it. */
		function setCheckEvent($value){
			if (!is_bool($value)){
				throw new Exception("The argument is not a boolean.");
			}

			$this->checkevent = $value;
		}

		/** Register a new event. */
		function addEvent($event){
			if (is_array($event)){
				foreach($event AS $value){
					$this->addEvent($value);
				}
				return null;
			}

			if (!$this->events[$event]){
				$this->events[$event] = array();
			}
		}

		/** Remove an event. */
		function removeEvent($event){
			if (is_array($event)){
				foreach($event AS $value){
					$this->removeEvent($value);
				}
				return null;
			}

			unset($this->events[$event]);
		}

		/** Calls an event. */
		function callEvent($event, $data = null){
			if (is_array($this->events[$event])){
				//$backtrace = debug_backtrace();
				foreach($this->events[$event] AS $value){
					//echo "Calling " . get_class($value[0]) . "->" . $value[1] . "(" . $event . ");\n";
					call_user_func($value, $event, $data);
				}
			}
		}

		/** Connects to a new event. */
		function connect($event, $callback){
			if (is_array($event)){
				$ids = array();
				foreach($event AS $newevent){
					$ids[] = $this->connect($newevent, $callback);
				}
				return $ids;
			}else{
				if ($this->checkevent){
					if (!array_key_exists($event, $this->events)){
						throw new Exception("Not a valid event callsign: " . $event);
					}
				}

				$id = $this->events_count;
				$this->events[$event][$id] = $callback;
				$this->events_count++;
				return $id;
			}
		}

		/** Unconnects an event. */
		function unconnect($id){
			if (is_array($id)){
				foreach($id AS $real_id){
					$this->unconnect($real_id);
				}
			}else{
				foreach($this->events AS $event => $ids_arr){
					foreach($ids_arr AS $event_id => $callback){
						if ($event_id == $id){
							unset($this->events[$event][$event_id]);
							return true;
						}
					}
				}
			}
		}

		/** Alias for unconnect. */
		function disconnect($id){
			return $this->unconnect($id);
		}
	}

