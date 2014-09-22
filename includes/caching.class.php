<?php

class NginxChampuru_Caching {
const WP_CRON_EXP = 60;

private $q = "nginx_get_commenter";
private $last_modified;

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
    add_action("wp", array($this, "wp"));
    add_action(
        'wp_enqueue_scripts',
        array($this, 'wp_enqueue_scripts')
    );
    add_filter(
        "wp_get_current_commenter",
        array($this, "wp_get_current_commenter"),
        9999
    );
    add_filter("got_rewrite", "__return_true");
    add_filter("pre_comment_user_ip", array($this, "pre_comment_user_ip"));
    add_filter("nocache_headers", array($this, "nocache_headers"));
    add_action("template_redirect", array($this, "template_redirect"), 9999);
    add_filter("nonce_life", array($this, "nonce_life"));

    if (!is_admin()) {
        $this->last_modified = gmdate('D, d M Y H:i:s', time()) . ' GMT';
        add_action('template_redirect', array($this, 'send_http_header_last_modified'));
        add_action('wp_head', array($this, 'last_modified_meta_tag'));
    }

	add_action('plugins_loaded', array($this, 'wp_cron_caching'));
}

public function nonce_life($life)
{
    $expires = get_option("nginxchampuru-cache_expires", array($life));
    $max = max(array_values($expires));
    if ($max > $life) {
        return $max;
    } else {
        return $life;
    }
}

public function template_redirect()
{
    global $nginxchampuru;
    $exp = $nginxchampuru->get_expire();
	$headers = array(
		'X-Accel-Expires' => intval($exp)
	);
	$headers = apply_filters('nginxchampuru_caching_headers', $headers);
	foreach ($headers as $h => $value) {
    	header($h.': '.intval($exp));
	}
}

public function nocache_headers($h)
{
    $h["X-Accel-Expires"] = 0;
	$h = apply_filters('nginxchampuru_nocache_headers', $h);
    return $h;
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

public function wp()
{
    global $post;
    if (is_singular() && post_password_required($post->ID)) {
        nocache_headers();
    }
}

public function wp_cron_caching() {
    if (defined('DOING_CRON') && DOING_CRON) {
	    header('X-Accel-Expires: '.intval(self::WP_CRON_EXP));
    }
	header('X-Cached: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
}

public function wp_enqueue_scripts()
{
	if (is_user_logged_in())
		return;

    if (is_singular() && comments_open()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
        	'jquery.cookie',
        	plugins_url('js/jquery.cookie.min.js', dirname(dirname(__FILE__)) . '/nginx-champuru.php'),
        	array('jquery'),
        	'1.3.1',
        	true
        );

        add_action(
            "wp_print_footer_scripts",
            array(&$this, "wp_print_footer_scripts_admin_ajax")
        );
    }

    if ($this->is_future_post() && (!defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON)) {
        wp_enqueue_script('jquery');
        add_action(
            "wp_print_footer_scripts",
            array(&$this, "wp_print_footer_scripts_wp_cron")
        );
    }
}

public function wp_print_footer_scripts_admin_ajax()
{
    $js = '
<script type="text/javascript">
(function($){
    $("#author").val($.cookie("comment_author_%1$s"));
    $("#email").val($.cookie("comment_author_email_%1$s"));
    $("#url").val($.cookie("comment_author_url_%1$s"));
})(jQuery);
</script>
';
    $js = sprintf($js, COOKIEHASH);

    echo apply_filters('wp_print_footer_scripts_admin_ajax', $js);
}

public function wp_print_footer_scripts_wp_cron(){
    $js = '
<script type="text/javascript">
(function($){
    $.get("%s");
})(jQuery);
</script>
';
    $js = sprintf($js, site_url('wp-cron.php'));
    echo apply_filters('wp_print_footer_scripts_wp_cron', $js);
}

private function add_last_modified() {
    global $nginxchampuru;
    return (function_exists('is_user_logged_in') && !is_user_logged_in() && $nginxchampuru->add_last_modified());
}

public function send_http_header_last_modified()
{
    if ($this->add_last_modified()) {
		header("Last-Modified: {$this->last_modified}");
    }
}

public function last_modified_meta_tag()
{
    if (!is_feed() && $this->add_last_modified()) {
        $last_modified_meta_tag = "<meta http-equiv=\"Last-Modified\" content=\"{$this->last_modified}\" />\n";
        echo $last_modified_meta_tag;
    }
}

public function wp_get_current_commenter($commenter)
{
    return array(
        'comment_author'       => '',
        'comment_author_email' => '',
        'comment_author_url'   => '',
    );
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

// EOF
