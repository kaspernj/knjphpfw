<?
	/**
	 * Returns the text from a textview.
	 * @param GtkTextView $tv The textview where the text should be read from.
	*/
	function textview_gettext(GtkTextView $tv){
		return $tv->get_buffer()->get_text(
			$tv->get_buffer()->get_start_iter(),
			$tv->get_buffer()->get_end_iter()
		);
	}

