<?php

/**
 * Class rester_redis
 */
class rester_redis
{
    protected static $redis = null;

    const cache = 'cache';
    const cache_host = 'host';
    const cache_port = 'port';
    const cache_timeout = 'timeout';
    const cache_auth = 'auth';
    const cache_conn = 'conn';

    // default configuration
    private static $data = [
        self::cache=>[
            self::cache_host=>'cache.rester.io',
            self::cache_port=>6379,
            self::cache_timeout=>600,
            self::cache_auth=>false,
            self::cache_conn=>false
        ]
    ];

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
     * @return string|bool
     */
    public static function cache_auth() { return self::$data[self::cache][self::cache_auth]; }

    /**
     * @return string|bool
     */
    public static function cache_conn() { return self::$data[self::cache][self::cache_conn]; }

    /**
     * @param string|object $v
     */
    public static function set_cache($k, $v, $timeout)
    {
        if (!self::cache_conn())
        {
            self::$redis = new Redis();
            self::$data[self::cache][self::cache_conn] = self::connection();
            if(self::cache_auth()) self::$redis->auth(self::cache_auth());
        }

        self::$redis->set($k, $v, $timeout);
    }

    /**
     * @return bool|string
     */
    public static function get_cache($k)
    {
        if (!self::cache_conn())
        {
            self::$redis = new Redis();
            self::$data[self::cache][self::cache_conn] = self::connection();
            if(self::cache_auth()) self::$redis->auth(self::cache_auth());
        }

        return self::$redis->get($k);
    }

    /**
     * @return array
     */
    public static function get_keys($pattern) { return self::$redis->keys($pattern); }

    /**
     * @param string|object $key
     */
    public static function del($key) { self::$redis->del($key); }

    /**
     * @return string|bool
     */
    protected static function connection() { return self::$redis->connect(self::cache_host(), self::cache_port()); }

    /**
     * rester_cdn constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        self::$redis = new Redis();
        self::$data[self::cache][self::cache_conn] = self::connection();
        if(self::cache_auth()) self::$redis->auth(self::cache_auth());
    }

    /**
     * rester destructor
     */
    public function __destruct()
    {
        if(self::$redis) self::$redis->close();
    }
}
