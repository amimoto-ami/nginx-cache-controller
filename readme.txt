=== Nginx Cache Controller ===
Contributors: miyauchi, wokamoto
Donate link: http://ninjax.cc/
Tags: nginx, reverse proxy, cache
Requires at least: 3.4
Tested up to: 3.8
Stable tag: 2.4.0

Provides some functions of controlling Nginx proxy server cache.

== Description ==

This plugin provides some functions of controlling Nginx proxy server cache.

= Security =

* Making comment authors' information ajaxed to prevent the information from caching.
* Send no-cache header on password protected posts to prevent the posts from caching.
* When a scheduled post is published, it will delete the cache through Ajax.

= Controlling cache =

* Sending X-ACCEL-EXPIRES, you can specify the available period of the cache.
* When you save your post and someone post comments, the cache is deleted automatically.
* Add a menu on the admin bar to delete the cache.

= Memo =

* Gets comment poster's IP address by HTTP_X_FORWARDED_FOR header.
* Fixes the issue that the permanent link setting includes index.php.
* When the cache's expiration period is more than 86400 sec, change the value of wp_verify_nonce() same as the period.

= WP-CLI Support =

Flush all proxy caches.
`wp nginx flush`

Show list of all proxy caches.
`wp nginx list`

See help.
`wp help nginx`

= Languages =
* English(en) - [JOTAKI Taisuke](http://tekapo.com/)
* Japanese(Ja) - [JOTAKI Taisuke](http://tekapo.com/)
* Vietnamese(vi) - [Trong](http://bizover.net/)

= Contributor =

* [Ninjax Team](http://ninjax.cc/) 
* [miyauchi](http://profiles.wordpress.org/miyauchi/)
* [wokamoto](http://profiles.wordpress.org/wokamoto/)
* [gatespace](http://profiles.wordpress.org/gatespace/)

== Installation ==

* A plug-in installation screen is displayed on the WordPress admin panel.
* It installs it in `wp-content/plugins`.
* The plug-in is made effective.

Example of Nginx settings:

Allow X-ACCEL-EXPIRES for fastcgi.

`   location ~ \.php$ {
        include        /etc/nginx/fastcgi_params;
        fastcgi_pass   unix:/tmp/php-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $vhost_root/$fastcgi_script_name;
        fastcgi_pass_header "X-Accel-Redirect";
        fastcgi_pass_header "X-Accel-Expires";
    }`

Setting cache directory for reverse proxy.

`proxy_cache_path  /var/cache/nginx levels=1:2 keys_zone=czone:4m max_size=50m inactive=120m;`

* The default path is /var/cache/nginx. 
* The default value of levels is 1:2.
* You can change the cache path at the admin panel.

Setting the key for the reverse cache proxy.

`proxy_cache_key "$scheme://$host$request_uri"`

* You can customize proxy_cache_key with `nginxchampuru_get_reverse_proxy_key` hook.


== Screenshots ==

1. Admin Panel
2. Adminbar


== Changelog ==

= 2.4.0 =

* Bug fix. (SQL faild at RDS on the AWS.)

https://github.com/megumiteam/nginx-cache-controller/compare/2.3.0...2.4.0

= 2.3.0 =
* Bug fix. (Menu doen't shown when DISALLOW_FILE_MODS is enabled.)

https://github.com/megumiteam/nginx-cache-controller/compare/2.2.1...2.3.0

= 2.2.1 =
* Add language Vietnamese (vi).

= 2.2.0 =
* Add Grunt.
* Update admin interface.

https://github.com/megumiteam/nginx-cache-controller/compare/2.1.0...2.2.0

= 2.1.0 =
* Tested up to 3.8.

= 2.0.0 =
* [Bug fix](https://github.com/megumiteam/nginx-cache-controller/compare/1.9.0...2.0.0)

= 1.9.0 =
* Add filter "nginxchampuru_db_cached_url"

= 1.8.0 =
* Add WP-CLI Support

= 1.7.0 =
* problem when redirect after clear cache fixed.
* Add filter hook to the HTTP responce header.

= 1.6.1 =
* Bug on SSL fixed

= 1.2.0 =
* fix large site issues.(timeout when too many urls)
* add like box to admin panel

= 1.0.0 =
* Rename to "Nginx Cache Controller"
* Cache Controll
* Auto-Flush Cache

= 0.1.0 =
* The first release.

== Credits ==

This plug-in is not guaranteed though the user of WordPress can freely use this plug-in free of charge regardless of the purpose.
The author must acknowledge the thing that the operation guarantee and the support in this plug-in use are not done at all beforehand.

== Contact ==

* https://github.com/miya0001/nginx-champuru

