<?php

//------------------------------------------------------------------------------
/// load classes
//------------------------------------------------------------------------------
require_once './rester/common.php';
require_once './rester/rester_cdn.class.php';

try
{
    //------------------------------------------------------------------------------
    /// run images
    //------------------------------------------------------------------------------
    $rester = new rester_cdn();
    $rester->init();
    $rester->run();
}
catch (Exception $e)
{
    //------------------------------------------------------------------------------
    /// print failed images
    //------------------------------------------------------------------------------
    header('Content-Type: '.mime_content_type('./'.$no_image));
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60*60*24*30)));
    echo file_get_contents('./'.$no_image);
}

