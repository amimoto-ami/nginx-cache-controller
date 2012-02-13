<?php

class NginxChampuru_FlushCache {

private $post_type = "nginx_cache_uri";

function __construct()
{
    add_action("wp", array(&$this, "wp"));
    add_action('wp_ajax_clearcache', array(&$this, 'wp_ajax_clearcache'));
}

public function wp_ajax_clearcache()
{
    if (current_user_can("administrator")) {
        global $nginxchampuru;
        $nginxchampuru->flush_cache("all");
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
