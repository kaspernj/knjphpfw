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

/**
 * TODO
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class knjdb_mysqli_tables implements knjdb_driver_tables
{
    public $knjdb;
    public $tables = array();
    public $tables_changed = true;

    /**
     * TODO
     *
     * @param object $knjdb TODO
     */
    function __construct(knjdb $knjdb)
    {
        $this->knjdb = $knjdb;
    }

    /**
     * TODO
     *
     * @return TODO
     */
    function getTables()
    {
        if ($this->tables_changed) {
            $f_gt = $this->knjdb->query("SHOW TABLE STATUS");
            while ($d_gt = $f_gt->fetch()) {
                if (!$this->tables[$d_gt["Name"]]) {
                    $data = array(
                        "name"      => $d_gt["Name"],
                        "engine"    => $d_gt["Engine"],
                        "collation" => $d_gt["Collation"],
                        "rows"      => $d_gt["Rows"]
                    );
                    $this->tables[$d_gt["Name"]] = new knjdb_table(
                        $this->knjdb,
                        $data
                    );
                }
            }

            $this->tables_changed = false;
        }

        return $this->tables;
    }

    /**
     * TODO
     *
     * @param object $table   TODO
     * @param string $newname TODO
     *
     * @return string TODO
     */
    function renameTable(knjdb_table $table, $newname)
    {
        $this->knjdb->query(
            "ALTER TABLE " .$this->knjdb->conn->sep_table .$table->get("name")
            .$this->knjdb->conn->sep_table ." RENAME TO "
            .$this->knjdb->conn->sep_table .$newname .$this->knjdb->conn->sep_table
        );

        unset($this->tables[$table->get("name")]);
        $table->data["name"] = $newname;
        $this->tables[$newname] = $table;
    }

    /**
     * TODO
     *
     * @param string $tablename TODO
     * @param string $cols      TODO
     * @param string $args      TODO
     *
     * @return string TODO
     */
    function createTable($tablename, $cols, $args = null)
    {
        $sql = "CREATE";

        if ($args["temp"]) {
            $sql .= " TEMPORARY";
        }

        $sql .= " TABLE " .$this->knjdb->conn->sep_table .$tablename
        .$this->knjdb->conn->sep_table ." (";
        $prim_keys = array();

        $first = true;
        foreach ($cols as $col) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->knjdb->columns()->getColumnSQL($col);
        }
        $sql .= ")";

        if ($args["returnsql"]) {
            return $sql;
        }

        $this->knjdb->query($sql);
        $this->tables_changed = true;
    }

    /**
     * TODO
     *
     * @param object $table TODO
     *
     * @return null
     */
    function dropTable(knjdb_table $table)
    {
        $this->knjdb->query(
            "DROP TABLE " .$this->knjdb->conn->sep_table .$table->get("name")
            .$this->knjdb->conn->sep_table
        );
        unset($this->tables[$table->get("name")]);
    }

    /**
     * TODO
     *
     * @param object $table TODO
     *
     * @return null
     */
    function truncateTable(knjdb_table $table)
    {
        $this->knjdb->query(
            "TRUNCATE " .$this->knjdb->conn->sep_table .$table->get("name")
            .$this->knjdb->conn->sep_table
        );
    }

    /**
     * Vacuum the entire database.
     *
     * @param object $table TODO
     *
     * @return null
     */
    function optimizeTable(knjdb_table $table)
    {
        $this->knjdb->query(
            "OPTIMIZE TABLE " .$this->knjdb->conn->sep_table .$table->get("name")
            .$this->knjdb->conn->sep_table
        );
    }
}

