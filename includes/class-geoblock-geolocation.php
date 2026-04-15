<?php
/**
 * Geolocation - detects the visitor's country using WooCommerce's
 * built-in geolocation engine, with a session/transient cache layer
 * to avoid repeated IP lookups on every page load.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Geolocation {

	/** Session/transient key */
	const CACHE_KEY = 'geoblock_country';

	/** How long to cache the detected country (seconds) */
	const CACHE_TTL = 3600; // 1 hour

	// -- Public API -----------------------------------------------------

	/**
	 * Returns the detected 2-letter ISO country code for the current visitor.
	 * Falls back to the WooCommerce base country if detection fails.
	 *
	 * @return string e.g. 'US', 'IN', 'DE'
	 */
	public function get_country(): string {

		$force_geo = $this->is_force_geolocation_enabled();

		// 1. Logged-in customer with a saved shipping address - most accurate.
		// Skipped if Force Geolocation is enabled in settings.
		if ( ! $force_geo ) {
			$country = $this->get_from_shipping_address();
			if ( $country ) {
				return $country;
			}
		}

		// 2. Logged-in customer with a saved billing address.
		// Skipped if Force Geolocation is enabled in settings.
		if ( ! $force_geo ) {
			$country = $this->get_from_billing_address();
			if ( $country ) {
				return $country;
			}
		}

		// 3. Check WC session cache (set in a previous request this session).
		// Also skipped when Force Geolocation is ON - the cached value may have
		// been set from a shipping/billing address in a previous request, so we must
		// bypass it and always go straight to IP detection.
		if ( ! $force_geo ) {
			$country = $this->get_from_session();
			if ( $country ) {
				return $country;
			}
		}

		// 4. Check transient cache (keyed by IP, TTL 1 hour).
		$country = $this->get_from_transient();
		if ( $country ) {
			return $country;
		}

		// 5. Detect via WC Geolocation (IP -> MaxMind DB lookup).
		$country = $this->detect_via_wc_geolocation();

		// 6. Cache the IP-detected result.
		if ( $country ) {
			$this->store_in_transient( $country );
			$this->store_in_session( $country );
		}

		return $country ?: $this->get_base_country();
	}

	/**
	 * Allow the country to be manually overridden (e.g. from a frontend widget).
	 *
	 * @param string $country_code 2-letter ISO code.
	 */
	public function set_country( string $country_code ): void {
		$country_code = strtoupper( sanitize_text_field( $country_code ) );
		if ( $this->is_valid_country_code( $country_code ) ) {
			$this->store_in_session( $country_code );
			$this->store_in_transient( $country_code );
		}
	}

	/**
	 * Returns the current visitor's IP address, respecting common proxy headers.
	 *
	 * @return string
	 */
	public function get_ip(): string {
		// Check common proxy / load-balancer headers first.
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				// HTTP_X_FORWARDED_FOR can contain a comma-separated list.
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				$ip = trim( explode( ',', $ip )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		// Fall back to REMOTE_ADDR (may be private IP in localhost dev).
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ) );
	}

	// -- Debug toolbar --------------------------------------------------

	/**
	 * Renders an admin-only debug toolbar in the footer showing
	 * the detected country and visitor IP.
	 */
	public function maybe_render_debug_toolbar(): void {
		$settings = get_option( 'geoblock_settings', array() );
		if ( empty( $settings['debug_mode'] ) || 'yes' !== $settings['debug_mode'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$country = $this->get_country();
		$ip      = $this->get_ip();
		$flag    = $this->get_flag_emoji( $country );
		$name    = $this->get_country_name( $country );
		$source  = $this->get_detection_source();
		?>
		<div id="geoblock-debug-bar" style="
			position:fixed;bottom:0;left:0;right:0;z-index:99999;
			background:#1e3a5f;color:#fff;font-family:monospace;font-size:13px;
			padding:6px 16px;display:flex;gap:24px;align-items:center;
			border-top:3px solid #2e75b6;">
			<strong style="color:#a8c8e8;">GeoBlock Debug</strong>
			<span><?php echo esc_html( $flag . ' ' . $name . ' (' . $country . ')' ); ?></span>
			<span style="color:#a8c8e8;">IP: <?php echo esc_html( $ip ); ?></span>
			<span style="color:#ffd580;">Source: <?php echo esc_html( $source ); ?></span>
			<span style="color:#88cc88;">WC Geo: <?php echo esc_html( $this->wc_geolocation_enabled() ? 'Enabled' : 'Disabled' ); ?></span>
			<span style="color:#ffa07a;"><?php echo $this->is_force_geolocation_enabled() ? 'Force Geo: ON' : ''; ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=geoblock-settings' ) ); ?>"
			   style="color:#a8c8e8;margin-left:auto;">Settings -&gt;</a>
		</div>
		<?php
	}

	// -- Private helpers ------------------------------------------------

	/**
	 * Use WooCommerce's own geolocation engine.
	 *
	 * @return string|null
	 */
	private function detect_via_wc_geolocation(): ?string {
		if ( ! class_exists( 'WC_Geolocation' ) ) {
			return null;
		}

		$ip       = $this->get_ip();
		$geo_data = WC_Geolocation::geolocate_ip( $ip, true, true );

		if ( ! empty( $geo_data['country'] ) ) {
			return strtoupper( sanitize_text_field( $geo_data['country'] ) );
		}

		return null;
	}

	/**
	 * Returns the billing country of a logged-in customer who has a saved
	 * billing address.
	 *
	 * @return string|null
	 */
	private function get_from_billing_address(): ?string {
		if ( ! is_user_logged_in() ) {
			return null;
		}
		$wc = function_exists( 'WC' ) ? WC() : null;
		$customer = $wc ? $wc->customer : null;
		if ( ! $customer instanceof WC_Customer ) {
			return null;
		}
		$country = strtoupper( (string) $customer->get_billing_country() );
		if ( $country && $this->is_valid_country_code( $country ) ) {
			return $country;
		}
		return null;
	}

	/**
	 * Returns the shipping country of a logged-in customer who has a saved
	 * shipping address. This is the most accurate source - overrides IP detection.
	 *
	 * @return string|null
	 */
	private function get_from_shipping_address(): ?string {
		if ( ! is_user_logged_in() ) {
			return null;
		}
		$wc = function_exists( 'WC' ) ? WC() : null;
		$customer = $wc ? $wc->customer : null;
		if ( ! $customer instanceof WC_Customer ) {
			return null;
		}
		$country = strtoupper( (string) $customer->get_shipping_country() );
		if ( $country && $this->is_valid_country_code( $country ) ) {
			return $country;
		}
		return null;
	}

	/** @return string|null */
	private function get_from_session(): ?string {
		$wc = function_exists( 'WC' ) ? WC() : null;
		if ( ! $wc || ! $wc->session ) {
			return null;
		}
		$country = $wc->session->get( self::CACHE_KEY );
		return ( $country && $this->is_valid_country_code( $country ) ) ? $country : null;
	}

	private function store_in_session( string $country ): void {
		$wc = function_exists( 'WC' ) ? WC() : null;
		if ( $wc && $wc->session ) {
			$wc->session->set( self::CACHE_KEY, $country );
		}
	}

	/** @return string|null */
	private function get_from_transient(): ?string {
		$key     = self::CACHE_KEY . '_' . md5( $this->get_ip() );
		$country = get_transient( $key );
		return ( $country && $this->is_valid_country_code( $country ) ) ? $country : null;
	}

	private function store_in_transient( string $country ): void {
		$key = self::CACHE_KEY . '_' . md5( $this->get_ip() );
		set_transient( $key, $country, self::CACHE_TTL );
	}

	/** @return string WooCommerce store base country */
	private function get_base_country(): string {
		if ( ! function_exists( 'wc_get_base_location' ) ) {
			return 'US';
		}

		$base_location = wc_get_base_location();
		return $base_location['country'] ?? 'US';
	}

	/**
	 * Returns true if Force Geolocation is enabled in GeoBlock settings.
	 * When true, shipping address detection is skipped and IP is always used.
	 *
	 * @return bool
	 */
	private function is_force_geolocation_enabled(): bool {
		$settings = get_option( 'geoblock_settings', array() );
		return isset( $settings['force_geolocation'] ) && 'yes' === $settings['force_geolocation'];
	}

	/** @return bool */
	private function wc_geolocation_enabled(): bool {
		return 'geolocation' === get_option( 'woocommerce_default_customer_address' )
			|| 'geolocation_ajax' === get_option( 'woocommerce_default_customer_address' );
	}

	/**
	 * Validates a 2-letter ISO 3166-1 alpha-2 country code.
	 *
	 * @param  string $code
	 * @return bool
	 */
	private function is_valid_country_code( string $code ): bool {
		return (bool) preg_match( '/^[A-Z]{2}$/', strtoupper( $code ) );
	}

	/**
	 * Returns a human-readable label describing how the country was detected.
	 * Used in the debug toolbar only.
	 *
	 * @return string
	 */
	private function get_detection_source(): string {
		$wc = function_exists( 'WC' ) ? WC() : null;
		if ( is_user_logged_in() && $wc && $wc->customer instanceof WC_Customer ) {
			$shipping = strtoupper( (string) $wc->customer->get_shipping_country() );
			if ( $shipping && $this->is_valid_country_code( $shipping ) ) {
				return 'Shipping address';
			}
			$billing = strtoupper( (string) $wc->customer->get_billing_country() );
			if ( $billing && $this->is_valid_country_code( $billing ) ) {
				return 'Billing address';
			}
		}
		$session = $this->get_from_session();
		if ( $session ) {
			return 'Session cache';
		}
		$transient = $this->get_from_transient();
		if ( $transient ) {
			return 'Transient cache';
		}
		return 'IP geolocation';
	}

	/**
	 * Returns a flag emoji for a 2-letter country code.
	 * Works by converting ASCII letters to Regional Indicator Symbols.
	 *
	 * @param  string $code
	 * @return string
	 */
	private function get_flag_emoji( string $code ): string {
		if ( strlen( $code ) !== 2 ) {
			return '??';
		}
		$offset = 127397; // ord('A') = 65; Regional Indicator A = U+1F1E6 = 127462; diff = 127397
		$chars  = array();
		foreach ( str_split( strtoupper( $code ) ) as $char ) {
			$chars[] = mb_chr( ord( $char ) + $offset, 'UTF-8' );
		}
		return implode( '', $chars );
	}

	/**
	 * Returns a human-readable country name for a given code.
	 *
	 * @param  string $code
	 * @return string
	 */
	private function get_country_name( string $code ): string {
		$wc        = function_exists( 'WC' ) ? WC() : null;
		$countries = ( $wc && isset( $wc->countries ) && $wc->countries ) ? $wc->countries->get_countries() : array();
		return $countries[ $code ] ?? $code;
	}
}
