<?
/**
 * cmr.php
 *
 * class definition for cmr high scores thingy
 * @author Peter Edwards <tech@e-2.org>
 * @version 0.2 development branch
 */
 
/* include setup file */
require_once(dirname(__FILE__) . "/setup.php");
/**
 * cmr class definition
 */
class cmr
{
    /**
     * @var object $db adodb database connection object
     */
    var $db;
    /**
     * @var array $config configuration settings
     */
    var $config;
    /**
     * @var array $times - times for a given stage
     */
    var $times = array();
    /**
     * @var array $drivers
     */
    var $drivers = array();
    /**
     * @var array $rallies
     */
    var $rallies = array();
    /**
     * constructor
     */
    function cmr(&$dbconn, &$config)
    {
        $this->config =& $config;
        $this->db =& $dbconn;
        $this->get_drivers();
        $this->get_rallies();
    }
    /**
     * get_drivers
     * gets all drivers
     */
    function get_drivers()
    {
        $query = sprintf("SELECT * FROM %s ORDER BY driver_name ASC", $this->config["tbl_drivers"]);
        $result = $this->db->Execute($query);
        if ($result === false) {
            $this->sql_error($this->db, __FILE__, __LINE__, $query);
            return false;
        }
        if ($result->EOF) {
            return false;
        }
        for (; !$result->EOF; $result->MoveNext()) {
            $row = $result->GetRowAssoc(false);
            $this->drivers[] = stripslashes($row["driver_name"]);
        }
    }
    /**
     * get_rally
     * gets all rally information
     */
    function get_rallies()
    {
        $query = sprintf("SELECT * FROM %s ORDER BY rally_name ASC", $this->config["tbl_rally"]);
        $result = $this->db->Execute($query);
        if ($result === false) {
            $this->sql_error($this->db, __FILE__, __LINE__, $query);
            return false;
        }
        if ($result->EOF) {
            return false;
        }
        $count = 0;
        for (; !$result->EOF; $result->MoveNext()) {
            $row = $result->GetRowAssoc(false);
            $this->rallies[$count] = array();
            $this->rallies[$count]["rally_name"] = stripslashes($row["rally_name"]);
            $this->rallies[$count]["rally_id"] = $row["rally_id"];
            $count++;
        }
        return true;
    }
    /**
     * get_rally_select
     * returns a select list with rally information
     * @param integer $selected_id
     */
    function get_rally_select($selected_id = false)
    {
        $sel = $selected_id === false? ' selected="selected"': '';
        $ret = sprintf('<select name="rally" id="rally" onchange="jumpToRally();"><option value="select"%s>select a rally...</option>', $sel);
        for ($i = 0; $i < count($this->rallies); $i++) {
            $sel = ($selected_id !== false && $this->rallies[$i]["rally_id"] == $selected_id)? ' selected="selected"': '';
            $ret .= sprintf('<option value="%s"%s>%s</option>', $this->rallies[$i]["rally_id"], $sel, $this->rallies[$i]["rally_name"]);
        }
        return $ret . '</select>';
    }
    /**
     * get_stages
     * gets all stages for a given rally
     * @param integer rally_id
     */
    function get_stages($rally_id = false)
    {
        if ($rally_id) {
            $query = sprintf("SELECT r.rally_name, s.* FROM %s r, %s s WHERE r.rally_id = s.rally_id AND s.rally_id = %s ORDER BY stage_order ASC", $this->config["tbl_rally"], $this->config["tbl_stages"], $rally_id);
            $result = $this->db->Execute($query);
            if ($result === false) {
                $this->sql_error($this->db, __FILE__, __LINE__, $query);
                return false;
            }
            if ($result->EOF) {
                return false;
            }
            $count = 0;
            $ret = array();
            for (; !$result->EOF; $result->MoveNext()) {
                $row = $result->GetRowAssoc(false);
                $ret[$count] = array();
                $ret[$count]["rally_name"] = stripslashes($row["rally_name"]);
                $ret[$count]["rally_id"] = $row["rally_id"];
                $ret[$count]["stage_id"] = $row["stage_id"];
                $ret[$count]["stage_name"] = $row["stage_name"];
                $ret[$count]["stage_order"] = $row["stage_order"];
                $count++;
            }
            return $ret;
        }
        return false;
    }
    /**
     * get_times
     * gets times for a rally
     */
    function get_times($stage_id = false)
    {
        if ($stage_id) {
            $query = sprintf("SELECT t.entry_id, t.drive_time, t.drive_date, t.stage_id, d.driver_name, d.driver_id FROM %s t, %s d WHERE t.stage_id = %d AND t.driver_id = d.driver_id ORDER BY t.drive_time DESC", $this->config["tbl_times"], $this->config["tbl_drivers"], $stage_id);
            $result = $this->db->Execute($query);
            if ($result === false) {
                $this->sql_error($this->db, __FILE__, __LINE__, $query);
                return false;
            }
            if ($result->EOF) {
                return false;
            }
            $count = 0;
            for (; !$result->EOF; $result->MoveNext()) {
                $row = $result->GetRowAssoc(false);
                $this->times[$count] = array();
                $this->times[$count]["stage_id"] = $row["stage_id"];
                $this->times[$count]["driver_name"] = stripslashes($row["driver_name"]);
                $this->times[$count]["driver_id"] = $row["driver_id"];
                $this->times[$count]["entry_id"] = $row["entry_id"];
                $this->times[$count]["drive_time"] = $row["drive_time"];
                $this->times[$count]["drive_date"] = $row["drive_date"];
                $count++;
            }
        }
    }
    /**
     * get_best_times
     * gets the best times for a given stage
     * @param integer $stage_id
     */
    function get_best_times($stage_id = false)
    {
        $best_times = array();
        if ($stage_id) {
            $this->get_times($stage_id);
            $this->sort_times("drive_time");
            /* get best times for each driver */
            for ($d = 0; $d < count($this->drivers); $d++) {
                for ($i = 0; $i < count($this->times); $i++) {
                    if (!isset($best_times[$this->drivers[$d]])) {
                        if ($this->times[$i]["driver_name"] == $this->drivers[$d] && $this->times[$i]["stage_id"] == $stage_id) {
                            $best_times[$this->drivers[$d]] = $this->times[$i];
                        } 
                    } elseif ($best_times[$this->drivers[$d]]["drive_time"] > $this->times[$i]["drive_time"] && $this->times[$i]["driver_name"] == $this->drivers[$d] && $this->times[$i]["stage_id"] == $stage_id) {
                        $best_times[$this->drivers[$d]] = $this->times[$i];
                    } 
                }
                /* check to see if driver hasn't registered a time */
                if (!isset($best_times[$this->drivers[$d]])) {
                    $best_times[$this->drivers[$d]] = array("drive_time" => 1000000, "drive_date" => 0);
                }
            }
            return $this->sort_best_times($best_times);
        }
        return false;
    }
    
