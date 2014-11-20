<?php
/*
Plugin Name: Nginx Cache Controller
Author: Ninjax Team (Takayuki Miyauchi)
Plugin URI: https://github.com/megumiteam/nginx-cache-controller
Description: Plugin for Nginx Reverse Proxy
Version: 3.1.0
Author URI: http://ninjax.cc/
Domain Path: /languages
Text Domain: nginxchampuru
*/

if ( defined('WP_CLI') && WP_CLI ) {
	require_once(dirname(__FILE__)."/includes/wp-cli.php");
}

$nginxchampuru = NginxChampuru::get_instance();
$nginxchampuru->add_hook();
register_activation_hook (__FILE__, array($nginxchampuru, 'activation'));

require_once(dirname(__FILE__)."/includes/caching.class.php");
$nginxchampuru_cache = NginxChampuru_Caching::get_instance();
$nginxchampuru_cache->add_hook();

require_once(dirname(__FILE__)."/includes/flush-cache.class.php");
$nginxchampuru_flushcache = NginxChampuru_FlushCache::get_instance();
$nginxchampuru_flushcache->add_hook();

require_once(dirname(__FILE__)."/includes/admin.class.php");
$nginxchampuru_admin = NginxChampuru_Admin::get_instance();
$nginxchampuru_admin->add_hook();


define("NGINX_CACHE_CONTROLER_URL", plugins_url('', __FILE__));
define("NGINX_CACHE_CONTROLER_BASE_NAME", plugin_basename(__FILE__));

