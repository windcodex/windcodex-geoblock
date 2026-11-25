<?php
/**
 * Health - runs setup checks for the Status tab so store owners can see why
 * country detection might not work before they blame the rules.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Health {

	/** @var GeoBlock_Geolocation */
	private $geo;

	/**
	 * @param GeoBlock_Geolocation $geo
	 */
	public function __construct( GeoBlock_Geolocation $geo ) {
		$this->geo = $geo;
	}

	/**
	 * Returns the list of checks.
	 *
	 * Each check: array( 'label' => string, 'status' => 'ok'|'warn'|'info', 'detail' => string ).
	 *
	 * @return array[]
	 */
	public function get_checks(): array {
		return array(
			$this->check_woocommerce_geolocation(),
			$this->check_ip_data_source(),
			$this->check_page_cache(),
			$this->check_detected_country(),
			$this->check_rules(),
		);
	}

	/**
	 * Overall status: 'warn' if any check warns, otherwise 'ok'.
	 *
	 * @param  array[] $checks
	 * @return string
	 */
	public function get_overall_status( array $checks ): string {
		foreach ( $checks as $check ) {
			if ( 'warn' === $check['status'] ) {
				return 'warn';
			}
		}
		return 'ok';
	}

	// -- Individual checks ----------------------------------------------

	private function check_woocommerce_geolocation(): array {
		$mode  = get_option( 'woocommerce_default_customer_address' );
		$label = __( 'WooCommerce geolocation', 'windcodex-geoblock' );

		if ( in_array( $mode, array( 'geolocation', 'geolocation_ajax' ), true ) ) {
			return array(
				'label'  => $label,
				'status' => 'ok',
				'detail' => 'geolocation_ajax' === $mode
					? __( 'Enabled (with page caching support).', 'windcodex-geoblock' )
					: __( 'Enabled.', 'windcodex-geoblock' ),
			);
		}

		return array(
			'label'  => $label,
			'status' => 'warn',
			'detail' => sprintf(
				/* translators: %s: WooCommerce settings URL */
				__( 'Not enabled. Guests fall back to your store base country. Set "Default customer location" to Geolocate in <a href="%s">WooCommerce &rarr; Settings &rarr; General</a>.', 'windcodex-geoblock' ),
				esc_url( admin_url( 'admin.php?page=wc-settings' ) )
			),
		);
	}

	private function check_ip_data_source(): array {
		$label = __( 'IP country data', 'windcodex-geoblock' );

		foreach ( array( 'HTTP_CF_IPCOUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE' ) as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				return array(
					'label'  => $label,
					'status' => 'ok',
					'detail' => __( 'Your host or CDN passes the visitor country in a request header.', 'windcodex-geoblock' ),
				);
			}
		}

		$maxmind     = (array) get_option( 'woocommerce_maxmind_geolocation_settings', array() );
		$has_license = ! empty( $maxmind['license_key'] );
		$has_db      = false;
		if ( class_exists( 'WC_Geolocation' ) && is_callable( array( 'WC_Geolocation', 'get_local_database_path' ) ) ) {
			$path   = WC_Geolocation::get_local_database_path();
			$has_db = $path && file_exists( $path );
		}

		if ( $has_db ) {
			return array(
				'label'  => $label,
				'status' => 'ok',
				'detail' => __( 'MaxMind GeoLite2 database is installed.', 'windcodex-geoblock' ),
			);
		}

		if ( $has_license ) {
			return array(
				'label'  => $label,
				'status' => 'info',
				'detail' => __( 'A MaxMind license key is saved. WooCommerce downloads the database on its schedule - check back shortly.', 'windcodex-geoblock' ),
			);
		}

		return array(
			'label'  => $label,
			'status' => 'warn',
			'detail' => sprintf(
				/* translators: %s: WooCommerce integration settings URL */
				__( 'No country data source found. Add a free MaxMind license key in <a href="%s">WooCommerce &rarr; Settings &rarr; Integration</a>, or use a host/CDN (such as Cloudflare) that passes the visitor country.', 'windcodex-geoblock' ),
				esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration' ) )
			),
		);
	}

	private function check_page_cache(): array {
		$label   = __( 'Page caching', 'windcodex-geoblock' );
		$mode    = get_option( 'woocommerce_default_customer_address' );
		$caching = ( defined( 'WP_CACHE' ) && WP_CACHE ) || defined( 'WP_ROCKET_VERSION' ) || defined( 'LSCWP_V' ) || defined( 'W3TC' ) || defined( 'WPFC_MAIN_PATH' ) || class_exists( 'WpFastestCache', false );

		if ( $caching && 'geolocation_ajax' !== $mode ) {
			return array(
				'label'  => $label,
				'status' => 'warn',
				'detail' => __( 'A page cache looks active. Set WooCommerce "Default customer location" to "Geolocate (with page caching support)" so cached pages still use the right country.', 'windcodex-geoblock' ),
			);
		}

		return array(
			'label'  => $label,
			'status' => 'ok',
			'detail' => $caching
				? __( 'A page cache is active and geolocation is in page-caching mode.', 'windcodex-geoblock' )
				: __( 'No page cache detected.', 'windcodex-geoblock' ),
		);
	}

	private function check_detected_country(): array {
		$label   = __( 'Your detected country', 'windcodex-geoblock' );
		$country = $this->geo->get_country();
		$ip      = $this->geo->get_ip();
		$flag    = $this->geo->get_flag_emoji( $country );
		$flag    = '??' === $flag ? '' : $flag . ' ';

		$is_public_ip = (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		$detail       = sprintf(
			/* translators: 1: country name and code, 2: IP address, 3: detection source */
			__( '%1$s - IP %2$s - source: %3$s', 'windcodex-geoblock' ),
			$flag . $this->geo->get_country_name( $country ) . ' (' . $country . ')',
			$ip,
			$this->geo->get_detection_source()
		);

		if ( ! $is_public_ip ) {
			return array(
				'label'  => $label,
				'status' => 'info',
				'detail' => $detail . ' - ' . __( 'This looks like a local or private IP, so detection falls back to your store base country. Use "Test as country" to simulate visitors.', 'windcodex-geoblock' ),
			);
		}

		return array(
			'label'  => $label,
			'status' => 'info',
			'detail' => $detail,
		);
	}

	private function check_rules(): array {
		$label = __( 'Products with country rules', 'windcodex-geoblock' );

		$query = new WP_Query( array(
			'post_type'              => 'product',
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'meta_key'               => '_geoblock_rules', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		$count = (int) $query->found_posts;

		if ( 0 === $count ) {
			return array(
				'label'  => $label,
				'status' => 'info',
				'detail' => __( 'None yet. Open any product and use the "GeoBlock - Country Restrictions" box to add a rule.', 'windcodex-geoblock' ),
			);
		}

		return array(
			'label'  => $label,
			'status' => 'ok',
			'detail' => sprintf(
				/* translators: %d: number of products */
				_n( '%d product has a country rule.', '%d products have country rules.', $count, 'windcodex-geoblock' ),
				$count
			),
		);
	}
}
