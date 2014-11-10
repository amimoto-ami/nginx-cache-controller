<?php

namespace NginxCC;
use \NginxCC\Conf as Conf;

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


    public static function get_option( $key, $default = null )
    {
        return trim( stripslashes( get_option( $key, $default ) ) );
    }


    public static function update_option( $key, $value )
    {
        return update_option( $key, $value );
    }


    public static function delete_option( $key )
    {
        return delete_option( $key );
    }


    /**
     * Return the path of Nginx reverse proxy cache directory.
     *
     * @since  4.0
     * @param  none
     * @return string The path of Nginx reverse proxy cache directory.
     */
    public static function get_cache_dir()
    {
        if ( ( defined( 'NCC_CACHE_DIR' ) && file_exists( NCC_CACHE_DIR ) ) ) {
            $path = NCC_CACHE_DIR;
        } else {
            $path = self::get_option( "nginxchampuru-cache_dir", Conf::cache_dir );
        }

        return $path;
    }


    /**
     * Return enable/disable flash as bool.
     *
     * @since  4.0
     * @param  none
     * @return bool The bool of enable/disable flash.
     */
    public static function is_enable_flush()
    {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return true;
        }

        return self::get_option( "nginxchampuru-enable_flush", false ) ? true : false;
    }


    /**
     * Return enable/disable add last modified.
     *
     * @since  4.0
     * @param  none
     * @return bool The bool of enable/disable add last modified.
     */
    public static function is_enable_add_last_modified()
    {
        return self::is_enable_flush() && self::get_option( "nginxchampuru-add_last_modified", 0 );
    }


    /**
     * Return the cache levels like '1:2'.
     *
     * @since  4.0
     * @param  none
     * @return string The cache levels like '1:2'.
     */
    public static function get_cache_levels()
    {
        return self::get_option( "nginxchampuru-cache_levels", Conf::cache_levels );
    }
}
