=== Nginx Cache Controller ===
Contributors: miyauchi, wokamoto
Donate link: http://ninjax.cc/
Tags: nginx, reverse proxy, cache 
Requires at least: 3.3
Tested up to: 3.3.2
Stable tag: 1.1.2

Nginxのリバースプロキシを使用する際に必要な機能を提供します。

== Description ==

Nginxリバースプロキシを使用する際に必要な以下の機能を提供するプラグインです。

= セキュリティ

* コメントフォームの投稿者情報をAjax化し、投稿者情報が静的にキャッシュされることを防止します。
* パスワード保護されたページで、no-cacheヘッダを送出し、誤って静的にキャッシュされることを防止します。
* 予約投稿時に、Ajax経由でキャッシュを自動的にクリアします。

= キャッシュコントロール

* X-ACCEL-EXPIRESヘッダを出力して、ホームページ、アーカイブ、単独ページごとにキャッシュの有効期間を設定できます。
* 記事の保存時や、コメントの投稿時に、設定した範囲でキャッシュを自動的にクリアします。
* 管理バーにキャッシュを削除するためのメニューを追加します。

= その他

* コメント投稿者のIPアドレスをHTTP_X_FORWARDED_FORヘッダから取得します。
* 管理画面のパーマリンク設定で、タグにindex.phpが含まれてしまう不具合を修正します。
* キャッシュの有効期間が86400秒以上の場合、wp_verify_nonce()の有効期間をキャッシュの有効期間と同じになるように変更します。

= Contributor =

* [Ninjax Team](http://ninjax.cc/) 
* [Takayuki Miyauchi](http://firegoby.theta.ne.jp/)

== Installation ==

* A plug-in installation screen is displayed on the WordPress admin panel.
* It installs it in `wp-content/plugins`.
* The plug-in is made effective.

Nginxの設定例は以下のとおりです。

(例) X-ACCEL-EXPIRESをfastcgiで許可する。

`   location ~ \.php$ {
        include        /etc/nginx/fastcgi_params;
        fastcgi_pass   unix:/tmp/php-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $vhost_root/$fastcgi_script_name;
        fastcgi_pass_header "X-Accel-Redirect";
        fastcgi_pass_header "X-Accel-Expires";
    }`

(例) リバースプロキシのキャッシュ用ディレクトリの設定

`proxy_cache_path  /var/cache/nginx levels=1:2 keys_zone=czone:4m max_size=50m inactive=120m;`

* デフォルトのパスの設定値は、/var/cache/nginxです。
* デフォルトのlevelsの設定値は、1:2です。
* キャッシュのパスの設定は、管理画面で変更可能です。

(例) リバースプロキシのキャッシュ用キーの設定
`proxy_cache_key "$scheme://$host$request_uri"`

* proxy_cache_keyは、`nginxchampuru_get_reverse_proxy_key`フックでカスタマイズできます。


== Changelog ==

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
