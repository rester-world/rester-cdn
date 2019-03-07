<?php

$filename = $this->data['file_name'];
$filesize = $this->data['file_size'];
//$filepath = $this->get_uploaded_path();

$headers = [
    "Pragma: public",
    "Expires: 0",
    "Content-Type: application/octet-stream",
    "Content-Disposition: attachment; filename='$filename'",
    "Content-Transfer-Encoding: binary",
    "Content-Length: $filesize"
];
rester::set_header($headers);

return file_get_contents($filepath);
