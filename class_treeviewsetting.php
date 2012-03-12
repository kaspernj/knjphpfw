<?php
/**
 * This class remembers the settings for a GtkTreeview.
 */
class TreeviewSetting
{
    private $name;
    private $tv;
    private $cols;
    private $pressed;

    /**
     * The constructor of TreeviewSetting.
     */
    function __construct(GtkTreeView $tv, $name, $args = array())
    {
        $this->name = $name;
        $this->tv = $tv;
        $this->tv->connect("button-press-event", array($this, "on_tv_buttonpress"));
        $this->tv->connect("button-release-event", array($this, "on_tv_buttonrelease"));

        if ($args) {
            foreach ($args as $key => $value) {
                if ($key == "dbconn") {
                    $this->db = $value;
                } elseif ($key == "defaults") {
                    if (!is_array($value)) {
                        throw new Exception("The defaults-argument only accepts an array.");
                    }

                    $defaults = $value;
                } else {
                    throw new Exception("Invalid argument: " . $key);
                }
            }
        }

        if (!$this->db) {
            $this->db = TreeviewSetting::getDBConn();
        }


        //Check if the name exists in the database.
        while (!$this->tv_data) {
            $f_gtvs = $this->db->query("SELECT * FROM treeviews WHERE name = '" . sql($name) . "' LIMIT 1") or die($this->db->query_error());
            $this->tv_data = $this->db->query_fetch_assoc($f_gtvs);

            if (!$this->tv_data) {
                $read_defaults = true;
                $this->db->query("INSERT INTO treeviews (name) VALUES ('" . sql($name) . "')") or die($this->db->query_error());
            }
        }


        //Read columns.
        $cols = $this->tv->get_columns();
        $count = 0;
        foreach ($cols as $col) {
            if ($col->get_visible()) {
                $this->cols[$count] = $col;
                $this->cols[$count]->thacount = $count;
                $this->cols[$count]->connect("clicked", array($this, "on_col_clicked"));
            }

            $count++;
        }


        //Count and reset, if count is not the same.
        $f_gc = $this->db->query("SELECT COUNT(id) AS tha_count FROM treeviews_columns WHERE treeview_id = '" . $this->tv_data["id"] . "' GROUP BY treeview_id") or die($this->db->query_error());
        $d_gc = $this->db->query_fetch_assoc($f_gc);

        if (count($this->cols) != $d_gc["tha_count"]) {
            echo "Warning: Columns-count doesnt match for the treeview: \"" . $this->name . "\". Resetting column-data.\n";
            $this->db->query("DELETE FROM treeviews_columns WHERE treeview_id = '" . $this->tv_data["id"] . "'") or die($this->db->query_error());
            $read_defaults = true;
        }

        foreach ($this->cols as $key => $col) {
            //Check if column exists.
            $f_gcol = $this->db->query("SELECT * FROM treeviews_columns WHERE treeview_id = '" . $this->tv_data["id"] . "' AND column_id = '$key' LIMIT 1") or die($this->db->query_error());
            $d_gcol = $this->db->query_fetch_assoc($f_gcol);

            if (!$d_gcol) {
                $this->db->query("INSERT INTO treeviews_columns (treeview_id, column_id, visible, width) VALUES ('" . $this->tv_data["id"] . "', '$key', '1', '0')") or die($this->db->query_error());
            } else {
                if (!$d_gcol["visible"]) {
                    $col->set_visible(false);
                }

                echo "Key: \"" . $key . "\".\nMax: \"" . $max . "\".\n";
                if ($d_gcol["width"] > 0 && $d_gcol["width"] != $col->get_width() && $key != $max) {
                    $col->set_sizing(Gtk::TREE_VIEW_COLUMN_FIXED);
                    $col->set_fixed_width($d_gcol["width"]);
                }
            }
        }



        //If this is the first time the treeview is loaded with treeview-settings then load the defaults (if any).
        if ($read_defaults && $defaults) {
            foreach ($defaults as $col => $value) {
                if (!is_numeric($col)) {
                    throw new Exception("The key has to be a number.");
                }

                if ($value == "hidden") {
                    $this->editColumn($col, array("hidden" => true));
                }
            }
        }


        //Set saved sort.
        if (strlen($this->tv_data["sort_column"]) > 0) {
            $sort_column = $this->tv_data["sort_column"];
            $sort_order = $this->tv_data["sort_order"];

            if ($sort_order == 0) {
                $this->tv->get_model()->set_sort_column_id($sort_column, Gtk::SORT_ASCENDING);
            } elseif ($sort_order == 1) {
                $this->tv->get_model()->set_sort_column_id($sort_column, Gtk::SORT_DESCENDING);
            } else {
                echo "Warning: Unknown sort-order: \"" . $sort_order . ".\n";
            }
        }
    }

