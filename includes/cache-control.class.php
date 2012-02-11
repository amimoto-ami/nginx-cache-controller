<?php

class NginxChampuru_CacheControl {

private $expire = 86400;

function __construct()
{
    add_action("template_redirect", array(&$this, "template_redirect"));
}

public function template_redirect()
{
    if (is_home()) {
        $exp = $this->get_expires("is_home");
    } elseif (is_archive()) {
        $exp = $this->get_expires("is_archive");
    } elseif (is_single()) {
        $exp = $this->get_expires("is_single");
    } elseif (is_page()) {
        $exp = $this->get_expires("is_page");
    } elseif (is_singular()) {
        $exp = $this->get_expires("is_singular");
    } else {
        $exp = $this->get_expires("other");
    }
    header('X-Accel-Expires: '.intval($exp));
}

private function get_expires()
{
    $expires = get_option("nginxchampuru-cache_expires");
    if (!strlen($expires[$par])) {
        return $this->expire;
    } else {
        return $expires[$par];
    }
}

}

// EOF
