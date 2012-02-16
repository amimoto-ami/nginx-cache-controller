<?php

class NginxChampuru_FlushCache {

function __construct()
{
    add_action("wp", array(&$this, "wp"));
    add_action('wp_ajax_flushcache', array(&$this, 'wp_ajax_flushcache'));
    add_action('wp_ajax_flushthis', array(&$this, 'wp_ajax_flushthis'));
    add_action("publish_future_post", array(&$this, "flush_by_post"));
    add_action("publish_post", array(&$this, "flush_by_post"));
    add_action("comment_post", array(&$this, "flush_by_comment"));
    add_action("wp_set_comment_status", array(&$this, "flush_by_comment"));
}

public function flush_by_post($id)
{
    global $nginxchampuru;
    $mode = $nginxchampuru->get_flush_method("publish");
    $this->flush_caches($mode, $id);
}

public function flush_by_comment($cid)
{
    global $nginxchampuru;
    $com = get_comment($cid);
    $mode = $nginxchampuru->get_flush_method("comment");
    $this->flush_caches($mode, $com->comment_post_ID);
}

public function flush_caches($mode = null, $id = 0)
{
    global $nginxchampuru;
    if (!$nginxchampuru->is_enable_flush()) {
        return;
    }
    if ($mode) {
        $nginxchampuru->transientExec("flush_cache", $mode, $id);
    }
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
