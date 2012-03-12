<?php
/**
 * This class shows and controls status-windows, which can be used by other classes. E.g. when backing up a database  a window with a statusbar will show.
 */
class WinStatus
{
    private $_window;
    private $_progress;
    private $_label;
    private $_perc;
    public $canceled = false;

    /**
     * The constructor. It builds the window.
     */
    function __construct($args = array())
    {
        include_once "knj/functions_knj_gtk2.php";

        //Create window.
        $this->_window = new GtkWindow();
        $this->_window->set_title("Status");
        $this->_window->set_resizable(false);
        $this->_window->set_position(GTK_WIN_POS_CENTER);
        $this->_window->connect("destroy", array($this, "CloseWindow"));
        $this->_window->set_size_request(500, -1);
        $this->_window->set_border_width(3);

        if ($args["window_parent"]) {
            $this->_window->set_transient_for($args["window_parent"]);
            $this->_window->set_modal(true);
        }


        //Create progressbar.
        $adj = new GtkAdjustment(0.5, 100.0, 200.0, 0.0, 0.0, 0.0);
        $this->_progress = new GtkProgressBar($adj);
        @$this->_progress->set_fraction(0);


        //Create status-label.
        $this->_label = new GtkLabel("Status: Waiting.");
        $this->_label->set_alignment(0, 0.5);


        //Attach to window.
        $box = new GtkVBox();
        $box->pack_start($this->_label, false);
        $box->pack_start($this->_progress, false);

        if ($args["with_cancelbtn"]) {
            $button_cancel = GtkButton::new_from_stock(Gtk::STOCK_CANCEL);
            $button_cancel->connect("clicked", array($this, "on_btnCancel_clicked"));
            $box->pack_start($button_cancel, false, false);
        }

        $this->_window->add($box);
        $this->_window->show_all();
    }

    function on_btnCancel_clicked()
    {
        $this->canceled = true;
    }

    /**
     * Destroys the window and free's resources.
     */
    function CloseWindow()
    {
        $this->_window->destroy();
        unset($this->_window, $this->_label, $this->_progress, $this->_perc);
    }

    /**
     * Updates the status-text and progress-bar.
     */
    function setStatus($perc, $text, $doupd = false)
    {
        //prevent crashes by bad code (protect the newbies!).
        if ($this->_window && $this->_label && $this->_progress) {
            $perc = round($perc, 3);
            if ($perc != $this->_perc || $doupd) {
                $this->_label->set_text("Status: " . $text);
                $this->_perc = $perc;
                @$this->_progress->set_fraction($perc);
                gtk2_refresh();
            }
        }
    }
}

