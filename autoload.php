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
class knj_autoload
{
    /**
     * TODO
     */
    function __construct()
    {
        $this->exts = array(
            "gtk" => "gtk2",
            "mysql" => "mysql",
            "sqlite3" => "sqlite"
        );
        $this->knj = array(
            "web" => "web",
            "knj_browser" => "web",
            "knj_cartesian_additions" => "cartesian_additions",
            "knj_date" => "date",
            "knj_ftp" => "ftp",
            "knj_os" => "os",
            "objects" => "objects",
            "knjarray" => "functions_array",
            "knjdb" => "db",
            "knjdb_async" => "knjdb/class_knjdb_async",
            "knjobjects" => "objects",
            "knj_csv" => "csv",
            "knj_login" => "web_login",
            "knj_mail" => "mail",
            "knj_strings" => "strings",
            "knj_ssh2" => "class_knj_ssh2",
            "knj_fs" => "functions_knj_filesystem",
            "knj_date" => "date",
            "knj_powerset" => "powerset",
            "notfoundexc" => "exceptions",
            "dbconnexc" => "exceptions",
            "noaccessexc" => "exceptions",
            "knj_translations" => "translations",
            "epay" => "epay"
        );
        $this->classes = array(
            "net_ftp" => "Net/FTP.php",
            "pclzip" => "libphp-pclzip/pclzip.lib.php",
            "fpdf" => "fpdf/fpdf.php"
        );
    }

    /**
     * TODO
     *
     * @param string $classname TODO
     *
     * @return null
     */
    function load($classname)
    {
        $class = mb_strtolower($classname);

        if (array_key_exists($class, $this->classes)) {
            include_once $this->classes[$class];
        }

        if (array_key_exists($class, $this->exts)) {
            include_once "knj/exts.php";
            knj_dl($this->ext[$classname]);
        }

        if (array_key_exists($class, $this->knj)) {
            include_once "knj/" .$this->knj[$class] .".php";
        }
    }

    /**
     * TODO
     *
     * @param mixed  $class TODO
     * @param string $file  TODO
     *
     * @return null
     */
    function add($class, $file = null)
    {
        if (is_array($class)) {
            foreach ($class as $key => $value) {
                $this->add($key, $value);
            }
        } else {
            $this->classes[mb_strtolower($class)] = $file;
        }
    }
}

