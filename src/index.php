<?php

try
{
    require_once './rester/common.php';
    $rester = new rester_cdn();
    $rester->run();
}
catch (Exception $e)
{
    rester_response_image::result_error_image($e->getMessage());
}

// print image
rester_response_image::run();

