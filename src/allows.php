<?php

try {
    require_once './rester/common.php';
    $rester = new rester_allows();
    $rester->run();
} catch (Exception $e) {
    rester_response::error($e->getMessage());
    rester_response::error_trace(explode("\n", $e->getTraceAsString()));
}

rester_response::run();
