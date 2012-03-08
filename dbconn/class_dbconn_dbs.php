<?php
/**
 * This class is extended by the DBConn-class, and it controls the functions for creating and manipulating databases with the DBConn-framework.
 */
class DBConnDBs extends DBConn_tables
{
    /**
     * Create a new database.
     */
    function createDB($name)
    {
        if ($this->getType() != "mysql" && $this->getType() != "pgsql") {
            throw new Exception("Invalid type of database: " . $this->getType());
        }

        $res = $this->query("CREATE DATABASE " . $name);
        if (!$res) {
            throw new Exception("SQL-error: " . $this->query_error());
        }

        return true;
    }

    /**
     * Returns a full list of databases, if the database supports it (only mysql and pgsql).
     */
    function getDBs()
    {
        if ($this->type == "mysql") {
            $f_gdbs = mysql_query("SHOW DATABASES", $this->conn);
            while ($d_gdbs = mysql_fetch_assoc($f_gdbs)) {
                if ($d_gdbs['Database'] != "mysql" && $d_gdbs['Database'] != "information_schema") {
                    $dbs[] = $d_gdbs['Database'];
                }
            }

            return $dbs;
        } elseif ($this->type == "pgsql") {
            $f_gdbs = pg_query($this->conn, "SELECT datname FROM pg_database");
            while ($d_gdbs = pg_fetch_assoc($f_gdbs)) {
                $dbs[] = d_gdbs['datname'];
            }

            return $dbs;
        }
    }

    /**
     * Choose another database, if the database supports it (only mysql and pgsql).
     */
    function chooseDB($db)
    {
        if ($this->type == "mysql") {
            return mysql_select_db($db, $this->conn);
        } elseif ($this->type == "pgsql") {
            $this->CloseConn();
            return $this->OpenConn("pgsql", $this->pg_ip, $this->pg_port, $db, $this->pg_user, $this->pg_pass);
        }

        return false;
    }
}

