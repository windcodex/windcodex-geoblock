<?php
/**
 * Admin - settings page, product meta box, AJAX handlers, and asset loading.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Admin {

	/** @var GeoBlock_Product */
	private $product;

	/** Default settings values */
	const DEFAULTS = array(
		'restriction_mode'          => 'hide',
		'message_position'          => 'after_title',
		'force_geolocation'         => 'no',
		'redirect_enabled'          => 'no',
		'redirect_url'              => '',
		'custom_message'            => '',
		'catalog_purchasable'       => 'no',   // 'no' = block ATC in catalog_only mode (default), 'yes' = allow
		'debug_mode'                => 'no',
	);

	/**
	 * @param GeoBlock_Product $product
	 */
	public function __construct( GeoBlock_Product $product ) {
		$this->product = $product;
	}

	// -- Admin menu -----------------------------------------------------

	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'GeoBlock Settings', 'windcodex-geoblock' ),
			__( 'GeoBlock Restrictions', 'windcodex-geoblock' ),
			'manage_woocommerce',
			'geoblock-settings',
			array( $this, 'render_settings_page' )
		);
	}

	// -- Settings registration ------------------------------------------

	public function register_settings(): void {
		register_setting(
			'geoblock_settings_group',
			'geoblock_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize all settings fields before saving.
	 *
	 * @param  array $input
	 * @return array
	 */
	public function sanitize_settings( array $input ): array {
		$clean = array();

		$clean['message_position']  = in_array( $input['message_position'] ?? '', array( 'before_title', 'after_title', 'after_price', 'after_cart' ), true )
			? $input['message_position']
			: 'after_title';
		$clean['force_geolocation'] = ( ( $input['force_geolocation'] ?? '' ) === 'yes' ) ? 'yes' : 'no';
		$clean['redirect_enabled']  = ( ( $input['redirect_enabled'] ?? '' ) === 'yes' ) ? 'yes' : 'no';
		// Only save redirect URL when Hide mode is active AND redirect is enabled.
		$is_hide_mode = ( ( $input['restriction_mode'] ?? '' ) === 'hide' );
		$redirect_on  = ( $clean['redirect_enabled'] === 'yes' );
		$clean['redirect_url'] = ( $is_hide_mode && $redirect_on && ! empty( $input['redirect_url'] ) )
			? esc_url_raw( $input['redirect_url'] )
			: '';
		$clean['restriction_mode'] = in_array( $input['restriction_mode'] ?? '', array( 'hide', 'catalog_only', 'message' ), true )
			? $input['restriction_mode']
			: 'hide';

		$clean['custom_message']      = wp_kses_post( $input['custom_message'] ?? '' );
		$clean['catalog_purchasable'] = ( ( $input['catalog_purchasable'] ?? '' ) === 'yes' ) ? 'yes' : 'no';
		$clean['debug_mode']          = ! empty( $input['debug_mode'] ) ? 'yes' : 'no';

		return $clean;
	}

	// -- Settings page render -------------------------------------------

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'windcodex-geoblock' ) );
		}
		require_once GEOBLOCK_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	// -- AJAX: save settings --------------------------------------------

	public function ajax_save_settings(): void {
		// Verify nonce.
		check_ajax_referer( 'geoblock_save_settings', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-geoblock' ) ), 403 );
		}

		$input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? wp_unslash( $_POST['settings'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			: array();

		$clean = $this->sanitize_settings( $input );
		$needs_redirect = ( 'hide' === $clean['restriction_mode'] && 'yes' === $clean['redirect_enabled'] );
		if ( $needs_redirect && empty( $clean['redirect_url'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a redirect URL before enabling redirect.', 'windcodex-geoblock' ) ), 400 );
		}
		update_option( 'geoblock_settings', $clean );

		wp_send_json_success( array(
			'message'  => __( 'Settings saved successfully.', 'windcodex-geoblock' ),
			'settings' => $clean,
		) );
	}

	// -- AJAX: reset settings -------------------------------------------

	public function ajax_reset_settings(): void {
		check_ajax_referer( 'geoblock_reset_settings', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-geoblock' ) ), 403 );
		}

		$defaults = self::DEFAULTS;
		$defaults['custom_message'] = __( 'Sorry, this product is not available in your country.', 'windcodex-geoblock' );

		update_option( 'geoblock_settings', $defaults );

		wp_send_json_success( array(
			'message'  => __( 'Settings reset to defaults.', 'windcodex-geoblock' ),
			'settings' => $defaults,
		) );
	}

	// -- Product meta box -----------------------------------------------

	public function add_product_meta_box(): void {
		add_meta_box(
			'geoblock_product_restrictions',
			__( 'GeoBlock - Country Restrictions', 'windcodex-geoblock' ),
			array( $this, 'render_product_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	public function render_product_meta_box( WP_Post $post ): void {
		$rules = $this->product->get_rules( $post->ID );
		require_once GEOBLOCK_PLUGIN_DIR . 'admin/views/product-meta-box.php';
	}

	/**
	 * Save product meta box data.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function save_product_meta( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['geoblock_product_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['geoblock_product_nonce'] ) ), 'geoblock_save_product_' . $post_id ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$mode = isset( $_POST['geoblock_mode'] ) && 'include' === sanitize_text_field( wp_unslash( $_POST['geoblock_mode'] ) )
			? 'include'
			: 'exclude';

		$countries = array();
		if ( ! empty( $_POST['geoblock_countries'] ) && is_array( $_POST['geoblock_countries'] ) ) {
			$countries = array_map(
				'strtoupper',
				array_map( 'sanitize_text_field', wp_unslash( $_POST['geoblock_countries'] ) )
			);
			$countries = array_values( array_filter( $countries, fn( $c ) => preg_match( '/^[A-Z]{2}$/', $c ) ) );
		}

		$restrictions = new GeoBlock_Restrictions( new GeoBlock_Geolocation() );
		$restrictions->save_product_rules( $post_id, array(
			'mode'      => $mode,
			'countries' => $countries,
		) );
	}

	// -- Assets ---------------------------------------------------------

	public function enqueue_assets( string $hook ): void {
		$screen           = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id        = $screen ? $screen->id : '';
		// Covers: classic list (edit-product), HPOS list (woocommerce_page_wc-orders--product),
		// product edit (product), new product (add product), and settings page.
		$post_type = '';
		if ( 'edit.php' === $hook && isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		}
		$is_product_list  = in_array( $screen_id, array( 'edit-product', 'product', 'woocommerce_page_wc-orders--product' ), true )
			|| ( 'edit.php' === $hook && 'product' === $post_type );
		$is_product_edit  = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_settings_page = ( 'woocommerce_page_geoblock-settings' === $hook );

		// CSS loads on products list, product edit screen, and settings page.
		if ( ! $is_product_list && ! $is_product_edit && ! $is_settings_page ) {
			return;
		}

		wp_enqueue_style(
			'geoblock-admin',
			GEOBLOCK_PLUGIN_URL . 'admin/assets/admin.css',
			array(),
			GEOBLOCK_VERSION
		);

		// JS + select2 only needed on product edit screen and settings page - not the list table.
		if ( ! $is_product_edit && ! $is_settings_page ) {
			return;
		}

		wp_enqueue_script(
			'geoblock-admin',
			GEOBLOCK_PLUGIN_URL . 'admin/assets/admin.js',
			array( 'jquery', 'select2' ),
			GEOBLOCK_VERSION,
			true
		);

		$settings = (array) get_option( 'geoblock_settings', array() );

			$wc        = function_exists( 'WC' ) ? WC() : null;
			$countries = ( $wc && isset( $wc->countries ) && $wc->countries ) ? $wc->countries->get_countries() : array();

			wp_localize_script( 'geoblock-admin', 'geoblock_admin', array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'save_nonce'       => wp_create_nonce( 'geoblock_save_settings' ),
				'reset_nonce'      => wp_create_nonce( 'geoblock_reset_settings' ),
				'current_settings' => $settings,
				'countries'        => $countries,
			'i18n'             => array(
				'saving'               => __( 'Saving...', 'windcodex-geoblock' ),
				'saved'                => __( 'Settings saved!', 'windcodex-geoblock' ),
				'save_error'           => __( 'Could not save. Please try again.', 'windcodex-geoblock' ),
				'redirect_url_required' => __( 'Please enter a redirect URL before enabling redirect.', 'windcodex-geoblock' ),
				'resetting'            => __( 'Resetting...', 'windcodex-geoblock' ),
				'reset_done'           => __( 'Settings reset to defaults!', 'windcodex-geoblock' ),
				'reset_confirm'        => __( 'Reset all settings to their default values? This cannot be undone.', 'windcodex-geoblock' ),
				'reset_error'          => __( 'Could not reset. Please try again.', 'windcodex-geoblock' ),
				'select_countries'     => __( 'Search countries...', 'windcodex-geoblock' ),
				'no_results'           => __( 'No countries found.', 'windcodex-geoblock' ),
				'no_restrictions'      => __( 'No restrictions - product available to all countries.', 'windcodex-geoblock' ),
				/* translators: %s: country list */
				'exclude_text'         => __( 'Customers from %s cannot purchase this product.', 'windcodex-geoblock' ),
				/* translators: %s: country list */
				'include_text'         => __( 'Only customers from %s can purchase this product.', 'windcodex-geoblock' ),
			),
		) );
	}

	// -- Products list column ------------------------------------------

	/**
	 * Add a "Geo Restriction" column to the Products list table.
	 *
	 * @param  array $columns
	 * @return array
	 */
	public function add_restriction_column( array $columns ): array {
		// Insert after the 'name' column for a logical position.
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'name' === $key ) {
				$new['geoblock_restriction'] = '<span class="gg-col-icon" title="'
					. esc_attr__( 'GeoBlock - Country Restriction', 'windcodex-geoblock' )
					. '"></span> '
					. esc_html__( 'Geo Restriction', 'windcodex-geoblock' );
			}
		}
		return $new;
	}

	/**
	 * Format a country label without duplicating the code.
	 *
	 * @param string $code
	 * @param string $name
	 * @return string
	 */
	private function format_country_label( string $code, string $name ): string {
		$label    = $name ?: $code;
		$code_tag = '(' . $code . ')';
		if ( false === stripos( $label, $code_tag ) ) {
			$label .= ' ' . $code_tag;
		}
		return $label;
	}

	/**
	 * Render the "Geo Restriction" column cell for each product row.
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function render_restriction_column( string $column, int $post_id ): void {
		if ( 'geoblock_restriction' !== $column ) {
			return;
		}

		$rules     = $this->product->get_rules( $post_id );
		$mode      = $rules['mode']      ?? 'exclude';
		$countries = $rules['countries'] ?? array();

		if ( empty( $countries ) ) {
			echo '<span class="gg-col-none">-</span>';
			return;
		}

		$wc            = function_exists( 'WC' ) ? WC() : null;
		$all_countries = ( $wc && isset( $wc->countries ) && $wc->countries ) ? $wc->countries->get_countries() : array();
		$count         = count( $countries );

		// Build tooltip: full country names.
		$names = array_map(
			fn( $c ) => $this->format_country_label( $c, $all_countries[ $c ] ?? $c ),
			$countries
		);
		$tooltip = implode( ', ', $names );

		if ( 'include' === $mode ) {
			$label     = sprintf(
				/* translators: %d: number of countries */
				_n( 'Only %d country', 'Only %d countries', $count, 'windcodex-geoblock' ),
				$count
			);
			$css_class = 'gg-col-include';
			$icon      = '&#9989;';
		} else {
			$label     = sprintf(
				/* translators: %d: number of countries */
				_n( 'Blocks %d country', 'Blocks %d countries', $count, 'windcodex-geoblock' ),
				$count
			);
			$css_class = 'gg-col-exclude';
			$icon      = '&#128683;';
		}

		printf(
			'<span class="gg-col-badge %s" title="%s">%s %s</span>',
			esc_attr( $css_class ),
			esc_attr( $tooltip ),
			wp_kses_post( $icon ),
			esc_html( $label )
		);
	}

	// -- Plugin action links -------------------------------------------

	public function plugin_action_links( array $links ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=geoblock-settings' ) ) . '">'
			. esc_html__( 'Settings', 'windcodex-geoblock' )
			. '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
