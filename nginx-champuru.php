<?php
/*
Plugin Name: Nginx Champuru
Author: Takayuki Miyauchi
Plugin URI: http://firegoby.theta.ne.jp/wp/nginx-champuru
Description: Plugin for Nginx Reverse Proxy 
Version: 0.2.0
Author URI: http://firegoby.theta.ne.jp/
Domain Path: /languages
Text Domain: nginx-champuru
*/

require_once(dirname(__FILE__).'/includes/class-addrewriterules.php');

new NginxChampuru();
register_activation_hook(__FILE__, 'flush_rewrite_rules');

class NginxChampuru {

private $js_version = "0.2.0";
private $query = "nginxchampuru";

function __construct()
{
    new WP_AddRewriteRules(
        'nginx-champuru.json$',
        $this->query,
        array(&$this, 'get_commenter_json')
    );
    add_action("wp", array(&$this, "wp"));
    add_action(
        'wp_enqueue_scripts',
        array(&$this, 'wp_enqueue_scripts')
    );
    add_filter(
        "wp_get_current_commenter",
        array(&$this, "wp_get_current_commenter"),
        9999
    );
}

public function wp()
{
    global $post;
    if (post_password_required($post->ID)) {
        nocache_headers();
    }
}

public function wp_enqueue_scripts()
{
    if (!comments_open()) {
        return;
    }
    wp_enqueue_script(
        'nginx-champuru',
        plugins_url('/nginx-champuru.js', __FILE__),
        array('jquery'),
        $this->js_version,
        true
    );
}

public function get_commenter_json()
{
    nocache_headers();
    header('Content-type: application/json');
    echo json_encode(wp_get_current_commenter());
    exit;
}

public function wp_get_current_commenter($commenter)
{
    if (get_query_var($this->query)) {
        return $commenter;
    } else {
        return array(
            'comment_author'       => '',
            'comment_author_email' => '',
            'comment_author_url'   => '',
        );
    }
}

}

?>