class NginxChampuru {

private $version;
private $db_version;

private $table;
private $expire = 86400;
private $cache_dir = "/var/cache/nginx";
private $cache_levels = "1:2";
private $transient_timeout = 60;

const OPTION_NAME_DB_VERSION = 'nginxchampuru-db_version';
const OPTION_NAME_CACHE_EXPIRES = 'nginxchampuru-cache_expires';

// hook and flush mode
private $method =array(
    'publish' => 'almost',
    'comment' => 'single',
);

private static $instance;

private function __construct() {}

public static function get_instance()
{
    if( !isset( self::$instance ) ) {
        $c = __CLASS__;
        self::$instance = new $c();
    }
    return self::$instance;
}

public function add_hook()
{
    global $wpdb;
    $this->table = $wpdb->prefix.'nginxchampuru';
    if (defined('NCC_CACHE_DIR') && file_exists(NCC_CACHE_DIR)) {
        $this->cache_dir = NCC_CACHE_DIR;
    }
    add_action('plugins_loaded',    array($this, 'plugins_loaded'));

    $data = get_file_data( __FILE__, array( 'version' => 'Version' ) );
    $this->version = $data['version'];
    $this->db_version = get_option(self::OPTION_NAME_DB_VERSION, 0);
}

public function is_enable_flush()
{
	if ( defined('WP_CLI') && WP_CLI ) {
		return true;
	}

    return get_option("nginxchampuru-enable_flush", 0);
}

public function add_last_modified()
{
    return $this->is_enable_flush() && get_option("nginxchampuru-add_last_modified", 0);
}

public function get_cache_levels()
{
    return get_option("nginxchampuru-cache_levels", $this->cache_levels);
}

public function get_flush_method($hook)
{
    return get_option(
        'nginxchampuru-'.$hook,
        $this->method[$hook]
    );
}

public function get_default_expire()
{
    return $this->expire;
}

public function get_plugin_url()
{
    return plugins_url('', __FILE__);
}

public function get_plugin_dir()
{
    return dirname(__FILE__);
}

public function plugins_loaded()
{
    load_plugin_textdomain(
        "nginxchampuru",
        false,
        dirname(plugin_basename(__FILE__)).'/languages'
    );

    if (version_compare($this->version, $this->db_version)) {
        $this->db_version = $this->alter_table($this->version, $this->db_version);
    }
}

public function add()
{
    if (is_admin()) {
        return;
    }
    if (!$this->is_enable_flush()) {
        return;
    }
    if ($this->get_expire() <= 0) {
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

public function transientExec($callback)
{
    if (!$this->is_enable_flush()) {
        return;
    }
    if (get_transient("nginxchampuru_flush")) {
        wp_die('Now removing cache. Please try after.');
    } else {
        set_transient("nginxchampuru_flush", 1, $this->transient_timeout);
    }

    $params = func_get_args();
    array_shift($params);
    call_user_func(array(&$this, $callback), $params);

    delete_transient("nginxchampuru_flush");
}

public function get_cached_objects()
{
    global $wpdb;

    $expire_limit = date('Y-m-d H:i:s', time() - $this->get_max_expire());

    $sql = $wpdb->prepare("select distinct `cache_id`, `cache_type` as post_type, ifnull(`cache_url`,\"\") as `cache_url`, `cache_saved` from `$this->table` where `cache_saved` > %s",
        $expire_limit
    );

    return $wpdb->get_results($sql);
}

private function flush_this()
{
    $params = func_get_args();
    $url    = $params[0][0];
    if (empty($url)) {
        return;
    }

    do_action('nginxchampuru_flush_cache', $url);

    // singular pages
    $id = url_to_postid($url);
    if ($id) {
        $this->flush_cache(array('single', $id));
        return;
    }

    $key    = $this->get_cache_key($url);
    $caches = $this->get_cache($key, $url);
    foreach ((array)$caches as $cache) {
        if (is_file($cache)) {
            unlink($cache);
        }
    }

    global $wpdb;
    $sql = $wpdb->prepare("delete from `$this->table` where cache_key=%s", $key);
    $wpdb->query($sql);
}

private function flush_cache()
{
    $params = func_get_args();
    $mode   = $params[0][0];
    $id     = $params[0][1];

    $expire_limit = date('Y-m-d H:i:s', time() - $this->get_max_expire());

    global $wpdb;
    if ($mode === "all") {
        $sql = $wpdb->prepare("select distinct `cache_key`, `cache_id`, `cache_type`, ifnull(`cache_url`,\"\") as `cache_url` from `$this->table` where `cache_saved` > %s",
            $expire_limit
        );
    } elseif ($mode === "single" && intval($id)) {
        $sql = $wpdb->prepare(
            "select distinct `cache_key`, `cache_id`, `cache_type`, ifnull(`cache_url`,\"\") as `cache_url` from `$this->table` where (`cache_id`=%d and cache_saved > %s) or (`cache_type`='is_feed')",
            intval($id),
            $expire_limit
        );
    } elseif ($mode === 'almost' && intval($id)) {
        $sql = $wpdb->prepare(
            "select distinct `cache_key`, `cache_id`, `cache_type`, ifnull(`cache_url`,\"\") as `cache_url` from `$this->table`
                where `cache_saved`> %s and (cache_id=%d or
                cache_type in ('is_home', 'is_archive', 'is_feed', 'other'))",
            $expire_limit,
            intval($id)
        );
    } else {
        return;
    }

    $keys = $wpdb->get_results($sql);
    $purge_keys = array();
    foreach ($keys as $key) {
        $url = $key->cache_url;
        if ($url) {
            do_action('nginxchampuru_flush_cache', $url);
        }
        $caches = $this->get_cache($key->cache_key, $url);
        foreach ((array)$caches as $cache) {
            if (is_file($cache)) {
                unlink($cache);
            }
        }
        $purge_keys[] = $key->cache_key;
    }

    if ($mode === 'all') {
        $sql = "delete from `$this->table`";
    } else {
        $sql = "delete from `$this->table` where cache_key in ('".join("','", $purge_keys)."')";
    }
    $wpdb->query($sql);
}

public function activation()
{
    global $wpdb;
    if ($wpdb->get_var("show tables like '$this->table'") != $this->table) {
        $sql = "CREATE TABLE `{$this->table}` (
            `cache_key` varchar(32) not null,
            `cache_id` bigint(20) unsigned default 0 not null,
            `cache_type` varchar(11) not null,
            `cache_url` varchar(256),
            `cache_saved` timestamp default current_timestamp not null,
            primary key (`cache_key`),
            key `cache_id` (`cache_id`),
            key `cache_saved`(`cache_saved`),
            key `cache_url`(`cache_url`),
            key `cache_type`(`cache_type`)
            );";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
	    update_option(self::OPTION_NAME_DB_VERSION, $this->version);
    }

    $this->add_caps();
}

private function add_caps()
{
    if (!function_exists('get_role'))
        return;

    $role = get_role('administrator');
    if ($role && !is_wp_error($role)) {
        $role->add_cap('flush_cache_single');
        $role->add_cap('flush_cache_all');
    }

    $role = get_role('editor');
    if ($role && !is_wp_error($role)) {
        $role->add_cap('flush_cache_single');
        $role->add_cap('flush_cache_all');
    }

    $role = get_role('author');
    if ($role && !is_wp_error($role)) {
        $role->add_cap('flush_cache_single');
    }
}

private function alter_table($version, $db_version)
{
    global $wpdb;
    if ($wpdb->get_var("show tables like '$this->table'") != $this->table) {
    	$this->activation();
    	return get_option(self::OPTION_NAME_DB_VERSION, $version);
    }

    switch (true) {
        case (version_compare('1.1.5', $db_version) > 0):
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `cache_url` varchar(256);";
            $wpdb->query($sql);
        case (version_compare('1.2.1', $db_version) > 0):
            $sql = "ALTER TABLE `{$this->table}` ADD COLUMN `cache_saved` timestamp default current_timestamp not null;";
            $wpdb->query($sql);
            $sql = "ALTER TABLE `{$this->table}` ADD INDEX `cache_saved`(`cache_saved`);";
            $wpdb->query($sql);
            $sql = "ALTER TABLE `{$this->table}` ADD INDEX `cache_url`(`cache_url`);";
            $wpdb->query($sql);
            $sql = "update `{$this->table}` set `cache_saved` = current_timestamp";
            $wpdb->query($sql);
        case (version_compare('1.4.2', $db_version) > 0):
            $this->add_caps();
        default:
            update_option(self::OPTION_NAME_DB_VERSION, $version);
            break;
    }
    return $version;
}

private function get_max_expire()
{
    $max = 0;

    $expires = get_option(self::OPTION_NAME_CACHE_EXPIRES);
    if (is_array($expires) && is_array(array_values($expires))) {
        $max = max(array_values($expires));
    }

    if (!$max) {
        $max = $this->get_default_expire();
    }
    return $max;
}

public function get_expire()
{
    $expires = get_option(self::OPTION_NAME_CACHE_EXPIRES);
    $par = $this->get_post_type();
    if (isset($expires[$par]) && strlen($expires[$par])) {
        return $expires[$par];
    } else {
        return $this->get_default_expire();
    }
}

public function get_post_type()
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

    return apply_filters('nginxchampuru_get_post_type', $type);
}

public function get_cache($key, $url = null)
{
    if (has_filter("nginxchampuru_get_cache")) {
        if (!$url) {
            $url = $this->get_the_url();
        }
        return apply_filters(
            'nginxchampuru_get_cache',
            $key,
            $url
        );
    } else {
        return $this->get_cache_file($key);
    }
}

public function get_cache_file($keys)
{
    $caches = array();
    $levels = preg_split("/:/", $this->get_cache_levels());
    $cache_dir = $this->get_cache_dir();
    foreach ((array)$keys as $key) {
        $path = array();
        $path[] = $cache_dir;
        $offset = 0;
        foreach ($levels as $l) {
            $offset = $offset + $l;
            $path[] = substr($key, 0-$offset, $l);
        }
        $path[] = $key;
        $caches[] = join("/", $path);
    }
    return $caches;
}

public function get_cache_dir()
{
    return
        (defined('NCC_CACHE_DIR') && file_exists(NCC_CACHE_DIR))
        ? NCC_CACHE_DIR
        : get_option("nginxchampuru-cache_dir", $this->cache_dir);
}

public function get_cache_key($url = null)
{
    if (!$url) {
        $url = $this->get_the_url();
    }
    if (has_filter("nginxchampuru_get_reverse_proxy_key")) {
        return apply_filters(
            'nginxchampuru_get_reverse_proxy_key',
            $url
        );
    } else {
        return md5($url);
    }
}

private function get_postid()
{
    $id = get_the_ID();
    if ( ! (is_singular() && intval($id) ) ) {
        $id = 0;
    }

    return apply_filters('nginxchampuru_get_post_id', $id);
}

public function get_the_url()
{
    $url = preg_replace('#(https?)://([^/]+)/.*$#i', '$1://$2', home_url());
    $url .= isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '/';
    return apply_filters('nginxchampuru_get_the_url', $url);
}

}

// EOF
