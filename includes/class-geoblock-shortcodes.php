<?php
/**
 * Shortcodes — registers [geoblock_product_message] for use in page builders,
 * custom product templates, and theme files.
 *
 * Usage:
 *   [geoblock_product_message]
 *   [geoblock_product_message id="123"]
 *   [geoblock_product_message class="my-custom-class"]
 *
 * Attributes:
 *   id    (int)    — Product ID to check. Defaults to current product in the loop.
 *   class (string) — Extra CSS class(es) to add to the wrapper div.
 *
 * Returns:
 *   The restriction message HTML if the current visitor is restricted from
 *   the product, otherwise an empty string (nothing rendered).
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Shortcodes {

	/** @var GeoBlock_Restrictions */
	private $restrictions;

	/**
	 * @param GeoBlock_Restrictions $restrictions
	 */
	public function __construct( GeoBlock_Restrictions $restrictions ) {
		$this->restrictions = $restrictions;
	}

	/**
	 * Register all shortcodes.
	 */
	public function register(): void {
		add_shortcode( 'geoblock_product_message', array( $this, 'render_product_message' ) );
	}

	// ─── Shortcode handlers ───────────────────────────────────────────────────

	/**
	 * [geoblock_product_message] — renders the restriction message when the
	 * current visitor is restricted from the product.
	 *
	 * @param  array  $atts  Shortcode attributes.
	 * @return string        HTML output or empty string.
	 */
	public function render_product_message( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'class' => '',
			),
			$atts,
			'geoblock_product_message'
		);

		// ── Resolve product ID ─────────────────────────────────────────────
		$product_id = (int) $atts['id'];

		if ( ! $product_id ) {
			// Try global $product first (single product template context).
			global $product;
			if ( $product instanceof WC_Product ) {
				$product_id = $product->get_id();
			}
		}

		if ( ! $product_id ) {
			// Last resort: get_the_ID() in the loop.
			$product_id = (int) get_the_ID();
		}

		if ( ! $product_id ) {
			return ''; // Cannot determine product — render nothing.
		}

		// ── Check restriction ──────────────────────────────────────────────
		if ( ! $this->restrictions->is_restricted( $product_id ) ) {
			return ''; // Not restricted — render nothing.
		}

		// ── Build message ──────────────────────────────────────────────────
		$mode = $this->restrictions->get_restriction_mode();

		if ( 'hide' === $mode ) {
			// In hide mode the product page itself is 404'd, so the shortcode
			// would never be reached. Return empty just in case.
			return '';
		}

		if ( 'catalog_only' === $mode ) {
			$message = esc_html__( 'This product is not available for purchase in your country.', 'windcodex-geoblock' );
		} else {
			// 'message' mode — use custom store message.
			$message = $this->restrictions->get_restriction_message();
		}

		// ── Build wrapper class ────────────────────────────────────────────
		// sanitize_html_class() only handles a single class name (strips spaces).
		// Split on whitespace, sanitize each class individually, then rejoin.
		$extra_class = '';
		if ( ! empty( $atts['class'] ) ) {
			$classes     = preg_split( '/\s+/', trim( $atts['class'] ) );
			$classes     = array_filter( array_map( 'sanitize_html_class', $classes ) );
			$extra_class = $classes ? ' ' . esc_attr( implode( ' ', $classes ) ) : '';
		}

		return '<div class="woocommerce-info geoblock-restriction-notice geoblock-shortcode-notice' . $extra_class . '" role="alert">'
			. wp_kses_post( $message )
			. '</div>';
	}
}
