<?php

namespace NginxCC;

class Conf {

    const default_expires   = 86400;
    const cache_dir         = "/var/cache/nginx";
    const cache_levels      = "1:2";
    const transient_timeout = 60;

    public static $flush_method    = array(
        'save_post'             => 'flush_current_page_and_archives_caches',
        'publish_future_post'   => 'flush_current_page_and_archives_caches',
        'comment_post'          => 'flush_current_page_caches',
        'wp_set_comment_status' => 'flush_current_page_caches',
    );
}
