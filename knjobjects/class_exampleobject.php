<?php
class folder extends knjdb_row
{
    static $table = "folders";

    function __construct($id, $data)
    {
        parent::__construct(gDB(), "folders", $id, $data);
    }

    static function addNew($arr)
    {
        gDB()->insert($arr);
        return getSharedOb("folder", gDB()->getLastID());
    }

    static function getList()
    {
        $return = array();
        $f_gdata = gDB()->select(folder::table, null, array("orderby" => "title"));
        while ($d_gdata = $f_gdata->fetch()) {
            $return[] = getSharedOb("folder", $d_gdata);
        }

        return $return;
    }

    function doDelete()
    {
        $this->dbconn->delete(folder::table, array("id" => $this->get("id")));
        unsetOb($this);
    }

    function getHTML($paras = null)
    {
        if (!$paras["key"]) {
            $paras["key"] = "title";
        }

        $title = $this->get($paras["key"]);

        if ($paras["maxlength"]) {
            if (strlen($title) > $paras["maxlength"]) {
                require_once "knj/functions_knj_strings.php";
                $title = trim(mb_substr($title, 0, $paras["maxlength"] - 1)) ."…";
            }
        }

        return htmlspecialchars($title);
    }
}

