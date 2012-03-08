<?php
require_once("knj/class_exceptions.php");

/**
 * This object handels paned-settings.
 */
class GtkSettingsPaned
{
    private $paned;
    private $idname;
    private $dbconn;

    /**
     * Handels when the object is spawned.
     */
    function __construct(GtkPaned $paned, $idname)
    {
        global $gtksettings_paned;

        $this->paned = $paned;
        $this->idname = $idname;

        $this->dbconn = GTKSettingsPaned::getDBConn();
        $this->destroy_handler = $this->paned->connect("destroy", array($this, "destroy"));

        $position = $this->getSavedPos();
        if ($position) {
            $this->paned->set_position($position->get("position"));
        }
    }

    function destroy()
    {
        $this->paned->disconnect($this->destroy_handler);
        unset($this->paned, $this->idname, $this->dbconn, $this->destroy_handler);
    }

    /**
     * Handels the event when the object is destroyed (or the application ends).
     */
    function __destruct()
    {
        if ($this->destroy_handler) {
            $this->paned->disconnect($this->destroy_handler);
        }

        if ($this->paned && $this->dbconn) {
            $pos = $this->paned->get_position();
            $saved = $this->getSavedPos();

            if ($saved) {
                $saved->setData(array("position" => $pos));
            } else {
                $this->dbconn->insert("gtksettings_paned", array(
                        "idname" => $this->idname,
                        "position" => $pos
                    )
                );
            }
        }
    }

    /**
     * Returns the saved-pos-object - if any.
     */
    function getSavedPos()
    {
        $res = $this->dbconn->selectfetch("gtksettings_paned", array("idname" => $this->idname), array("limit" => 1));
        $res = $res[0];

        return $res;
    }

    static function setDBConn($dbconn)
    {
        global $gtksettings_paned;
        $gtksettings_paned["dbconn"] = $dbconn;
    }

    static function getDBConn()
    {
        global $gtksettings_paned;
        return $gtksettings_paned["dbconn"];
    }
}

