<?php
/**
 * This class makes a popup-menu and handels the signals for it.
 */
class knj_popup extends GtkMenu
{
    /**
     * The constructor of knj_popup.
     */
    function __construct($menu_items, $connect)
    {
        parent::__construct();

        $this->connect_info = $connect;
        foreach ($menu_items as $key => $value) {
            if (!is_array($value)) {
                $value = array(
                    "type" => "menuitem",
                    "text" => $value
                );
            }

            if ($value["type"] == "menuitem") {
                $this->opt[$key] = new GtkMenuItem($value["text"]);
                $this->opt[$key]->connect("activate", array($this, "ItemActivate"), $key);
            } elseif ($value["type"] == "checkitem") {
                $this->opt[$key] = new GtkCheckMenuItem($value["text"], false);

                if ($value["active"]) {
                    $this->opt[$key]->set_active(true);
                }

                $this->opt[$key]->connect("toggled", array($this, "CheckActivate"), $key);
            }

            $this->append($this->opt[$key]);
        }

        $this->show_all();
        $this->popup(null, null, null, 1);
    }

    /**
     * Handels the event when an item is activated.
     */
    function ItemActivate($data, $mode)
    {
        call_user_func($this->connect_info, $mode);
    }

    /**
     * Handels the event when a check-item is activated.
     */
    function CheckActivate($widget, $key)
    {
        call_user_func($this->connect_info, $key, $this->opt[$key]->get_active());
    }
}

