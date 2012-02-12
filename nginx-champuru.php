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

class NginxChampuru {

private $table;
private $expire = 86400;
private $cache_dir = "/var/cache/nginx";

function __construct()
{
    global $wpdb;
    $this->table = $wpdb->prefix.'nginxchampuru';
}

public function add()
{
    if (is_admin()) {
        return;
    }
    global $wpdb;
    $res = $wpdb->insert(
        $this->table,
        array(
            'cache_key'  => $this->get_cache_key(),
            'cache_id'   => $this->get_postid(),
            'cache_type' => $this->get_post_type()
        ),
        array('%s', '%d', '%s')
    );
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

private function get_cache_dir()
{
    if (defined("NGINX_CACHE_DIR") && is_dir("NGINX_CACHE_DIR")) {
        return NGINX_CACHE_DIR;
    } elseif (get_option("nginxchampuru_cache_dir") &&
            is_dir(get_option("nginxchampuru_cache_dir"))) {
        return get_option("nginxchampuru_cache_dir");
    } else {
        return $this->cache_dir;
    }
}

private function get_cache_key()
{
    if (has_filter("nginxchampuru_get_reverse_proxy_key")) {
        return apply_filters(
            'nginxchampuru_get_reverse_proxy_key',
            $this->get_the_url()
        );
    } else {
        return md5($this->get_the_url());
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
