<?php
/**
 * Authentication class
 *
 * Automatically authenticates users on construction<br />
 * <b>Note:</b> requires the Session class
 * @package skooltv
 * @author Peter Edwards <tech@e-2.org>
 * @version 1.0
 */
require_once(dirname(__FILE__) . "/session.php");
require_once(dirname(__FILE__) . "/setup.php");
class Auth {
    /**
     * Instance of database connection class
     * @access private
     * @var object
     */
    var $db;
    /**
     * Instance of configuration array
     * @access private
     * @var array
     */
    var $config;
    /**
     * Instance of Session class
     * @access private
     * @var object
     */
    var $session;
    /**
     * Url to re-direct to if not authenticated
     * @access private
     * @var string
     */
    var $redirect = 'login.php';
    /**
     * String to use when making hash of username and password
     * @access private
     * @var string
     */
    var $hashKey = 'cmr';
    /**
     * flag used to denote whether the user is logged in
     * @access private
     * @var boolean
     */
    var $logged_in = false;
    /**
     * user_id
     * @var integer
     */
    var $user_id;
    /**
     * Auth constructor
     * Checks for valid user automatically
     * @param object database connection
     * @param string URL to redirect to on failed login
     * @param string key to use when making hash of username and password
     * @param boolean if passwords are md5 encrypted in database (optional)
     * @access public
     */
    function Auth(&$db, &$config)
    {
        $this->db =& $db;
        $this->config =& $config;
        $this->session =& new Session($config);
        if ($this->session->get('login_hash')) {
            $this->confirm_auth();
        }
    }
    /**
     * Checks username and password against database
     * @return void
     * @access private
     */
    function login()
    {
        // See if we have values already stored in the session
        if ($this->session->get('login_hash')) {
            return $this->confirm_auth();
        }
        // If this is a fresh login, check $_POST variables
        if (isset($_POST["username"]) && trim($_POST["username"]) != '' && isset($_POST["password"]) && trim($_POST["password"]) != '') {
            //prepare variables
            $username = trim($_POST["username"]);
            $password = trim($_POST["password"]);
            // Escape the variables for the query
            $dbu = $this->db->Quote($username);
            $dbp = $this->db->Quote(md5($password));
            // Get user with this combination
            $query = sprintf("SELECT * FROM `%s` WHERE `%s` = %s AND `%s` = %s", $this->config["tbl_auth"], $this->config["un_col"], $dbu, $this->config["pw_col"], $dbp);
            $result = $this->db->Execute($query);
            // If there isn't is exactly one entry, redirect
            if ($result === false || $result->EOF) {
                return false;
            // Else is a valid user; set the session variables
            } else {
                $row = $result->GetRowAssoc(false);
                $this->user_id = $row[$this->config["id_col"]];
                $this->store_auth($row);
                $query = sprintf("UPDATE %s SET `last_login` = %d WHERE `driver_id` = %d", $this->config["tbl_drivers"], time(), $this->user_id);
                $result = $this->db->Execute($query);
                return true;
                
            }
        } else {
            return false;
        }
    }
    
    /**
     * Sets the session variables after a successful login
     * @return void
     * @access protected
     */
    function store_auth($data)
    {
        $this->session->set("user_id", $data[$this->config["id_col"]]);
        $this->session->set("username", $data[$this->config["un_col"]]);
        $this->session->set("password", $data[$this->config["pw_col"]]);
        $this->session->set("data", $data);
        // Create a session variable to use to confirm sessions
        $hashKey = md5($this->hashKey . $data[$this->config["un_col"]] . $data[$this->config["pw_col"]]);
        $this->session->set('login_hash', $hashKey);
    }
    /**
     * Confirms that an existing login is still valid
     * @return void
     * @access private
     */
    function confirm_auth()
    {
        $username = $this->session->get('username');
        $password = $this->session->get('password');
        $clienthash = $this->session->get('login_hash');
        if (md5($this->hashKey . $username . $password) != $clienthash) {
            $this->logout();
            return false;
        } else {
            $this->logged_in = true;
            return true;
        }
    }
    /**
     * Confirms that a given password is correct
     * @return boolean
     * @access private
     */
    function confirm_password($password)
    {
        $username = $this->session->get('username');
        $clienthash = $this->session->get('login_hash');
        if (md5($this->hashKey . $username . $password) != $clienthash) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * Logs the user out
     * @param boolean Parameter to pass on to Auth::redirect() (optional)
     * @return void
     * @access public
     */
    function logout()
    {
        $this->session->delete('username');
        $this->session->delete('password');
        $this->session->delete('login_hash');
        $this->logged_in = false;
        $this->session->destroy();
        return true;
    }
    
    /**
     * get_data
     */
    function get_data($key)
    {
        if ($data = $this->session->get($key)) {
            return $data;
        } else {
            $data = $this->session->get("data");
            if (isset($data[$key])) {
                return $data[$key];
            } else {
                return false;
            }
        }
    }
}
?>