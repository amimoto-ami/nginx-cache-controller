<?php


class Functions_Test extends WP_UnitTestCase {

    /**
     * @test
     */
    public function get_option()
    {
        \NginxCC\Utils::update_option( "test-option", "   Is your name O'Reilly?   " );
        $this->assertEquals( "   Is your name O'Reilly?   ", get_option( "test-option" ) );
        $this->assertEquals( "Is your name O'Reilly?", \NginxCC\Utils::get_option( "test-option" ) );
    }


    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function get_cache_dir()
    {
        $this->assertEquals( '/var/cache/nginx', \NginxCC\Utils::get_cache_dir() );

        \NginxCC\Utils::update_option( 'nginxchampuru-cache_dir', '/cache_dir' );
        $this->assertEquals( '/cache_dir', \NginxCC\Utils::get_cache_dir() );

        define( 'NCC_CACHE_DIR', '/tmp' );
        $this->assertEquals( '/tmp', \NginxCC\Utils::get_cache_dir() );
    }


    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function is_enable_flush()
    {
        $this->assertTrue( false === \NginxCC\Utils::is_enable_flush() );

        \NginxCC\Utils::update_option( 'nginxchampuru-enable_flush', 'this should be true' );
        $this->assertTrue( true === \NginxCC\Utils::is_enable_flush() );

        define('WP_CLI', true);
        $this->assertTrue( true === \NginxCC\Utils::is_enable_flush() );
    }


    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function is_enable_add_last_modified()
    {
        \NginxCC\Utils::update_option( 'nginxchampuru-enable_flush', false );
        \NginxCC\Utils::update_option( 'nginxchampuru-add_last_modified', false );
        $this->assertTrue( false === \NginxCC\Utils::is_enable_add_last_modified() );

        \NginxCC\Utils::update_option( 'nginxchampuru-enable_flush', false );
        \NginxCC\Utils::update_option( 'nginxchampuru-add_last_modified', true );
        $this->assertTrue( false === \NginxCC\Utils::is_enable_add_last_modified() );

        \NginxCC\Utils::update_option( 'nginxchampuru-enable_flush', true );
        \NginxCC\Utils::update_option( 'nginxchampuru-add_last_modified', false );
        $this->assertTrue( false === \NginxCC\Utils::is_enable_add_last_modified() );

        \NginxCC\Utils::update_option( 'nginxchampuru-enable_flush', true );
        \NginxCC\Utils::update_option( 'nginxchampuru-add_last_modified', true );
        $this->assertTrue( true === \NginxCC\Utils::is_enable_add_last_modified() );

        define('WP_CLI', true);
        \NginxCC\Utils::update_option( 'nginxchampuru-add_last_modified', false );
        $this->assertTrue( false === \NginxCC\Utils::is_enable_add_last_modified() );

        // define('WP_CLI', true); it's already set.
        \NginxCC\Utils::update_option( 'nginxchampuru-add_last_modified', true );
        $this->assertTrue( true === \NginxCC\Utils::is_enable_add_last_modified() );
    }


    /**
     * @test
     */
    public function get_cache_levels()
    {
        $this->assertEquals( '1:2', \NginxCC\Utils::get_cache_levels() );

        \NginxCC\Utils::update_option( 'nginxchampuru-cache_levels', '2:3' );
        $this->assertEquals( '2:3', \NginxCC\Utils::get_cache_levels() );
    }
}
