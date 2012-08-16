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
class knjdb_mysqli_dbs implements knjdb_driver_dbs
{
    private $_knjdb;

    /**
     * TODO
     *
     * @param object $knjdb TODO
     */
    function __construct(knjdb $knjdb)
    {
        $this->_knjdb = $knjdb;
    }

    /**
     * TODO
     *
     * @return array TODO
     */
    function getDBs()
    {
        $return = array();
        $f_gdbs = $this->_knjdb->query("SHOW DATABASES");
        while ($d_gdbs = $f_gdbs->fetch()) {
            if ($d_gdbs["Database"] != "mysql"
                && $d_gdbs["Database"] != "information_schema"
            ) {
                $return[] = new knjdb_db(
                    array(
                        "name" => $d_gdbs["Database"]
                    )
                );
            }
        }

        return $return;
    }

    /**
     * TODO
     *
     * @param string $name TODO
     *
     * @return object TODO
     */
    function getDB($name)
    {
        foreach ($this->getDBs() as $db) {
            if ($db->getName() == $name) {
                return $db;
            }
        }

        throw new Exception("Could not find the database.");
    }

    /**
     * TODO
     *
     * @param object $db TODO
     *
     * @return null
     */
    function chooseDB(knjdb_db $db)
    {
        $this->_knjdb->query("USE " .$db->getName());
    }

    /**
     * TODO
     *
     * @param array $data TODO
     *
     * @return null
     */
    function createDB($data)
    {
        if (!$data["name"]) {
            throw new Exception("No name given.");
        }

        $this->_knjdb->query("CREATE DATABASE " .$data["name"]);
    }
}

