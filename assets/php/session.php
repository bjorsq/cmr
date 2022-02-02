<?php
/**
 * session.php
 *
 * contains definitions for the Session class (a wrapper around PHP's session
 * functions) and the db_session class (used as a custom session handler to store
 * session data in a database). Also contains some settings for sessions.
 * @author Peter Edwards <tech@e-2.org>
 * @package skooltv
 * @version 1.0
 */

/* Make sure session handling is set to 'user' */
ini_set('session.save_handler', 'user');
/* Change the session ID name */
ini_set('session.name', 'cmr');
/* make sure only cookies are used */
ini_set('session.use_only_cookies', '1');

/* include adodb class libraries and database connection string */
require_once(dirname(__FILE__) . "/setup.php");
/* Create new object of class, passing the database connection object as a parameter */
$db_sessObj = new db_session($dbconn, $config);
/* Change the save_handler to use the class functions */
session_set_save_handler (array(&$db_sessObj, '_open'),
                          array(&$db_sessObj, '_close'),
                          array(&$db_sessObj, '_read'),
                          array(&$db_sessObj, '_write'),
                          array(&$db_sessObj, '_destroy'),
                          array(&$db_sessObj, '_gc'));
/**
 * db_session class
 *
 * enables storage of session information in a database
 */
class db_session
{
    /**
     * @var object $db
     */
    var $db;
    
    /**
     * @var string $session_table - the name of the database table to store sessions in
     */
    var $session_table = "sessions";
    
    /**
     * @var integer $lifetime - lifetime of the session in minutes
     */
    var $lifetime = 10;
    
    /**
     * constructor
     */
    function db_session(&$dbconn, &$config)
    {
        $this->db = &$dbconn;
        $this->config = &$config;
        if (isset($this->config["tbl_sessions"])) {
            $this->session_table = $this->config["tbl_sessions"];
        }
        if (isset($this->config["session_lifetime"])) {
            $this->lifetime = $this->config["session_lifetime"];
        }
        
    }

    /**
     * Opens a new session.
     *
     * nothing to do here as we have already got a database connection object 
     * stored in the member variable $db
     * @param string $save_path    The value of session.save_path.
     * @param string $session_name The name of the session ('PHPSESSID').
     */
    function _open($path, $name)
    {
        return true;
    }

    /**
     * Close session
     *
     * This is used for a manual call to the session garbage collection function 
     */
    function _close()
    {
        $this->_gc(0);
        return true;
    }

    /**
     * Reads the requested session data from the database.
     *
     * @param  string  $session_id Unique session ID of the requested entry.
     * @return string  The requested session data.  A failure condition will
     *                 result in an empty string being returned.
     */
    function _read($session_id = false)
    {
        if ($session_id === false) {
            return '';
        }
        /**
         * Attempt to retrieve a row of existing session data.
         *
         * We begin by starting a new transaction.  All of the session-related
         * operations will happen within this transcation.  The transaction will
         * be committed by either session_write() or session_destroy(), depending
         * on which is called.
         *
         * We mark this SELECT statement as FOR UPDATE because it is probable that
         * we will be updating this row later on in session_write(), and performing
         * an exclusive lock on this row for the lifetime of the transaction is
         * desirable.
         */
        $query = sprintf("SELECT * FROM %s WHERE session_id = '%s'", $this->session_table, $session_id);
        $this->log($query);
        $result = $this->db->Execute($query);
        if ($result === false || $result->EOF) {
            /*
             * If we were unable to retrieve an existing row of session data, insert a
             * new row.  This ensures that the UPDATE operation in _write() will succeed.
             */
            $query = sprintf("INSERT INTO %s (session_id, session_time, session_start, session_value) VALUES ('%s', '%s', '%s', '')", $this->session_table, $session_id, time(), time());
            $this->log($query);
            $result = $this->db->Execute($query);
            if ($result === false) {
                /*
                 * If the insertion fails, it may be due to a race condition that
                 * exists between multiple instances of this session handler in the
                 * case where a new session is created by multiple script instances
                 * at the same time (as can occur when multiple session-aware frames
                 * exist).
                 *
                 * In this case, we attempt another SELECT operation which will
                 * hopefully retrieve the session data inserted by the competing
                 * instance.
                 */
                $query = sprintf("SELECT * FROM %s WHERE session_id = '%s'", $this->session_table, $session_id);
        $this->log($query);
                $result = $this->db->Execute($query);
                if ($result === false || $result-EOF) {
                    /* 
                     * If this attempt also fails, give up and return an empty string.
                     */
                    return '';
                } else {
                    $session_row = $result->GetRowAssoc(false);
                    $session_data = $session_row["session_value"];
                    return $session_data;
                }
            } else {
                return '';
            }
        } else {
            $session_row = $result->GetRowAssoc(false);
            $session_data = $session_row["session_value"];
            return $session_data;
        }
    }

    /**
     * Writes the provided session data associated with the $session_id to the database.
     *
     * @param string $key Unique session ID of the current entry.
     * @param string $val String containing the session data.
     * @return boolean True on success, false on failure.
     */
    function _write($session_id, $data)
    {
        $query = sprintf("UPDATE %s SET session_time = '%s', session_value = %s WHERE session_id = %s", $this->session_table, time(), $this->db->quote($data), $this->db->quote($session_id));
        $this->log($query);
        $result = $this->db->Execute($query);
        if ($result === false || $result->EOF) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Destroy session record in database
     *
     * @param string $key Unique session ID of the current entry.
     * @return boolean True on success, false on failure.
     */
    function _destroy($session_id)
    {
        $query = sprintf("DELETE FROM %s WHERE session_id = %s", $this->session_table, $this->db->quote($session_id));
        $this->log($query);
        $result = $this->db->Execute($query);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Garbage collection, deletes old sessions
     *
     * @param string $life lifetime of sessions (set previously as the $lifetime member variable).
     * @return boolean True on success, false on failure.
     */
    function _gc($life)
    {
        $session_life = strtotime("-" . $this->lifetime . " minutes");
        $query = sprintf("DELETE FROM %s WHERE session_time < %d", $this->session_table, $session_life);
        $this->log($query);
        $result = $this->db->Execute($query);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }
    function log($query) {
		    if (isset($this->config["query_log"])) {
            if ($fh = fopen($this->config["query_log"], "ab")) {
                fwrite($fh, $query);
                fclose($fh);
						}
				}
    }
}
/**
 * Session class
 *
 * a wrapper around PHPs session functions
 */
class Session {
    /**
     * Session constructor<br />
     * Starts the session using session_start()
     * <b>Note:</b> that if the session has already started, session_start()
     * generates a NOTICE - precede with @ to prevent this
     * @access public
     */
    function Session()
    {
        @session_start();
    }

    /**
     * Sets a session variable
     * @param string name of variable
     * @param mixed value of variable
     * @return void
     * @access public
     */
    function set($name,$value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Fetches a session variable
     * @param string name of variable
     * @return mixed value of session varaible
     * @access public
     */
    function get($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return false;
        }
    }

    /**
     * Deletes a session variable
     * @param string name of variable
     * @return boolean
     * @access public
     */
    function delete($name)
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Destroys the whole session
     * @return void
     * @access public
     */
    function destroy ()
    {
        $_SESSION = array();
        session_destroy();
    }
}
?>