<?php

class NginxChampuru_CacheControl {

private $ngx;

function __construct()
{
    add_action("template_redirect", array(&$this, "template_redirect"));
}

public function admin_menu()
{
    $hook = add_menu_page(
        "Nginx Settings",
        "Nginx Settings",
        "update_core",
        "nginxchampuru",
        array(&$this, "cache_control"),
        plugins_url($this->plugin_dir.'/img/nginx.png', __FILE__),
        "3"
    );
    add_action('admin_print_styles-'.$hook, array(&$this, 'admin_styles'));
}

public function admin_styles()
{
    wp_register_style(
        "ninjax-style",
        plugins_url($this->plugin_dir.'/admin.css', __FILE__),
        array(),
        filemtime(dirname(__FILE__).$this->plugin_dir."/admin.css")
    );
    wp_enqueue_style("ninjax-style");
}

public function template_redirect()
{
    global $nginxchampuru;
    $exp = $nginxchampuru->get_expire();
    header('X-Accel-Expires: '.intval($exp));
}

}

// EOF
