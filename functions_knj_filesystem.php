<?php
/**
 * Checks if a given user has full read/write-access to a given file by checking its Unix-rights, groups and so on.
 *
 * @param string $file The file which should be checked against the user.
 * @param string $user The Linux-user which should be checked against the file.
 */
function fs_file_checkMod($file, $user, $mods)
{
    $fileinfo = fs_getFileInfo($file);
    $member = fs_file_checkMemberOfGroup($fileinfo["group"], $user);

    //Error handeling.
    if (!is_array($mods)) {
        throw new Exception("The third argument supplied has to be an array.");
    }

    foreach ($mods as $mod) {
        if ($mod != "read" && $mod != "write" && $mod != "exe") {
            throw new Exception("Invalid mod: " . $mod);
        }

        $access = false;
        if ($mod == "read") {
            if ($fileinfo["mods"]["all"]["read"] == true) {
                $access = true;
            }

            if ($fileinfo["mods"]["user"]["read"] == true && $user == $fileinfo["user"]) {
                $access = true;
            }

            if ($fileinfo["mods"]["groups"]["read"] == true && $member == true) {
                $access = true;
            }
        } elseif ($mod == "write") {
            if ($fileinfo["mods"]["all"]["write"] == true) {
                $access = true;
            }

            if ($fileinfo["mods"]["user"]["write"] == true && $user == $fileinfo["user"]) {
                $access = true;
            }

            if ($fileinfo["mods"]["groups"]["write"] == true && $member == true) {
                $access = true;
            }
        } elseif ($mod == "exe") {
            if ($fileinfo["mods"]["all"]["exe"] == true) {
                $access = true;
            }

            if ($fileinfo["mods"]["user"]["exe"] == true && $user == $fileinfo["user"]) {
                $access = true;
            }

            if ($fileinfo["mods"]["groups"]["exe"] == true && $member == true) {
                $access = true;
            }
        }

        if ($access != true) {
            return false;
        }
    }

    return true;
}

/**
 * Checks if a specified user is member of a specified group.
 *
 * @param string $user The user which should be checked against the group.
 * @param string $group The group.
 *
 * FIXME: Make a group-lookup to check if the user belongs to the file's group.
 */
function fs_file_checkMemberOfGroup($user, $group)
{
    if ($user == $group) {
        return true;
    }
}

/**
 * Returns the user and group that a specified file belongs to. It also returns the mods for the file.
 *
 * @param string $file The file which should be read.
 */
function fs_getFileInfo($file)
{
    if (!file_exists($file)) {
        throw new Exception("fs_getMods(): The file does not exist (" . $file . ").");
    }

    require_once "knj/strings.php";
    require_once "knj/os.php";
    $stat = knj_os::systemCMD("ls -l " .knj_strings::UnixSafe($file));

    $return["mods"]["user"]["mod"] = substr($stat, 1, 3);
    $return["mods"]["group"]["mod"] = substr($stat, 4, 3);
    $return["mods"]["all"]["mod"] = substr($stat, 7, 3);

    foreach ($return["mods"] as $key => $value) {
        if (substr($value["mod"], 0, 1) == "r") {
            $return["mods"][$key]["read"] = true;
        } else {
            $return["mods"][$key]["read"] = false;
        }

        if (substr($value["mod"], 1, 1) == "w") {
            $return["mods"][$key]["write"] = true;
        } else {
            $return["mods"][$key]["write"] = false;
        }

        if (substr($value["mod"], 2, 1) == "x") {
            $return["mods"][$key]["exe"] = true;
        } else {
            $return["mods"][$key]["exe"] = false;
        }
    }

    $groups = preg_split("/\s+/", $stat);
    $return["user"] = $groups[2];
    $return["group"] = $groups[3];

    return $return;
}

/**
 * Removes everything inside a directory.
 */
function fs_cleanDir($dir, $rmdir = false)
{
    $fp = opendir($dir) or die("Could not open dir.\n");
    while (($file = readdir($fp)) !== false) {
        if ($file != "." && $file != "..") {
            if (is_file($dir . "/" . $file)) {
                if (!unlink($dir . "/" . $file)) {
                    throw new Exception("Could not remove the file: \"" . $file . "\".");
                }
            } else {
                fs_cleanDir($dir . "/" . $file, true);
            }
        }
    }

    if ($rmdir) {
        rmdir($dir);
    }
}

/**
 * This class can help PHP read information about the filesystem.
 */
