<?php
/**
 * auth.php
 * @package cmr
 * @author Peter Edwards <tech@e-2.org>
 * @version 1.0
 */

/**
 * require setup file
 */
require_once(dirname(__FILE__) . "/setup.php");
/**
 * require Auth class
 */
require_once(dirname(__FILE__) . "/authClass.php");
/**
 * create an Auth object
 */
$a = &new Auth($dbconn, $config);
/**
 * check to see whether the user needs to be logged out
 */
if (isset($_GET['action']) && $_GET['action'] == "exit" && $a->login()) {
    $a->logout();
}
/**
 * authorise the user
 */
if (!$a->login()) {
    print(loginform());
    exit;
}
?>