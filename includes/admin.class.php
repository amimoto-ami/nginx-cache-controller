<?php


class NginxChampuru_Admin {

private $default_cache_params = array();
private $methods = array();

function __construct()
{
    $this->default_cache_params = array(
        'is_home'     => __("Home", "nginxchampuru"),
        'is_archive'  => __("Archives", "nginxchampuru"),
        'is_singular' => __("Singular", "nginxchampuru"),
        'other'       => __("Other", "nginxchampuru"),
    );
    $this->methods = array(
        'none' => __('None', 'nginxchampuru'),
        'all' => __('Flush All Caches.', 'nginxchampuru'),
        'almost' => __('Flush current page and non-article pages.', 'nginxchampuru'),
        'single' => __('Flush current page only.', 'nginxchampuru'),
    );

    add_action("admin_bar_menu", array(&$this, "admin_bar_menu"), 9999);
    add_action("admin_menu", array(&$this, "admin_menu"));
}

private function get_modes_select($name)
{
    global $nginxchampuru;
    $method = $nginxchampuru->get_flush_method($name);
    $input = '<li><input type="radio" name="%s" value="%s" %s /> %s</li>';
    echo "<ul class=\"checkbox\">";
    foreach ($this->methods as $key => $text) {
        if ($key === $method) {
            $check = 'checked="checked"';
        } else {
            $check = null;
        }
        printf(
            $input,
            $name,
            $key,
            $check,
            $text
        );
    }
    echo "</ul>";
}

public function admin_menu()
{
    global $nginxchampuru;

    $hook = add_menu_page(
        "Nginx Cache",
        "Nginx Cache",
        "update_core",
        "nginx-champuru",
        array(&$this, "admin_panel"),
        $nginxchampuru->get_plugin_url().'/img/nginx.png',
        "3"
    );
    add_action('admin_print_styles-'.$hook, array(&$this, 'admin_styles'));
    add_action('admin_head-'.$hook, array(&$this, 'admin_head'));
}

public function admin_head()
{
    if (!isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], "nginxchampuru-optionsave")) {
        return;
    }

    foreach ($this->default_cache_params as $key => $value) {
        $_POST['expires'][$key] = intval($_POST['expires'][$key]);
    }
    update_option("nginxchampuru-cache_expires", $_POST['expires']);
    update_option("nginxchampuru-publish", $_POST["publish"]);
    update_option("nginxchampuru-comment", $_POST["comment"]);
    wp_redirect(admin_url("admin.php?page=nginx-champuru&message=true"));
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
    wp_enqueue_style("nginxchampuru");
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
