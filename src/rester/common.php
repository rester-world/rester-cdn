<?php define('__RESTER__', TRUE);

///=============================================================================
/// Set the global variables [_POST / _GET / _COOKIE]
/// initial a post and a get variables.
/// if not support short grobal variables, will be avariable.
///=============================================================================
if (isset($HTTP_POST_VARS) && !isset($_POST))
{
    $_POST   = &$HTTP_POST_VARS;
    $_GET    = &$HTTP_GET_VARS;
    $_SERVER = &$HTTP_SERVER_VARS;
    $_COOKIE = &$HTTP_COOKIE_VARS;
    $_ENV    = &$HTTP_ENV_VARS;
    $_FILES  = &$HTTP_POST_FILES;
    if (!isset($_SESSION))
        $_SESSION = &$HTTP_SESSION_VARS;
}

// force to set register globals off
// http://kldp.org/node/90787
if(ini_get('register_globals'))
{
    foreach($_GET as $key => $value) { unset($$key); }
    foreach($_POST as $key => $value) { unset($$key); }
    foreach($_COOKIE as $key => $value) { unset($$key); }
}

function stripslashes_deep($value)
{
    $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    return $value;
}

// if get magic quotes gpc is on, set off
// set magic_quotes_gpc off
if (get_magic_quotes_gpc())
{

    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

///=============================================================================
/// add slashes
///=============================================================================
if(is_array($_POST)) array_walk_recursive($_POST, function(&$item){ $item = addslashes($item); });
if(is_array($_GET)) array_walk_recursive($_GET, function(&$item){ $item = addslashes($item); });
if(is_array($_COOKIE)) array_walk_recursive($_COOKIE, function(&$item){ $item = addslashes($item); });

