<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit ();

global $wpdb;
$sql = "drop table if exists ".$wpdb->prefix.'nginxchampuru';
$wpdb->query($sql);

// EOF