class knj_fs
{
    /**
     * Returns the "funny" array after testing vs the arguments.
     */
    static function getFiles_getArr($fn, $args = array())
    {
        $add = true;
        $full = $fn;
        $pathinfo = pathinfo($fn);
        $path = $pathinfo["dirname"];
        $name = $pathinfo["basename"];

        if ($args["replace_paths"]) {
            foreach ($args["replace_paths"] as $key => $value) {
                $full = str_replace($key, $value, $full);
            }
            foreach ($args["replace_paths"] as $key => $value) {
                $path = str_replace($key, $value, $path);
            }
        }

        if (is_file($fn)) {
            if ($args["ignore_backups"]) {
                if (substr($name, -1) == "~") {
                    $add = false;
                }
            }

            if ($args["ignore_files"]) {
                foreach ($args["ignore_files"] as $ignore_name) {
                    if ($ignore_name == $name) {
                        $add = false;
                    }
                }
            }

            if ($args["ignore_hidden"]) {
                if (substr($name, 0, 1) == ".") {
                    $add = false;
                }
            }

            if ($args["filetypes"] && !in_array($pathinfo["extension"], $args["filetypes"])) {
                $add = false;
            }

            if ($add) {
                //Find filetype.
                $dot_pos = strrpos($name, ".");
                if ($dot_pos !== false) {
                    $filetype = strtolower(substr($name, $dot_pos + 1));
                } else {
                    $filetype = "";
                }

                return array(
                    "full" => $full,
                    "name" => $name,
                    "path" => $path,
                    "type" => "file",
                    "ftype" => $filetype,
                    "orig" => $fn
                );
            }
        } elseif (is_dir($fn)) {
            if ($args["ignore_folders"]) {
                foreach ($args["ignore_folders"] as $ignore_name) {
                    if ($ignore_name == $name) {
                        $add = false;
                    }
                }
            }

            if ($args["ignore_hidden"] && substr($name, 0, 1) == ".") {
                $add = false;
            }

            if ($add) {
                return array(
                    "full" => $full,
                    "name" => $name,
                    "path" => $path,
                    "type" => "folder",
                    "orig" => $fn
                );
            }
        }

        return false;
    }

    /**
     * Recursive function for reading the dirs.
     */
    static function getFiles_getDir($dir, $args)
    {
        if (is_file($dir)) {
            $return[] = knj_fs::getFiles_getArr($dir, &$args);
            return $return;
        }

        $return = array();
        $fp = opendir($dir);
        if ($fp) {
            while (($file = readdir($fp)) !== false) {
                if ($file != "." && $file != "..") {
                    $add = true;

                    if ($args["ignore_hidden"] && substr($file, 0, 1) == ".") {
                        $add = false;
                    }

                    if ($add) {
                        $new = knj_fs::getFiles_getArr($dir . "/" . $file, &$args);

                        if ($new) {
                            $return[] = $new;
                            if (!is_file($dir . "/" . $file)) {
                                $morefiles = knj_fs::getFiles_getDir($dir . "/" . $file, &$args);
                                $return = array_merge($return, $morefiles);
                            }
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Returns a complete list of files in a given dir.
     */
    static function getFiles($dir, $args = array())
    {
        foreach ($args as $key => $value) {
            if ($key == "ignore_folders") {
            } elseif ($key == "ignore_files") {
            } elseif ($key == "ignore_backups") {
            } elseif ($key == "replace_paths" && is_array($value)) {
            } elseif ($key == "ignore_hidden" && is_bool($value)) {
            } elseif ($key == "filetypes" && is_array($value)) {
            } else {
                throw new Exception("Unknown argument: " . $key);
            }
        }

        return knj_fs::getFiles_getDir($dir, &$args);
    }

    /**
     * Returns information about a specific file by running the Linux "file"-command on a file.
     */
    static function fileInfo($file)
    {
        if (!file_exists($file)) {
            throw new Exception("The file does not exist (" . $file . ").");
        }

        require_once "knj/os.php";
        require_once "knj/strings.php";

        $result = knj_os::shellCMD("file " .knj_strings::UnixSafe($file));
        $result = substr($result["result"], strlen($file) + 2, -1);

        return $result;
    }

    /**
     * Converts the output from "ls -l" to a PHP-readable array.
     */
    static function conv_lsl($string)
    {
        $files = explode("\n", $string);

        $return = array();
        foreach ($files as $file) {
            if (trim($file)) {
                if (substr($file, 0, 5) == "total") {
                    //ignore this line.
                } elseif (preg_match("/^(\S)([rwx-]{3})([rwx-]{3})([rwx-]{3})\s+([0-9])\s+(\S+)\s+(\S+)\s+([0-9]+)\s+(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2})\s+([\s\S]+)$/", $file, $match)) {
                    if ($match[1] == "d") {
                        $type = "dir";
                    } elseif ($match[1] == "-") {
                        $type = "file";
                    } else {
                        throw new Exception("Unknown type: " . $match[1]);
                    }

                    $date = mktime(0, 0, 0, $match[10], $match[11], $match[9]);

                    $return[] = array(
                        "name" => $match[14],
                        "size" => $match[8],
                        "user" => $match[6],
                        "group" => $match[7],
                        "date" => $date,
                        "type" => $type
                    );
                } else {
                    throw new Exception("Could not parse the line: " . $file);
                }
            }
        }

        return $return;
    }

    static function rmdir($path)
    {
        fs_cleanDir($path, true);
    }
}

