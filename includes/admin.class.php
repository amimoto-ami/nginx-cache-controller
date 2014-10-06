<?php


class NginxChampuru_Admin {

private $default_cache_params = array();
private $methods = array();

private static $instance;

private function __construct() {}

public static function get_instance()
{
    if( !isset( self::$instance ) ) {
        $c = __CLASS__;
        self::$instance = new $c();    
    }
    return self::$instance;
}

public function add_hook()
{
    add_action("admin_bar_menu", array($this, "admin_bar_menu"), 9999);
    add_action("admin_menu", array($this, "admin_menu"));
    add_filter('plugin_row_meta',   array($this, 'plugin_row_meta'), 10, 2);
}

public function plugin_row_meta($links, $file)
{
    if (NGINX_CACHE_CONTROLER_BASE_NAME === $file) {
        $link = '<a href="%s">%s</a>';
        $url = __("http://wpbooster.net/", 'nginxchampuru');
        $links[] = sprintf($link, esc_url($url), __("Make WordPress Site Load Faster", "nginxchampuru"));
    }
    return $links;
}

private function is_enable_flush()
{
    global $nginxchampuru;
    $enabled = $nginxchampuru->is_enable_flush();
    $checked = '<input type="%s" name="%s" id="%s" value="%d" checked="checked" /> %s';
    $notchecked = '<input type="%s" name="%s" id="%s" value="%d" /> %s';
    $list = array();
    if ($enabled) {
        $list[] = sprintf(
            $checked,
            "radio",
            "enable_flush",
            "radio-yes",
            "1",
            "<label for=\"radio-yes\">Yes</label>"
        );
        $list[] = sprintf(
            $notchecked,
            "radio",
            "enable_flush",
            "radio-no",
            "0",
            "<label for=\"radio-no\">No</label>"
        );
    } else {
        $list[] = sprintf(
            $notchecked,
            "radio",
            "enable_flush",
            "radio-yes",
            "1",
            "<label for=\"radio-yes\">Yes</label>"
        );
        $list[] = sprintf(
            $checked,
            "radio",
            "enable_flush",
            "radio-no",
            "0",
            "<label for=\"radio-no\">No</label>"
        );
    }

    echo "<ul><li>".join("</li><li>", $list)."</li></ul>";
}

private function get_modes_select($name)
{
    global $nginxchampuru;
    $method = $nginxchampuru->get_flush_method($name);
    $input = '<li><input type="radio" name="%1$s" value="%2$s" id="%1$s_%2$s" %3$s /> <label for="%1$s_%2$s">%4$s</label></li>';
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

private function add_last_modified()
{
    global $nginxchampuru;
    $enabled = $nginxchampuru->add_last_modified();
    $checked = '<input type="%s" name="%s" id="%s" value="%d" checked="checked" /> %s';
    $notchecked = '<input type="%s" name="%s" id="%s" value="%d" /> %s';
    $list = array();
    if ($enabled) {
        $list[] = sprintf(
            $checked,
            "radio",
            "add_last_modified",
            "add_last_modified-yes",
            "1",
            "<label for=\"add_last_modified-yes\">Yes</label>"
        );
        $list[] = sprintf(
            $notchecked,
            "radio",
            "add_last_modified",
            "add_last_modified-no",
            "0",
            "<label for=\"add_last_modified-no\">No</label>"
        );
    } else {
        $list[] = sprintf(
            $notchecked,
            "radio",
            "add_last_modified",
            "add_last_modified-yes",
            "1",
            "<label for=\"add_last_modified-yes\">Yes</label>"
        );
        $list[] = sprintf(
            $checked,
            "radio",
            "add_last_modified",
            "add_last_modified-no",
            "0",
            "<label for=\"add_last_modified-no\">No</label>"
        );
    }

    echo "<ul><li>".join("</li><li>", $list)."</li></ul>";
}

public function admin_menu()
{
    global $nginxchampuru, $wp_version;

    $this->default_cache_params = array(
        'is_home'     => __("Home", "nginxchampuru"),
        'is_archive'  => __("Archives", "nginxchampuru"),
        'is_singular' => __("Singular", "nginxchampuru"),
        'is_feed'     => __("Feed", "nginxchampuru"),
        'other'       => __("Other", "nginxchampuru"),
    );

    $this->methods = array(
        'none' => __('None', 'nginxchampuru'),
        'all' => __('Flush All Caches.', 'nginxchampuru'),
        'almost' => __('Flush all caches except the ones of the current page and single post / page.', 'nginxchampuru'),
        'single' => __('Flush current page only.', 'nginxchampuru'),
    );

    $icon = 'none';
    if ( version_compare( $wp_version, '3.8', '<' ) ) {
        $icon = $nginxchampuru->get_plugin_url().'/img/nginx.png';
    }

    $hook = add_menu_page(
        "Nginx Cache",
        "Nginx Cache",
        "administrator",
        "nginx-champuru",
        array(&$this, "admin_panel"),
        $icon,
        "3"
    );

    if ( version_compare( $wp_version, '3.8', '>=' ) ) {
        add_action('admin_enqueue_scripts', array(&$this, 'admin_menu_styles'));
    }

    add_action('admin_print_styles-'.$hook, array(&$this, 'admin_styles'));
    add_action('admin_head-'.$hook, array(&$this, 'admin_head'));
}

public function admin_head()
{
    if (!isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], "nginxchampuru-optionsave")) {
        return;
    }

