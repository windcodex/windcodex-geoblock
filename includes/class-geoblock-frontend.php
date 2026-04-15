<?php
/**
 * Frontend — applies restriction rules to the WooCommerce catalog,
 * single product pages, and cart/checkout.
 *
 * Restriction behaviour per mode:
 *
 *  hide           → Hidden from loops AND single page returns 404/redirects.
 *  catalog_only   → Hidden from loops/search, but direct product URL still loads.
 *  message        → Visible in loops, single page replaces price + ATC with message.
 *
 * Hooks used:
 *  - woocommerce_product_is_visible        → hide from shop/category/search loops
 *  - woocommerce_is_purchasable            → block Add to Cart (all non-hide modes)
 *  - woocommerce_get_price_html            → replace price in 'message' mode
 *  - woocommerce_single_product_summary    → show restriction notice on product page
 *  - template_redirect                     → block / redirect single product page
 *  - woocommerce_add_to_cart_validation    → block direct cart add (API/AJAX safety)
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Frontend {

	/** @var GeoBlock_Restrictions */
	private $restrictions;

	/**
	 * @param GeoBlock_Restrictions $restrictions
	 */
	public function __construct( GeoBlock_Restrictions $restrictions ) {
		$this->restrictions = $restrictions;
	}

	// ─── 1. Catalog visibility (loops / search / widgets) ────────────────────

	/**
	 * Hide restricted products from shop loops, category pages, and search.
	 * Only applies in 'hide' mode — in other modes the product stays visible
	 * in the catalog but is non-purchasable on the product page.
	 *
	 * @param  bool $visible
	 * @param  int  $product_id
	 * @return bool
	 */
	public function filter_product_visibility( bool $visible, int $product_id ): bool {
		if ( ! $visible ) {
			return false; // Already hidden — don't interfere.
		}
		$mode = $this->restrictions->get_restriction_mode();
		// Both 'hide' and 'catalog_only' remove the product from loops/search.
		// 'catalog_only' still allows the direct product URL to load (handled below).
		if ( ! in_array( $mode, array( 'hide', 'catalog_only' ), true ) ) {
			return $visible;
		}
		if ( $this->restrictions->is_restricted( $product_id ) ) {
			return false;
		}
		return $visible;
	}

	// ─── 2. Single product page block ────────────────────────────────────────

	/**
	 * Block or redirect restricted product pages BEFORE any output is sent.
	 *
	 * Runs on template_redirect (priority 10 — before WC outputs anything).
	 *
	 * Behaviour depends on restriction mode + whether a redirect URL is set:
	 *
	 *  hide mode + redirect URL  → wp_safe_redirect() to that URL.
	 *  hide mode + no redirect   → Return 404 (page not found).
	 *  nonpurchasable/message    → Allow page to load but block purchasing
	 *                              (handled via other hooks below).
	 *                              If a redirect URL is also set, redirect anyway.
	 */
	public function block_restricted_product_page(): void {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_the_ID();
		if ( ! $product_id || ! $this->restrictions->is_restricted( $product_id ) ) {
			return;
		}

		$mode         = $this->restrictions->get_restriction_mode();
		$redirect_url = $this->restrictions->get_redirect_url();

		// ── Always redirect if a redirect URL is configured ──
		if ( $redirect_url ) {
			wp_safe_redirect( esc_url_raw( $redirect_url ), 302 );
			exit;
		}

		// ── 'hide' mode with no redirect → 404 ──
		if ( 'hide' === $mode ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			// Load the theme's 404 template so it looks natural.
			$template_404 = get_query_template( '404' );
			if ( $template_404 ) {
				include $template_404;
			} else {
				wp_die(
					esc_html__( 'This product is not available in your country.', 'windcodex-geoblock' ),
					esc_html__( 'Product not available', 'windcodex-geoblock' ),
					array( 'response' => 404 )
				);
			}
			exit;
		}

		// ── 'catalog_only' mode — allow the direct URL to load, but block purchase ──
		// 'nonpurchasable', 'message', and 'catalog_only' all allow page to load;
		// the hooks below handle removing Add to Cart and showing a restriction notice.
	}

	// ─── 3. Purchasability (Add to Cart button) ───────────────────────────────

	/**
	 * Disable Add to Cart for restricted products across all non-hide modes.
	 *
	 * @param  bool        $purchasable
	 * @param  WC_Product  $product
	 * @return bool
	 */
	public function filter_purchasable( bool $purchasable, WC_Product $product ): bool {
		if ( ! $purchasable ) {
			return false;
		}
		$mode = $this->restrictions->get_restriction_mode();

		if ( ! in_array( $mode, array( 'catalog_only', 'message' ), true ) ) {
			return $purchasable;
		}

		if ( ! $this->restrictions->is_restricted( $product ) ) {
			return $purchasable; // Not restricted — leave untouched.
		}

		// ── catalog_only mode ──────────────────────────────────────────────
		if ( 'catalog_only' === $mode ) {
			$settings            = (array) get_option( 'geoblock_settings', array() );
			$catalog_purchasable = $settings['catalog_purchasable'] ?? 'no';
			if ( 'yes' === $catalog_purchasable ) {
				return $purchasable; // Toggle ON — allow purchase via direct URL.
			}
		}

		// ── message mode: block all product types including variable/variation.
		// The variation form + ATC button are hidden via JS injected in wp_footer.
		// Server-side validation catches any bypass attempts.
		if ( 'message' === $mode ) {
			return false;
		}

		// ── catalog_only mode (purchase blocked): keep variable/variation,
		// bundle, and variable-subscription products "purchasable" so their
		// selection forms still render. The actual cart add is blocked by
		// woocommerce_add_to_cart_validation (and the compatibility class
		// handles bundle/subscription-specific purchasability filters).
		if ( $product->is_type( array( 'variable', 'variation', 'bundle', 'variable-subscription' ) ) ) {
			return $purchasable;
		}

		// Simple products in catalog_only: block purchasability (removes ATC button).
		return false;
	}

	// ─── 4. Add-to-cart server-side validation (API / AJAX safety net) ────────

	/**
	 * Prevent restricted products from being added to the cart even via
	 * direct POST, REST API, or AJAX (e.g. third-party quick-add buttons).
	 *
	 * @param  bool $passed
	 * @param  int  $product_id
	 * @return bool
	 */
	public function validate_add_to_cart( bool $passed, int $product_id ): bool {
		if ( ! $passed ) {
			return false;
		}

		$mode = $this->restrictions->get_restriction_mode();

		if ( ! in_array( $mode, array( 'hide', 'catalog_only', 'message' ), true ) ) {
			return $passed;
		}

		if ( ! $this->restrictions->is_restricted( $product_id ) ) {
			return $passed;
		}

		// catalog_only mode: if "Allow purchase via direct URL" is ON, let it through.
		if ( 'catalog_only' === $mode ) {
			$settings            = (array) get_option( 'geoblock_settings', array() );
			$catalog_purchasable = $settings['catalog_purchasable'] ?? 'no';
			if ( 'yes' === $catalog_purchasable ) {
				return $passed;
			}
		}

		wc_add_notice(
			esc_html__( 'Sorry, this product is not available in your country.', 'windcodex-geoblock' ),
			'error'
		);
		return false;
	}

	// ─── 5. Price HTML ────────────────────────────────────────────────────────

	/**
	 * In 'message' mode: remove the price entirely on restricted products.
	 * The message itself is shown once by maybe_show_restriction_notice().
	 * We return empty string here so the price area is blank — no duplication.
	 *
	 * @param  string      $price_html
	 * @param  WC_Product  $product
	 * @return string
	 */
	public function filter_price_html( string $price_html, WC_Product $product ): string {
		if ( 'message' !== $this->restrictions->get_restriction_mode() ) {
			return $price_html;
		}
		// Only hide the price on the single product page — the price should
		// remain visible on shop/category loop grid cards so customers can
		// browse normally. The ATC is hidden on the product page separately.
		if ( ! is_product() ) {
			return $price_html;
		}
		if ( $this->restrictions->is_restricted( $product ) ) {
			return ''; // Hide price — message is displayed by the notice hook below.
		}
		return $price_html;
	}

	// ─── 6. Cart validation — remove restricted items already in cart ──────────

	/**
	 * Fires on woocommerce_check_cart_items (cart page + checkout page load).
	 * Scans every cart item and removes any that are now restricted.
	 * Shows a notice for each removed item so the customer knows why.
	 *
	 * This handles the case where:
	 * - Customer added product BEFORE a restriction was applied.
	 * - Store owner changed restriction settings after customer started session.
	 * - Customer switched country mid-session (e.g. VPN).
	 */
	public function check_cart_items_for_restrictions(): void {
		// Only run in modes that restrict purchasing.
		$mode = $this->restrictions->get_restriction_mode();
		if ( ! in_array( $mode, array( 'hide', 'catalog_only', 'message' ), true ) ) {
			return;
		}

		// catalog_only with "Allow purchase" ON — cart is allowed.
		if ( 'catalog_only' === $mode ) {
			$settings            = (array) get_option( 'geoblock_settings', array() );
			$catalog_purchasable = $settings['catalog_purchasable'] ?? 'no';
			if ( 'yes' === $catalog_purchasable ) {
				return;
			}
		}

		$wc   = function_exists( 'WC' ) ? WC() : null;
		$cart = $wc ? $wc->cart : null;
		if ( ! $cart || $cart->is_empty() ) {
			return;
		}

		$items_to_remove = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			// Always check the parent product ID for rules (not the variation ID).
			$check_id = $cart_item['product_id'];

			if ( $this->restrictions->is_restricted( $check_id ) ) {
				$items_to_remove[] = array(
					'key'  => $cart_item_key,
					'name' => $cart_item['data']->get_name(),
				);
			}
		}

		foreach ( $items_to_remove as $item ) {
			$cart->remove_cart_item( $item['key'] );
			wc_add_notice(
				sprintf(
					/* translators: %s: product name */
					__( '"%s" has been removed from your cart as it is not available in your country.', 'windcodex-geoblock' ),
					$item['name']
				),
				'notice'
			);
		}
	}

	/**
	 * Fires on woocommerce_checkout_process — last-line-of-defence before order
	 * is created. Blocks the order if any cart item is restricted.
	 * This catches cases where check_cart_items_for_restrictions was bypassed
	 * (e.g. express checkout, REST API order creation).
	 */
	public function block_restricted_items_at_checkout(): void {
		$mode = $this->restrictions->get_restriction_mode();
		if ( ! in_array( $mode, array( 'hide', 'catalog_only', 'message' ), true ) ) {
			return;
		}

		if ( 'catalog_only' === $mode ) {
			$settings            = (array) get_option( 'geoblock_settings', array() );
			$catalog_purchasable = $settings['catalog_purchasable'] ?? 'no';
			if ( 'yes' === $catalog_purchasable ) {
				return;
			}
		}

		$wc   = function_exists( 'WC' ) ? WC() : null;
		$cart = $wc ? $wc->cart : null;
		if ( ! $cart || $cart->is_empty() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			$check_id = $cart_item['product_id'];
			if ( $this->restrictions->is_restricted( $check_id ) ) {
				wc_add_notice(
					esc_html__( 'Your cart contains a product that is not available in your country. Please remove it before placing your order.', 'windcodex-geoblock' ),
					'error'
				);
				return; // One notice is enough — order is blocked.
			}
		}
	}

	// ─── 7. Single product restriction notice ────────────────────────────────

	/**
	 * Show ONE restriction notice on the single product page.
	 *
	 * - 'nonpurchasable' mode → shows the notice (price remains visible).
	 * - 'message' mode        → shows the notice (price is hidden by filter_price_html).
	 * - 'hide' mode           → never reaches here (page is already 404'd above).
	 *
	 * Hooked at priority 25 (after title/price, before ATC button).
	 * This is the ONLY place the message is rendered — no duplication.
	 */
	public function maybe_show_restriction_notice(): void {
		global $product;

		if ( ! is_product() ) {
			return;
		}
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$mode = $this->restrictions->get_restriction_mode();
		if ( 'hide' === $mode ) {
			return; // Page is already 404'd / redirected in block_restricted_product_page().
		}

		if ( ! $this->restrictions->is_restricted( $product ) ) {
			return;
		}

		if ( 'catalog_only' === $mode ) {
			// If "Allow purchase via direct URL" is ON — product is purchasable,
			// so show no restriction notice at all.
			$settings            = (array) get_option( 'geoblock_settings', array() );
			$catalog_purchasable = $settings['catalog_purchasable'] ?? 'no';
			if ( 'yes' === $catalog_purchasable ) {
				return;
			}
			// Purchase is blocked — show the generic notice.
			echo '<div class="woocommerce-info geoblock-restriction-notice" role="alert">'
				. esc_html__( 'This product is not available for purchase in your country.', 'windcodex-geoblock' )
				. '</div>';
			return;
		}

		// 'message' mode: show the store owner custom message.
		echo '<div class="woocommerce-info geoblock-restriction-notice" role="alert">'
			. wp_kses_post( $this->restrictions->get_restriction_message() )
			. '</div>';
	}
	// ─── 8. Inline CSS — hide variation form + ATC in message mode ──────────

	/**
	 * On a restricted variable product page in 'message' mode, inject a small
	 * inline CSS rule to hide the variation form and Add to Cart button.
	 *
	 * We cannot rely solely on woocommerce_is_purchasable returning false for
	 * variable products — WooCommerce removes the entire dropdown form when the
	 * parent returns false. Instead we return false (which WC handles gracefully
	 * for simple products) AND inject CSS for variable products so both the
	 * form and the button are visually hidden. Server-side validation blocks
	 * any bypass attempt.
	 */
	public function maybe_inject_restriction_css(): void {
		// Use global $product — this fires in wp_footer, WC has set it up by then.
		global $product;

		if ( ! is_product() ) {
			return;
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		// Only variable products need CSS — simple products are handled by
		// filter_purchasable returning false (WC removes the ATC button natively).
		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$mode = $this->restrictions->get_restriction_mode();

		if ( ! in_array( $mode, array( 'catalog_only', 'message' ), true ) ) {
			return;
		}

		// catalog_only + Allow purchase ON → no restriction, nothing to hide.
		if ( 'catalog_only' === $mode ) {
			$settings            = (array) get_option( 'geoblock_settings', array() );
			$catalog_purchasable = $settings['catalog_purchasable'] ?? 'no';
			if ( 'yes' === $catalog_purchasable ) {
				return;
			}
		}

		if ( ! $this->restrictions->is_restricted( $product->get_id() ) ) {
			return;
		}

		// Pass a flag to the registered frontend script instead of printing raw
		// <script> tags directly into the page.
		wp_enqueue_script( 'geoblock-frontend' );
		wp_localize_script(
			'geoblock-frontend',
			'geoblock_data',
			array(
				'hide_variation_form' => true,
			)
		);
	}
}
