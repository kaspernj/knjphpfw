<?
	/*
		//Basic use of the class...
		$clist = new knj_clist(array("Column1", "Column2", "Column3");
		$clist->add(array("1", "2", "3"));
		$clist->set_changed("RegisterChanged");
		
		$window->add($clist->scrwin);
		
		function RegisterChanged($array){
			print_r($array);
		}
		
		
		//More basic use...
		Of course you can also access another class with set_changed() like this:
		class myClass{
			function MyMethod($array){
				print_r($array);
			}
		}
		
		$new_class = new myClass();
		$clist->set_changed(array($new_class, "MyMethod"));
		
		
		//How to clear() a list. Access to the GtkListStore()-object... See the documentation 
		//for GtkListStore() for more. http://gtk.php.net/manual/en/gtk.gtkliststore.php
		$clist->ls->clear();
		
		
		//How to change a active columns content by column number.
		$clist->set_active(2, "New value");
		
		//How to change a active columns content by column title. If the title wasnt found in 
		//one of the columns, the script will die() with an error.
		$clist->set_active("Title", "New value");
		
		
		//How to set it to be able to select multiple columns.
		$clist->set_multiple_select();
		
		//When you want to get the values, of a clist with multiple selects set on:
		$values = $clist->get_value_all();
		print_r($values);
		
		
		//How to add a rightclick menu for the whole clist.
		$clist->set_rightclickmenu(array("Create new", "Delete", "Edit"), "Action"));
		function Action($itemtitle){
			echo "Rightclickmenu: " . $itemtitle . "\n";
		}
		
		
		Written by Kasper Johansen <kaspernj@gmail.com>
	*/
	
	require_once "knj/functions_knj_strings.php";
	class knj_clist{
		//This variabled is passed when a object from knj_clist() is dragged somewhere.
		var $other;
		
		function __construct($columns){
			$eval = '$this->ls = new GtkListStore(';
			
			$first = true;
			foreach($columns AS $value){
				if ($first == true){
					$first = false;
				}else{
					$eval .= ", ";
				}
				
				$eval .= '_TYPE_STRING';
			}
			
			$eval .= ');';
			eval($eval);
			
			
			$cell_renderer = new GtkCellRendererText();
			$this->tv = new GtkTreeView($this->ls);
			$this->tv->set_reorderable(false);
			$this->tv->set_enable_search(true);
			
			$count = 0;
			foreach($columns AS $value){
				$col =& new GtkTreeViewColumn($value, $cell_renderer, "text", $count);
				$col->set_sort_column_id($count);
				$col->set_clickable(true);
				$col->set_resizable(true);
				$col->connect("clicked", array($this, "ColClicked"), $count);
				
				$this->tv->append_column($col);
				$this->columns[$count] = &$col;
				
				$count++;
			}
			
			$this->scrwin =& new GtkScrolledWindow();
			$this->scrwin->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_ALWAYS);
			$this->scrwin->add($this->tv);
		}
		
		function clear(){
			$this->ls->clear();
		}
		
		function set_drawrec(){
			//Enables dragging FROM the clist.
			$this->tv->drag_source_set(Gdk::BUTTON1_MASK, array(array("text/plain", 0, 0)), Gdk::ACTION_COPY|Gdk::ACTION_MOVE);
			
			//Setting the dragged data-object.
			$this->tv->connect("drag-data-get", array($this, "drag_data_save"));
		}
		
		function drag_data_save($widget, $context, $data, $info, $time){
			$data->set_text(
				serialize(
					array(
						"type" => "knj_clist",
						"selected" => $this->get_value_all(),
						"other" => $this->other
					)
				)
			);
		}
		
		function set_rightclickmenu($options, $connect){
			$this->rcmenu_connect = $connect;
			$this->rcmenu_options = $options;
			
			$this->rcmenu = new GtkMenu();
			$this->rcmenu->set_size_request(135, -1);
			
			$count = 0;
			foreach($options AS $value){
				$this->rcmenu_opt[$count] = new GtkMenuItem($value);
				$this->rcmenu_opt[$count]->connect("activate", array($this, "TV_ButtonPressed_Activate"), $value);
				$this->rcmenu->append($this->rcmenu_opt[$count]);
				$count++;
			}
			
			$this->rcmenu->show_all();
			$this->tv->connect("button_press_event", array($this, "TV_ButtonPressed"));
		}
		
		function set_multiple_select(){
			$this->tv->get_selection()->set_mode(Gtk::SELECTION_MULTIPLE);
		}
		
		function ColClicked($col, $number){
			$this->tv->set_search_column($number);
		}
		
		function set_changed($array){
			$this->changed_array = $array;
			$this->tv->get_selection()->connect("changed", array($this, "TV_Changed"));
		}
		
		function set_dbclick($array){
			$this->dbclick_array = $array;
			$this->tv->connect("event", array($this, "TV_DbClick"));
		}
		
		function TV_ButtonPressed_Activate($entry, $rc_event){
			if (is_array($this->rcmenu_connect)){
				$eval = '$this->rcmenu_connect[0]->' . $this->rcmenu_connect[1] . '($rc_event);';
				eval($eval);
			}else{
				eval($this->rcmenu_connect . '($rc_event);');
			}
		}
		
		function TV_ButtonPressed($entry, $event){
			if ($event->button == 3){
				$this->rcmenu->popup(null, null, null, 1, $event->time);
			}
		}
		
		function TV_Changed($selection){
			$return = $this->get_value();
			
			if (is_array($this->changed_array)){
				$eval = '$this->changed_array[0]->' . $this->changed_array[1] . '($return);';
				eval($eval);
			}else{
				eval($this->changed_array . '($return);');
			}
		}
		
		function TV_DbClick($selection, $event){
			if ($event->type == 5){
				$return = $this->get_value();
				
				if (is_array($this->dbclick_array)){
					$eval = '$this->dbclick_array[0]->' . $this->dbclick_array[1] . '($return);';
					eval($eval);
				}else{
					eval($this->changed_array . '($return);');
				}
			}
		}
		
		function set_active($column, $value){
			$selection = $this->tv->get_selection();
			list($model, $iter) = $selection->get_selected();
			
			if (!is_numeric($column)){
				$count = 0;
				foreach($this->columns AS $column_arr){
					if ($column_arr->get_title() == $column){
						$column = $count;
						break;
					}
					
					$count++;
				}
				
				if (!is_numeric($column)){
					die("Could find a matching column to the given string: \"" . $column . "\".\n");
				}
			}
			
			$this->ls->set($iter, $column, $value);
		}
		
		function get_value(){
			$selection = $this->tv->get_selection();
			list($model, $iter) = $selection->get_selected();
			
			if ($iter && $model){
				for($i = 0; $i < count($this->columns); $i++){
					$value = $model->get_value($iter, $i);
					
					$return[$i] = $value;
					$return[$this->columns[$i]->get_title()] = $value;
				}
			}
			
			return $return;
		}
		
		function get_value_all(){
			list($model, $arPaths) = $this->tv->get_selection()->get_selected_rows();
			
			$count = 0;
			if ($arPaths){
				foreach($arPaths as $path){
					for($i = 0; $i < count($this->columns); $i++){
						//henter key
						$key = $this->columns[$i]->get_title();
						
						//henter value
						$iter = $model->get_iter($path);
						$value = $model->get_value($iter, $i);

						//sæter $return
						$return[$count][$i] = $value;
						$return[$count][$key] = $value;
					}
					
					$count++;
				}
			}
			
			return $return;
		}
		
		function add($values){
			$eval = '$this->ls->append(array(';
			
			$first = true;
			foreach($values AS $value){
				if ($first == true){
					$first = false;
				}else{
					$eval .= ', ';
				}
				
				$eval .= "'" . parse_quotes($value) . "'";
			}
			
			$eval .= "));";
			$state = eval($eval);
			
			if (!$state && $state !== null){
				echo $eval . "\n";
			}
		}
		
		function set_size($width, $height){
			$this->scrwin->set_size_request($width, $height);
		}
	}
?>
