<?php


class NginxChampuru_Admin {

function __construct()
{
    add_action("admin_bar_menu", array(&$this, "admin_bar_menu"), 9999);
}

public function admin_bar_menu($bar)
{
    if (current_user_can("administrator")) {
        $bar->add_menu(array(
            "id"    => "ninjax",
            "title" => "Nginx Cache",
            "href"  => false,
        ));
        $bar->add_menu(array(
            "parent" => "ninjax",
            "id"    => "clearcache",
            "title" => __("Flush all caches", "nginxchampuru"),
            "href"  => $this->get_cacheclear_url(),
            "meta"  => false,
        ));
    }
}

private function get_cacheclear_url()
{
    return admin_url(sprintf(
        "/admin-ajax.php?action=clearcache&redirect_to=%s",
        esc_url($_SERVER["REQUEST_URI"])
    ));
}

}

// EOF
