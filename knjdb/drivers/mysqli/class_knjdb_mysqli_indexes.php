<?php
/**
 * TODO
 *
 * PHP version 5
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */

require_once "knj/knjdb/interfaces/class_knjdb_driver_indexes.php";

/**
 * TODO
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class knjdb_mysqli_indexes implements knjdb_driver_indexes
{
    public $knjdb;

    /**
     * TODO
     *
     * @param object $knjdb TODO
     */
    function __construct($knjdb)
    {
        $this->knjdb = $knjdb;
    }

    /**
     * TODO
     *
     * @param object $index TODO
     *
     * @return string TODO
     */
    function getIndexSQL(knjdb_index $index)
    {
        $sql = "";

        $columns = array();
        foreach ($index->getColumns() as $column) {
            $columns[] = $this->knjdb->connob->sep_col .$column->get("name")
            .$this->knjdb->connob->sep_col;
        }

        if (!$columns) {
            return $sql;
        }

        $sql = "CREATE INDEX " .$this->knjdb->connob->sep_col .$index->get("name")
        .$this->knjdb->connob->sep_col ." ON " .$this->knjdb->connob->sep_table
        .$index->getTable()->get("name") .$this->knjdb->connob->sep_table ." ("
        .implode(", ", $columns) .");\n";

        return $sql;
    }

    /**
     * TODO
     *
     * @param object $table TODO
     * @param array  $cols  TODO
     * @param string $name  TODO
     * @param array  $args  TODO
     *
     * @return string TODO
     */
    function addIndex(knjdb_table $table, $cols, $name = null, $args = null)
    {
        if (!$name) {
            $name = "index";
            foreach ($cols as $col) {
                $name .= "_" .$col->get("name");
            }
        }
        $sql = "CREATE";

        if ($args["unique"]) {
            $sql .= " UNIQUE";
        }

        $columns = array();
        foreach ($cols as $column) {
            $columns[] = $this->knjdb->connob->sep_column .$column->get("name")
            .$this->knjdb->connob->sep_column;
        }

        $sql .= " INDEX " .$this->knjdb->connob->sep_table .$name
        .$this->knjdb->connob->sep_table ." ON " .$this->knjdb->connob->sep_table
        .$table->get("name") .$this->knjdb->connob->sep_table ." ("
        .implode(", ", $columns) .")";

        if ($args["returnsql"]) {
            return $sql;
        }

        $this->knjdb->query($sql);
        $table->indexes_changed = true;
    }

    /**
     * TODO
     *
     * @param object $table TODO
     * @param object $index TODO
     *
     * @return null
     */
    function removeIndex(knjdb_table $table, knjdb_index $index)
    {
        $sql = "DROP INDEX " .$this->knjdb->conn->sep_index .$index->get("name")
        .$this->knjdb->conn->sep_index ." ON " .$this->knjdb->conn->sep_table
        .$table->get("name") .$this->knjdb->conn->sep_table;
        $this->knjdb->query($sql);
        unset($table->indexes[$index->get("name")]);
    }

    /**
     * TODO
     *
     * @param object $table TODO
     *
     * @return array TODO
     */
    function getIndexes(knjdb_table $table)
    {
        if ($table->indexes_changed) {
            $sql = "SHOW INDEX FROM " .$this->knjdb->connob->sep_table
            .$table->get("name") .$this->knjdb->connob->sep_table;
            $f_gi = $this->knjdb->query($sql);
            while ($d_gi = $f_gi->fetch()) {
                if ($d_gi["Key_name"] != "PRIMARY") {
                    $key                 = $d_gi["Key_name"];
                    $index[$key]["name"] = $d_gi["Key_name"];
                    $index[$key]["columns"][]
                        = $table->getColumn($d_gi["Column_name"]);
                }
            }

            //Making keys to numbers (as in SQLite).
            $return = array();
            if ($index) {
                foreach ($index as $name => $value) {
                    if (!$this->indexes[$name]) {
                        $table->indexes[$name] = new knjdb_index($table, $value);
                    }
                }
            }

            $table->indexes_changed = false;
        }

        return $table->indexes;
    }
}

