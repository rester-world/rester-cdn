<?php
//------------------------------------------------------
/// cfg const value
/// use cfg class
//------------------------------------------------------
const QUERY_FILE            = 'file';
const QUERY_MODULE          = 'm';
const QUERY_CACHE           = 'cache';
const QUERY_CACHE_TIMEOUT   = 'timeout';
const QUERY_THUMB           = 'thumb';
const QUERY_THUMB_WIDTH     = 'width';
const QUERY_THUMB_HEIGHT    = 'height';

/**
 * Class rester
 */
class rester_cdn
{
    /**
     * @var Redis
     */
    protected $redis = null;

    protected $cache = false;
    protected $cache_connected = false;
    protected $cache_timeout = 600;
    protected $cache_key = null;
    protected $cache_size_key = null;
    protected $cache_header_key = null;

    protected $thumb = false;
    protected $thumb_width = 0;
    protected $thumb_height = 0;

    protected $file_path = false;
    protected $file_thumb_path = false;

    /**
     * rester_cdn constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        if(!cfg::check_site())
        {
            throw new Exception(cfg::error_images_site);
        }

        $extension = substr($_GET[QUERY_FILE],strrpos($_GET[QUERY_FILE],'.')+1);
        if(!cfg::check_extension($extension))
        {
            throw new Exception(cfg::error_images_extension);
        }

        // check request method
        if(cfg::method()!='GET')
        {
            throw new Exception(cfg::error_images_method);
        }

        /// Extract file_path & check file
        // Check module name
        if(preg_match('/^[a-z0-9-_]*$/i',strtolower($_GET[QUERY_MODULE]),$matches))
        {
            $module = $matches[0];
        }
        else
        {
            throw new Exception(cfg::error_images_module_name);
        }

        $file = base64_decode(urldecode(substr($_GET[QUERY_FILE],0,strrpos($_GET[QUERY_FILE],'.'))));
        $file_path = '../files/'.$module.'/'.$file.'.'.$extension;
        if(!is_file($file_path))
        {
            throw new Exception(cfg::error_images_noimage);
        }
        $this->file_path = $file_path;

        //----------------------------------------------
        /// Extract request parameters
        //----------------------------------------------
        // Cache
        if($_GET[QUERY_CACHE])
        {
            $this->cache = true;
            if($_GET[QUERY_CACHE_TIMEOUT]) $this->cache_timeout = $_GET[QUERY_CACHE_TIMEOUT];
            else if(cfg::cache_timeout()) $this->cache_timeout = cfg::cache_timeout();
        }
        // Thumb
        if($_GET[QUERY_THUMB])
        {
            $this->thumb = true;
            if($_GET[QUERY_THUMB_WIDTH]) $this->thumb_width = $_GET[QUERY_THUMB_WIDTH];
            if($_GET[QUERY_THUMB_HEIGHT]) $this->thumb_height = $_GET[QUERY_THUMB_HEIGHT];
        }

        //----------------------------------------------
        /// Create thumbnail path
        //----------------------------------------------
        if($this->thumb)
        {
            $this->file_thumb_path = sprintf("%s_%s_%s", $file_path, $this->thumb_width, $this->thumb_height);
            $this->create_thumb();
        }

        //------------------------------------------------------------------------------
        /// redis host & port
        //------------------------------------------------------------------------------
        if((cfg::cache_host() && cfg::cache_port()))
        {
            $this->cache_connected = true;

            $this->redis = new Redis();
            $this->redis->connect(cfg::cache_host(), cfg::cache_port());
            if(cfg::cache_auth()) $this->redis->auth(cfg::cache_auth());

            $__path = urlencode($this->file_thumb_path?$this->file_thumb_path:$this->file_path);
            $this->cache_header_key = 'rester-cdn-header-'.$__path;
            $this->cache_size_key = 'rester-cdn-size-'.$__path;
            $this->cache_key = 'rester-cdn-'.$__path;
        }
    }

    /**
     * rester destructor
     */
    public function __destruct()
    {
        if($this->redis) $this->redis->close();
    }

