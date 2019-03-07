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
        throw new Exception("파일이 없습니다.");
    }
    $file_name = urldecode(explode('_',$file)[1]);
    $file_size = filesize($file_path);
    $headers = [
        "Pragma: public",
        "Expires: 0",
        "Content-Type: application/octet-stream",
        "Content-Disposition: attachment; filename='$file_name'",
        "Content-Transfer-Encoding: binary",
        "Content-Length: $file_size"
    ];
    foreach ($headers as $v)
    {
        header($v);
    }
    echo file_get_contents($file_path);
    exit;
}
catch (Exception $e)
{
    rester_response::error($e->getMessage());
    rester_response::error_trace(explode("\n",$e->getTraceAsString()));
}

rester_response::run();


$filename = $this->data['file_name'];
$filesize = $this->data['file_size'];
//$filepath = $this->get_uploaded_path();