    global $nginxchampuru;
    $nginxchampuru->activation();

    foreach ($this->default_cache_params as $key => $value) {
        $_POST['expires'][$key] = intval($_POST['expires'][$key]);
    }
    update_option("nginxchampuru-cache_expires", $_POST['expires']);
    update_option("nginxchampuru-enable_flush", intval($_POST['enable_flush']));
    update_option("nginxchampuru-cache_dir", $_POST['cache_dir']);
    update_option("nginxchampuru-cache_levels", $_POST['cache_levels']);
    update_option("nginxchampuru-publish", esc_html($_POST["publish"]));
    update_option("nginxchampuru-comment", esc_html($_POST["comment"]));
    update_option("nginxchampuru-add_last_modified", intval($_POST['add_last_modified']));
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
        $nginxchampuru->get_plugin_url().'/css/admin.min.css',
        array(),
        filemtime($nginxchampuru->get_plugin_dir()."/css/admin.min.css")
    );
    wp_enqueue_style("nginxchampuru");
}

public function admin_menu_styles()
{
    global $nginxchampuru;
    wp_register_style(
        "nginxchampuru-menu",
        $nginxchampuru->get_plugin_url().'/css/menu.css',
        array(),
        filemtime($nginxchampuru->get_plugin_dir()."/css/menu.css")
    );
    wp_enqueue_style("nginxchampuru-menu");
}

public function admin_bar_menu($bar)
{
    global $nginxchampuru;
    if (!$nginxchampuru->is_enable_flush()) {
        return;
    }

    if (current_user_can("flush_cache_single") || current_user_can("flush_cache_all")) {
        if (is_admin() && current_user_can('flush_cache_all')) {
            $bar->add_menu(array(
                "id"    => "nginxchampuru",
                "title" => "Nginx Cache",
                "href"  => false,
            ));
            if (current_user_can('flush_cache_all')) {
                $bar->add_menu(array(
                    "parent" => "nginxchampuru",
                    "id"    => "clearcache",
                    "title" => __("Flush All Caches", "nginxchampuru"),
                    "href"  => $this->get_cacheclear_url(),
                    "meta"  => false,
                ));
            }
        } elseif (!is_admin()) {
            $bar->add_menu(array(
                "id"    => "nginxchampuru",
                "title" => "Nginx Cache",
                "href"  => false,
            ));
            if (current_user_can('flush_cache_all')) {
                $bar->add_menu(array(
                    "parent" => "nginxchampuru",
                    "id"    => "clearcache",
                    "title" => __("Flush All Caches", "nginxchampuru"),
                    "href"  => $this->get_cacheclear_url(),
                    "meta"  => false,
                ));
            }
            if (current_user_can("flush_cache_single")) {
                $bar->add_menu(array(
                    "parent" => "nginxchampuru",
                    "id"    => "flushcache",
                    "title" => __("Flush This Page Cache", "nginxchampuru"),
                    "href"  => $this->get_flushthis_url(),
                    "meta"  => false,
                ));
            }
        }
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
        urlencode($_SERVER['REQUEST_URI']),
        wp_create_nonce("flushcache")
    ));
}

}

// EOF
