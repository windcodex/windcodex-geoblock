<?php
/**
 * Compatibility — ensures GeoBlock works correctly alongside popular
 * WordPress / WooCommerce plugins.
 *
 * Handled integrations:
 *
 *  Enhancement plugins:
 *   - Price Based on Country for WooCommerce (WC Zone Pricing)
 *   - WPML (multilingual product translations)
 *
 *  Product type plugins:
 *   - WooCommerce Product Bundles
 *   - WooCommerce Subscriptions
 *
 *  Cache plugins:
 *   - WP Rocket
 *   - Speed Optimizer by SiteGround
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Compatibility {

	/** @var GeoBlock_Restrictions */
	private $restrictions;

	/**
	 * @param GeoBlock_Restrictions $restrictions
	 */
	public function __construct( GeoBlock_Restrictions $restrictions ) {
		$this->restrictions = $restrictions;
	}

	/**
	 * Register all compatibility hooks.
	 * Called once from GeoBlock_Loader after all classes are initialised.
	 */
	public function init(): void {
		$this->init_price_based_on_country();
		$this->init_wpml();
		$this->init_bundles();
		$this->init_subscriptions();
		$this->init_wp_rocket();
		$this->init_speed_optimizer();
	}

	// ─── 1. Price Based on Country for WooCommerce ────────────────────────────

	/**
	 * "Price Based on Country" (by Oscar Gare / woocommerce-price-based-country)
	 * hooks into woocommerce_get_price_html (priority 10) and modifies prices
	 * per country/zone.
	 *
	 * In GeoBlock 'message' mode, we replace the price with our restriction
	 * notice — we run at priority 5 so we win, and return early so PBOC's
	 * filter never fires on restricted products.
	 *
	 * In 'catalog_only' / 'hide' modes PBOC still runs normally because
	 * restricted products are not visible / purchasable anyway.
	 */
	private function init_price_based_on_country(): void {
		// Only relevant if PBOC is active.
		if ( ! class_exists( 'WCPBC_Pricing_Zone' ) && ! class_exists( 'WC_Price_Based_Country' ) ) {
			return;
		}

		// Run our price filter before PBOC (their priority is 10).
		// GeoBlock's filter_price_html is already registered at priority 10;
		// we add an additional earlier filter here to short-circuit PBOC.
		add_filter( 'woocommerce_get_price_html', array( $this, 'pboc_price_filter' ), 5, 2 );
	}

	/**
	 * Short-circuit Price Based on Country on restricted products in message mode.
	 *
	 * @param  string     $price_html
	 * @param  WC_Product $product
	 * @return string
	 */
	public function pboc_price_filter( string $price_html, WC_Product $product ): string {
		if ( 'message' !== $this->restrictions->get_restriction_mode() ) {
			return $price_html;
		}
		if ( $this->restrictions->is_restricted( $product ) ) {
			// Return empty — GeoBlock's own filter_price_html (priority 10) will
			// also return '' and the restriction notice is shown by the summary hook.
			// Returning '' here prevents PBOC modifying the price before we hide it.
			return '';
		}
		return $price_html;
	}

	// ─── 2. WPML ─────────────────────────────────────────────────────────────

	/**
	 * WPML creates translated copies of each product with a different post ID.
	 * GeoBlock rules are saved on the ORIGINAL (default language) product only.
	 *
	 * We hook into our restriction lookup to always resolve to the original
	 * product ID before reading _geoblock_rules post meta, so translated
	 * product pages respect the same country rules as the original.
	 */
	private function init_wpml(): void {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return;
		}

		// Filter applied inside GeoBlock_Restrictions::get_product_rules().
		add_filter( 'geoblock_resolve_product_id', array( $this, 'wpml_resolve_original_id' ), 10, 1 );
	}

	/**
	 * Translate a WPML-translated product ID back to the original language ID.
	 *
	 * @param  int $product_id
	 * @return int
	 */
	public function wpml_resolve_original_id( int $product_id ): int {
		if ( ! function_exists( 'wpml_object_id' ) ) {
			return $product_id;
		}
		// Detect the actual post type so both 'product' and 'product_variation'
		// IDs resolve correctly to their original-language counterpart.
		$post_type    = get_post_type( $product_id ) ?: 'product';
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$default_lang = apply_filters( 'wpml_default_language', null );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$original_id  = (int) apply_filters( 'wpml_object_id', $product_id, $post_type, true, $default_lang );
		return $original_id ?: $product_id;
	}

	// ─── 3. WooCommerce Product Bundles ───────────────────────────────────────

	/**
	 * WooCommerce Product Bundles adds a 'bundle' product type.
	 *
	 * Behaviour:
	 * - If the BUNDLE product itself has a GeoBlock rule → restrict the whole bundle.
	 * - Bundled child items are NOT checked individually — restricting a child
	 *   item would silently break the bundle without any clear user-facing message.
	 *   Store owners should apply rules to the bundle parent.
	 *
	 * The bundle product type uses WC's standard purchasability hooks, so our
	 * existing filters already work. However, WC Bundles also adds a
	 * 'woocommerce_bundle_is_purchasable' filter that we must respect.
	 */
	private function init_bundles(): void {
		if ( ! class_exists( 'WC_Product_Bundle' ) ) {
			return;
		}

		// Ensure our is_purchasable filter runs after WC Bundles sets up its own
		// (WC Bundles uses priority 10; we run at 10 too, so WordPress calls them
		// in registration order — ours comes after since Loader registers it after
		// plugins_loaded. As a safety net we also hook at priority 15).
		add_filter( 'woocommerce_bundle_is_purchasable', array( $this, 'bundle_purchasable' ), 15, 2 );
	}

	/**
	 * Block restricted bundle products from being purchased.
	 *
	 * @param  bool           $purchasable
	 * @param  WC_Product     $product
	 * @return bool
	 */
	public function bundle_purchasable( bool $purchasable, WC_Product $product ): bool {
		if ( ! $purchasable ) {
			return false;
		}
		$mode = $this->restrictions->get_restriction_mode();
		if ( ! in_array( $mode, array( 'catalog_only', 'message' ), true ) ) {
			return $purchasable;
		}
		if ( 'catalog_only' === $mode ) {
			$settings            = (array) get_option( 'geoblock_settings', array() );
			$catalog_purchasable = $settings['catalog_purchasable'] ?? 'no';
			if ( 'yes' === $catalog_purchasable ) {
				return $purchasable;
			}
		}
		if ( $this->restrictions->is_restricted( $product ) ) {
			return false;
		}
		return $purchasable;
	}

	// ─── 4. WooCommerce Subscriptions ─────────────────────────────────────────

	/**
	 * WooCommerce Subscriptions adds 'subscription' and 'variable-subscription'
	 * product types. These use non-standard type strings which our
	 * filter_purchasable variable-product bypass doesn't cover.
	 *
	 * We hook into WC Subscriptions' own purchasability filter to ensure
	 * restricted subscription products are properly blocked.
	 */
	private function init_subscriptions(): void {
		if ( ! class_exists( 'WC_Subscriptions' ) && ! class_exists( 'WC_Subscriptions_Product' ) ) {
			return;
		}

		// WC Subscriptions hooks woocommerce_is_purchasable via its own methods;
		// we add a secondary filter on their specific hook.
		add_filter( 'woocommerce_subscription_is_purchasable',          array( $this, 'subscription_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_variable_subscription_is_purchasable', array( $this, 'subscription_purchasable' ), 10, 2 );
	}

	/**
	 * Block restricted subscription products.
	 *
	 * @param  bool        $purchasable
	 * @param  WC_Product  $product
	 * @return bool
	 */
	public function subscription_purchasable( bool $purchasable, WC_Product $product ): bool {
		if ( ! $purchasable ) {
			return false;
		}
		$mode = $this->restrictions->get_restriction_mode();
		if ( ! in_array( $mode, array( 'catalog_only', 'message' ), true ) ) {
			return $purchasable;
		}
		if ( 'catalog_only' === $mode ) {
			$settings            = (array) get_option( 'geoblock_settings', array() );
			$catalog_purchasable = $settings['catalog_purchasable'] ?? 'no';
			if ( 'yes' === $catalog_purchasable ) {
				return $purchasable;
			}
		}
		if ( $this->restrictions->is_restricted( $product ) ) {
			return false;
		}
		return $purchasable;
	}

	// ─── 5. WP Rocket ─────────────────────────────────────────────────────────

	/**
	 * WP Rocket caches full HTML pages. Geolocation-dependent pages must NOT
	 * be served from a static cache, otherwise all visitors get the same
	 * restricted/unrestricted version.
	 *
	 * Solutions applied:
	 *  a) Exclude GeoBlock's geolocation AJAX action from WP Rocket's
	 *     cache exclusions (not needed — AJAX is never cached by WP Rocket).
	 *  b) Set DONOTCACHEPAGE on single product pages so WP Rocket skips them.
	 *  c) Add the page to WP Rocket's "never cache" list via filter.
	 *
	 * NOTE: The recommended WooCommerce setup is "Geolocate (with page caching
	 * support)" which uses AJAX-based detection and is compatible with caching.
	 * This compatibility layer is a safety net for stores using standard
	 * "Geolocate" mode with WP Rocket page caching.
	 */
	private function init_wp_rocket(): void {
		if ( ! defined( 'WP_ROCKET_VERSION' ) ) {
			return;
		}

		// Tell WP Rocket not to cache individual product pages.
		// This is safe — WP Rocket will still cache shop/category/home pages.
		add_action( 'template_redirect', array( $this, 'rocket_disable_cache_on_product' ), 1 );

		// Exclude GeoBlock AJAX from WP Rocket cache exclusions list (informational).
		add_filter( 'rocket_cache_reject_uri', array( $this, 'rocket_exclude_geoblock_ajax' ) );
	}

	/**
	 * Set DONOTCACHEPAGE on single product pages so WP Rocket never caches them.
	 */
	public function rocket_disable_cache_on_product(): void {
		if ( is_product() && ! defined( 'DONOTCACHEPAGE' ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
			define( 'DONOTCACHEPAGE', true );
		}
	}

	/**
	 * Hook exists for future use — WP Rocket already excludes admin-ajax.php
	 * by default, so no additional URI exclusions are needed.
	 *
	 * @param  array $uris
	 * @return array
	 */
	public function rocket_exclude_geoblock_ajax( array $uris ): array {
		// WP Rocket natively excludes /wp-admin/admin-ajax.php.
		// No additional exclusions required for GeoBlock.
		return $uris;
	}

	// ─── 6. Speed Optimizer by SiteGround ────────────────────────────────────

	/**
	 * SiteGround Speed Optimizer (formerly SG CachePress) offers both server-side
	 * (Nginx/Dynamic) caching and a WordPress-level cache layer.
	 *
	 * We disable dynamic caching for individual product pages using their
	 * SGCACHENOCACHE constant, and hook into their bypass filters.
	 */
	private function init_speed_optimizer(): void {
		// SiteGround Speed Optimizer (plugin slug: sg-cachepress, class: SiteGround_Speed).
		if ( ! defined( 'SG_OPTIMIZER_VERSION' ) && ! class_exists( 'SiteGround_Speed' ) ) {
			return;
		}

		// Disable SG cache on product pages.
		add_action( 'template_redirect', array( $this, 'sg_disable_cache_on_product' ), 1 );

		// SG Optimizer respects the no-cache header approach as well.
		add_filter( 'sgo_bypass_cache', array( $this, 'sg_bypass_cache_on_product' ) );
	}

	/**
	 * Set the SGCACHENOCACHE constant to bypass SiteGround dynamic caching
	 * on single product pages.
	 */
	public function sg_disable_cache_on_product(): void {
		if ( is_product() && ! defined( 'SGCACHENOCACHE' ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
			define( 'SGCACHENOCACHE', true );
		}
	}

	/**
	 * Return true on product pages to signal SG Optimizer to bypass its cache.
	 *
	 * @param  bool $bypass
	 * @return bool
	 */
	public function sg_bypass_cache_on_product( bool $bypass ): bool {
		if ( is_product() ) {
			return true;
		}
		return $bypass;
	}
}
