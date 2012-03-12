<?php
/**
 * Parses a string to be SQL-safe.
 */
function sql($string)
{
    global $functions_knj_sql;

    if (!$functions_knj_sql) {
        $functions_knj_sql["type"] = "mysql";
    }

    if ($functions_knj_sql["type"] == "mysql") {
        $string = mysql_escape_string($string);
    } elseif ($functions_knj_sql["type"] == "sqlite" || $functions_knj_sql["type"] == "sqlite3") {
        $string = sqlite_escape_string($string);
    } else {
        throw new Exception("Invalid type: " . $functions_knj_sql["type"]);
    }

    return $string;
}

/**
 * Sets the db-type for escaping and other stuff...
 */
function sql_setDBType($dbtype)
{
    global $functions_knj_sql;

    if ($dbtype == "mysql" || $dbtype == "sqlite" || $dbtype == "sqlite3") {
        $functions_knj_sql["type"] = $dbtype;

        if ($dbtype == "sqlite" || $dbtype == "sqlite3") {
            if (!function_exists("sqlite_escape_string")) {
                require_once "knj/functions_knj_extensions.php";
                knj_dl("sqlite");
            }
        } elseif ($dbtype == "mysql") {
            if (!function_exists("mysql_escape_string")) {
                require_once "knj/functions_knj_extensions.php";
                knj_dl("mysql");
            }
        }
    } else {
        throw new Exception("Invalid type: " . $functions_knj_sql["type"]);
    }
}

/**
 * Parses a string to be SQL-safe (if maginc_quotes_gpc if off).
 */
function sqlhttp($string)
{
    if (ini_get("magic_quotes_gpc") == 0) {
        $string = sql($string);
    }

    return $string;
}

/**
 * Parses an array to become a SQL-insert.
 */
function sql_parseInsert($arr, $table)
{
    $sql = "INSERT INTO " . $table . " (";

    $first = true;
    foreach ($arr as $key => $value) {
        if ($first == true) {
            $first = false;
        } else {
            $sql .= ", ";
        }

        $sql .= $key;
    }

    $sql .= ") VALUES " . sql_parseInsertMPart($arr);

    return $sql;
}

/**
 * Parse an array to become a SQL-update.
 */
function sql_parseUpdate($arr, $table, $id_val, $id_col = "id")
{
    $sql = "UPDATE " . $table . " SET ";

    $first = true;
    foreach ($arr as $key => $value) {
        if ($first == true) {
            $first = false;
        } else {
            $sql .= ", ";
        }

        $sql .= $key . " = '" . sql($value) . "'";
    }

    $sql .= " WHERE " . $id_col . " = '" . sql($id_val) . "'";

    return $sql;
}

/**
 * Parses an array to become part of an multiple SQL-insert.
 */
function sql_parseInsertMPart($arr)
{
    $first = true;
    $sql = "(";
    foreach ($arr as $value) {
        if ($first == true) {
            $first = false;
        } else {
            $sql .= ", ";
        }

        $sql .= "'" . sql($value) . "'";
    }
    $sql .= ")";

    return $sql;
}

/**
 * Parses an array of multi-part arrays to be inserted into an table.
 */
function sql_convMPart($arr, $table)
{
    $sql = "INSERT INTO " . $table . " (";

    $first = true;
    foreach ($arr[0] as $column => $value) {
        if ($first == true) {
            $first = false;
        } else {
            $sql .= ", ";
        }

        $sql .= $column;
    }

    $sql .= ") VALUES ";
    $first_ins = true;
    foreach ($arr as $arrins) {
        if ($first_ins == true) {
            $first_ins = false;
        } else {
            $sql .= ", ";
        }

        $sql .= "(";
        $first = true;
        foreach ($arrins as $ins) {
            if ($first == true) {
                $first = false;
            } else {
                $sql .= ", ";
            }

            $sql .= "'" . sql($ins) . "'";
        }

        $sql .= ")";
    }

    return $sql;
}

