<?php
/*
Plugin Name: Nginx Champuru
Author: Takayuki Miyauchi
Plugin URI: http://firegoby.theta.ne.jp/wp/nginx-champuru
Description: Plugin for Nginx Reverse Proxy
Version: 0.5.0
Author URI: http://firegoby.theta.ne.jp/
Domain Path: /languages
Text Domain: nginx-champuru
*/

$nginxchampuru = new NginxChampuru();
register_activation_hook (__FILE__, array($nginxchampuru, 'activation'));

require_once(dirname(__FILE__)."/includes/caching.class.php");
new NginxChampuru_Caching();
require_once(dirname(__FILE__)."/includes/cache-control.class.php");
new NginxChampuru_CacheControl();
require_once(dirname(__FILE__)."/includes/flush-cache.class.php");
new NginxChampuru_FlushCache();
require_once(dirname(__FILE__)."/includes/admin.class.php");
new NginxChampuru_Admin();

class NginxChampuru {

private $table;
private $expire = 86400;
private $cache_dir = "/var/cache/nginx";

function __construct()
{
    global $wpdb;
    $this->table = $wpdb->prefix.'nginxchampuru';
    add_action('plugins_loaded',    array(&$this, 'plugins_loaded'));
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
    if (is_admin() || is_user_logged_in()) {
        return;
    }
    global $wpdb;
    $wpdb->hide_errors();
    @$wpdb->insert(
        $this->table,
        array(
            'cache_key'  => $this->get_cache_key(),
            'cache_id'   => $this->get_postid(),
            'cache_type' => $this->get_post_type()
        ),
        array('%s', '%d', '%s')
    );
    $wpdb->show_errors();
}

public function flush_cache($mode = "all", $id = 0)
{
    global $wpdb;
    if ($mode == "all") {
        $sql = "select `cache_key` from `$this->table`";
    } elseif ($mode == "single" && intval($id)) {
        $sql = $wpdb->prepare(
            "select `cache_key` from `$this->table` where cache_id=%d",
            intval($id)
        );
    } elseif (!$mode && intval($id)) {
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
            `cache_type` varchar(10) not null,
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
    if (!strlen($expires[$par])) {
        return $this->expire;
    } else {
        return $expires[$par];
    }
}

public function get_post_type()
{
    if (is_home()) {
        $type = "is_home";
    } elseif (is_archive()) {
        $type = "is_archive";
    } elseif (is_single()) {
        $type = "is_single";
    } elseif (is_page()) {
        $type = "is_page";
    } elseif (is_singular()) {
        $type = "is_singular";
    } else {
        $type = "other";
    }

    return $type;
}

public function get_cache($key)
{
    if (has_filter("nginxchampuru_get_cache")) {
        return apply_filters(
            'nginxchampuru_get_cache',
            $key
        );
    } else {
        $path = array();
        $path[] = $this->get_cache_dir();
        $path[] = substr($key, -1);
        $path[] = substr($key, -3, 2);
        $path[] = $key;
        return join("/", $path);
    }
}

private function get_cache_dir()
{
    return $this->cache_dir;
}

private function get_cache_key($url = null)
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

private function get_the_url()
{
    return apply_filters(
        'nginxchampuru_get_the_url',
        'http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]
    );
}

}

// EOF
