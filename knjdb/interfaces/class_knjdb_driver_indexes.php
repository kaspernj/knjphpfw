<?php
    interface knjdb_driver_indexes{
        function addIndex(knjdb_table $table, $columns, $name = null, $args = null);
        function getIndexSQL(knjdb_index $index);
        function getIndexes(knjdb_table $table);
        function removeIndex(knjdb_table $table, knjdb_index $index);
    }

