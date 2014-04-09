<?php

class NginxChampuru_FlushCache {

function __construct()
{
    add_action("template_redirect", array(&$this, "template_redirect"), 0);
    add_action('wp_ajax_flushcache', array(&$this, 'wp_ajax_flushcache'));
    add_action('wp_ajax_flushthis', array(&$this, 'wp_ajax_flushthis'));
    add_action("publish_future_post", array(&$this, "flush_by_post"));
    add_action("save_post", array(&$this, "flush_by_post"));
    add_action("comment_post", array(&$this, "flush_by_comment"));
    add_action("wp_set_comment_status", array(&$this, "flush_by_comment"));
}

public function flush_by_post($id)
{
    $post = get_post($id);
    $stat = array(
        'publish',
        'inherit',
    );
    if (in_array($post->post_status, $stat)) {
        global $nginxchampuru;
        $mode = $nginxchampuru->get_flush_method("publish");
        self::flush_caches($mode, $id);
    }
}

public function flush_by_comment($cid)
{
    global $nginxchampuru;
    $com = get_comment($cid);
    $mode = $nginxchampuru->get_flush_method("comment");
    self::flush_caches($mode, $com->comment_post_ID);
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
    if (current_user_can("flush_cache_single")) {
        global $nginxchampuru;
        $nginxchampuru->transientExec("flush_this", esc_url($_GET["redirect_to"]));
    } else {
        wp_die('Permission denied.');
    }
    wp_redirect(esc_url($_GET["redirect_to"]));
    exit;
}

public function wp_ajax_flushcache()
{
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], "flushcache")) {
        die('Security check');
    }
    if (current_user_can("flush_cache_all")) {
        global $nginxchampuru;
        $nginxchampuru->transientExec("flush_cache", "all", false);
    } else {
        wp_die('Permission denied.');
    }
    wp_redirect(esc_url($_GET["redirect_to"]));
    exit;
}

public function template_redirect()
{
    if (!is_404()) {
        global $nginxchampuru;
        $nginxchampuru->add();
    }
}

}

// EOF
