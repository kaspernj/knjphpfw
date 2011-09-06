<?
	/**
		An easy msgbox-function - Just like in Visal Basic. This makes the use of dialog-boxes a lot
		easier. Look a these examples:
		<?
			if (msgbox("Question", "Do you want to continue?", "yesno"){
				echo "You pressed yes.\n";
			}else{
				echo "You pressed no or close the question-window.\n";
			}

			$answer = msgbox("Question", "Do you want to continue?", "yesno");

			if ($answer == "yes"){
				echo "You pressed yes.\n";
			}elseif($answer == "no"){
				echo "You pressed no.\n";
			}elseif($answer == "cancel"){
				echo "You pressed cancel.\n";
			}


			$name = knj_input("Your name?", "Please enter your name");

			if ($name == "cancel"){
				echo "You closed the window.\n";
			}else{
				echo "Your name is " . $name . ".\n";
			}


			$date = msgbox("Choose date", strtotime("+5 days"), "date");
			echo date("d/m Y");
		?>
	*/
	function msgbox($title, $message, $type = "info"){
		$def_key = 0;
		if ($type == "yesno"){
			$def_key = 1;
			$items = array(Gtk::STOCK_YES, Gtk::RESPONSE_YES, Gtk::STOCK_NO, Gtk::RESPONSE_NO);
			$image = GtkImage::new_from_stock(Gtk::STOCK_DIALOG_QUESTION, Gtk::ICON_SIZE_DIALOG);
		}elseif($type == "info"){
			$items = array(Gtk::STOCK_OK, Gtk::RESPONSE_YES);
			$image = GtkImage::new_from_stock(Gtk::STOCK_DIALOG_INFO, Gtk::ICON_SIZE_DIALOG);
		}elseif($type == "warning"){
			$items = array(Gtk::STOCK_OK, Gtk::RESPONSE_YES);
			$image = GtkImage::new_from_stock(Gtk::STOCK_DIALOG_WARNING, Gtk::ICON_SIZE_DIALOG);
		}elseif($type == "date"){
			$items = array(
				Gtk::STOCK_OK, Gtk::RESPONSE_OK,
				Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL
			);
		}

		$dialog = new GtkDialog($title, null, Gtk::DIALOG_DESTROY_WITH_PARENT, $items);
		$dialog->set_title($title);
		$dialog->set_position(GTK_WIN_POS_CENTER);
		$dialog->set_has_separator(false);

		if ($type == "date"){
			$calendar = new GtkCalendar();
			if ($message){
				$calendar->select_day(date("d", $message));
				$calendar->select_month(date("m", $message) - 1, date("Y", $message));
			}
			$dialog->vbox->add($calendar);
		}else{
			$text = new GtkLabel($message);
			$text->set_alignment(0, 0.5);

			$alignment = new GtkAlignment();
			$alignment->set_padding(10, 10, 10, 20);
			$alignment->add($text);

			$box = new GtkHBox();
			$box->set_border_width(4);
			$box->pack_start($image, false);
			$box->add($alignment);

			//Set default button.
			$button_default = $dialog->action_area->get_children();
			$button_default = $ok_button[$def_key];
			$dialog->set_default($button_default);

			$dialog->vbox->add($box);
		}

		$dialog->set_modal(true);
		$dialog->show_all();
		$result = $dialog->run();
		$dialog->destroy();

		if ($type == "date"){
			if ($result == Gtk::RESPONSE_OK){
				$date = $calendar->get_date();
				$date_unix = mktime(0, 0, 0, $date[1] + 1, $date[2], $date[0]);
				return $date_unix;
			}else{
				return false;
			}
		}elseif($result == Gtk::RESPONSE_YES){
			return "yes";
		}elseif($result == Gtk::RESPONSE_NO){
			return "no";
		}else{
			return "cancel";
		}
	}

	/**
		This will prompt the user for input via a GtkEntry() (a textfield). It will halt the main-loop, until the user have enteret something
		in the GtkEntry(), and then return it.

		It makes it a lot easier to prompt the user for input.

		Example:
		<?
			$text = knj_input("Your name", "Please enter your name:");
			echo "Your name is: " . $text . "\n";
		?>
	*/
	function knj_input($title, $message, $default_value = ""){
		$text = new GtkLabel("\n" . $message . "\n");
		$text->set_alignment(0, 0.5);

		$entry = new GtkEntry();
		$entry->set_text($default_value);

		$box = new GtkVBox();
		$box->set_border_width(4);
		$box->pack_start($text, false, false);
		$box->pack_start($entry, false);

		$dialog = new GtkDialog($title, null, Gtk::DIALOG_DESTROY_WITH_PARENT, array(
			Gtk::STOCK_OK, Gtk::RESPONSE_OK,
			Gtk::STOCK_CANCEL, Gtk::RESPONSE_CANCEL
		));
		$dialog->set_position(GTK_WIN_POS_CENTER);
		$dialog->set_title($title);
		$dialog->set_has_separator(false);
		$dialog->vbox->add($box);
		$dialog->show_all();

		//Set default widget-
		$ok_button = $dialog->action_area->get_children();
		$ok_button = $ok_button[1];
		$dialog->set_default($ok_button);
		$entry->set_activates_default(true);

		$result = $dialog->run();
		$text = $entry->get_text();
		$dialog->destroy();

		if ($result == Gtk::RESPONSE_OK){
			return $text;
		}else{
			return false;
		}
	}

	/**
		This will prompt the user with a list of choices and return the chossen one (or false if the user-cancels).s
		This makes it a lot easier to prompt the user for a choice.

		Example:
		<?
			$choice = knj_listbox("Choice", "Who is your favorite hacker?",
				array(
					"Raymond, Eric" => "Eric Raymond",
					"Reveman, David" => "David Reveman",
					"Johansen, Kasper" => "Kasper Johansen"
				)
			);

			echo "You think that " . $choice . " is your favorite hacker.\n";
		?>
	*/
	function knj_listbox($title, $message, $items, $args = array()){
		$tv_items = new GtkTreeView(new GtkListStore(_TYPE_STRING, _TYPE_STRING));
		$tv_items->set_enable_search(true);
		$tv_items->set_search_column(1);
		$tv_items->append_column(
			new GtkTreeViewColumn("ID", new GtkCellRendererText(), "text", 0)
		);
		$tv_items->append_column(
			new GtkTreeViewColumn("Items", new GtkCellRendererText(), "text", 1)
		);
		$tv_items->get_column(0)->set_visible(false);

		foreach($items AS $key => $value){
			$tv_items->get_model()->append(array($key, $value));
		}

		if ($args["multiple"] == true){
			$tv_items->get_selection()->set_mode(Gtk::SELECTION_MULTIPLE);
		}

		$text = new GtkLabel("\n" . $message . "\n");
		$text->set_alignment(0, 0.5);

		$scrwin = new GtkScrolledWindow();
		$scrwin->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_ALWAYS);
		$scrwin->set_shadow_type(Gtk::SHADOW_IN);
		$scrwin->add($tv_items);

		$box = new GtkVBox();
		$box->pack_start($text, false, false);
		$box->add($scrwin);
		$box->show_all(); /** NOTE: It has to be done one the box, or the window will be hidden under modal windows. */

		$dialog = new GtkDialog($title, null, Gtk::DIALOG_DESTROY_WITH_PARENT, array(Gtk::STOCK_OK, Gtk::RESPONSE_YES, Gtk::STOCK_CANCEL, Gtk::RESPONSE_NO));
		$dialog->set_position(GTK_WIN_POS_CENTER);
		$dialog->set_title($title);
		$dialog->set_has_separator(false);
		$dialog->set_size_request(300, 300);
		$dialog->vbox->add($box);
		$result = $dialog->run();

		$return = false;
		if ($result == Gtk::RESPONSE_YES){
			if ($args["multiple"] == true){
				require_once("knj/functions_treeview.php");
				$rows = $tv_items->get_selection()->get_selected_rows();

				if ($rows[1]){
					$return = array();
					foreach($rows[1] AS $key => $value){
						$iter = $tv_items->get_model()->get_iter($value);
						$return[] = $tv_items->get_model()->get_value($iter, 0);
					}
				}
			}else{
				$columns = $tv_items->get_columns();
				$selection = $tv_items->get_selection();
				list($model, $iter) = $selection->get_selected();

				if ($iter && $model){
					$return = $model->get_value($iter, 0);
				}
			}
		}

		$dialog->destroy();
		return $return;
	}

	/** The class is used to save files in a very one-line and simple way. Use the static function newDialog() to spawn it. */
	class dialog_saveFile{
		private $dialog_export;
		public $cancel = false;
		public $filename;

		/** The constructor of dialog_saveFile. */
		function __construct(){
			$button_save = GtkButton::new_from_stock(Gtk::STOCK_SAVE);
			$button_save->connect("clicked", array($this, "on_btnSave_clicked"));

			$button_close = GtkButton::new_from_stock(Gtk::STOCK_CANCEL);
			$button_close->connect("clicked", array($this, "on_btnClose_clicked"));

			$box = new GtkHButtonBox();
			$box->set_spacing(4);
			$box->set_layout(Gtk::BUTTONBOX_END);
			$box->pack_start($button_close);
			$box->pack_start($button_save);

			$this->dialog_export = new GtkFileChooserDialog();
			//$this->dialog_export->set_default($button_save);

			/** FIXME: This code should make the save-button default, but I did not have enough time... */
			//$vbox = $this->dialog_export->get_children();
			//$fc = $vbox[0]->get_children();
			//$fc = $fc[0];
			//$children = $fc->get_children();

			//foreach($children AS $widget){
			//	echo get_class($widget) . "\n";
			//}

			$this->dialog_export->set_action(Gtk::FILE_CHOOSER_ACTION_SAVE);
			$this->dialog_export->set_title("Save file");
			$this->dialog_export->set_extra_widget($box);
			$this->dialog_export->set_position(Gtk::WIN_POS_CENTER);
			$this->dialog_export->show_all();
			$this->dialog_export->run();
		}

		/** Handels the event, when the save-button has been clicked. */
		function on_btnSave_clicked(){
			$this->filename = $this->dialog_export->get_filename();
			$this->dialog_export->destroy();

			if (file_exists($this->filename)){
				if (msgbox(gtext("Question"), gtext("Do you want to replace the file?"), "yesno") != "yes"){
					$this->cancel = true;
					return false;
				}
			}
		}

		/** Handels the event, when the close button has been clicked. */
		function on_btnClose_clicked(){
			$this->cancel = true;
			$this->dialog_export->destroy();
		}

		/** Used to make the class work like a one-line-command. */
		static function newDialog(){
			$obj = new dialog_saveFile();
			if ($obj->cancel){
				return false;
			}

			return $obj->filename;
		}
	}

	class knj_msgbox{
		/** Shows a proper error-message. */
		static function error_exc($e){
			global $knj_msgbox;

			if (!$knj_msgbox["error_exc"]["title"]){
				$title = "Warning";
			}else{
				$title = $knj_msgbox["error_exc"]["title"];
			}

			if (!$knj_msgbox["error_exc"]["premsg"]){
				$msg = "An error occurred:\n\n%s";
			}else{
				$msg = $knj_msgbox["error_exc"]["premsg"];
			}

			msgbox($title, sprintf($msg, $e->getMessage()), "warning");
		}

		/** Kills the application - but shows a Gtk-box with the message first. */
		static function gtk_die($msg){
			global $knj_msgbox;

			if ($knj_msgbox["error_exc"]["title"]){
				$title = $knj_msgbox["error_exc"]["title"];
			}else{
				$title = "Warning";
			}

			msgbox($title, $msg, "warning");
			die($msg . "\n");
		}

		static function input($title, $message, $default_value = ""){
			return knj_input($title, $message, $default_value);
		}

		static function listbox($title, $message, $items, $args = array()){
			return knj_listbox($title, $message, $items, $args);
		}
	}

