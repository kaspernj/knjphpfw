<?php

/**
 * This class handels a very simple login-system for any website.
 */
class knj_login
{
    private $dbconn;
    private $dbtype;

    private $table = "users";
    private $id_col = "id";
    private $nick_col = "nick";
    private $pass_col = "pass";

    function __construct($args = null)
    {
        if ($args) {
            $this->setDBInfo($args);
        }
    }

    /**
     * Sets the database-specific options which should be used with this module.
     */
    function setDBInfo($args)
    {
        foreach ($args as $key => $value) {
            if ($key == "table" || $key == "id_col" || $key == "nick_col" || $key == "pass_col" || $key == "dbconn") {
                $this->$key = $value;

                if ($key == "dbconn") {
                    $this->dbtype = "dbconn";
                }
            } elseif ($key == "dbtype") {
                if ($value == "mysql") {
                    $this->dbtype == "mysql";
                } elseif ($value == "dbconn") {
                    $this->dbtype = "dbconn";
                } else {
                    throw new Exception("Invalid dbtype: \"" . $value . "\".");
                }
            } else {
                throw new Exception("Unknown argument: " . $key);
            }
        }
    }

    function query($sql)
    {
        if ($this->dbtype == "mysql") {
            $query = mysql_query($sql) or die(mysql_error());
        } elseif ($this->dbtype == "dbconn") {
            $query = $this->dbconn->query($sql) or die($this->dbconn->query_error());
        } else {
            throw new Exception("Invalid dbtype: \"" . $this->dbtype . "\".");
        }

        return $query;
    }

    function query_fetch($query)
    {
        if ($this->dbtype == "mysql") {
            return mysql_fetch_assoc($query);
        } elseif ($this->dbtype == "dbconn") {
            return $this->dbconn->fetch($query);
        } else {
            throw new Exception("Invalid dbtype: \"" . $this->dbtype . "\".");
        }
    }

    function error()
    {
        if ($this->dbtype == "mysql") {
            return mysql_error();
        } elseif ($this->dbtype == "dbconn") {
            return $this->dbconn->error();
        } else {
            throw new Exception("Invalid dbtype: \"" . $this->dbtype . "\".");
        }
    }

    function sql($string)
    {
        if ($this->dbtype == "mysql") {
            return mysql_escape_string($string);
        } elseif ($this->dbtype == "dbconn") {
            return $this->dbconn->sql($string);
        } else {
            throw new Exception("Invalid dbtype: \"" . $this->dbtype . "\".");
        }
    }

    function setMySQLInfo($args)
    {
        $this->setDBInfo($args);
    }

    function tryLogin($nick, $pass = null, $remember = false)
    {
        if (is_array($nick)) {
            $args = $nick;
            $nick = $args["nick"];
            $pass = $args["passwd"];
            $remember = $args["remember"];
        }

        if ($args["is_md5_hash"]) {
            $f_gu = $this->query("SELECT * FROM " . $this->sql($this->table) . " WHERE LOWER(" . $this->nick_col . ") = LOWER('" . $this->sql($nick) . "') AND " . $this->sql($this->pass_col) . " = '" . $this->sql($pass) . "' LIMIT 1") or die($this->error());
        } else {
            $f_gu = $this->query("SELECT * FROM " . $this->sql($this->table) . " WHERE LOWER(" . $this->nick_col . ") = LOWER('" . $this->sql($nick) . "') AND " . $this->sql($this->pass_col) . " = MD5('" . $this->sql($pass) . "') LIMIT 1") or die($this->error());
        }

        $d_gu = $this->query_fetch($f_gu);

        if ($d_gu) {
            $login_cookie = $d_gu[$this->id_col] . ";" . md5($d_gu[$this->nick_col] . "-,." . md5($d_gu[$this->pass_col]));

            if ($remember) {
                setcookie("knjlogin", $login_cookie, strtotime("5 years"));
            } else {
                setcookie("knjlogin", $login_cookie);
            }

            return true;
        }

        return false;
    }

    function checkLogin()
    {
        if ($_COOKIE and array_key_exists("knjlogin", $_COOKIE)) {
            $expl = explode(";", $_COOKIE["knjlogin"]);

            $f_gu = $this->query("SELECT * FROM " . $this->sql($this->table) . " WHERE " . $this->id_col . " = '" . $this->sql($expl[0]) . "' LIMIT 1") or die($this->error());
            $d_gu = $this->query_fetch($f_gu);

            if ($d_gu) {
                $hash = md5($d_gu[$this->nick_col] . "-,." . md5($d_gu[$this->pass_col]));

                if ($hash == $expl[1]) {
                    global $in_user;
                    $in_user = $d_gu;
                    $this->user = $d_gu;

                    return true;
                }
            }
        }

        return false;
    }

    function doLogout()
    {
        setcookie("knjlogin", "", time() - 3600);
    }

    function getUserInfo()
    {
        return $this->user;
    }
}

