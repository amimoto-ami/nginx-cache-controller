<div id="nginxchampuru-settings">
<div id="icon-options-general" class="icon32"><br /></div>
<h2>キャッシュの設定 <a href="<?php echo $this->get_cacheclear_url(); ?>" class="add-new-h2">キャッシュを削除</a></h2>

<form action="admin.php?page=ninjax-cachecontrol" method="post">
<input type="hidden" name="nonce" value="<?php echo wp_create_nonce("ninjax-cachecontrol"); ?>" />

<?php

$expires = get_option("nginxchampuru-cache_expires");
if (!is_array($expires)) {
    $expires = array();
}

?>

<dl>
<?php foreach ($this->default_cache_params as $par => $title): ?>
    <?php
        if (isset($expires[$par]) && strlen($expires[$par])) {
            $expires[$par] = intval($expires[$par]);
        } else {
            $expires[$par] = $nginxchampuru->get_default_expire();
        }
    ?>
    <dt><?php echo $title; ?></dt>
    <dd><input type="text" class="int" name="expires[<?php echo $par; ?>]" value="<?php echo $expires[$par]; ?>" /> sec</dd>
<?php endforeach; ?>
</dl>

<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="変更を保存"  /></p>
</form>

</div><!-- #ninjax-expirescontrol -->
