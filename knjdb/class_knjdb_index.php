<?php
class knjdb_index
{
    public $knjdb;
    public $table;
    public $data;

    function __construct($table, $data)
    {
        $this->knjdb = $table->knjdb;
        $this->table = $table;
        $this->data = $data;
    }

    /**
     * Returns a key from the row.
     */
    function get($key)
    {
        if (!array_key_exists($key, $this->data)) {
            throw new Exception("The key does not exist: \"" . $key . "\".");
        }

        return $this->data[$key];
    }

    function getColumns()
    {
        return $this->data["columns"];
    }

    function getColText()
    {
        $text = "";
        foreach ($this->getColumns() as $column) {
            if (strlen($text) > 0) {
                $text .= ", ";
            }

            $text .= $column->get("name");
        }

        return $text;
    }
}

