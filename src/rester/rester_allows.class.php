<?php

/**
 * class file
 */
class rester_allows
{
    const cache_key = 'rester-allows';

    /**
     * @param array $v
     */
    protected function set_cache_allows($v)
    {
        rester_redis::set_cache(self::cache_key, json_encode($v, JSON_UNESCAPED_UNICODE), 60*60);
    }

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
    }

    /**
     * rester destructor
     */
    public function __destruct()
    {
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
        if (rester_redis::cache_conn())
        {
            $allows = [];
            foreach($_POST as $key=>$row)
            {
                if($key !== 'token') $allows[] = $row;
            }
            $this->set_cache_allows([
                'allows' => $allows,
                'datetime' => date("Y-m-d H:i:s")
            ]);
        }
    }
}
