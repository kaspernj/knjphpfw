<?php
/**
 * This class is basicly a GtkEntry, but implements some date-functions.
 */
class GtkEntryDate extends GtkHBox
{
    private $entry;
    private $eventm;
    private $date_unix;
    private $btn_select;
    private $eventmanager;

    /**
     * The constructor of GtkEntryDate.
     */
    function __construct()
    {
        parent::__construct();

        require_once "knj/class_knj_eventmanager.php";
        $this->eventm = new EventManager();
        $this->eventm->addEvent("changed");
        $this->eventm->addEvent("changed-manually");

        $this->entry = new GtkEntry();
        $this->entry->set_width_chars(10);
        $this->entry->connect_after("key-press-event", array($this, "on_entry_keypress"));
        $this->entry->connect_after("key-release-event", array($this, "on_entry_keyrelease"));

        $this->btn_select = new GtkButton("...");
        $this->btn_select->connect("clicked", array($this, "showDialog"));

        $this->pack_start($this->entry);
        $this->pack_start($this->btn_select, false, false);
    }

    /**
     * Returns the event-manager for this object.
     */
    function getEventManager()
    {
        return $this->eventm;
    }

    /**
     * Handels the event when a key has been pressed.
     */
    function on_entry_keypress($widget, $event)
    {
        if ($event->keyval == Gdk::KEY_F3) {
            $this->showDialog();
        }
    }

    /**
     * Handels the event when a key has been released.
     */
    function on_entry_keyrelease()
    {
        $text = $this->entry->get_text();

        if (preg_match("/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{2,4})$/", $text, $match)) {
            $this->date_unix = mktime(0, 0, 0, $match[2], $match[1], $match[3]);
        } else {
            $this->date_unix = null;
        }

        $this->eventm->callEvent("changed", $this->date_unix);
        $this->eventm->callEVent("changed-manually", $this->date_unix);
    }

    /**
     * Shows the dialog with the GtkCalendar()-object.
     */
    function showDialog()
    {
        $date_unix = msgbox(gtext("Choose date"), $this->date_unix, "date");
        if ($date_unix) {
            $this->date_unix = $date_unix;
            $this->entry->set_text(date("d-m-Y", $date_unix));
            $this->eventm->callEvent("changed", $this->date_unix);
            $this->eventm->callEVent("changed-manually", $this->date_unix);
        }
    }

    /**
     * Returns the date.
     */
    function getDate()
    {
        return $this->date_unix;
    }

    /**
     * Sets the date.
     */
    function setDate($date)
    {
        if ($date == null) {
            $this->entry->set_text("");
            $this->date_unix = null;
            $this->eventm->callEvent("changed", $this->date_unix);
            return null;
        }

        if (!is_numeric($date)) {
            throw new Exception("Please give a valid unix-timestamp.");
        }

        $this->entry->set_text(date("d-m-Y", $date));
        $this->date_unix = $date;
        $this->eventm->callEvent("changed", $this->date_unix);
    }

    /**
     * Returns the GtkEntry().
     */
    function getEntry()
    {
        return $this->entry;
    }
}

