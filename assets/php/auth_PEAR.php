<?php
/**
 * is_a
 *
 * function which returns TRUE if the object is of this class or has this class as one
 * of its parents (part of PHP 4 >= 4.2.0) - replicated here for backwards compatibility
 * (this is used in later versions of PEAR)
 * @param object $object
 * @param string $className
 * @return boolean
 */
if (!function_exists('is_a')) {
    function is_a( $object, $className ) {
        return ((strtolower($className) == get_class($object))
        or (is_subclass_of($object, $className)));
    }
}
/**
 * require PEAR Auth libraries
 */
require_once("Auth/Auth.php");
/**
 * set authorisation parameters
 */
$auth_params = array(
    "dsn" => $config["db_type"] . "://" . $config["db_user"] . ":" . $config["db_password"] . "@" . $config["db_host"] . "/" . $config["db_name"],
    "table" => $config["tbl_prefix"] . "_" . $config["tbl_drivers"],
    "usernamecol" => $config["un_col"],
    "passwordcol" => $config["pw_col"]
    );
/**
 * create an Auth object
 */
$a = &new Auth("DB", $auth_params, "login");
/**
 * set a login callback to store additional information in the session from the 
 * login form
 */
$a->setLoginCallback('loginCallback');
/**
 * set a logout callback function to store the date of last login
 */
$a->setLogoutCallback('logoutCallBack');
/**
 * authorise the user
 */
$a->start();
/**
 * check to see whether the user needs to be logged out
 */
if (isset($_GET['action']) && $_GET['action'] == "exit" && $a->getAuth()) {
    $a->logout();
    $a->start();
}
/**
 * login callback function
 */
function loginCallback($un, $a)
{
    $query = sprintf("SELECT `last_login`, `driver_name`, `driver_id` FROM %s WHERE `driver_name` = '%s'", $a->storage->options['table'], $a->getUsername());
    $result = $a->storage->query($query);
    if (!DB::isError($result) && $result->numRows()) {
        $row = $result->fetchRow();
        $a->setAuthData("last_login", $row[0]);
        $a->setAuthData("driver_name", $row[1]);
        $a->setAuthData("driver_id", $row[2]);
    }
}
/**
 * logout callback function
 */
function logoutCallBack($un, $a)
{
    $query = sprintf("UPDATE %s SET `last_login` = %d WHERE `driver_name` = '%s'", $a->storage->options['table'], time(), $un);
    $a->storage->query($query);
}
/**
 * if the user is logged in, include the relevant configuration file and any other
 * instance specific stuff (themes, etc.)
 */
if (!$a->getAuth()) {
    exit;
}
?>