<?
	/** This class remembers the settings for a GtkTreeview. */
	class GtkSettingsTreeview{
		private $name;
		private $tv;
		private $tv_data;
		private $cols;
		private $pressed;
		
		/** The constructor of TreeviewSetting. */
		function __construct(GtkTreeView $tv, $name, $args = array()){
			$this->name = $name;
			$this->tv = $tv;
			$this->tv->connect("button-press-event", array($this, "on_tv_buttonpress"));
			$this->tv->connect("button-release-event", array($this, "on_tv_buttonrelease"));
			$this->tv->connect("destroy", array($this, "destroy"));
			
			if ($args){
				foreach($args AS $key => $value){
					if ($key == "dbconn"){
						$this->db = $value;
					}elseif($key == "defaults"){
						if (!is_array($value)){
							throw new Exception("The defaults-argument only accepts an array.");
						}
						
						$defaults = $value;
					}else{
						throw new Exception("Invalid argument: " . $key);
					}
				}
			}
			
			if (!$this->db){
				$this->db = GtkSettingsTreeview::getDBConn();
			}
			
			
			//Check if the name exists in the database.
			while(!$this->tv_data){
				$res = $this->db->selectfetch("gtksettings_treeviews", array("name" => $this->name), array("limit" => 1));
				$this->tv_data = $res[0];
				
				if (!$this->tv_data){
					$read_defaults = true;
					$this->db->insert("gtksettings_treeviews", array("name" => $name));
					$this->tv_data = $this->db->getRow($this->db->getLastInsertedID(), "gtksettings_treeviews");
				}
			}
			
			
			//Read columns.
			$cols = $this->tv->get_columns();
			$count = 0;
			foreach($cols AS $col){
				if ($col->get_visible()){
					$this->cols[$count] = $col;
					$this->cols[$count]->thacount = $count;
					$this->cols[$count]->connect("clicked", array($this, "on_col_clicked"));
				}
				
				$count++;
			}
			
			
			//Count and reset, if count is not the same.
			$d_gc = $this->db->query("SELECT COUNT(id) AS tha_count FROM gtksettings_treeviews_columns WHERE treeview_id = '" . $this->tv_data->get("id") . "' GROUP BY treeview_id")->fetch();
			if (count($this->cols) != $d_gc["tha_count"]){
				echo("Warning: Columns-count doesnt match for the treeview: \"" . $this->name . "\". Resetting column-data.\n");
				$this->db->delete("gtksettings_treeviews_columns", array("treeview_id" => $this->tv_data->get("id")));
				$read_defaults = true;
			}
			
			$max = count($this->cols);
			foreach($this->cols AS $key => $col){
				//Check if column exists.
				$d_gcol = $this->db->selectsingle("gtksettings_treeviews_columns", array("treeview_id" => $this->tv_data->get("id"), "column_id" => $key));
				
				if (!$d_gcol){
					$this->db->insert("gtksettings_treeviews_columns", array(
							"treeview_id" => $this->tv_data->get("id"),
							"column_id" => $key,
							"visible" => 1,
							"width" => 0
						)
					);
				}else{
					if (!$d_gcol["visible"]){
						$col->set_visible(false);
					}
					
					if ($d_gcol["width"] > 0 && $d_gcol["width"] != $col->get_width() && $key != $max){
						$col->set_sizing(Gtk::TREE_VIEW_COLUMN_FIXED);
						$col->set_fixed_width($d_gcol["width"]);
					}
				}
			}
			
			
			
			//If this is the first time the treeview is loaded with treeview-settings then load the defaults (if any).
			if ($read_defaults && $defaults){
				foreach($defaults AS $col => $value){
					if (!is_numeric($col)){
						throw new Exception("The key has to be a number.");
					}
					
					if ($value == "hidden"){
						$this->editColumn($col, array("hidden" => true));
					}
				}
			}
			
			
			//Set saved sort.
			if (strlen($this->tv_data->get("sort_column")) > 0){
				$sort_column = $this->tv_data->get("sort_column");
				$sort_order = $this->tv_data->get("sort_order");
				
				if ($sort_order == 0){
					$this->tv->get_model()->set_sort_column_id($sort_column, Gtk::SORT_ASCENDING);
				}elseif($sort_order == 1){
					$this->tv->get_model()->set_sort_column_id($sort_column, Gtk::SORT_DESCENDING);
				}else{
					echo("Warning: Unknown sort-order: \"" . $sort_order . ".\n");
				}
			}
		}
		
		/** Destructor - saves column-setting when the object is destroyed. */
		function __destruct(){
			if ($this->db && $this->tv && $this->cols){
				$this->destroy();
			}
		}
		
		function saveSettings(){
			if ($this->cols && $this->db && $this->tv){
				$this->db->trans_begin();
				foreach($this->cols AS $count => $col){
					if ($col->get_visible()){
						$visible = "1";
					}else{
						$visible = "0";
					}
					
					$this->db->update("gtksettings_treeviews_columns", array(
							"width" => $col->get_width(),
							"visible" => $visible
						),
						array(
							"treeview_id" => $this->tv_data->get("id"),
							"column_id" => $count
						)
					);
				}
				$this->db->trans_commit();
			}
		}
		
		function destroy(){
			if ($this->db && $this->tv && $this->cols){
				$this->saveSettings();
			}
			unset($this->cols, $this->db, $this->name, $this->tv, $this->tv_data, $this->pressed);
		}
		
		/** Handels the event when the treeview is clicked. */
		function on_tv_buttonpress($widget, $event){
			if ($event->button == 1){
				$this->pressed[$event->button] = true;
			}
			
			if ($this->pressed[1] == true && $event->button == 3){
				$this->showPopupMenu();
				return true;
			}
		}
		
		/** Handels the event when a button is released on the treeview. */
		function on_tv_buttonrelease($widget, $event){
			if ($event->button == 1){
				unset($this->pressed[$event->button]);
			}
		}
		
		/** Updates the sort. */
		function updateSort(){
			/*
			$cols = $this->tv->get_columns();
			$count = 0;
			foreach($cols AS $col){
				echo "Col " . $col->thacount . " is now " . $count . "\n";
				$count++;
			}
			*/
		}
		
		/** Handels the event when a column has been clicked. */
		function on_col_clicked($widget){
			$column_id = $widget->thacount;
			$search_column = $this->tv->get_search_column();
			$sort_order = $widget->get_sort_order();
			
			$this->db->update("gtksettings_treeviews", array(
					"sort_column" => $column_id,
					"sort_order" => $sort_order
				),
				array(
					"name" => $this->name
				)
			);
		}
		
		/** Handels the event when a column has been clicked. */
		function showPopupMenu(){
			$arr_popup = array();
			foreach($this->cols AS $count => $col){
				$arr_popup[$count] = array(
					"type" => "checkitem",
					"text" => $col->get_title(),
					"active" => $col->get_visible()
				);
			}
			
			require_once("knjphpframework/class_knj_popup.php");
			$popup = new knj_popup(
				$arr_popup,
				array(
					$this,
					"on_popup_choose"
				)
			);
			unset($this->pressed[1]);
			
			return true;
		}
		
		/** Handels the event when an item is activated. */
		function on_popup_choose($key, $activated){
			if ($activated){
				$args["visible"] = true;
			}else{
				$args["hidden"] = true;
			}
			
			$this->editColumn($key, $args);
		}
		
		/** Edits a column. */
		function editColumn($col, $args){
			if ($args["visible"] == true){
				$this->cols[$col]->set_visible(true);
			}elseif($args["hidden"] == true){
				$this->cols[$col]->set_visible(false);
			}
		}
		
		/** Sets the DBConn (database-connection) to use. */
		static function setDBConn($dbconn){
			global $class_treeviewsetting;
			$class_treeviewsetting["dbconn"] = $dbconn;
		}
		
		/** Returns the default DBConn-connection used by treeview-settings. */
		static function getDBConn(){
			global $class_treeviewsetting;
			return $class_treeviewsetting["dbconn"];
		}
	}
?>