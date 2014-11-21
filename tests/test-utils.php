<?php

use \NginxCC\Utils as Utils;
use \NginxCC\Conf  as Conf;


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


	/**
	* @test
	*/
	public function get_default_expires()
	{
		$this->assertEquals( 86400, Utils::get_default_expires() );

		add_filter( 'nginxchampuru_default_expires', function(){
			return 100;
		} );

		$this->assertEquals( 100, Utils::get_default_expires() );
	}


	/**
	* @test
	*/
	public function get_expires()
	{
		$this->assertEquals( 86400, Utils::get_expires() );
	}


	/**
	* @test
	*/
	public function get_post_type()
	{
		$this->assertEquals( 'other', Utils::get_post_type() );
	}


	/**
	* @test
	*/
	public function add_cache_data()
	{
		$this->assertEquals( 1, 1 );
	}


	/**
	* @test
	*/
	public function get_table_name()
	{
		$this->assertEquals( 'wptests_nginxchampuru', Utils::get_table_name() );
	}


	/**
	* @test
	*/
	public function create_table()
	{
		$this->assertEquals( Conf::table_version, Utils::create_table() );
		$this->assertEquals( Conf::table_version, get_option( 'nginxchampuru-db_version' ) );
	}
}
