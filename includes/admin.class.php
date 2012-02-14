<?php


class NginxChampuru_Admin {

private $default_cache_params = array();

function __construct()
{
    $this->default_cache_params = array(
        'is_home'     => __("Home", "nginxchampuru"),
        'is_archive'  => __("Archives", "nginxchampuru"),
        'is_singular' => __("Singular", "nginxchampuru"),
        'other'       => __("Other", "nginxchampuru"),
    );
    add_action("admin_bar_menu", array(&$this, "admin_bar_menu"), 9999);
    add_action("admin_menu", array(&$this, "admin_menu"));
}

public function admin_menu()
{
    global $nginxchampuru;

    $hook = add_menu_page(
        "Nginx Champuru",
        "Nginx Champuru",
        "update_core",
        "nginx-champuru",
        array(&$this, "admin_panel"),
        $nginxchampuru->get_plugin_url().'/img/nginx.png',
        "3"
    );
    add_action('admin_print_styles-'.$hook, array(&$this, 'admin_styles'));
}

public function admin_panel()
{
    global $nginxchampuru;
    echo "<div class=\"wrap\">";
    require_once(dirname(__FILE__)."/admin_panel.php");
    echo "</div>";
}

public function admin_styles()
{
    global $nginxchampuru;
    wp_register_style(
        "nginxchampuru",
        $nginxchampuru->get_plugin_url().'/admin.css',
        array(),
        filemtime($nginxchampuru->get_plugin_dir()."/admin.css")
    );
    wp_enqueue_style("ninjax-style");
}

public function admin_bar_menu($bar)
{
    if (current_user_can("administrator")) {
        $bar->add_menu(array(
            "id"    => "nginxchampuru",
            "title" => "Nginx Cache",
            "href"  => false,
        ));

        if (!is_admin()) {
            $bar->add_menu(array(
                "parent" => "nginxchampuru",
                "id"    => "flushcache",
                "title" => __("Flush This Page Cache", "nginxchampuru"),
                "href"  => $this->get_flushthis_url(),
                "meta"  => false,
            ));
        }

        $bar->add_menu(array(
            "parent" => "nginxchampuru",
            "id"    => "clearcache",
            "title" => __("Flush all caches", "nginxchampuru"),
            "href"  => $this->get_cacheclear_url(),
            "meta"  => false,
        ));
    }
}

private function get_flushthis_url()
{
    global $nginxchampuru;
    return admin_url(sprintf(
        "/admin-ajax.php?action=flushthis&redirect_to=%s&nonce=%s",
        urlencode(esc_url($nginxchampuru->get_the_url())),
        wp_create_nonce("flushthis")
    ));
}

private function get_cacheclear_url()
{
    global $nginxchampuru;
    return admin_url(sprintf(
        "/admin-ajax.php?action=flushcache&redirect_to=%s&nonce=%s",
        urlencode(esc_url($nginxchampuru->get_the_url())),
        wp_create_nonce("flushcache")
    ));
}

}

// EOF
