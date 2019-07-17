<?php

try
{
    require_once './rester/common.php';
    rester_response::body(rester_traffic::get_cache_traffic($_GET[QUERY_TRAFFIC_DELETE] == 'false' ? false : true));
}
catch (Exception $e)
{
    rester_response::error($e->getMessage());
    rester_response::error_trace(explode("\n",$e->getTraceAsString()));
}

rester_response::run();

