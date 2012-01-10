<?php
/*
Plugin Name: Nginx Champuru
Author: Takayuki Miyauchi
Plugin URI: http://firegoby.theta.ne.jp/wp/nginx-champuru
Description: Plugin for Nginx Reverse Proxy 
Version: 0.4.0
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
private $cache_dir = '/var/cache/nginx';

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
    add_action("publish_future_post ", array(&$this, "flush_caches"));
    add_action("save_post", array(&$this, "flush_caches"));
    add_action("comment_post", array(&$this, "flush_caches"));
    add_action("wp_set_comment_status", array(&$this, "flush_caches"));
    add_filter("got_rewrite", "__return_true");
    add_filter("pre_comment_user_ip", array(&$this, "pre_comment_user_ip"));
}

public function pre_comment_user_ip()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $X_FORWARDED_FOR = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
        $REMOTE_ADDR = trim($X_FORWARDED_FOR[0]);
    } else {
        $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
    }
    return $REMOTE_ADDR;
}

public function flush_caches()
{
    if (defined("NGINX_DELETE_CACHES") && NGINX_DELETE_CACHES) {
        if (defined("NGINX_CACHE_DIR") && NGINX_CACHE_DIR) {
            $this->cache_dir = NGINX_CACHE_DIR;
        }
        $cmd = sprintf(
            'grep -lr %s %s | xargs rm -f &> /dev/null &',
            escapeshellarg(home_url()),
            escapeshellarg($this->cache_dir)
        );
        exec($cmd);
    }
}

public function wp()
{
    global $post;
    if (is_singular() && post_password_required($post->ID)) {
        nocache_headers();
    }
}

public function wp_enqueue_scripts()
{
    if ((is_singular() && comments_open()) || $this->is_future_post()) {
        wp_enqueue_script(
            'nginx-champuru',
            plugins_url('/nginx-champuru.js', __FILE__),
            array('jquery'),
            $this->js_version,
            true
        );
    }
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

private function is_future_post()
{
    $cron = get_option("cron");
    foreach ($cron as $key => $jobs) {
        if (is_array($jobs)) {
            $res = array_key_exists("publish_future_post", $jobs);
            if ($res) {
                return true;
            }
        }
    }
    return false;
}

}

?>
