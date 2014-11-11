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


    /**
     * Return the method for flush.
     *
     * @since  4.0
     * @param  $hook  publish or comment or update
     * @return string The flush method.
     */
    public static function get_flush_method( $hook )
    {
        if ( isset( Conf::$flush_method[$hook] ) && Conf::$flush_method[$hook] ) {
            $default = Conf::$flush_method[$hook];
        } else {
            $default = '';
        }

        $default = apply_filters( 'nginxchampuru_flush_method_'.$hook, $default );

        return get_option( 'nginxchampuru-'.$hook, $default );
    }


    /**
     * Return the default cache expire.
     *
     * @since  4.0
     * @param  none
     * @return string The default cache expire.
     */
    public static function get_default_expires()
    {
        return apply_filters( 'nginxchampuru_default_expires', Conf::default_expires );
    }



    public static function get_expires()
    {
        $expires = get_option( 'nginxchampuru-cache_expires' );
        $post_type = self::get_post_type();

        if ( isset( $expires[$post_type] ) && strlen( $expires[$post_type] ) ) {
            return $expires[$post_type];
        } else {
            return self::get_default_expires();
        }
    }

    public static function get_post_type()
    {
        if (is_home()) {
            $type = "is_home";
        } elseif (is_archive()) {
            $type = "is_archive";
        } elseif (is_singular()) {
            $type = "is_singular";
        } elseif (is_feed()) {
            $type = "is_feed";
        } else {
            $type = "other";
        }

        return $type;
    }

    public static function add_cache_data()
    {
        if (is_admin()) {
            return;
        }
        if (!self::is_enable_flush()) {
            return;
        }
        if (self::get_expire() <= 0) {
            return;
        }
        global $wpdb;
        $sql = $wpdb->prepare(
            "replace into `{$this->table}` values(%s, %d, %s, %s, CURRENT_TIMESTAMP)",
            $this->get_cache_key(),
            $this->get_postid(),
            $this->get_post_type(),
            $this->get_the_url()
        );
        $wpdb->query($sql);
    }
}
