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
class knjdb_mysqli
{
    private $_args;
    private $_knjdb;
    public $sep_col   = "`";
    public $sep_val   = "'";
    public $sep_table = "`";
    public $sep_index = "`";

    /**
     * TODO
     *
     * @param object $knjdb TODO
     * @param array  &$args TODO
     */
    function __construct(knjdb $knjdb, &$args)
    {
        $this->_args  = $args;
        $this->_knjdb = $knjdb;

        include_once "knj/functions_knj_extensions.php";
        knj_dl("mysqli");
    }

    /**
     * TODO
     *
     * @return array TODO
     */
    static function getArgs()
    {
        return array(
            "host" => array(
                "type" => "text",
                "title" => "Hostname"
            ),
            "user" => array(
                "type" => "text",
                "title" => "Username"
            ),
            "pass" => array(
                "type" => "passwd",
                "title" => "Password"
            ),
            "db" => array(
                "type" => "text",
                "title" => "Database"
            )
        );
    }

    /**
     * TODO
     *
     * @return null
     */
    function connect()
    {
        $this->conn = new MySQLi(
            $this->_args["host"],
            $this->_args["user"],
            $this->_args["pass"],
            $this->_args["db"]
        );

         //do not use the OO-way - it was broken until 5.2.9.
        if (mysqli_connect_error()) {
            $msg = "Could not connect (" .mysqli_connect_errno() ."): "
            .mysqli_connect_error();
            throw new Exception($msg);
        }
    }

    /**
     * Close the database connection
     *
     * @return null
     */
    function close()
    {
        $this->conn->close();
        unset($this->conn);
    }

    /**
     * TODO
     *
     * @param string $query The SQL query to be executed
     *
     * @return object TODO
     */
    function query($query)
    {
        $res = $this->conn->query($query);
        if (!$res) {
            $msg = "Query error: " .$this->error() ."\n\nSQL: " .$query;
            throw new exception($msg);
        }

        return new knjdb_result($this->_knjdb, $this, $res);
    }

    /**
     * TODO
     *
     * @param string $query TODO
     *
     * @return object TODO
     */
    function query_ubuf($query)
    {
        if (!$this->conn->real_query($query)) {
            throw new exception("Query error: " . $this->error() . "\n\nSQL: " . $query);
        }

        return new knjdb_result($this->knjdb, $this, $this->conn->use_result());
    }

    /**
     * TODO
     *
     * @param TODO $res TODO
     *
     * @return TODO
     */
    function fetch($res)
    {
        return $res->fetch_assoc();
    }

    /**
     * TODO
     *
     * @return TODO
     */
    function error()
    {
        return $this->conn->error;
    }

    /**
     * TODO
     *
     * @param TODO $res TODO
     *
     * @return TODO
     */
    function free($res)
    {
        return $res->free();
    }

    /**
     * TODO
     *
     * @return int TODO
     */
    function getLastID()
    {
        return $this->conn->insert_id;
    }

    /**
     * TODO
     *
     * @param string $string TODO
     *
     * @return null
     */
    function sql($string)
    {
        return $this->conn->real_escape_string($string);
    }

    /**
     * TODO
     *
     * @param string $string TODO
     *
     * @return string TODO
     */
    function escape_table($string)
    {
        if (strpos($string, "`")) {
            throw new exception("Tablename contains invalid character.");
        }

        return $string;
    }

    /**
     * TODO
     *
     * @return null
     */
    function trans_begin()
    {
        $this->conn->autocommit(false); //turn off autocommit.
    }

    /**
     * TODO
     *
     * @return null
     */
    function trans_commit()
    {
        $this->conn->commit();
        $this->conn->autocommit(true); //turn on autocommit.
    }

    /**
     * TODO
     *
     * @param string $table TODO
     * @param array  $arr   TODO
     *
     * @return object TODO
     */
    function insert($table, $arr)
    {
        $sql = "INSERT INTO " .$this->sep_table .$table .$this->sep_table ." (";

        $first = true;
        foreach ($arr as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_col .$key .$this->sep_col;
        }

        $sql .= ") VALUES (";
        $first = true;
        foreach ($arr as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_val .$this->sql($value) .$this->sep_val;
        }
        $sql .= ")";

        $this->query($sql);
    }

    /**
     * TODO
     *
     * @param string $table TODO
     * @param array  $arr   TODO
     *
     * @return object TODO
     */
    function replace($table, $arr)
    {
        $sql = "REPLACE INTO " .$this->sep_table .$table .$this->sep_table ." (";

        $first = true;
        foreach ($arr as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_col .$key .$this->sep_col;
        }

        $sql .= ") VALUES (";
        $first = true;
        foreach ($arr as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_val .$this->sql($value) .$this->sep_val;
        }
        $sql .= ")";

        $this->query($sql);
    }

