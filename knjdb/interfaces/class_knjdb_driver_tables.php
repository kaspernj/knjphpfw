<?php
	interface knjdb_driver_tables{
		function __construct(knjdb $knjdb);
		function getTables();
		function createTable($name, $cols, $args = null);
		function renameTable(knjdb_table $table, $newname);
		function dropTable(knjdb_table $table);
		function truncateTable(knjdb_table $table);
	}

