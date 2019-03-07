<?php

/**
 * Class file
 */
class rester_upload
{
    const QUERY_MODULE  = 'm';
    const FORM_NAME     = 'rester-cdn';
    protected $module_name; // 호출 모듈명
    protected $upload_date; // 파일업로드 경로(date)
    protected $upload_path; // 파일업로드 경로(all)

    /**
     * rester_upload constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        if(!cfg::check_upload())
        {
            throw new Exception("업로드 권한 오류");
        }

        /// Extract file_path & check file
        // Check module name
        if(preg_match('/^[a-z0-9-_]*$/i',strtolower($_GET[QUERY_MODULE]),$matches))
        {
            $this->module_name = $matches[0];
        }
        else
        {
            throw new Exception("모듈명이 올바르지 않습니다.");
        }

        // 최종 업로드 경로
        $this->upload_date = date('Y-m/d');
        $this->upload_path = sprintf("../files/%s/%s/",$this->module_name, $this->upload_date);

        // 업로드 폴더 생성
        umask(0);
        mkdir($this->upload_path, 0775, true);
    }

    /**
     * 업로드될 파일 경로 생성
     *
     * 웹에서 실행가능한 파일들 방지
     * 중복된 파일이 있을경우 반복해서 파일명 생성
     *
     * @param string $file_name
     * @return string 생성된 파일 경로
     */
    public function gen_filename($file_name)
    {
        $file_name = urlencode($file_name);
        do
        {
            $gen_file_name = substr(md5(uniqid(time())),0,20).'_'.$file_name;
        } while(is_file($this->upload_path.$gen_file_name));
        return $gen_file_name;
    }

    /**
     * @param string $file_name
     *
     * @return string
     */
    public function get_cdn_path($file_name)
    {
        $ext = substr($file_name,strrpos($file_name,'.')+1);
        $filename = substr($file_name,0,strrpos($file_name,'.'));
        return '/rester-cdn/'.$this->module_name.'/'.urlencode(base64_encode($this->upload_date.'/'.$filename)).'.'.$ext;
    }

    /**
     * @return array 업로드된 파일목록
     * @throws Exception
     */
    public function run()
    {
        // 업로드된 파일
        $uploaded_files = array();
        // 폼이름
        $name = self::FORM_NAME;

        // 단일파일 => 파일 배열
        if(!is_array($_FILES[$name]['name']) && $_FILES[$name]['name'])
        {
            $files['name'][0] = $_FILES[$name]['name'];
            $files['type'][0] = $_FILES[$name]['type'];
            $files['tmp_name'][0] = $_FILES[$name]['tmp_name'];
            $files['size'][0] = $_FILES[$name]['size'];
            $_FILES[$name] = $files;
        }

        // 파일개수만큼 돌기
        foreach($_FILES[$name]['name'] as $k=>$v)
        {
            $file_name = $_FILES[$name]['name'][$k];
            $file_ext = array_pop(explode('.',$file_name));
            $tmp_name = $_FILES[$name]['tmp_name'][$k];

            $filesize_limit = cfg::upload_limit_filesize();
            $filesize = $_FILES[$name]['size'][$k];
            if($filesize_limit>0 && $filesize_limit<$filesize)
                throw new Exception("파일 업로드 용량 초과. ({$filesize}/$filesize_limit)");

            // 확장자 체크
            if(!cfg::check_extension($file_ext))
                throw new Exception("Not allowed file extension. ({$file_ext})");

            // 이미지 크기 체크
            $mime = $_FILES[$name]['type'][$k];
            if(strpos($mime,'image')===0)
            {
                $limit_width = cfg::upload_limit_width();
                $limit_height = cfg::upload_limit_height();
                $image_size = getimagesize($tmp_name);
                if($limit_width>0 && $limit_width<$image_size[0])
                    throw new Exception("이미지(width) 크기 초과 ({$image_size[0]}/{$limit_width})");
                if($limit_height>0 && $limit_height<$image_size[1])
                    throw new Exception("이미지(height) 크기 초과 ({$image_size[1]}/{$limit_height})");
            }

            // 파일 업로드
            if(is_uploaded_file($tmp_name))
            {
                $real_file_name = $this->gen_filename($file_name);
                $dest_file = $this->upload_path.$real_file_name;

                if(move_uploaded_file($tmp_name, $dest_file))
                {
                    umask(0);
                    chmod($dest_file, 0664);
                    $uploaded_files[] = $this->get_cdn_path($real_file_name);
                }
            }
        }

        return $uploaded_files;
    }
}
