<?php

class NginxChampuru_FlushCache {

private $post_type = "nginx_cache_uri";

function __construct()
{
    add_action("wp", array(&$this, "wp"));
    add_action('wp_ajax_flushcache', array(&$this, 'wp_ajax_flushcache'));
    add_action('wp_ajax_flushthis', array(&$this, 'wp_ajax_flushthis'));
}

public function wp_ajax_flushthis()
{
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], "flushthis")) {
        die('Security check');
    }
    if (current_user_can("administrator")) {
        global $nginxchampuru;
        $nginxchampuru->transientExec("flush_this", esc_url($_GET["redirect_to"]));
    }
    wp_redirect(esc_url($_GET["redirect_to"]));
    exit;
}

public function wp_ajax_flushcache()
{
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], "flushcache")) {
        die('Security check');
    }
    if (current_user_can("administrator")) {
        global $nginxchampuru;
        $nginxchampuru->transientExec("flush_cache", "all", 1212);
    }
    wp_redirect(esc_url($_GET["redirect_to"]));
    exit;
}

public function wp()
{
    global $nginxchampuru;
    $nginxchampuru->add();
}

}

// EOF
