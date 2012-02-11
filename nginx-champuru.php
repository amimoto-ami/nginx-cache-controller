<?php
/*
Plugin Name: Nginx Champuru
Author: Takayuki Miyauchi
Plugin URI: http://firegoby.theta.ne.jp/wp/nginx-champuru
Description: Plugin for Nginx Reverse Proxy
Version: 0.5.0
Author URI: http://firegoby.theta.ne.jp/
Domain Path: /languages
Text Domain: nginx-champuru
*/

new NginxChampuru();

class NginxChampuru {

function __construct()
{
    require_once(dirname(__FILE__)."/includes/caching.class.php");
    new NginxChampuru_Caching();
    require_once(dirname(__FILE__)."/includes/cache-control.class.php");
    new NginxChampuru_CacheControl();
    require_once(dirname(__FILE__)."/includes/flush-cache.class.php");
    new NginxChampuru_FlushCache();
}

}

// EOF
