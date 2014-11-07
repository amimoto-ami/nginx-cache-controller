<?php

namespace NginxCC;

class Utils {

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {

    }


    /**
     * Returns the *Singleton* instance of this class.
     *
     * @staticvar Singleton $instance The *Singleton* instances of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }


    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {

    }


    /**
     * Return the path of Nginx reverse proxy cache directory.
     *
     * @param  none
     * @return string The path of Nginx reverse proxy cache directory.
     */
    public static function get_cache_dir()
    {
        if ( ( defined( 'NCC_CACHE_DIR' ) && file_exists( NCC_CACHE_DIR ) ) ) {
            $path = NCC_CACHE_DIR;
        } else {
            $path = get_option( "nginxchampuru-cache_dir", \NginxCC\Conf::cache_dir );
        }

        return $path;
    }
}
