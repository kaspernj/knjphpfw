<?
	/** Refreshes all the Gtk2-widgets if the thread is locked (by while, for, foreach or whatever). */
	function gtk2_refresh(){
		while(gtk::events_pending()){
			gtk::main_iteration();
		}
	}

