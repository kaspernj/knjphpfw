<?php
/**
 * This class handels the individual person.
 */
class knj_gallery_person extends knjdb_row
{
    private $knj_gallery;
    public $dbconn;

    /**
     * The constructor.
     */
    function __construct($knj_gallery, $id, $data = null)
    {
        $this->knj_gallery = $knj_gallery;
        $this->dbconn = $knj_gallery->getDBConn();
        parent::__construct($this->dbconn, "persons", $id, $data);
    }

    /**
     * Returns a link to the person.
     */
    function getHTML()
    {
        return "<a href=\"?show=persons_show&person_id=" . $this->get("id") . "\">" . htmlspecialchars($this->get("callsign")) . "</a>";
    }

    /**
     * Returns the groups where this person is in.
     */
    function getGroups()
    {
        $groups = array();
        $f_gg = $this->dbconn->query("
            SELECT
                COUNT(pictures.id) AS pics_count,
                groups.*

            FROM
                pictures,
                pictures_personlinks,
                groups

            WHERE
                pictures_personlinks.person_id = '" . sql($this->get("id")) . "' AND
                pictures.id = pictures_personlinks.picture_id AND
                groups.id = pictures.group_id

            GROUP BY
                groups.date_from DESC,
                groups.title
        ");
        while ($d_gg = $f_gg->fetch()) {
            $pics_count = $d_gg["pics_count"];
            unset($d_gg["pics_count"]);

            $groups[] = array(
                "pics_count" => $pics_count,
                "group" => $this->knj_gallery->getGroup($d_gg["id"], $d_gg)
            );
        }

        return $groups;
    }

    /**
     * Calculates the age of the person based on a given timestamp.
     */
    function calculateAge($timestamp = null)
    {
        if (!is_numeric($timestamp) || !$timestamp) {
            $timestamp = time();
        }

        $birthday = strtotime($this->get("birthday"));
        $years = date("Y", $timestamp) - date("Y", $birthday);
        if (date("m", $timestamp) < date("m", $birthday) || date("m", $timestamp) <= date("m", $birthday) && date("d", $timestamp) < date("d", $birthday)) {
            $years--;
        }

        return $years;
    }

    function getView()
    {
        $d_gview = $this->dbconn->selectsingle("views", array("person_id" => $this->get("id"), "group_id" => 0), array("limit" => 1));

        if ($d_gview) {
            $view = $this->knj_gallery->getView($d_gview["id"], $d_gview);
            return $view;
        }

        //Create new.
        require_once("knj/gallery/class_knjgallery_view.php");
        $view = knj_gallery_view::createNew($this->knj_gallery, array("person_id" => $this->get("id")));
        return $view;
    }
}

