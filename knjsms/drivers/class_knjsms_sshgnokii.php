<?php
class Knjsms_Sshgnokii implements knjsms_driver
{
    function __construct($arr_opts)
    {
        include_once "knj/functions_knj_extensions.php";
        knj_dl("ssh2");

        include_once "knj/class_knj_ssh2.php";
        $this->ssh = knj_ssh2::quickConnect($arr_opts["host"], $arr_opts["user"], $arr_opts["passwd"], $arr_opts["port"]);
    }

    function sendSMS($number, $text)
    {
        $this->ssh->shellCMD('echo "' .addcslashes($text, '"\\') .'" | gnokii --sendsms ' .$number);
    }
}

