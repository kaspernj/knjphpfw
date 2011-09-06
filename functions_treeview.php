<?
	/**
	 * This returns the selected item for a GtkTreeView(). Saves code.
	 * @param GtkTreeView $treeview The treeview for which selection, the function should return.
	*/
	function treeview_getSelection(GtkTreeView $treeview){
		$columns = $treeview->get_columns();
		$selection = $treeview->get_selection(); //GtkTreeSelection().
		$return = array();

		if ($selection->get_mode() == Gtk::SELECTION_MULTIPLE){
			$rows = $selection->get_selected_rows();
			if ($rows[1]){
				$count = 0;
				foreach($rows[1] AS $key => $value){
					$iter = $treeview->get_model()->get_iter($value);

					foreach($columns AS $i => $column){
						$value = $treeview->get_model()->get_value($iter, $i);
						$return[$count][$i] = $value;
						$return[$count][$column->get_title()] = $value;
					}

					$count++;
				}
			}
		}else{
			list($model, $iter) = $selection->get_selected();

			if ($iter && $model){
				foreach($columns AS $i => $column){
					$value = $model->get_value($iter, $i);
					$return[$i] = $value;
				}
			}
		}

		return $return;
	}

	/** NOTE: Since iter_parent() is buggy, we need to do it this idiotic way. */
	function treeview_moveUp($tv){
		$tv_model = $tv->get_model();

		if (method_exists($tv_model, "foreach")){ //crashes with new version of PHP-GTK.
			global $treeview_loop_iter_parent, $treeview_loop_current_file, $treeview_loop_set;
			$iter_current = $tv->get_selection()->get_selected();

			if ($iter_current){
				$treeview_loop_current_file = $tv_model->get_value($iter_current[1], 0);
				$treeview_loop_set = false;
				$treeview_loop_iter_parent = null;
				$tv_model->foreach("treeview_select_parent_loop", $tv);

				if (!$treeview_loop_set){
					$iter = $tv_model->get_iter_first();
					if ($iter){
						$tv->get_selection()->select_iter($iter);
						$path = $tv_model->get_path($iter);
						$tv->scroll_to_cell($path);
					}
				}
			}
		}else{
			//a bit slow way to do it - but since foreach() doesnt exeist and iter_parent() is buggy - this is the only way :'-(
			$all = treeview_getAll($tv);
			$sel = treeview_getSelection($tv);

			foreach($all AS $key => $values){
				if ($values["values"][0] == $sel[0] && $values["values"][1] == $sel[1]){
					$iter = $parent_iter;
					$tv->get_selection()->select_iter($iter);
					$path = $tv_model->get_path($iter);
					$tv->scroll_to_cell($path);
					return true;
				}

				$parent_iter = $values["iter"];
			}
		}

		return true;
	}

	function treeview_select_parent_loop($store, $path, $iter, $tv){
		global $treeview_loop_iter_parent, $treeview_loop_current_file, $treeview_loop_set;
		$file = $store->get_value($iter, 0);

		if ($file == $treeview_loop_current_file && $treeview_loop_iter_parent){
			$tv->get_selection()->select_iter($treeview_loop_iter_parent);
			$treeview_loop_set = true;

			//scroll to the active row.
			$path = $store->get_path($treeview_loop_iter_parent);
			$tv->scroll_to_cell($path);

			return true; //stops loop.
		}else{
			$treeview_loop_iter_parent = $iter;
		}
	}

	function treeview_moveDown($tv){
		$iter_current = $tv->get_selection()->get_selected();
		$tv_model = $tv->get_model();

		if ($iter_current[1]){
			$iter_next = $tv_model->iter_next($iter_current[1]);

			if ($iter_next){
				$tv->get_selection()->select_iter($iter_next);

				//scroll to the active row.
				$path = $tv_model->get_path($iter_next);
				$tv->scroll_to_cell($path);
			}
		}

		return true;
	}

	/**
	 * This returns the selected items for a GtkTreeView() (if it is possible to select more items). Saves code.
	 * @param GtkTreeView $treeview The treeview for which the function should return values.
	*/
	function treeview_getAll(GtkTreeView $treeview){
		$columns = $treeview->get_columns();
		$model = $treeview->get_model();

		$return = array();
		$first = true;
		while(true){
			if ($first == true){
				$iter = @$model->get_iter_first();
				$first = false;
			}else{
				$iter = $model->iter_next($iter);
			}

			if (!$iter){
				break;
			}

			foreach($columns AS $key => $column){
				$value = $model->get_value($iter, $key);
				$return_new[$key] = $value;
			}

			$return[] = array("values" => $return_new, "iter" => $iter);
		}

		return $return;
	}

	/**
	 * Add a column (or more columns if you give it an array instead of a string) to a GtkTreeView(). Saves code.
	 * @param GtkTreeView $treeview The treeview which the function should add columns to.
	 * @param mixed $arrcolumn As a string, this would be the title of the one column added. As an array, this should be all the titles which should be added to the treeview as strings in the array.
	*/
	function treeview_addColumn(GtkTreeView $treeview, $arrcolumn){
		if (is_array($arrcolumn) && !$arrcolumn["type"] && !$arrcolumn["title"]){
			//Adding a liststore to the treeview.
			$eval = "\$ls = new GtkListStore(";
			$first = true;
			foreach($arrcolumn AS $column){
				if ($first == true){
					$first = false;
				}else{
					$eval .= ", ";
				}

				if (!is_array($column)){
					$type = "text";
				}else{
					$type = $column["type"];
				}

				if ($type == "text"){
					$eval .= "_TYPE_STRING";
				}elseif($type == "active"){
					$eval .= "_TYPE_BOOLEAN";
				}elseif($type == "int"){
					$eval .= "_TYPE_LONG";
				}else{
					throw new Exception("Invalid type: " . $type);
				}

				$count++;
			}
			$eval .= ");";
			eval($eval);

			$treeview->set_model($ls);
			$treeview->set_enable_search(true);

			//Add columns to the treeview.
			foreach($arrcolumn AS $value){
				treeview_addColumn($treeview, $value);
			}
		}else{
			$number = count($treeview->get_columns());

			if (is_array($arrcolumn)){
				$type = $arrcolumn["type"];
				$title = $arrcolumn["title"];
			}else{
				$title = $arrcolumn;
				$type = "text";
			}

			if ($type == "text" || $type == "int"){
				$render = new GtkCellRendererText();
				$type = "text";
			}elseif($type == "active"){
				$render = new GtkCellRendererToggle();
				$render->set_property("activatable", true);
			}else{
				throw new Exception("Invalid type: " . $type);
			}

			if (is_array($arrcolumn["connect"])){
				foreach($arrcolumn["connect"] AS $key => $value){
					$render->connect_after($key, $value);
				}
			}

			$column = new GtkTreeViewColumn($title, $render, $type, $number);
			$column->set_reorderable(true);
			$column->set_resizable(true);
			$column->set_clickable(true);
			$column->set_sort_column_id($number);
			$treeview->append_column($column);

			if (is_array($arrcolumn)){
				if ($arrcolumn["hidden"] == true){
					$column->set_visible(false);
				}

				if ($arrcolumn["searchcol"] == true){
					$treeview->set_search_column($number);
				}

				if ($arrcolumn["sortcol"] == true){
					if ($arrcolumn["sort"] == "desc"){
						$treeview->get_model()->set_sort_column_id($number, Gtk::SORT_DESCENDING);
					}else{
						$treeview->get_model()->set_sort_column_id($number, Gtk::SORT_ASCENDING);
					}
				}
			}
		}
	}

	/** Returns a line in a treeview. */
	function treeview_getLine(GtkTreeView $tv, $col_no, $col_val, $where){
		$all = treeview_getAll($tv);

		foreach($all AS $key => $value){
			if ($value[values][$col_no] == $col_val){
				//found.
				$return = $key + $where;
				return $all[$return];
			}
		}
	}

