<?php
class knjdb_sqlite3
{
    private $args;
    private $knjdb;
    public $tables = array();
    public $sep_col = "`";
    public $sep_val = "'";
    public $sep_table = "`";
    public $sep_index = "`";

    function __construct(knjdb $knjdb, $args)
    {
        $this->args = $args;
        $this->knjdb = $knjdb;

        require_once "knj/functions_knj_extensions.php";
        knj_dl("sqlite3");
    }

    function connect()
    {
        $this->conn = new SQLite3($this->args["path"]);
    }

    function close()
    {
        unset($this->conn);
    }

    function query($query)
    {
        $res = $this->conn->query($query);
        if (!$res) {
            throw new exception("Could not execute query: " . $this->error());
        }

        return new knjdb_result($this->knjdb, $this, $res);
    }

    function fetch($res)
    {
        return $res->fetchArray();
    }

    function error()
    {
        return $this->conn->lastErrorMsg();
    }

    function getLastID()
    {
        return $this->conn->lastInsertRowID();
    }

    function sql($string)
    {
        return $this->conn->escapeString($string);
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

    function select($table, $where = null, $args = null)
    {
        $sql = "SELECT";

        $sql .= " * FROM " . $this->sep_table . $table . $this->sep_table;

        if ($where) {
            $sql .= " WHERE " . $this->makeWhere($where);
        }

        if ($args["orderby"]) {
            $sql .= $this->makeOrderby($args["orderby"]);
        }

        if ($args["limit"]) {
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
        $sql .= "UPDATE " . $this->sep_table . $table . $this->sep_table . " SET ";

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

    function countRows($res)
    {
        return mysql_num_rows($res);
    }

    function makeWhere($where)
    {
        $first = true;
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

    function makeOrderby($orderby)
    {
        if (is_string($orderby)) {
            return " ORDER BY " . $orderby;
        } elseif (is_array($orderby)) {
            $sql = " ORDER BY ";

            $first = true;
            foreach ($orderby as $col_name) {
                if ($first == true) {
                    $first = false;
                } else {
                    $sql .= ", ";
                }

                $sql .= $this->sep_col . $col_name . $this->sep_col;
            }

            return $sql;
        }
    }
}