    /**
     * add_new_time
     */
    function add_new_time()
    {
        if (isset($_POST["driver_id"]) && isset($_POST["stage_id"]) && isset($_POST["m"]) && isset($_POST["s"]) && isset($_POST["cs"])) {
            $time = ((((int) $_POST["m"] * 6000) + ((int) $_POST["s"] * 100) + ((int) $_POST["cs"])) / 100);
            $query = sprintf("INSERT INTO %s (entry_id, stage_id, driver_id, drive_time, drive_date) VALUES ('', %d, %d, %.02f, %d);", $this->config["tbl_times"], $_POST["stage_id"], $_POST["driver_id"], $time, time());
            $result = $this->db->Execute($query);
            if ($result === false) {
                $this->sql_error($this->db, __FILE__, __LINE__, $query);
                return false;
            }
            return true;         
        } else {
            return false;
        }
    }
    /**
     * delete_time
     */
    function delete_time()
    {
        if (isset($_POST["delete"]) && isset($_POST["entry_id"])) {
            $query = sprintf("DELETE FROM %s WHERE `entry_id` = %d;", $this->config["tbl_times"], $_POST["entry_id"]);
            $result = $this->db->Execute($query);
            if ($result === false) {
                $this->sql_error($this->db, __FILE__, __LINE__, $query);
                return false;
            }
            return true;         
        } else {
            return false;
        }
    }
    
