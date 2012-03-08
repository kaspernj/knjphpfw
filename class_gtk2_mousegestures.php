<?php
/**
 * This class reads input directly from the mouse and parses it into events that can be used to control an application.
 */
class GtkMouseGestures
{
    public $em; //EventManager-object - use this to catch the events.
    private $proc;

    function __construct()
    {
        require_once("knj/class_knj_eventmanager.php");
        $this->em = new EventManager();
        $this->em->addEvent(array("mouseclick", "north", "east", "south", "west", "northeast", "northwest", "southeast", "southwest"));

        $this->proc = fopen("/dev/input/mice", "r") or die();

        Gtk::io_add_watch($this->proc, GObject::IO_IN, array($this, "on_mouseevent_do"));
    }

    function on_mouseevent_do($fp)
    {
        $m1 = ord(fread($fp, 1));
        $m2 = ord(fread($fp, 1));
        $m3 = ord(fread($fp, 1));

        if ($m1 == 9) {
            $this->em->callEvent("mouseclick", "leftclick");
        } elseif ($m1 == 10) {
            $this->em->callEvent("mouseclick", "rightclick");
        } elseif ($m1 == 12) {
            $this->em->callEvent("mouseclick", "middleclick");
        }

        /*
            //Debug.
            echo "m1: " . $m1 . "\n";
            echo "m2: " . $m2 . "\n";
            echo "m3: " . $m3 . "\n";
            echo "\n";
        */

        if ($m2 >= 0 && $m2 <= 100) {
            $x = $m2;
        } else {
            $x = -(255 - $m2) - 1;
        }

        if ($m3 >= 0 && $m3 <= 100) {
            $y = $m3;
        } else {
            $y = -(255 - $m3) - 1;
        }

        $orien = "";
        if ($y > 0) {
            $orien = "north";
        } elseif ($y < 0) {
            $orien = "south";
        }

        if ($x > 0) {
            $orien .= "east";
        } elseif ($x < 0) {
            $orien .= "west";
        }

        $event = array(
            "x" => $x,
            "y" => $y
        );
        $this->em->callEvent($orien, $event);

        return true;
    }

    function easyBindStart()
    {
        if (!$this->binds) {
            $this->binds = true;
            $this->em->addEvent(array("eb_up", "eb_right", "eb_down", "eb_left"));
            $this->em->connect(array("north", "east", "south", "west", "northeast", "northwest", "southeast", "southwest"), array($this, "easyBind_event"));
        }
    }

    function easyBind_event($event, $data)
    {
        $new_t = microtime(true);
        $factor = 75;

        if (($new_t - $this->eb_t) > 0.5) {
            $this->eb_x = 0;
            $this->eb_y = 0;
        }

        $this->eb_x += $data["x"];
        $this->eb_y += $data["y"];
        $this->eb_t = $new_t;

        if ($this->eb_x > 0 && $this->eb_x > $factor) {
            $this->eb_x = 0;
            $this->em->callEvent("eb_right");
        }

        if ($this->eb_x < 0 && $this->eb_x < -$factor) {
            $this->eb_x = 0;
            $this->em->callEvent("eb_left");
        }

        if ($this->eb_y > 0 && $this->eb_y > $factor) {
            $this->eb_y = 0;
            $this->em->callEvent("eb_up");
        }

        if ($this->eb_y < 0 && $this->eb_y < -$factor) {
            $this->eb_y = 0;
            $this->em->callEvent("eb_down");
        }
    }
}

