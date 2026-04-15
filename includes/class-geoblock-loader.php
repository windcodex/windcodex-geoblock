<?php
/**
 * Hook Loader — registers all actions and filters for GeoBlock.
 *
 * Centralising hook registration here makes the plugin easier to test,
 * debug with Query Monitor, and extend via child-plugins or PRO add-ons.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Loader {

	/** @var GeoBlock_Loader|null Singleton instance */
	private static $instance = null;

	/** @var array Collected actions */
	private $actions = array();

	/** @var array Collected filters */
	private $filters = array();

	/**
	 * Singleton accessor.
	 *
	 * @return GeoBlock_Loader
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->define_hooks();
			self::$instance->run();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {}

	// ─── Hook registration ────────────────────────────────────────────────────

	private function define_hooks() {

		$geo           = new GeoBlock_Geolocation();
		$restrictions  = new GeoBlock_Restrictions( $geo );
		$product       = new GeoBlock_Product();
		$frontend      = new GeoBlock_Frontend( $restrictions );
		$admin         = new GeoBlock_Admin( $product );
		$public        = new GeoBlock_Public( $restrictions );
		$shortcodes    = new GeoBlock_Shortcodes( $restrictions );
		$compatibility = new GeoBlock_Compatibility( $restrictions );
		$compatibility->init();

		// --- Admin hooks ---
		$this->add_action( 'admin_menu',                      $admin, 'register_menu' );
		$this->add_action( 'admin_init',                      $admin, 'register_settings' );
		$this->add_action( 'admin_enqueue_scripts',           $admin, 'enqueue_assets' );
		$this->add_action( 'add_meta_boxes',                  $admin, 'add_product_meta_box' );
		$this->add_action( 'save_post_product',               $admin, 'save_product_meta', 10, 2 );
		$this->add_action( 'wp_ajax_geoblock_save_settings',  $admin, 'ajax_save_settings' );
		$this->add_action( 'wp_ajax_geoblock_reset_settings', $admin, 'ajax_reset_settings' );
		$this->add_filter( 'plugin_action_links_' . GEOBLOCK_PLUGIN_BASE, $admin, 'plugin_action_links' );
		// Products list column — covers classic WP list table AND WooCommerce HPOS list.
		$this->add_filter( 'manage_product_posts_columns',              $admin, 'add_restriction_column' );
		$this->add_action( 'manage_product_posts_custom_column',        $admin, 'render_restriction_column', 10, 2 );
		// HPOS / WooCommerce custom product table (wc_get_products list screen).
		$this->add_filter( 'manage_edit-product_columns',               $admin, 'add_restriction_column' );
		$this->add_action( 'manage_edit-product_custom_column',         $admin, 'render_restriction_column', 10, 2 );

		// --- Frontend / catalog hooks ---
		$this->add_filter( 'woocommerce_product_is_visible',      $frontend, 'filter_product_visibility', 10, 2 );
		$this->add_filter( 'woocommerce_is_purchasable',          $frontend, 'filter_purchasable', 10, 2 );
		$this->add_filter( 'woocommerce_add_to_cart_validation',  $frontend, 'validate_add_to_cart', 10, 2 );
		$this->add_action( 'woocommerce_check_cart_items',        $frontend, 'check_cart_items_for_restrictions' );
		$this->add_action( 'woocommerce_checkout_process',        $frontend, 'block_restricted_items_at_checkout' );
		$this->add_filter( 'woocommerce_get_price_html',       $frontend, 'filter_price_html',         10, 2 );
		// Hook restriction notice at the position chosen in settings.
		$gg_settings     = get_option( 'geoblock_settings', array() );
		$gg_msg_position = $gg_settings['message_position'] ?? 'after_title';
		// WC woocommerce_single_product_summary priorities:
		// 5=title, 10=rating, 20=price, 25=excerpt, 30=add-to-cart, 40=meta, 50=share
		$gg_position_map = array(
			'before_title' => 4,   // Before title (WC: 5).
			'after_title'  => 11,  // After title+rating (WC: 5+10), before price (WC: 20).
			'after_price'  => 21,  // After price (WC: 20), before excerpt (WC: 25).
			'after_cart'   => 35,  // After add-to-cart (WC: 30), before meta (WC: 40).
		);
		$gg_priority = $gg_position_map[ $gg_msg_position ] ?? 6;
		$this->add_action( 'woocommerce_single_product_summary', $frontend, 'maybe_show_restriction_notice', $gg_priority );
		$this->add_action( 'template_redirect',                   $frontend, 'block_restricted_product_page', 10 );

		// --- Public assets ---
		$this->add_action( 'wp_enqueue_scripts', $public, 'enqueue_assets' );
		$this->add_action( 'wp_footer',          $frontend, 'maybe_inject_restriction_css' );

		// --- Shortcodes ---
		$this->add_action( 'init', $shortcodes, 'register' );

		// --- Debug toolbar ---
		$this->add_action( 'wp_footer', $geo, 'maybe_render_debug_toolbar' );
	}

	// ─── Helpers ──────────────────────────────────────────────────────────────

	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/**
	 * Register all collected hooks with WordPress.
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