    /**
     * TODO
     *
     * @param string $table TODO
     * @param array  $rows  TODO
     *
     * @return object TODO
     */
    function insert_multi($table, $rows)
    {
        $sql = "INSERT INTO " .$this->sep_table .$table .$this->sep_table ." (";

        $first = true;
        foreach ($rows[0] as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_col .$key .$this->sep_col;
        }

        $sql .= ") VALUES";

        $first_row = true;
        foreach ($rows as $arr) {
            if ($first_row) {
                $first_row = false;
            } else {
                $sql .= ",";
            }

            $sql .= " (";
            $first = true;
            foreach ($arr as $key => $value) {
                if ($first == true) {
                    $first = false;
                } else {
                    $sql .= ", ";
                }

                $sql .= $this->sep_val .$this->sql($value) .$this->sep_val;
            }
            $sql .= ")";
        }

        $this->query($sql);
    }

    /**
     * TODO
     *
     * @param string $table TODO
     * @param array  $where TODO
     * @param array  $args  TODO
     *
     * @return object TODO
     */
    function select($table, $where = null, $args = null)
    {
        $sql = "SELECT";

        $sql .= " * FROM " .$this->sep_table .$table .$this->sep_table;

        if ($where) {
            $sql .= " WHERE " .$this->makeWhere($where);
        }

        if ($args["orderby"]) {
            $sql .= " ORDER BY " .$args["orderby"];
        }

        if ($args["limit"]) {
            $sql .= " LIMIT " .$args["limit"];
        }

        return $this->query($sql);
    }

    /**
     * TODO
     *
     * @param string $table TODO
     * @param array  $where TODO
     *
     * @return object TODO
     */
    function delete($table, $where = null)
    {
        $sql = "DELETE FROM " .$this->sep_table .$table .$this->sep_table;

        if ($where) {
            $sql .= " WHERE " .$this->makeWhere($where);
        }

        return $this->query($sql);
    }

    /**
     * TODO
     *
     * @param string $table TODO
     * @param array  $data  TODO
     * @param array  $where TODO
     *
     * @return object TODO
     */
    function update($table, $data, $where = null)
    {
        $sql .= "UPDATE " .$this->sep_table .$table .$this->sep_table ." SET ";

        $first = true;
        foreach ($data as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= $this->sep_col .$key .$this->sep_col ." = " .$this->sep_val
            .$this->sql($value) .$this->sep_val;
        }

        if ($where) {
            $sql .= " WHERE " .$this->makeWhere($where);
        }

        return $this->query($sql);
    }

    /**
     * TODO
     *
     * @param array $tables TODO
     *
     * @return object TODO
     */
    function optimize($tables)
    {
        if (!is_array($tables)) {
            $tables = array($tables);
        }

        $data = array(
            "array" => $tables,
            "surr" => "`",
            "impl" => ",",
            "self_callback" => array($this, "escape_table")
        );
        $sql = "OPTIMIZE TABLE " .knjarray::implode($data);

        return $this->query($sql);
    }

    /**
     * TODO
     *
     * @param array $where TODO
     *
     * @return string TODO
     */
    function makeWhere($where)
    {
        $first = true;
        foreach ($where as $key => $value) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= " AND ";
            }

            if (is_array($value)) {
                $data = array(
                    "array" => $value,
                    "impl" => ",",
                    "surr" => "'",
                    "self_callback" => array($this, "sql")
                );
                $sql .= $this->sep_col .$key .$this->sep_col ." IN ("
                .knjarray::implode($data) .")";
            } else {
                $sql .= $this->sep_col .$key .$this->sep_col ." = " .$this->sep_val
                .$this->sql($value) .$this->sep_val;
            }
        }

        return $sql;
    }

    /**
     * Alias of strtotime()
     *
     * @param string $str See PHP documentation for strtotime()
     *
     * @return int See PHP documentation for strtotime()
     */
    function date_in($str)
    {
        return strtotime($str);
    }

    /**
     * TODO
     *
     * @param int   $unixt TODO
     * @param array $args  TODO
     *
     * @return string TODO
     */
    function date_format($unixt, $args = array())
    {
        $format = "Y-m-d";

        if (!array_key_exists("time", $args) || $args["time"]) {
            $format .= " H:i:s";
        }

        return date($format, $unixt);
    }
}