    /**
     * get_times_since
     */
    function get_times_since($driver_id, $timestamp = 0)
    {
        $query = sprintf("SELECT t.entry_id, t.drive_time, t.drive_date, t.stage_id, d.driver_name, d.driver_id, s.stage_name, s.stage_order, r.rally_name, r.rally_id FROM %s t, %s d, %s s, %s r WHERE t.stage_id = s.stage_id AND t.driver_id = d.driver_id AND t.drive_date > %d AND t.driver_id != %d AND s.rally_id = r.rally_id ORDER BY r.rally_id", $this->config["tbl_times"], $this->config["tbl_drivers"], $this->config["tbl_stages"], $this->config["tbl_rally"], $timestamp, $driver_id);
        $result = $this->db->Execute($query);
        if ($result === false) {
            $this->sql_error($this->db, __FILE__, __LINE__, $query);
            return false;
        }
        if ($result->EOF) {
            return false;
        }
        $count = 0;
        $times = array();
        for (; !$result->EOF; $result->MoveNext()) {
            $row = $result->GetRowAssoc(false);
            $times[$count] = $row;
            $count++;
        }
        if (count($times)) {
            return $this->format_newtimes($times);
        } else {
            return false;
        }
    }
    function format_newtimes($newtimes = array())
    {
        $results = array();
        $res_ids = array();
        for ($i = 0; $i < count($newtimes); $i++) {
            if (!isset($results[$newtimes[$i]["driver_name"]])) {
                $results[$newtimes[$i]["driver_name"]] = array();
                $res_ids[$newtimes[$i]["driver_name"]] = array();
            }
            if (!in_array($newtimes[$i]["rally_id"], $res_ids[$newtimes[$i]["driver_name"]])) {
                $results[$newtimes[$i]["driver_name"]][] = array("rally_id" => $newtimes[$i]["rally_id"], "rally_name" => $newtimes[$i]["rally_name"]);
                $res_ids[$newtimes[$i]["driver_name"]][] = $newtimes[$i]["rally_id"];
            }
        }
        return $results;
    }
    /**
     * times formatting function
     */
    function to_timestr($time)
    {
        if ($time == 1000000) return '--';
        $mins = floor($time/60);
        $secs = $time - ($mins * 60);
        return sprintf("%02d:%05.2f", $mins, $secs);
    }
    /**
     * times array sorting function
     */
    function sort_times($key)
    {
        $cmp_val = "((\$a['$key']>\$b['$key'])?1:((\$a['$key']==\$b['$key'])?0:-1))";
        $cmp = create_function('$a, $b', "return $cmp_val;");
        uasort($this->times, $cmp);
        reset($this->times);
    }
    /**
     * times array sorting function
     */
    function sort_best_times($t)
    {
        $cmp_val = "((\$a['drive_time']>\$b['drive_time'])?1:((\$a['drive_time']==\$b['drive_time'])?0:-1))";
        $cmp = create_function('$a, $b', "return $cmp_val;");
        uasort($t, $cmp);
        return $t;
    }
    /**
     * sql_error
     *
     * wrapper for the adodb error object so the query is shown in the output
     * and database errors can be logged or output to the browser
     * @param object $db adodb database connection object
     * @param string $prg file where the error took place
     * @param integer $line line number where the error took place
     * @param $query SQL query sent to the database which caused the error
     */
    function sql_error($db, $prg = '', $line = 0, $query = 'No database query specified')
    {
        $lcmessage = '<p class="error">An error has occurred in file' . $prg . ' - Line No.: ' . $line . 'when trying to execute the query: ' . $query . '</p>';
        if ($db->ErrorNo() <> 0) {
            $lcmessage .= '<p class="error">The database said (Error number:' . $db->ErrorNo() . ') : ' . $db->ErrorMsg() . '</p>';
        }
        die($lcmessage);
    }

}
/**
 * end of class cmr
 */
?>