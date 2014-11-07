<?php


class Functions_Test extends WP_UnitTestCase {

    function test_get_cache_dir()
    {
        $this->assertTrue(  '/var/cache/nginx' === \NginxCC\Utils::get_cache_dir() );


        update_option( 'nginxchampuru-cache_dir', '/cache_dir' );
        $this->assertTrue(  '/cache_dir' === \NginxCC\Utils::get_cache_dir() );

        define( 'NCC_CACHE_DIR', '/tmp' );
        $this->assertTrue(  '/tmp' === \NginxCC\Utils::get_cache_dir() );
    }
}
