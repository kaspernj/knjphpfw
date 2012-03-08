<?php
/**
 * This function handels the locales-command on Linux-systems in an easy way.
 */
function knj_locate($string)
{
    require_once("knj/functions_knj_os.php");
    require_once("knj/functions_knj_strings.php");


    //Error handeling.
    $os = knj_os::getOS();
    if ($os["os"] != "linux") {
        throw new Exception("This function only works on Linux.");
    }


    //Make shell-command.
    $cmd = "locate";
    if (is_array($string)) {
        foreach ($string as $str) {
            $cmd .= " " .knj_strings::UnixSafe($str);
        }
    } else {
        $cmd .= " " .knj_strings::UnixSafe($string);
    }


    //Execute shell-command.
    $result = knj_os::shellCMD($cmd);
    if (strlen(trim($result["error"]))) {
        throw new Exception($result["error"]);
    }


    //Make array of files found and unset the last one (because it will be empty).
    $files = explode("\n", $result["result"]);
    unset($files[count($files) - 1]);


    //Return the array.
    return $files;
}

