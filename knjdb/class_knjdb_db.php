<?php
class knjdb_db
{
    private $knjdb, $data;

    function __construct(knjdb $knjdb, $data)
    {
        $this->knjdb = $knjdb;
        $this->data = $data;
    }

    function getName()
    {
        return $this->data["name"];
    }

    function getTables()
    {
        return $this->knjdb->dbs()->getTablesForDB($this);
    }

    function optimize()
    {
        foreach ($this->getTables() as $table) {
            $table->optimize();
        }
    }
}

