<?php

/**
 * Sets the options.
 */
function opt_set($arr)
{
    global $knj_options;

    if (!is_array($arr)) {
        throw new Exception("Invalid parameter - only accepts array.");
    }

    foreach ($arr as $key => $value) {
        if ($key == "dbconn" || $key == "keycol" || $key == "valcol" || $key == "table") {
            $knj_options[$key] = $value;
        } else {
            throw new Exception("Invalid key: " . $key);
        }
    }

    if (!array_key_exists("table", $knj_options) or !$knj_options["table"]) {
        $knj_options["table"] = "options";
    }

    if (!array_key_exists("keycol", $knj_options) or !$knj_options["keycol"]) {
        $knj_options["keycol"] = "title";
    }

    if (!array_key_exists("valcol", $knj_options) or !$knj_options["valcol"]) {
        $knj_options["valcol"] = "value";
    }
}

/**
 * Returns the value of an option.
 */
function opt_get($title)
{
    global $knj_options;
    $dbconn = $knj_options["dbconn"];

    $f_gv = $dbconn->select($knj_options["table"], array($knj_options["keycol"] => $title), array("limit" => 1));
    $d_gv = $f_gv->fetch();

    return $d_gv[$knj_options["valcol"]];
}

/**
 * Write a new value to an option.
 */
function opt_write($title, $value)
{
    global $knj_options;
    $dbconn = $knj_options["dbconn"];

    $f_gv = $dbconn->select($knj_options["table"], array($knj_options["keycol"] => $title), array("limit" => 1));
    $d_gv = $f_gv->fetch();

    $arr = array(
        $knj_options["keycol"] => $title,
        $knj_options["valcol"] => $value
    );

    if (!$d_gv) {
        $dbconn->insert($knj_options["table"], $arr);
    } else {
        $dbconn->update($knj_options["table"], $arr, array($knj_options["keycol"] => $title));
    }

    return true;
}

