<?
	/**
	 * Used for settings up a GtkComboBox() without writing tons of code.
	 * @param GtkComboBox $cb The GtkComboBox() which should be initialized.
	*/
	function combobox_init(GtkComboBox $cb){
		$cellRenderer = new GtkCellRendererText();
		$cb->set_model(new GtkListStore(_TYPE_STRING));
		$cb->pack_start($cellRenderer);
		$cb->set_attributes($cellRenderer, "text", 0);
	}
?>