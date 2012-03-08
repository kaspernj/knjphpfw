#!/usr/bin/php5
<?php
if (trim(system("whoami")) != "root") {
    die("You have to run this script as root.\n");
}

$dirname = dirname(__FILE__);
$dirunix = $dirname;

$dirunix = str_replace("\\", "\\\\", $dirunix);
$dirunix = str_replace(" ", "\\ ", $dirunix);

$testdirs = array(
    "/usr/share/php",
    "/usr/share/php5",
    "/usr/share/php4"
);
foreach ($testdirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir);
    }

    chdir($dir);
    echo("Found PHP extensions-dir: " . $dir . ".\n");

    if (is_link($dir . "/knj")) {
        echo("Its already there - unlinking.\n");
        unlink($dir . "/knj");
    }

    echo "Making symlink.\n";
    system("ln -s " . $dirunix . " knj");
}

echo "\n\nDone.\n";

