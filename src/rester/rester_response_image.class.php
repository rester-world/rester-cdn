<?php

/**
 * Class rester_response
 */
class rester_response
{
    protected static $mime;
    protected static $expires = 60*60*24*30;
    protected static $data;

    /**
     * print image
     */
    public static function run()
    {
        if(!self::$mime || !self::$data) self::result_error_image(cfg::error_images_etc);

        header('Content-Type: '.self::$mime);
        header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (self::$expires)));
        echo self::$data;
    }

    /**
     * @param string $type
     */
    public static function result_error_image($type)
    {
        $path = dirname(__FILE__).'/../error_images/'.cfg::error_images($type);
        if($type!=cfg::error_images_noimage || is_file($path))
            self::result_image($path);
    }

    /**
     * @param string $path
     */
    public static function result_image($path)
    {
        if(is_file($path))
        {
            self::mime(mime_content_type($path));
            self::$data = file_get_contents($path);
        }
        else
        {
            self::result_error_image(cfg::error_images_noimage);
        }
    }

    /**
     * @param string $mime
     */
    public static function mime($mime) { self::$mime = $mime; }

    /**
     * @param int $expires
     */
    public static function expires($expires) { self::$expires = $expires; }

    /**
     * @param $data
     */
    public static function data($data) { self::$data = $data; }
}