    /**
     * Destructor - saves column-setting when the object is destroyed.
     */
    function __destruct()
    {
        foreach ($this->cols as $count => $col) {
            if ($col->get_visible()) {
                $visible = "1";
            } else {
                $visible = "0";
            }

            $this->db->query("UPDATE treeviews_columns SET width = '" . $col->get_width() . "', visible = '$visible' WHERE treeview_id = '" . $this->tv_data["id"] . "' AND column_id = '" . $count . "'") or die($this->db->query_error());
        }
    }

    /**
     * Handels the event when the treeview is clicked.
     */
    function on_tv_buttonpress($widget, $event)
    {
        if ($event->button == 1) {
            $this->pressed[$event->button] = true;
        }

        if ($this->pressed[1] == true && $event->button == 3) {
            $this->showPopupMenu();
            return true;
        }
    }

    /**
     * Handels the event when a button is released on the treeview.
     */
    function on_tv_buttonrelease($widget, $event)
    {
        if ($event->button == 1) {
            unset($this->pressed[$event->button]);
        }
    }

    /**
     * Updates the sort.
     */
    function updateSort()
    {
        /*
        $cols = $this->tv->get_columns();
        $count = 0;
        foreach ($cols as $col) {
            echo "Col " . $col->thacount . " is now " . $count . "\n";
            $count++;
        }
        */
    }

    /**
     * Handels the event when a column has been clicked.
     */
    function on_col_clicked($widget)
    {
        $column_id = $widget->thacount;
        $search_column = $this->tv->get_search_column();
        $sort_order = $widget->get_sort_order();

        $this->db->query("UPDATE treeviews SET sort_column = '$column_id', sort_order = '$sort_order' WHERE name = '" . sql($this->name) . "'") or die($this->db->query_error());
    }

    /**
     * Handels the event when a column has been clicked.
     */
    function showPopupMenu()
    {
        $arr_popup = array();
        foreach ($this->cols as $count => $col) {
            $arr_popup[$count] = array(
                "type" => "checkitem",
                "text" => $col->get_title(),
                "active" => $col->get_visible()
            );
        }

        require_once "knj/class_knj_popup.php";
        $popup = new knj_popup(
            $arr_popup,
            array(
                $this,
                "on_popup_choose"
            )
        );

        unset($this->pressed[1]);

        return true;
        //echo "Count: " . $widget->thacount . "\n";
        //$this->updateSort();

        //echo "Count 7 is now: " . $this->tv->get_column(7)->thacount . "\n";
    }

    /**
     * Handels the event when an item is activated.
     */
    function on_popup_choose($key, $activated)
    {
        if ($activated) {
            $args["visible"] = true;
        } else {
            $args["hidden"] = true;
        }

        $this->editColumn($key, $args);
    }

    /**
     * Edits a column.
     */
    function editColumn($col, $args)
    {
        if ($args["visible"] == true) {
            $this->cols[$col]->set_visible(true);
        } elseif ($args["hidden"] == true) {
            $this->cols[$col]->set_visible(false);
        }
    }

    /**
     * Sets the DBConn (database-connection) to use.
     */
    static function setDBConn(DBConn $dbconn)
    {
        global $class_treeviewsetting;
        $class_treeviewsetting["dbconn"] = $dbconn;
    }

    /**
     * Returns the default DBConn-connection used by treeview-settings.
     */
    static function getDBConn()
    {
        global $class_treeviewsetting;
        return $class_treeviewsetting["dbconn"];
    }
}

