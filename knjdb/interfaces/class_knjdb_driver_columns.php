<?php
    interface knjdb_driver_columns{
        function getColumnSQL($column);
        function getColumns(knjdb_table $table);
        function removeColumn(knjdb_table $table, knjdb_column $column);
        function addColumns(knjdb_table $table, $columns);
        function editColumn(knjdb_column $col, $newdata);
    }

