<?php
/**
 * @wordpress-plugin
 * Plugin Name:       WindCodex GeoBlock
 * Tagline:           Country Restrictions for WooCommerce
 * Description:       Restrict WooCommerce products by country using geolocation. Hide products, block purchases, or show a custom message per product. No API key required.
 * Version:           1.0.0
 * Author:            WindCodex
 * Author URI:        https://www.windcodex.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       windcodex-geoblock
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 7.0
 * WC tested up to:   10.6
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────

define( 'GEOBLOCK_VERSION',     '1.0.0' );
define( 'GEOBLOCK_PLUGIN_FILE', __FILE__ );
define( 'GEOBLOCK_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'GEOBLOCK_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'GEOBLOCK_PLUGIN_BASE', plugin_basename( __FILE__ ) );

function geoblock_has_woocommerce(): bool {
	if ( class_exists( 'WooCommerce', false ) ) {
		return true;
	}

	$active_plugins = (array) get_option( 'active_plugins', array() );
	if ( in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
		if ( isset( $network_plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		}
	}

	return false;
}

// ─── WooCommerce dependency check ─────────────────────────────────────────────

function geoblock_check_woocommerce() {
	if ( ! geoblock_has_woocommerce() ) {
		add_action( 'admin_notices', 'geoblock_missing_wc_notice' );
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( GEOBLOCK_PLUGIN_BASE );
		}
	}
}
add_action( 'plugins_loaded', 'geoblock_check_woocommerce' );

function geoblock_missing_wc_notice() {
	echo '<div class="notice notice-error"><p>';
	echo wp_kses_post(
		sprintf(
			/* translators: %s: WooCommerce plugin URL */
			__( '<strong>GeoBlock Country Restrictions</strong> requires <a href="%s">WooCommerce</a> to be installed and active.', 'windcodex-geoblock' ),
			'https://wordpress.org/plugins/woocommerce/'
		)
	);
	echo '</p></div>';
}

// ─── Declare WooCommerce HPOS compatibility ────────────────────────────────────

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

// ─── Boot the plugin ──────────────────────────────────────────────────────────

function geoblock_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// Load all classes.
	require_once GEOBLOCK_PLUGIN_DIR . 'includes/class-geoblock-loader.php';
	require_once GEOBLOCK_PLUGIN_DIR . 'includes/class-geoblock-geolocation.php';
	require_once GEOBLOCK_PLUGIN_DIR . 'includes/class-geoblock-restrictions.php';
	require_once GEOBLOCK_PLUGIN_DIR . 'includes/class-geoblock-product.php';
	require_once GEOBLOCK_PLUGIN_DIR . 'includes/class-geoblock-frontend.php';
	require_once GEOBLOCK_PLUGIN_DIR . 'includes/class-geoblock-shortcodes.php';
	require_once GEOBLOCK_PLUGIN_DIR . 'includes/class-geoblock-compatibility.php';
	require_once GEOBLOCK_PLUGIN_DIR . 'admin/class-geoblock-admin.php';
	require_once GEOBLOCK_PLUGIN_DIR . 'public/class-geoblock-public.php';

	// Boot loader (registers all hooks).
	GeoBlock_Loader::instance();


}
add_action( 'plugins_loaded', 'geoblock_init', 20 );

// ─── Activation / Deactivation ────────────────────────────────────────────────

register_activation_hook( __FILE__, 'geoblock_activate' );
function geoblock_activate() {
	if ( ! geoblock_has_woocommerce() ) {
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( GEOBLOCK_PLUGIN_BASE );
		}
		wp_die(
			esc_html__( 'WindCodex GeoBlock requires WooCommerce to be installed and active before activation.', 'windcodex-geoblock' ),
			esc_html__( 'Plugin activation failed', 'windcodex-geoblock' ),
			array( 'response' => 200, 'back_link' => true )
		);
	}

	// Store plugin version for future upgrade routines.
	update_option( 'geoblock_version', GEOBLOCK_VERSION );

	// Set default options on first activation.
	if ( false === get_option( 'geoblock_settings' ) ) {
		update_option( 'geoblock_settings', array(
			'restriction_mode'          => 'hide',
			'message_position'          => 'after_title',
			'force_geolocation'         => 'no',
			'redirect_enabled'          => 'no',
			'redirect_url'              => '',
			'custom_message'            => __( 'Sorry, this product is not available in your country.', 'windcodex-geoblock' ),
			'catalog_purchasable'       => 'no',
			'debug_mode'                => 'no',
		) );
	}
}

register_deactivation_hook( __FILE__, 'geoblock_deactivate' );
function geoblock_deactivate() {
	// Flush rewrite rules, clear transients.
	delete_transient( 'geoblock_ip_cache' );
}
