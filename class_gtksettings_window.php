<?
	//Check if function exists to make it compatible, if somehow the function already got loaded through another file.
	if (!function_exists("destroy_obj")){
		require_once("knjphpframework/functions_knj_objects.php");
	}
	
	/** This class keeps track of window sizes- and positions. It also loads and restores them, when they are created again. */
	class GtkSettingsWindow{
		private $win;								//The reference to the window.
		private $name;								//The name identifier of the window.
		private $maximized = "false";			//If the window is maximized or not.
		private $size_width;
		private $size_height;
		private $pos_left;
		private $pos_top;
		private $dbconn;
		
		/**
		 * The constructor of the class.
		 * 
		 * @param GtkWindow $win The window which should be manipulated.
		 * @param string $name The name of the window - the identifier.
		*/
		function __construct(GtkWindow $win, $name){
			$this->name = $name;
			$this->win = $win;
			
			//Connect signals.
			$this->win->connect("size-request", array($this, "set_settings"));
			$this->win->connect("event", array($this, "event"));
			
			//Get variables from database.
			$this->dbconn = GtkSettingsWindow::getDBConn();
			$this->winsettings = $this->dbconn->selectfetch("gtksettings_windows", array("name" => $this->name), array("limit" => 1));
			$this->winsettings = $this->winsettings[0];
			
			if ($this->winsettings){
				if (is_numeric($this->winsettings->get("size_width")) && is_numeric($this->winsettings->get("size_height"))){
					$this->win->resize($this->winsettings->get("size_width"), $this->winsettings->get("size_height"));
				}
				
				if (is_numeric($this->winsettings->get("pos_left")) && is_numeric($this->winsettings->get("pos_top"))){
					$this->win->move($this->winsettings->get("pos_left"), $this->winsettings->get("pos_top"));
				}
				
				$this->size_width = $this->winsettings->get("size_width");
				$this->size_height = $this->winsettings->get("size_height");
				$this->pos_left = $this->winsettings->get("pos_left");
				$this->pos_top = $this->winsettings->get("pos_top");
				
				if ($this->winsettings->get("maximized") == "true"){
					/** NOTE: The variable should not be set, since the maximize()-function triggers the setting of this. */
					$this->win->maximize();
				}
			}else{
				//The window does not exists in the database - create it.
				$this->dbconn->insert("gtksettings_windows", array("name" => $this->name));
				$this->winsettings = $this->dbconn->selectfetch("gtksettings_windows", array("name" => $this->name), array("limit" => 1));
				$this->winsettings = $this->winsettings[0];
				$this->win->set_position(Gtk::WIN_POS_CENTER);
			}
			
			$this->set_settings();
		}
		
		/** Handels the event when the object is destroyed. */
		function __destruct(){
			$this->save_settings();
		}
		
		/** Free resources. */
		function destroy(){
			destroy_obj($this);
		}
		
		/** Catches random events. */
		function event($temp, $event){
			//echo $this->name . ": " . $event->type . "\n";
			
			if ($event->type == 32){
				//The window has been maximized or unmaximized.
				if ($event->new_window_state == 4){
					$this->maximized = "true";
				}elseif($event->new_window_state == 0){
					$this->maximized = "false";
				}
			}elseif($event->type == 12){
				//Done with resizing/move/whatever - set new settings.
				$this->set_settings();
			}elseif($event->type == 0){
				//Closed the window - save information.
				$this->save_settings();
				$this->destroy();
			}
		}
		
		/** Set new settings for the window. */
		function set_settings(){
			if ($this->maximized == "false"){
				$size = $this->win->get_size();
				$pos = $this->win->get_position();
				
				$this->size_width = $size[0];
				$this->size_height = $size[1];
				
				$this->pos_left = $pos[0];
				$this->pos_top = $pos[1];
			}
		}
		
		/** Saves the settings to the database. */
		function save_settings(){
			if ($this->winsettings){
				$this->winsettings->setData(array(
						"size_width" => $this->size_width,
						"size_height" => $this->size_height,
						"pos_left" => $this->pos_left,
						"pos_top" => $this->pos_top,
						"maximized" => $this->maximized
					)
				);
			}
		}
		
		/** Sets which SQLite-database that should be used for storing the window-settings. */
		function setDBConn($dbconn){
			global $gtksettings_window;
			$gtksettings_window["dbconn"] = $dbconn;
		}
		
		/** Returns the SQLite-database. */
		function getDBConn(){
			global $gtksettings_window;
			return $gtksettings_window["dbconn"];
		}
	}
?>