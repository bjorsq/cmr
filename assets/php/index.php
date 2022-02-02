<?
/**
 * CMR
 *
 * Main index file for the application
 * @package cmr
 * @author Peter Edwards <tech@e-2.org>
 * @version 0.1
 */

/**
 * start output buffering
 */
ob_start();
/**
 * require setup file
 */
require_once(dirname(__FILE__) . "/includes/setup.php");

/**
 * display all errors and notices
 */
ini_set('error_reporting', E_ALL);
ini_set("display_errors", 1);

/**
 * set include_path
 */
ini_set("include_path", $config["lib_dir"] . PATH_SEPARATOR . $config["pear_dir"]); 

/**
 * send headers to prevent page caching
 */
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
/**
 * authorise the user
 */
include($config["lib_dir"] . "/auth.php");

/**
 * require cmr class definition
 */
require_once($config["lib_dir"] . "/cmr.php");

/**
 * create the object
 */
$c = new cmr($dbconn, $config);
/**
 * process POST requests
 */
if (isset($_POST["enter"])) {
    $c->add_new_time();
}
if (isset($_POST["delete"])) {
    $c->delete_time();
}
/**
 * process GET requests
 */
if (isset($_GET["rally"]) || isset($_POST["rally_id"])) {
    $rally_id = isset($_GET["rally"])? $_GET["rally"]: $_POST["rally_id"];
    print(head($a));
    if ($s = $c->get_stages($rally_id)) {
        printf('<div id="rallydata"><h1>%s</h1><table summary="rallies" cellpadding="2" cellspacing="3" border="0" width="100%%">', $s[0]["rally_name"]);
        for ($i = 0; $i < count($s); $i++) {
            printf('<tr><td width="40%%">Stage %d: <strong>%s</strong></td>', ($i + 1), $s[$i]["stage_name"]);
            if ($times = $c->get_best_times($s[$i]["stage_id"])) {
                foreach ($times as $driver => $time) {
                    $timestr = $c->to_timestr($time["drive_time"]);
                    $isnew = $time["drive_date"] > $a->get_data("last_login") && $time["driver_id"] != $a->get_data("driver_id")? "new": "";
                    $delete_link = ($timestr != '--' && strtolower($driver) == strtolower($a->get_data("username")))? sprintf('<a href="#" onclick="deleteEntry(%s, %s);return false;"><img src="images/delete.gif" width="10" height="10" alt="delete" class="trashcan" border="0" /></a>', $time["entry_id"], $rally_id): '';
                    printf('<td class="%s%s" width="15%%">%s%s</td>', strtolower($driver), $isnew, $delete_link, $timestr);
                }
            }
        }
        print('</table></div>');
    } else {
        print('<p>no stages set up for this rally yet. <a href="index.php">go back</a></p>');
    }
    print(forms($a, $c, $rally_id));
    print($foot);
} else {
    print(head($a));
    printf('<div id="rallydata"><p>Welcome, %s</p>', $a->get_data("driver_name"));
    if ($a->get_data("last_login")) {
        printf('<p>Last login: %s</p>', date("l dS of F Y h:i:s A", $a->get_data("last_login")));
    }
    if (!$newtimes = $c->get_times_since($a->get_data("driver_id"), $a->get_data("last_login"))) {
        print('<p>No new times posted since you last logged in</p>');
    } else {
        print('<p>Since your last visit:</p>');
        foreach ($newtimes as $driver => $rally) {
            printf('<p>New times posted by %s in ', $driver);
            $rs = array();
            for ($i = 0; $i < count($rally); $i++) {
                array_push($rs, sprintf('<a href="javascript:goToRally(%d);">%s</a>', $rally[$i]["rally_id"], $rally[$i]["rally_name"]));
            }
            print(implode(", ", $rs) . '.</p>');
        }
    }
    print('</div>');
    print(forms($a, $c));
    print($foot);
}
ob_end_flush();
?>