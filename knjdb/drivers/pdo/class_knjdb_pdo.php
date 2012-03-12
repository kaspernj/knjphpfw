<?php

/**
 * This class replaces the old DBConn-class. It aims to be much faster by not including so much code by default, and load much less libs.
 */
class knjdb_pdo
{
    private $args, $knjdb, $tables_driver, $columns_driver, $indexes_driver;
    public $sep_col = "`";
    public $sep_val = "'";
    public $sep_table = "`";
    public $sep_index = "`";

    function __construct(&$knjdb, &$args)
    {
        $this->args = $args;
        $this->knjdb = $knjdb;

        require_once "knj/functions_knj_extensions.php";
        knj_dl("pdo");

        if ($args["dbtype"] == "sqlite3") {
            knj_dl("pdo_sqlite");
        } elseif ($args["dbtype"] == "mysql") {
            knj_dl("pdo_mysql");
        } elseif (!$this->args["pdostring"]) {
            throw new Exception("No valid db-type given and no pdostring given.");
        }
    }

    function tables()
    {
        if (!$this->tables_driver) {
            require_once dirname(__FILE__) . "/../" . $this->args["dbtype"] . "/class_knjdb_" . $this->args["dbtype"] . "_tables.php";
            $obname = "knjdb_" . $this->args["dbtype"] . "_tables";
            $this->tables_driver = new $obname($this->knjdb);
        }

        return $this->tables_driver;
    }

    function columns()
    {
        if (!$this->columns_driver) {
            require_once dirname(__FILE__) . "/../" . $this->args["dbtype"] . "/class_knjdb_" . $this->args["dbtype"] . "_columns.php";
            $obname = "knjdb_" . $this->args["dbtype"] . "_columns";
            $this->columns_driver = new $obname($this->knjdb);
        }

        return $this->columns_driver;
    }

    function indexes()
    {
        if (!$this->indexes_driver) {
            require_once dirname(__FILE__) . "/../" . $this->args["dbtype"] . "/class_knjdb_" . $this->args["dbtype"] . "_indexes.php";
            $obname = "knjdb_" . $this->args["dbtype"] . "_indexes";
            $this->indexes_driver = new $obname($this->knjdb);
        }

        return $this->indexes_driver;
    }

    function connect()
    {
        if (array_key_exists("pdostring", $this->args) and $this->args["pdostring"]) {
            $pdostring = $this->args["pdostring"];
        } elseif ($this->args["dbtype"] == "sqlite3") {
            $pdostring = "sqlite:" . $this->args["path"];
        } else {
            throw new Exception("Invalid DB-type: \"" . $this->args["dbtype"] . "\".");
        }

        $this->conn = new PDO($pdostring);
    }

    function close()
    {
        unset($this->conn);
    }

    function query($sql)
    {
        while (true) {
            $res = $this->conn->query($sql);
            if ($res) {
                break;
            }

            $err = $this->error();
            if ($err == "database schema has changed") {
                $this->connect();
                continue;
            } else {
                throw new exception("Query error: " . $err);
            }
        }

        return new knjdb_result($this->knjdb, $this, $res);
    }

    function fetch($res)
    {
        return $res->fetch(PDO::FETCH_ASSOC);
    }

    function error()
    {
        $tha_error = $this->conn->errorInfo();
        $tha_error = $tha_error[2];
        if (!$tha_error && $this->lastsqlite3_error) {
            $tha_error = $this->lastsqlite3_error;
            unset($this->lastsqlite3_error);
        }

        return $tha_error;
    }

    function getLastID()
    {
        return $this->conn->lastInsertID();
    }

    function sql($string)
    {
        return substr($this->conn->quote($string), 1, -1);
    }

    function trans_begin()
    {
        $this->conn->beginTransaction();
    }

    function trans_commit()
    {
        $this->conn->commit();
    }

    function insert($table, $arr)
    {
        $sql = "INSERT INTO " . $this->sep_table . $table . $this->sep_table . " (";

        $first = true;
        foreach ($arr as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_col . $key . $this->sep_col;
        }

        $sql .= ") VALUES (";
        $first = true;
        foreach ($arr as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_val . $this->sql($value) . $this->sep_val;
        }
        $sql .= ")";

        $this->query($sql);
    }

    function select($table, $where = array(), $args = array())
    {
        $sql = "SELECT * FROM " . $this->sep_table . $table . $this->sep_table;

        if ($where) {
            $sql .= " WHERE " . $this->makeWhere($where);
        }

        if ($args and array_key_exists("orderby", $args) and $args["orderby"]) {
            $sql .= " ORDER BY " . $args["orderby"];
        }

        if ($args and array_key_exists("limit", $args) and $args["limit"]) {
            $sql .= " LIMIT " . $args["limit"];
        }

        return $this->query($sql);
    }

    function delete($table, $where = null)
    {
        $sql = "DELETE FROM " . $this->sep_table . $table . $this->sep_table;

        if ($where) {
            $sql .= " WHERE " . $this->makeWhere($where);
        }

        return $this->query($sql);
    }

    function update($table, $data, $where = null)
    {
        $sql = "UPDATE " . $this->sep_table . $table . $this->sep_table . " SET ";

        $first = true;
        foreach ($data as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_col . $key . $this->sep_col . " = " . $this->sep_val . $this->sql($value) . $this->sep_val;
        }

        if ($where) {
            $sql .= " WHERE " . $this->makeWhere($where);
        }

        return $this->query($sql);
    }

    function makeWhere($where)
    {
        $first = true;
        $sql = "";

        foreach ($where as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= " AND ";
            }

            $sql .= $this->sep_col . $key . $this->sep_col . " = " . $this->sep_val . $this->sql($value) . $this->sep_val;
        }

        return $sql;
    }
}

