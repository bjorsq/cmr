<?php
/**
 * setup.php
 *
 * sets up all includes, object libraries and templates for skool.tv
 * @package skooltv
 * @author Peter Edwards <tech@e-2.org>
 * @version 1.0
 */

/**
 * require configuration information
 */
require_once(dirname(__FILE__) . '/config.php');
/**
 * fix table names
 */
if (isset($config["table_prefix"]) && $config["table_prefix"] != "") {
    foreach($config as $key => $value) {
        if (strstr( $key, "tbl_")) {
            $config[$key] = $config["table_prefix"] . '_' . $value;
        }
    }
}
/**
 * require database abstraction layer
 */
require_once(dirname(__FILE__) . '/adodb/adodb.inc.php');
/**
 * require templates
 */
require_once(dirname(__FILE__) . '/templates.php');
/**
 * set up a database connection
 */
$dbconn = NewADOConnection($config["db_type"]);
$dbconn->Connect($config["db_host"], $config["db_user"], $config["db_password"], $config["db_name"]);

/**
 * quote stripping
 * all EGPCS have magic quotes stripped - functions and classes accessing these
 * globals all assume magic quotes is turned off and act accordingly
 */

/**
 * strip_quotes
 *
 * magic quotes stripping function
 * @param mixed string or array data to have magic quotes stripped
 * @return mixed corresponding string/array data with magic quotes stripped
 */
function strip_quotes($var)
{
    if (is_array($var)) {
        foreach ($var as $k => $v) {
            if (is_array($v)) {
                $var[$k] = strip_quotes($v);
            } else {
                $var[$k] = stripslashes($v);
            }
        }
    } else {
        $var = stripslashes($var);
    }
    return $var;
}
// strip all quotes from GPC if needed
if (get_magic_quotes_gpc()) {
    if (!empty($_GET)) {
        $_GET = strip_quotes($_GET);
    }
    if (!empty($_POST)) {
        $_POST = strip_quotes($_POST);
    }
    if (!empty($_COOKIE)) {
        $_COOKIE = strip_quotes($_COOKIE);
    }
}
function database_error(&$db, $file, $line, $query)
{
    global $config;
    if ($config["display_errors"]) {
        printf('<p>error in file <strong>%s</strong> on line <strong>%s</strong><br />query: <strong>%s</strong></p><p>MySQL said:<pre>%s</pre></p>', $file, $line, $query, $db->ErrorMsg());
    } else {
        log_error($file, $line, sprintf('database error in file %s on line %s::query::%s::MySQL said::%s', $file, $line, $query, $db->ErrorMsg()));
    }
}
function log_error($file, $line, $message)
{
    global $config;
    if ($fp = fopen($config["error_log"], "ab")) {
        fwrite($fp, sprintf("%s|error in file %s, line %s|%s\n", time(), $file, $line, $message));
        fclose($fp);
    }
}
?>