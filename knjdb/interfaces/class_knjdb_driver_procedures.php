<?php
    interface knjdb_driver_procedures{
        function __construct(knjdb $knjdb);
        function getProcedures();
    }

