<?php

const QUERY_TRAFFIC_DELETE  = 'delete';
/**
 * Class rester
 */
class rester_traffic
{
    /**
     * @var Redis
     */
    protected static $redis = null;

    protected static $cache_traffic_key = null;

    protected static function gen_key($length = 40)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.!@#$%^&()-_*=+';
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[rand(0, strlen($characters))];
        }
        return $token;
    }

    /**
     * @throws Exception
     */
    protected static function connect_cache()
    {
        self::$cache_traffic_key = 'rester-cdn-traffic-' . self::gen_key();

        if (self::$redis) return;

        $redis_cfg = cfg::cache();
        if (!($redis_cfg['host'] && $redis_cfg['port']))
            throw new Exception("Require cache config to use auth.");

        self::$redis = new Redis();
        if (self::$redis->connect($redis_cfg['host'], $redis_cfg['port'], 1.0)) {
            if ($redis_cfg['auth']) self::$redis->auth($redis_cfg['auth']);
        } else {
            throw new Exception("Can not access redis server.");
        }

    }

    /**
     * @param $v
     * @throws Exception
     */
    public static function set_cache_traffic($v)
    {
        self::connect_cache();
        self::$redis->set(self::$cache_traffic_key, json_encode($v), 60 * 60);
    }

    /**
     * @param bool $delete
     * @return array
     * @throws Exception
     */
    public static function get_cache_traffic($delete = false)
    {
        self::connect_cache();

        $retArray = Array();
        foreach (self::$redis->getKeys('rester-cdn-traffic-*') as $key) {
            $ret = self::$redis->get($key);
            if (json_decode($ret, true)) {
                $ret = json_decode($ret, true);
            }
            array_push($retArray, $ret);

            if ($delete)
                self::$redis->del($key);
        }
        return $retArray;
    }

    /**
     * @param $cache
     */
    public static function set_cache($cache)
    {
        self::$redis = $cache;
    }

}
