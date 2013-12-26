<div id="nginxchampuru-settings">

<?php if (isset($_GET['message']) && $_GET['message'] === "true"): ?>
<div id="message" class="updated"><p><?php _e("Saved.", "nginxchampuru"); ?></p></div>
<?php endif; ?>

<div class="title">
<div id="icon-options-general" class="icon32"><br /></div>
<h2>Nginx Cache Controller <a href="<?php echo $this->get_cacheclear_url(); ?>" class="add-new-h2"><?php _e("Flush All Caches", "nginxchampuru"); ?></a></h2>
</div>

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">
<div id="post-body-content">

<h3><?php _e("Cache Expire", "nginxchampuru"); ?></h3>

<form action="admin.php?page=nginx-champuru" method="post">
<input type="hidden" name="nonce" value="<?php echo wp_create_nonce("nginxchampuru-optionsave"); ?>" />

<?php

$expires = get_option("nginxchampuru-cache_expires");
if (!is_array($expires)) {
    $expires = array();
}

?>

<table class="form-table">
<?php foreach ($this->default_cache_params as $par => $title): ?>
<tr>
    <?php
        if (isset($expires[$par]) && strlen($expires[$par])) {
            $expires[$par] = intval($expires[$par]);
        } else {
            $expires[$par] = intval($nginxchampuru->get_default_expire());
        }
    ?>
    <th><?php echo esc_html($title); ?></th>
    <td><input type="text" class="int" name="expires[<?php echo esc_attr($par); ?>]" value="<?php echo $expires[$par]; ?>" /> sec</td>
</tr>
<?php endforeach; ?>
</table>


<h3><?php _e("Settings for Flush Cache", "nginxchampuru"); ?></h3>

<table class="form-table">
<tr>
    <th><?php _e("Enable Flush Cache", "nginxchampuru"); ?></th>
    <td><?php $this->is_enable_flush(); ?></td>
</tr>
</table>

<div id="enable-flush">

<h4><?php _e("Nginx Reverse Proxy Settings", "nginxchampuru"); ?></h4>

<table class="form-table">
<tr>
    <th><?php _e("Cache Directory", "nginxchampuru"); ?></th>
    <td><?php
        printf(
            '<input type="text" name="cache_dir" value="%s" %s/>',
            esc_attr($nginxchampuru->get_cache_dir()) ,
            (defined('NCC_CACHE_DIR') && file_exists(NCC_CACHE_DIR) ? 'readonly="readonly" ' : '')
        );
    ?></td>
</tr>
<tr>
    <th><?php _e("Cache Levels", "nginxchampuru"); ?></th>
    <td><?php
        printf(
            '<input type="text" name="cache_levels" value="%s" />',
            esc_attr($nginxchampuru->get_cache_levels())
        );
    ?></td>
</tr>
</table>

<h4><?php _e("Auto-Flush Hooks", "nginxchampuru"); ?></h4>

<table class="form-table">
<tr>
    <th><?php _e("On Publish", "nginxchampuru"); ?></th>
    <td><?php $this->get_modes_select("publish"); ?></td>
</tr>
<tr>
    <th><?php _e("On Comment Posted", "nginxchampuru"); ?></th>
    <td><?php $this->get_modes_select("comment"); ?></td>
</tr>
</table>


<h4><?php _e("Add Last modified", "nginxchampuru"); ?></h4>

<table class="form-table">
<tr>
    <th><?php _e("Add Last modified", "nginxchampuru"); ?></th>
    <td><?php $this->add_last_modified(); ?></td>
</tr>
</table>

</div><!-- #enable-flush -->

<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e("Save Changes", "nginxchampuru"); ?>"  /></p>
</form>


</div><!-- post-body-content -->

<div id="postbox-container-1" class="postbox-container">
    <div class="postbox">
        <div class="title"><h3>Contributors</h3></div>
        <div class="inside">
            <ul>
                <li><a href="http://profiles.wordpress.org/miyauchi">Takayuki Miyauchi</a></li>
                <li><a href="http://profiles.wordpress.org/wokamoto">Wataru Okamoto</a></li>
                <li><a href="http://profiles.wordpress.org/gatespace">Kazue Igarashi</a></li>
            </ul>
        </div>
    </div>
    <div class="postbox">
        <div class="hndle"><h3>High Performance WordPress Cloud</h3></div>
        <div class="inside">
            <a href="http://megumi-cloud.com/"><img src="<?php echo NGINX_CACHE_CONTROLER_URL.'/img/amimoto.png'; ?>" alt="Amimoto"></a>
        </div>
    </div>
</div>

</div><!-- post-body -->
</div><!-- $poststuff -->

</div><!-- #ninjax-expirescontrol -->

<script type="text/javascript">
(function($){
    $("#radio-yes").click(function(){
        $("#enable-flush").fadeIn(50);
    });
    $("#radio-no").click(function(){
        $("#enable-flush").fadeOut(50);
    });
    if ($("#radio-yes").prop('checked')) {
        $("#enable-flush").show();
    }
})(jQuery);
</script>

