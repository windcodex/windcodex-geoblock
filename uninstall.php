<?php
/**
 * Uninstall — runs when the plugin is deleted from the WordPress admin.
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

// ── Clear transient cache ─────────────────────────────────────────────────────
// Transients are prefixed with 'geoblock_country_' — delete them via SQL
// since there's no native bulk transient deletion API.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '_transient_geoblock_%'
	    OR option_name LIKE '_transient_timeout_geoblock_%'"
);
