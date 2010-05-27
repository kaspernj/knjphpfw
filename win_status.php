<?
	/** This class shows and controls status-windows, which can be used by other classes. E.g. when backing up a database  a window with a statusbar will show. */
	class WinStatus{
		private $window;
		private $progress;
		private $label;
		private $perc;
		public $canceled = false;
		
		/** The constructor. It builds the window. */
		function __construct($args = array()){
			require_once("knjphpframework/functions_knj_gtk2.php");
			
			//Create window.
			$this->window = new GtkWindow();
			$this->window->set_title("Status");
			$this->window->set_resizable(false);
			$this->window->set_position(GTK_WIN_POS_CENTER);
			$this->window->connect("destroy", array($this, "CloseWindow"));
			$this->window->set_size_request(500, -1);
			$this->window->set_border_width(3);
			
			if ($args["window_parent"]){
				$this->window->set_transient_for($args["window_parent"]);
				$this->window->set_modal(true);
			}
			
			
			//Create progressbar.
			$adj = new GtkAdjustment(0.5, 100.0, 200.0, 0.0, 0.0, 0.0);
			$this->progress = new GtkProgressBar($adj);
			@$this->progress->set_fraction(0);
			
			
			//Create status-label.
			$this->label = new GtkLabel("Status: Waiting.");
			$this->label->set_alignment(0, 0.5);
			
			
			//Attach to window.
			$box = new GtkVBox();
			$box->pack_start($this->label, false);
			$box->pack_start($this->progress, false);
			
			if ($args["with_cancelbtn"]){
				$button_cancel = GtkButton::new_from_stock(Gtk::STOCK_CANCEL);
				$button_cancel->connect("clicked", array($this, "on_btnCancel_clicked"));
				$box->pack_start($button_cancel, false, false);
			}
			
			$this->window->add($box);
			$this->window->show_all();
		}
		
		function on_btnCancel_clicked(){
			$this->canceled = true;
		}
		
		/** Destroys the window and free's resources. */
		function CloseWindow(){
			$this->window->destroy();
			unset($this->window, $this->label, $this->progress, $this->perc);
		}
		
		/** Updates the status-text and progress-bar. */
		function setStatus($perc, $text, $doupd = false){
			if ($this->window && $this->label && $this->progress){ //prevent crashes by bad code (protect the newbies!).
				$perc = round($perc, 3);
				if ($perc != $this->perc || $doupd){
					$this->label->set_text("Status: " . $text);
					$this->perc = $perc;
					@$this->progress->set_fraction($perc);
					gtk2_refresh();
				}
			}
		}
	}
?>