<?php
/**
 * templates.php
 * code snippets for parts of CMR application
 * @package cmr
 * @author Peter Edwards <tech@e-2.org>
 * @version 1.0
 */
 
/**
 * head
 * prints HTML for the <head> part of all pages
 */ 
function head(&$a)
{
    $out = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>cmr</title><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /><script language="JavaScript" type="text/javascript" src="js/scripts.js"></script><link rel="stylesheet" href="css/style.css" type="text/css" />';
    $out .= sprintf('<style type="text/css">body {background:#fff url(images/bg_%s.jpg) no-repeat left top; }</style>', strtolower($a->get_data("username")));
    $out .= '</head><body><div id="page">';
    return $out;
}
$header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>cmr</title><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /><script language="JavaScript" type="text/javascript" src="js/scripts.js"></script><link rel="stylesheet" href="css/style.css" type="text/css" media="screen" /><link rel="stylesheet" href="css/print.css" type="text/css" media="print" /></head><body><div id="page">';

/**
 * @var string $foot - HTML for the footer of all pages
 */ 
$foot = <<<EOF
</div>
</body>
</html>
EOF;

/**
 * login
 */
function loginform($error = false)
{
    print('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"');
    print(' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
    print('<html xmlns="http://www.w3.org/1999/xhtml">');
    print('<head>');
    print('<title>Please log in</title>');
    print('<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />');
    print('<link rel="stylesheet" href="css/style.css" type="text/css" />');
    print('</head><body>');
    print('<div id="login"><form method="post" action="index.php"><fieldset><legend>Please log in</legend>');
    if ($error) {
        print('<p class="error">Sorry, but your details were not recognised</p>');
    }
    print('<label for="username">username:</label><input type="text" size="30" name="username" id="username" maxlength="50" />');
    print('<label for="password">password:</label><input type="password" size="30" name="password" id="password" maxlength="50" />');
    print('<input type="submit" value="log in" class="submit" /></fieldset></form></div>');
    print('</body></html>');
}

/**
 * entryform
 * @param object auth object
 * @param string data
 */
function forms(&$a, &$c, $rally_id = false)
{
    $out = '<div id="forms">';
    /* rally selection form */
    $out .= '<div id="rallyform"><form action="index.php" method="get"><fieldset><legend>rally navigation</legend>';
    $out .= $c->get_rally_select($rally_id);
    $out .= '</fieldset></form></div>';
    /* rally time entry form */
    if ($rally_id !== false && $s = $c->get_stages($rally_id)) {
        $out .= sprintf('<div id="entryform"><form action="index.php" method="post" name="entry" id="entry" onsubmit="return checkEntryForm();"><input type="hidden" name="driver_name" value="%s" /><input type="hidden" name="driver_id" value="%d" /><fieldset><legend>enter a new time</legend>', $a->get_data("username"), $a->get_data("driver_id"));
        $out .= sprintf('<input type="hidden" name="rally_id" value="%d" /><select name="stage_id" id="stage_id" onchange="jumpToEntry();">', $rally_id);
        for ($i = 0; $i < count($s); $i++) {
            $out .= sprintf('<option value="%s">%s</option>', $s[$i]["stage_id"], $s[$i]["stage_name"]);
        }
        $out .= '</select>';
        $out .= '<input type="text" size="1" maxlength="1" name="m" id="m" class="time" onkeyup="goToNext(this);" autocomplete="off" />:<input type="text" size="2" maxlength="2" name="s" id="s" class="time" onkeyup="goToNext(this);" autocomplete="off" />:<input type="text" size="2" maxlength="2" name="cs" id="cs" class="time" onkeyup="goToNext(this);" autocomplete="off" /><input type="submit" name="enter" id="enter" value="-&gt;" class="button" /></fieldset></form></div>';
    }
    /* log out form */
    $out .= '<div id="logoutform"><fieldset><form action="index.php" method="get"><input type="submit" name="action" id="action" value="exit" /><input type="button" name="home" value="home" onclick="window.location=\'index.php\'" /></fieldset></form></div>';
    /* deletion form */
    $out .= '<div id="deleteform"><form action="index.php" method="post" name="deleteForm" id="deleteForm"><input type="hidden" name="delete" value="yes" /><input type="hidden" name="entry_id" id="entry_id" value="" /><input type="hidden" name="rally_id" id="rally_id" value="" /></form></div>';
    $out .= '</div>';
    return $out;
}
?>
