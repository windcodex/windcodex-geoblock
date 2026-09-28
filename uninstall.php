<?php
/**
 * Uninstall - runs when the plugin is deleted from the WordPress admin.
 * Removes all plugin options and post meta created by GeoBlock.
 *
 * This file is called by WordPress directly, NOT via include.
 *
 * @package GeoBlock_Country_Restrictions
 */

// Must be called from WordPress uninstall flow.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * GeoBlock Pro shares this plugin's settings option and product rule meta
 * (geoblock_settings, _geoblock_rules) so an upgrade carries data over with
 * no migration. If Pro is active, this Free plugin is just a deactivated
 * leftover on disk - deleting it must never remove the data Pro is using.
 */
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( is_plugin_active( 'windcodex-geoblock-pro/windcodex-geoblock-pro.php' ) ) {
	return;
}

// -- Clear transient cache -----------------------------------------------------
// Transients are prefixed with 'geoblock_country_' - delete them via SQL
// since there's no native bulk transient deletion API.
global $wpdb;

delete_option( 'geoblock_settings' );
delete_option( 'geoblock_version' );
delete_option( 'geoblock_activated_time' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '_transient_geoblock_%'
	    OR option_name LIKE '_transient_timeout_geoblock_%'"
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
		'_geoblock_rules'
	)
);
