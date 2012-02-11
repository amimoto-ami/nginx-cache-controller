<?php

class NginxChampuru_FlushCache {

private $cache_dir = "/var/cache/nginx";

function __construct()
{
}

private function get_cache_dir()
{
    if (defined("NGINX_CACHE_DIR") && is_dir("NGINX_CACHE_DIR")) {
        return NGINX_CACHE_DIR;
    } elseif (get_option("nginxchampuru-cache_dir") &&
            is_dir(get_option("nginxchampuru-cache_dir"))) {
        return get_option("nginxchampuru-cache_dir");
    } else {
        return $this->cache_dir;
    }
}

}

// EOF
