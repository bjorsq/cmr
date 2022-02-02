<?php
/**
 * cmr configuration file
 */
$config = array(
    /* database type */
    "db_type" => "mysql",
    /* database host */
    "db_host" => "localhost",
    /* database name */
    "db_name" => "bjorsqdb",
    /* database username */
    "db_user" => "bjorsq",
    /* database password */
    "db_password" => "F^s$2kPq",
    /* prefix for tablespaces */
    "table_prefix" => "cmr",
    /* rally table */
    "tbl_rally" => "rally",
    /* driver table */
    "tbl_drivers" => "drivers",
    /* stages table */
    "tbl_stages" => "stages",
    /* stage times table */
    "tbl_times" => "times",
    /* session table */
    "tbl_sessions" => "sessions",
    /* auth table */
    "tbl_auth" => "drivers",
    /* name of username column in drivers table */
    "un_col" => "driver_name",
    /* name of password column in drivers table */
    "pw_col" => "driver_password",
    /* name of id column in drivers table */
    "id_col" => "driver_id",
    /* directory of library files */
    "lib_dir" => dirname(__FILE__),
    /* directory containing PEAR classes */
    "pear_dir" => dirname(__FILE__) . "/PEAR",
    /* whether or not to display database errors */
    "display_errors" => true,
    /* error log */
    "error_log" => dirname(__FILE__) . "/errorlog.txt",#
		/* query log */
		"query_log" => dirname(__FILE__) . "/querylog.txt"
);
?>
