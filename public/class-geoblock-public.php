<?php
/**
 * Public — enqueues frontend styles for restriction notices
 * and any future public-facing JavaScript.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Public {

	/** @var GeoBlock_Restrictions */
	private $restrictions;

	public function __construct( GeoBlock_Restrictions $restrictions ) {
		$this->restrictions = $restrictions;
	}

	public function enqueue_assets(): void {
		// Only load on WooCommerce pages.
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
			return;
		}
		wp_enqueue_style(
			'geoblock-public',
			GEOBLOCK_PLUGIN_URL . 'public/assets/public.css',
			array(),
			GEOBLOCK_VERSION
		);

		wp_register_script(
			'geoblock-frontend',
			GEOBLOCK_PLUGIN_URL . 'public/assets/frontend.js',
			array(),
			GEOBLOCK_VERSION,
			true
		);
	}
}
