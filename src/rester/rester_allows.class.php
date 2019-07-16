<?php
//------------------------------------------------------
/// cfg const value
/// use cfg class
//------------------------------------------------------
const QUERY_MODULE          = 'm';
const QUERY_CACHE           = 'cache';
const QUERY_CACHE_TIMEOUT   = 'timeout';

/**
 * class file
 */
class rester_allows
{
    protected $redis = null;

    protected $cache = false;
    protected $cache_connected = false;
    protected $cache_timeout = 600;
    protected $cache_key = null;

    /**
     * rester_cdn constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        if(!cfg::check_upload())
        {
            throw new Exception(cfg::error_images_site);
        }

        // check request method
        if(cfg::method()!='POST')
        {
            throw new Exception(cfg::error_images_method);
        }

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

        //------------------------------------------------------------------------------
        /// redis host & port
        //------------------------------------------------------------------------------
        if((cfg::cache_host() && cfg::cache_port()))
        {
            $this->cache_connected = true;

            $this->redis = new Redis();
            $this->redis->connect(cfg::cache_host(), cfg::cache_port());
            if(cfg::cache_auth()) $this->redis->auth(cfg::cache_auth());
            $this->cache_key = 'rester-allows';
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
     * @param array $v
     */
    protected function set_cache_allows($v)
    {
        $this->redis->set($this->cache_key, json_encode($v, JSON_UNESCAPED_UNICODE), 60*60);
    }

    /**
     * run rester
     *
     * @throws Exception
     */
    public function run()
    {
        //--------------------------------------------------------------------------------
        /// include file
        //--------------------------------------------------------------------------------
        if ($this->cache_connected)
        {
            $this->set_cache_allows([
                'allows' => $_POST,
                'datetime' => date("Y-m-d H:i:s")
            ]);
        }
    }
}
