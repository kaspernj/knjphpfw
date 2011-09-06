<?php
	/** Initiates a standard combobox-entry. */
	function cbe_init(GtkComboBoxEntry $cbe){
		$cr = new GtkCellRendererText();
		$cr->set_property("xalign", 0);
		$cr->set_property("yalign", 0);

		$cbe->set_model(new GtkListStore(_TYPE_STRING));
		$cbe->pack_start($cr);
		$cbe->set_attributes($cr, "text", 0);
		$cbe->connect("changed", "cbe_event", $cbe);
	}

	/** Handels the event when a standard Combobox-entry is changed - sets it to the text, that has been selected. */
	function cbe_event($button, $combobox){
		$model = $combobox->get_model();
		$iter = $combobox->get_active_iter();

		if ($iter){
			$text = $model->get_value($iter, 0);
			$combobox->get_child()->set_text(
				$text
			);
		}
	}