    /**
     * @return bool|string
     */
    protected function get_cache_header()
    {
        return $this->redis->get($this->cache_header_key);
    }

    /**
     * @return bool|string
     */
    protected function get_cache()
    {
        return $this->redis->get($this->cache_key);
    }

    /**
     * @return bool|string
     */
    protected function get_cache_size()
    {
        return $this->redis->get($this->cache_size_key);
    }

    /**
     * @param string $v
     */
    protected function set_cache_header($v)
    {
        $this->redis->set($this->cache_header_key,$v,$this->cache_timeout);
    }

    /**
     * @param string|object $v
     */
    protected function set_cache_size($v)
    {
        $this->redis->set($this->cache_key,$v,$this->cache_timeout);
    }

    /**
     * @param string|object $v
     */
    protected function set_cache($v)
    {
        $this->redis->set($this->cache_key,$v,$this->cache_timeout);
    }

    /**
     * run rester
     *
     * @throws Exception
     */
    public function run()
    {
        $response_mime = null;
        $response_data = null;
        $response_size = null;

        $__path = $this->file_thumb_path?$this->file_thumb_path:$this->file_path;

        //--------------------------------------------------------------------------------
        /// Get cached data
        //--------------------------------------------------------------------------------
        if($this->cache)
        {
            $response_data = $this->get_cache();
            $response_mime = $this->get_cache_header();
            $response_size = $this->get_cache_size();
        }


        //--------------------------------------------------------------------------------
        /// include file
        //--------------------------------------------------------------------------------
        if(!$response_mime || !$response_data || !$response_size)
        {
            $response_mime = mime_content_type($__path);
            $response_data = file_get_contents($__path);
            $response_size = filesize($__path);

            if($this->cache)
            {
                $this->set_cache_header($response_mime);
                $this->set_cache_size($response_size);
                $this->set_cache($response_data);
            }
        }

        if ($this->cache_connected)
        {
            rester_traffic::set_cache($this->redis);
            rester_traffic::set_cache_traffic([
                'ip' => cfg::access_ip(),
                'referer' => $_SERVER['HTTP_REFERER'],
                'datetime' => date("Y-m-d H:i:s"),
                'path' => $__path,
                'size' => $response_size]);
        }

        rester_response_image::mime($response_mime);
        rester_response_image::expires(cfg::expires());
        rester_response_image::data($response_data);
    }

