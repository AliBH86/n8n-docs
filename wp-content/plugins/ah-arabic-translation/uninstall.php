<?php
// Runs when the plugin is deleted from WordPress admin.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ah_translations" );
delete_option( 'ah_arabic_version' );
delete_option( 'ah_arabic_settings' );
