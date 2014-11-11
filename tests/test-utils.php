<?php

use \NginxCC\Utils as Utils;

class Functions_Test extends WP_UnitTestCase {

    /**
     * @test
     */
    public function get_option()
    {
        Utils::update_option( "test-option", "   Is your name O'Reilly?   " );
        $this->assertEquals( "   Is your name O'Reilly?   ", get_option( "test-option" ) );
        $this->assertEquals( "Is your name O'Reilly?", Utils::get_option( "test-option" ) );
    }


    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function get_cache_dir()
    {
        $this->assertEquals( '/var/cache/nginx', Utils::get_cache_dir() );

        Utils::update_option( 'nginxchampuru-cache_dir', '/cache_dir' );
        $this->assertEquals( '/cache_dir', Utils::get_cache_dir() );

        define( 'NCC_CACHE_DIR', '/tmp' );
        $this->assertEquals( '/tmp', Utils::get_cache_dir() );
    }


    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function is_enable_flush()
    {
        $this->assertTrue( false === Utils::is_enable_flush() );

        Utils::update_option( 'nginxchampuru-enable_flush', 'this should be true' );
        $this->assertTrue( true === Utils::is_enable_flush() );

        define('WP_CLI', true);
        $this->assertTrue( true === Utils::is_enable_flush() );
    }


    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function is_enable_add_last_modified()
    {
        Utils::update_option( 'nginxchampuru-enable_flush', false );
        Utils::update_option( 'nginxchampuru-add_last_modified', false );
        $this->assertTrue( false === Utils::is_enable_add_last_modified() );

        Utils::update_option( 'nginxchampuru-enable_flush', false );
        Utils::update_option( 'nginxchampuru-add_last_modified', true );
        $this->assertTrue( false === Utils::is_enable_add_last_modified() );

        Utils::update_option( 'nginxchampuru-enable_flush', true );
        Utils::update_option( 'nginxchampuru-add_last_modified', false );
        $this->assertTrue( false === Utils::is_enable_add_last_modified() );

        Utils::update_option( 'nginxchampuru-enable_flush', true );
        Utils::update_option( 'nginxchampuru-add_last_modified', true );
        $this->assertTrue( true === Utils::is_enable_add_last_modified() );

        define('WP_CLI', true);
        Utils::update_option( 'nginxchampuru-add_last_modified', false );
        $this->assertTrue( false === Utils::is_enable_add_last_modified() );

        // define('WP_CLI', true); it's already set.
        Utils::update_option( 'nginxchampuru-add_last_modified', true );
        $this->assertTrue( true === Utils::is_enable_add_last_modified() );
    }

    /**
     * @test
     */
    public function get_cache_levels()
    {
        $this->assertEquals( '1:2', Utils::get_cache_levels() );

        Utils::update_option( 'nginxchampuru-cache_levels', '2:3' );
        $this->assertEquals( '2:3', Utils::get_cache_levels() );
    }


    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function get_flush_method()
    {
        $this->assertEquals(
            'flush_current_page_and_archives_caches',
            Utils::get_flush_method( 'save_post' )
        );

        $this->assertEquals(
            'flush_current_page_and_archives_caches',
            Utils::get_flush_method( 'publish_future_post' )
        );

        $this->assertEquals(
            'flush_current_page_caches',
            Utils::get_flush_method( 'comment_post' )
        );

        $this->assertEquals(
            'flush_current_page_caches',
            Utils::get_flush_method( 'wp_set_comment_status' )
        );

        add_filter( 'nginxchampuru_flush_method_save_post', function(){
            return 'test method';
        } );

        $this->assertEquals( 'test method', Utils::get_flush_method( 'save_post' ) );

        update_option( 'nginxchampuru-save_post', 'hoge' );
        $this->assertEquals( 'hoge', Utils::get_flush_method( 'save_post' ) );
    }
}