    /**
     * 썸네일 이미지 생성
     * 썸네일 비율 유지한채 축소 여백에는 가장 많이 쓰인 컬러키 값으로 체움
     * 이미 생성된 썸네일은 다시 생성하지 않음
     *
     * @return string
     * @throws Exception
     */
    protected function create_thumb()
    {
        $path_source = $this->file_path;
        $thumb_path = $this->file_thumb_path;
        $thumb_width = $this->thumb_width;
        $thumb_height = $this->thumb_height;

        // 썸네일 이미지 인스턴스
        $target = null;

        if(!is_file($thumb_path))
        {
            // 소스이미지
            $source = $this->load($path_source);
            list($ori_width, $ori_height) = getimagesize($path_source);

            // 썸네일 비율 및 사이즈 계산
            // 찌그러지지 않도록 비율로 줄인다.

            // 1. 원본영상 크기를 긴 사이즈 기준으로 비율축소함
            // 2. 짧은 사이즈 가 썸네일 크기를 초과하지 않으면 바로 적용
            $width = $ori_width;
            $height = $ori_height;

            if($thumb_width && $thumb_height)
            {
                if($width>$height)
                {
                    $ratio = $thumb_width/$width;
                    $height = $height*$ratio;
                    $width = $thumb_width;

                    // 썸네일 크기 보다 초과될 경우 다시 줄인다.
                    if($height>$thumb_height)
                    {
                        $ratio = $thumb_height/$height;
                        $width = $width*$ratio;
                        $height = $thumb_height;
                    }
                }
                else
                {
                    $ratio = $thumb_height/$height;
                    $width = $width*$ratio;
                    $height = $thumb_height;

                    // 썸네일 크기 보다 초과될 경우 다시 줄인다.
                    if($width>$thumb_width)
                    {
                        $ratio = $thumb_width/$width;
                        $height = $height*$ratio;
                        $width = $thumb_width;
                    }
                }
            }
            elseif($thumb_width)
            {
                if($thumb_width>$ori_width) $thumb_width = $ori_width;
                $ratio = $thumb_width/$width;
                $height = $height*$ratio;
                $width = $thumb_width;
                $thumb_height = $height;
            }
            elseif($thumb_height)
            {
                if($thumb_height>$ori_height) $thumb_height = $ori_height;
                $ratio = $thumb_height/$height;
                $width = $width*$ratio;
                $height = $thumb_height;
                $thumb_width = $width;
            }

            // 칠하기 포지션 계산
            $diff_width = abs($thumb_width - $width);
            $diff_height = abs($thumb_height - $height);

            $dest_x = 0;
            $dest_y = 0;
            $dest_width = $width;
            $dest_height = $height;

            // 가로 너비가 넓을 경우 가로기준 맞추고 상하에 배경색 칠함
            if($diff_width>$diff_height)
            {
                $dest_x = round($diff_width/2);
            }
            else
            {
                $dest_y = round($diff_height/2);
            }

            // 배경을 깔기위한 컬러키 값 뽑기
            // 가장 많이 사용된 컬러 값
            $colors = array();
            for($x = 0; $x < $ori_width; $x+=2)
            {
                for($y = 0; $y < $ori_height; $y+=2)
                {
                    $c = imagecolorat($source, $x, $y);
                    if(array_key_exists($c, $colors)) $colors[$c]++;
                    else $colors[$c] = 1;
                }
            }

            // 정렬하여 가장 많이 사용된 컬러키 값을 뽑아옴
            arsort($colors);
            $bgColorRGB = imagecolorsforindex($source, array_shift(array_keys($colors)));

            $target = @imagecreatetruecolor($thumb_width, $thumb_height);
            imagefill($target,0,0,imagecolorallocate($target,$bgColorRGB['red'],$bgColorRGB['green'],$bgColorRGB['blue']));

            @imagecopyresampled(
                $target,
                $source,
                $dest_x,
                $dest_y,
                0,
                0,
                $dest_width,
                $dest_height,
                $ori_width,
                $ori_height
            );

            // 파일로 쓰기
            @imagejpeg($target, $thumb_path, 100);
            umask(0);
            @chmod($thumb_path, 0664); // 추후 삭제를 위하여 파일모드 변경
            imagedestroy($source);
        }

        return $thumb_path;
    }

    /**
     * 이미지 형태의 파일을 읽어들인다.
     * 파일 형식에 맞게 이미지 리소스를 로드함
     * 이미지 형식이 아닌 파일은 false 를 반환하며 에러코드를 남긴다.
     * $echo 변수에 따라 바로 출력하거나 리소스를 반환한다.
     *
     * @param string $path 이미지 경로
     *
     * @return false|resource 이미지 리소스 반환 실패시 false
     *
     * @throws Exception
     */
    protected function load($path)
    {
        if(is_file($path))
        {
            $mime_type = mime_content_type($path);
            switch($mime_type)
            {
                case 'image/jpeg':
                    $resource = imagecreatefromjpeg($path);
                    break;
                case 'image/png':
                    $resource = imagecreatefrompng($path);
                    $background = imagecolorallocate($resource, 0, 0, 0);
                    imagecolortransparent($resource, $background);
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                    break;
                case "image/gif":
                    $resource = imagecreatefromgif($path);
                    $background = imagecolorallocate($resource, 0, 0, 0);
                    imagecolortransparent($resource, $background);
                    break;
                case 'image/svg+xml':
                    $resource = file_get_contents($path);
                    break;
                default : throw new Exception("지원되는 이미지 타입이 아닙니다.");
            }
        }
        else
        {
            throw new Exception("1번째 파라미터는 읽을수 있는 파일 경로가 필요합니다.");
        }
        return $resource;
    }

}
