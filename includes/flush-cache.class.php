<?php

class NginxChampuru_FlushCache {

private $post_type = "nginx_cache_uri";

function __construct()
{
    add_action("wp", array(&$this, "wp"));
}

public function wp()
{
    global $nginxchampuru;
    $nginxchampuru->add();
}

}

// EOF
