<?php
class knjdb_sqlite2_tables implements knjdb_driver_tables
{
    private $knjdb;
    private $tables_changed = true;
    public $tables = array();

    function __construct(knjdb $knjdb)
    {
        $this->knjdb = $knjdb;
    }

    function getTables()
    {
        if ($this->tables_changed) {
            $f_gt = $this->knjdb->select("sqlite_master", array("type" => "table"), array("orderby" => "name"));
            while ($d_gt = $f_gt->fetch()) {
                if ($d_gt["name"] != "sqlite_sequence" && !$this->tables[$d_gt["name"]]) {
                    $this->tables[$d_gt["name"]] = new knjdb_table($this->knjdb, array(
                            "name" => $d_gt["name"],
                            "engine" => "sqlite",
                            "collation" => "sqlite"
                        )
                    );
                }
            }

            $this->tables_changed = false;
        }

        return $this->tables;
    }

    function createTable($tablename, $columns, $args = null)
    {
        $sql = "CREATE TABLE " . $this->knjdb->connob->sep_table . $tablename . $this->conn->sep_table . " (";
        $prim_keys = array();

        $first = true;
        foreach ($columns as $column) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->knjdb->columns()->getColumnSQL($column);
        }
        $sql .= ");";

        if ($args["returnsql"]) {
            return $sql;
        }

        $this->knjdb->query($sql);
        $this->tables_changed = true;
    }

    function renameTable(knjdb_table $table, $newname)
    {
        //Fuck you very much SQLite. This is just pure pain... No "ALTER TABLE" :'(
        $indexes = $table->getIndexes();
        $columns = $table->getColumns();

        $newcols = array();
        foreach ($columns as $column) {
            $newcols[] = $column->data;
        }

        $this->createTable($newname, $newcols);
        $newtable = $this->knjdb->getTable($newname);

        //Inserting the old data.
        $this->knjdb->query("INSERT INTO " . $this->knjdb->connob->sep_table . $newtable->get("name") . $this->knjdb->conn_sep_table . " SELECT * FROM " . $this->knjdb->connob->sep_table . $table->get("name") . $this->knjdb->connob->sep_table);
        foreach ($indexes as $index) {
            $cols = array();
            $index_name = "table_" . $newtable->get("name") . "_cols";
            foreach ($index->getColumns() as $col) {
                $index_name .= "_" . $col->get("name");
                $cols[] = $newtable->getColumn($col->get("name"));
            }
            $index_name .= "_" . round(microtime(true), 0); //prevent it from using the same name.

            $newtable->addIndex($cols, $index_name);
        }
        $table->drop();
    }

    function dropTable(knjdb_table $table)
    {
        $this->knjdb->query("DROP TABLE " . $this->knjdb->connob->sep_table . $table->get("name") . $this->knjdb->connob->sep_table);
        unset($this->tables[$table->get("name")]);
    }

    function truncateTable(knjdb_table $table)
    {
        $this->knjdb->query("DELETE FROM " . $this->knjdb->conn->sep_table . $table->get("name") . $this->knjdb->conn->sep_table);
    }
}

