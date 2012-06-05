<?php
/*
Plugin Name: Nginx Cache Controller
Author: Ninjax Team (Takayuki Miyauchi)
Plugin URI: http://ninjax.cc/
Description: Plugin for Nginx Reverse Proxy
Version: 1.1.2
Author URI: http://ninjax.cc/
Domain Path: /languages
Text Domain: nginxchampuru
*/

$nginxchampuru = new NginxChampuru();
register_activation_hook (__FILE__, array($nginxchampuru, 'activation'));

require_once(dirname(__FILE__)."/includes/caching.class.php");
new NginxChampuru_Caching();
require_once(dirname(__FILE__)."/includes/flush-cache.class.php");
new NginxChampuru_FlushCache();
require_once(dirname(__FILE__)."/includes/admin.class.php");
new NginxChampuru_Admin();

class NginxChampuru {

private $table;
private $expire = 86400;
private $cache_dir = "/var/cache/nginx";
private $cache_levels = "1:2";
private $transient_timeout = 60;

// hook and flush mode
private $method =array(
    'publish' => 'almost',
    'comment' => 'single',
);

function __construct()
{
    global $wpdb;
    $this->table = $wpdb->prefix.'nginxchampuru';
    if (defined('NCC_CACHE_DIR') && file_exists(NCC_CACHE_DIR)) {
        $this->cache_dir = NCC_CACHE_DIR;
    }
    add_action('plugins_loaded',    array(&$this, 'plugins_loaded'));
}

public function is_enable_flush()
{
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
}

public function add()
{
    if (is_admin()) {
        return;
    }
    global $wpdb;
    $sql = $wpdb->prepare(
        "replace into `{$this->table}` values(%s, %d, %s)",
        $this->get_cache_key(),
        $this->get_postid(),
        $this->get_post_type()
    );
    $wpdb->query($sql);
}

public function transientExec($callback)
{
    if (!$this->is_enable_flush()) {
        return;
    }

    if (get_transient("nginxchampuru_flush")) {
        return;
    } else {
        set_transient("nginxchampuru_flush", 1, $this->transient_timeout);
    }

    $params = func_get_args();
    array_shift($params);
    call_user_func(array(&$this, $callback), $params);

    delete_transient("nginxchampuru_flush");
}

private function flush_this()
{
    $params = func_get_args();
    $url    = $params[0][0];

    $key    = $this->get_cache_key($url);
    $caches = $this->get_cache($key, $url);

    foreach ((array)$caches as $cache) {
        if (is_file($cache)) {
            unlink($cache);
        }
    }

    global $wpdb;
    $sql = $wpdb->prepare(
        "delete from `$this->table` where cache_key=%s",
        $key
    );
}

private function flush_cache()
{
    $params = func_get_args();
    $mode   = $params[0][0];
    $id     = $params[0][1];

    global $wpdb;
    if ($mode === "all") {
        $sql = "select `cache_key` from `$this->table`";
    } elseif ($mode === "single" && intval($id)) {
        $sql = $wpdb->prepare(
            "select `cache_key` from `$this->table` where cache_id=%d",
            intval($id)
        );
    } elseif ($mode === 'almost' && intval($id)) {
        $sql = $wpdb->prepare(
            "select `cache_key` from `$this->table`
                where cache_id=%d or
                cache_type in ('is_home', 'is_archive', 'other')",
            intval($id)
        );
    } else {
        return;
    }

    $keys = $wpdb->get_col($sql);
    foreach ($keys as $key) {
        $cache = $this->get_cache($key);
        if (is_file($cache)) {
            unlink($cache);
        }
    }

    $sql = "delete from `$this->table` where cache_key in ('".join("','", $keys)."')";
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
            primary key (`cache_key`),
            key `cache_id` (`cache_id`),
            key `cache_type`(`cache_type`)
            );";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

public function get_expire()
{
    $expires = get_option("nginxchampuru-cache_expires");
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
    } else {
        $type = "other";
    }

    return $type;
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
    $id = url_to_postid($this->get_the_url());
    if (is_singular() && intval($id)) {
        return $id;
    } else {
        return 0;
    }
}

public function get_the_url()
{
    return apply_filters(
        'nginxchampuru_get_the_url',
        'http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]
    );
}

}

// EOF
