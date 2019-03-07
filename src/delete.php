<?php

try
{
    require_once './rester/common.php';

    $extension = substr($_GET[QUERY_FILE],strrpos($_GET[QUERY_FILE],'.')+1);
    if(preg_match('/^[a-z0-9-_]*$/i',strtolower($_GET[QUERY_MODULE]),$matches))
    {
        $module = $matches[0];
    }
    else
    {
        throw new Exception("모듈명 오류");
    }

    $file = base64_decode(urldecode(substr($_GET[QUERY_FILE],0,strrpos($_GET[QUERY_FILE],'.'))));
    $file_path = '../files/'.$module.'/'.$file.'.'.$extension;
    if(!is_file($file_path))
    {
        throw new Exception("삭제할 파일이 없습니다.");
    }

    foreach (glob($file_path.'*') as $v)
    {
        if(is_file($v)) unlink($v);
    }

}
catch (Exception $e)
{
    rester_response::error($e->getMessage());
    rester_response::error_trace(explode("\n",$e->getTraceAsString()));
}

rester_response::run();

