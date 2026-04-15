<?php
/**
 * Restrictions Engine — evaluates whether a product is restricted
 * for the current visitor's country.
 *
 * Rule format stored in post meta (_geoblock_rules):
 * array(
 *   'mode'      => 'include' | 'exclude',
 *   'countries' => array( 'US', 'IN', 'DE' ),
 * )
 *
 * - include: product is ONLY available to listed countries.
 * - exclude: product is UNAVAILABLE to listed countries.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Restrictions {

	/** @var GeoBlock_Geolocation */
	private $geo;

	/** @var array|null Cached global settings */
	private $settings = null;

	/**
	 * @param GeoBlock_Geolocation $geo
	 */
	public function __construct( GeoBlock_Geolocation $geo ) {
		$this->geo = $geo;
	}

	// ─── Public API ───────────────────────────────────────────────────────────

	/**
	 * Returns true if the given product is restricted for the current visitor.
	 *
	 * @param  int|WC_Product $product  Product ID or object.
	 * @return bool
	 */
	public function is_restricted( $product ): bool {
		$product_id = $this->resolve_product_id( $product );
		if ( ! $product_id ) {
			return false;
		}

		$country = $this->geo->get_country();
		$rules   = $this->get_product_rules( $product_id );

		if ( empty( $rules ) || empty( $rules['countries'] ) ) {
			return false;
		}

		return $this->evaluate( $rules, $country );
	}

	/**
	 * Returns the restriction mode for the current store config.
	 *
	 * @return string  'hide' | 'nonpurchasable' | 'message'
	 */
	public function get_restriction_mode(): string {
		$settings = $this->get_settings();
		$mode = $settings['restriction_mode'] ?? 'hide';
		return in_array( $mode, array( 'hide', 'catalog_only', 'message' ), true ) ? $mode : 'hide';
	}

	/**
	 * Returns the custom restriction message.
	 *
	 * @return string
	 */
	public function get_restriction_message(): string {
		$settings = $this->get_settings();
		return ! empty( $settings['custom_message'] )
			? wp_kses_post( $settings['custom_message'] )
			: esc_html__( 'Sorry, this product is not available in your country.', 'windcodex-geoblock' );
	}

	/**
	 * Returns the redirect URL (if any) for restricted products.
	 *
	 * @return string
	 */
	public function get_redirect_url(): string {
		$settings = $this->get_settings();
		return ! empty( $settings['redirect_url'] )
			? esc_url_raw( $settings['redirect_url'] )
			: '';
	}

	/**
	 * Returns the restriction rules array for a specific product.
	 *
	 * @param  int $product_id
	 * @return array
	 */
	public function get_product_rules( int $product_id ): array {
		// Allow WPML (and other translation plugins) to resolve the translated
		// product ID back to the original so rules are shared across languages.
		$product_id = (int) apply_filters( 'geoblock_resolve_product_id', $product_id );

		$raw = get_post_meta( $product_id, '_geoblock_rules', true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		// Sanitize on read.
		return array(
			'mode'      => in_array( $raw['mode'] ?? '', array( 'include', 'exclude' ), true )
				? $raw['mode']
				: 'exclude',
			'countries' => array_map( 'strtoupper', array_filter(
				(array) ( $raw['countries'] ?? array() ),
				fn( $c ) => preg_match( '/^[A-Z]{2}$/', strtoupper( $c ) )
			) ),
		);
	}

	/**
	 * Saves restriction rules for a product.
	 *
	 * @param int   $product_id
	 * @param array $rules  array( 'mode' => ..., 'countries' => [...] )
	 */
	public function save_product_rules( int $product_id, array $rules ): void {
		$mode      = in_array( $rules['mode'] ?? '', array( 'include', 'exclude' ), true )
			? $rules['mode']
			: 'exclude';
		$countries = array_values( array_unique( array_map(
			'strtoupper',
			array_filter( (array) ( $rules['countries'] ?? array() ), fn( $c ) => preg_match( '/^[A-Za-z]{2}$/', $c ) )
		) ) );

		if ( empty( $countries ) ) {
			delete_post_meta( $product_id, '_geoblock_rules' );
		} else {
			update_post_meta( $product_id, '_geoblock_rules', array(
				'mode'      => $mode,
				'countries' => $countries,
			) );
		}
	}

	// ─── Private helpers ──────────────────────────────────────────────────────

	/**
	 * Core rule evaluation logic.
	 *
	 * @param  array  $rules
	 * @param  string $country
	 * @return bool
	 */
	private function evaluate( array $rules, string $country ): bool {
		$in_list = in_array( strtoupper( $country ), $rules['countries'], true );

		if ( 'include' === $rules['mode'] ) {
			// Include mode: restricted if NOT in the list.
			return ! $in_list;
		}

		// Exclude mode: restricted if IN the list.
		return $in_list;
	}

	/**
	 * @param  int|WC_Product $product
	 * @return int|null
	 */
	private function resolve_product_id( $product ): ?int {
		if ( is_numeric( $product ) ) {
			$id = (int) $product;
			// If this is a variation, resolve to the parent product ID
			// so rules set on the parent are found correctly.
			if ( $id && 'product_variation' === get_post_type( $id ) ) {
				$parent = wp_get_post_parent_id( $id );
				return $parent ?: $id;
			}
			return $id;
		}
		if ( $product instanceof WC_Product ) {
			// For variations, check the parent product's rules.
			return $product->get_parent_id() ?: $product->get_id();
		}
		return null;
	}

	/** @return array */
	private function get_settings(): array {
		if ( null === $this->settings ) {
			$this->settings = (array) get_option( 'geoblock_settings', array() );
		}
		return $this->settings;
	}
}
