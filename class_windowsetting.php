<?php
//Check if function exists to make it compatible, if somehow the function already got loaded through another file.
if (!function_exists("destroy_obj")) {
    require_once "knj/functions_knj_objects.php";
}

/**
 * This class keeps track of window sizes- and positions. It also loads and restores them, when they are created again.
 */
class WindowSetting
{
    private $win;								//The reference to the window.
    private $name;								//The name identifier of the window.
    private $maximized = "false";			//If the window is maximized or not.
    private $size_width;
    private $size_height;
    private $pos_left;
    private $pos_top;

    /**
     * The constructor of the class.
     *
     * @param GtkWindow $win The window which should be manipulated.
     * @param string $name The name of the window - the identifier.
    */
    function __construct(GtkWindow $win, $name)
    {
        $this->name = $name;
        $this->win = $win;

        //Connect signals.
        $this->win->connect("size-request", array($this, "set_settings"));
        $this->win->connect("event", array($this, "event"));
        $this->win->connect("destroy", array($this, "save_settings"));

        //Get variables from database.
        $f_gwin = WS_getDBConn()->query("SELECT * FROM windows WHERE name = '" . sql($this->name) . "' LIMIT 1") or die(WS_getDBConn()->query_error());
        $d_gwin = WS_getDBConn()->query_fetch_assoc($f_gwin);

        if ($d_gwin) {
            if (is_numeric(d_gwin['size_width']) && is_numeric(d_gwin['size_height'])) {
                $this->win->resize(d_gwin['size_width'], d_gwin['size_height']);
            }

            if (is_numeric(d_gwin['pos_left']) && is_numeric(d_gwin['pos_top'])) {
                $this->win->move(d_gwin['pos_left'], d_gwin['pos_top']);
            }

            $this->size_width = d_gwin['size_width'];
            $this->size_height = d_gwin['size_height'];
            $this->pos_left = d_gwin['pos_left'];
            $this->pos_top = d_gwin['pos_top'];

            if (d_gwin['maximized'] == "true") {
                /** NOTE: The variable should not be set, since the maximize()-function triggers the setting of this. */
                $this->win->maximize();
            }
        } else {
            //The window does not exists in the database - create it.
            WS_getDBConn()->query("INSERT INTO windows (name) VALUES ('" . sql($this->name) . "')") or die(WS_getDBConn()->query_error());
            $this->win->set_position(Gtk::WIN_POS_CENTER);
        }

        $this->set_settings();
    }

    /**
     * Free resources.
     */
    function destroy()
    {
        destroy_obj($this);
    }

    /**
     * Catches random events.
     */
    function event($temp, $event)
    {
        //echo $this->name . ": " . $event->type . "\n";

        if ($event->type == 32) {
            //The window has been maximized or unmaximized.
            if ($event->new_window_state == 4) {
                $this->maximized = "true";
            } elseif ($event->new_window_state == 0) {
                $this->maximized = "false";
            }
        } elseif ($event->type == 12) {
            //Done with resizing/move/whatever - set new settings.
            $this->set_settings();
        } elseif ($event->type == 0) {
            //Closed the window - save information.
            $this->save_settings();
            $this->destroy();
        }
    }

    /**
     * Set new settings for the window.
     */
    function set_settings()
    {
        if ($this->maximized == "false") {
            $size = $this->win->get_size();
            $pos = $this->win->get_position();

            $this->size_width = $size[0];
            $this->size_height = $size[1];

            $this->pos_left = $pos[0];
            $this->pos_top = $pos[1];
        }
    }

    /**
     * Saves the settings to the database.
     */
    function save_settings()
    {
        WS_getDBConn()->query("
            UPDATE
                windows

            SET
                size_width = '" . $this->size_width . "',
                size_height = '" . $this->size_height . "',
                pos_left = '" . $this->pos_left . "',
                pos_top = '" . $this->pos_top . "',
                maximized = '" . $this->maximized . "'

            WHERE
                name = '" . sql($this->name) . "'
        ") or die(WS_getDBConn()->query_error());
    }
}

/**
 * Sets which SQLite-database that should be used for storing the window-settings.
 */
function WS_setDBConn($dbconn)
{
    global $windowsetting;
    $windowsetting["dbconn"] = $dbconn;
}

/**
 * Returns the SQLite-database.
 */
function WS_getDBConn()
{
    global $windowsetting;
    return $windowsetting["dbconn"];
}

