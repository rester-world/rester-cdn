<?php

/**
 * Class cfg
 */
class cfg
{
    const prefix = 'rester-cdn';

    const filename = 'rester.ini';

    const method = 'method';

    const common = 'common';
    const common_extensions = 'extensions';
    const common_expires = 'expires';

    const error_images = 'error_images';
    const error_images_noimage = 'noimage';

    const cache = 'cache';
    const cache_host = 'host';
    const cache_port = 'port';
    const cache_timeout = 'timeout';

    const access_control = 'access_control';
    const access_control_allows_sites = 'allows_sites';
    const access_control_allows_upload = 'allows_upload';

    // default configuration
    private static $data = [
        self::common=>[
            self::common_extensions=>['jpg','png','jpeg','gif','svg'],
            self::common_expires=>172600,
        ],
        self::error_images=>[
            self::error_images_noimage=>'no_image.gif'
        ],
        self::access_control=>[
            self::access_control_allows_sites=>'*',
            self::access_control_allows_upload=>'*'
        ]
    ];

    /**
     * @return string
     */
    public static function method() { return self::$data[self::method]; }

    /**
     * @return array
     */
    public static function extensions() { return self::$data[self::common][self::common_extensions]; }

    /**
     * @param string $ext
     *
     * @return bool
     */
    public static function check_extension($ext)
    {
        if(in_array($ext,self::$data[self::common][self::common_extensions])) return true;
        return false;
    }

    /**
     * @return int
     */
    public static function expires() { return self::$data[self::common][self::common_expires]; }

    /**
     * @param string $select
     *
     * @return string
     */
    public static function error_images($select) { return self::$data[self::error_images][$select]; }

    /**
     * @return string
     */
    public static function noimage() { return self::$data[self::error_images][self::error_images_noimage]; }

    /**
     * @return array
     */
    public static function cache() { return self::$data[self::cache]; }

    /**
     * @return string
     */
    public static function cache_host() { return self::$data[self::cache][self::cache_host]; }

    /**
     * @return string
     */
    public static function cache_port() { return self::$data[self::cache][self::cache_port]; }

    /**
     * @return int
     */
    public static function cache_timeout() { return self::$data[self::cache][self::cache_timeout]; }

    /**
     * @return array
     */
    public static function allows_sites() { return self::$data[self::access_control][self::access_control_allows_sites]; }

    /**
     * @return bool
     */
    public static function check_site()
    {
        $allows = self::allows_sites();
        if($allows=='*') return true;
        if(in_array($_SERVER['HTTP_REFERER'],$allows)) return true;
        return false;
    }

    /**
     * @return array
     */
    public static function allows_upload() { return self::$data[self::access_control][self::access_control_allows_upload]; }

    /**
     * @return bool
     */
    public static function check_upload()
    {
        $allows = self::allows_upload();
        if($allows=='*') return true;
        if(in_array(self::access_ip(),$allows)) return true;
        return false;
    }

    /**
     * Initialize default config
     *
     * @throws Exception
     */
    public static function init()
    {
        // ---------------------------------------------------------------------
        /// Load config
        // ---------------------------------------------------------------------
        $path = dirname(__FILE__).'/../../cfg/'.self::filename;
        if(!is_file($path))
            throw new Exception("There is no config file.(".self::filename.")");

        $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);

        // common extensions
        $extensions = $cfg[self::common][self::common_extensions];
        if($extensions && !is_array($extensions))
        {
            $cfg[self::common][self::common_extensions] = [];
            foreach(explode(',',$extensions) as $ext)
            {
                if($ext = trim($ext))
                    $cfg[self::common][self::common_extensions][] = $ext;
            }
        }

        // Extract access control
        if($cfg[self::access_control])
        {
            // sites
            $origin = $cfg[self::access_control][self::access_control_allows_sites];
            if(!is_array($origin) && trim($origin)!='*')
            {
                foreach(explode(',',$origin) as $ori)
                {
                    if($ext = trim($ori))
                        $cfg[self::access_control][self::access_control_allows_sites][] = $ori;
                }
            }

            // upload
            $origin = $cfg[self::access_control][self::access_control_allows_upload];
            if(!is_array($origin) && trim($origin)!='*')
            {
                foreach(explode(',',$origin) as $ori)
                {
                    if($ext = trim($ori))
                        $cfg[self::access_control][self::access_control_allows_upload][] = $ori;
                }
            }
        }

        array_walk_recursive($cfg, function(&$v) { $v = trim($v); });
        foreach ($cfg as $section=>$values)
        {
            foreach($values as $kk=>$vv)
            {
                self::$data[$section][$kk] = $vv;
            }
        }

        // check site
        if(!self::check_site()) throw new Exception("Access denied.");
    }

    /**
     * @return string
     */
    protected static function access_ip()
    {
        // Check allows ip address
        // Check ip from share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            $access_ip=$_SERVER['HTTP_CLIENT_IP'];
        }
        //to check ip is pass from proxy
        else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $access_ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $access_ip=$_SERVER['REMOTE_ADDR'];
        }
        return $access_ip;
    }

    /**
     * return config
     *
     * @param string $section
     * @param string $key
     *
     * @return array|string
     */
    public static function get($section='', $key='')
    {
        if($section==='') return self::$data;
        if($section && $key) return self::$data[$section][$key];
        return self::$data[$section];
    }

}
