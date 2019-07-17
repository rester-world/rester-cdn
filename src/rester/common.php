<?php

define('__RESTER__', TRUE);
/**
 *  @file common.php
 *  @brief  가장 먼저 실행되는 파일이면서 각종 초기화를 수행함
 */

require_once dirname(__FILE__).'/cfg.class.php';
require_once dirname(__FILE__).'/rester_cdn.class.php';
require_once dirname(__FILE__).'/rester_response.class.php';
require_once dirname(__FILE__).'/rester_response_image.class.php';
require_once dirname(__FILE__).'/rester_upload.class.php';
require_once dirname(__FILE__).'/rester_traffic.class.php';
require_once dirname(__FILE__).'/rester_allows.class.php';

// ini set
ini_set('memory_limit','-1');

try
{
    cfg::init();
}
catch (Exception $e)
{
    throw new $e;
}

// -----------------------------------------------------------------------------
/// catch 되지 않은 예외에 대한 처리함수
// -----------------------------------------------------------------------------
set_exception_handler(function(Error $e) {
    if($e->getFile()=='/var/www/html/index.php')
    {
        rester_response_image::result_error_image(cfg::error_images_etc);
        rester_response_image::run();
    }
    else
    {
        rester_response::error($e->getMessage());
        rester_response::error_trace(explode("\n",$e->getTraceAsString()));
        rester_response::run();
    }
});

// -----------------------------------------------------------------------------
/// 오류출력설정
// -----------------------------------------------------------------------------
if (cfg::debug_mode())
    error_reporting(E_ALL ^ (E_NOTICE | E_STRICT | E_WARNING | E_DEPRECATED));
else
    error_reporting(0);

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

