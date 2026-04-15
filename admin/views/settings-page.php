<?php
/**
 * Settings page view ─ tabbed, AJAX save, one-click reset.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$gg_settings              = (array) get_option( 'geoblock_settings', array() );
$gg_mode                  = $gg_settings['restriction_mode']      ?? 'hide';
$gg_message               = $gg_settings['custom_message']        ?? __( 'Sorry, this product is not available in your country.', 'windcodex-geoblock' );
$gg_message_position      = $gg_settings['message_position']      ?? 'after_title';
$gg_redirect              = $gg_settings['redirect_url']          ?? '';
$gg_force_geo             = $gg_settings['force_geolocation']     ?? 'no';
$gg_catalog_purchasable   = $gg_settings['catalog_purchasable']   ?? 'no';
$gg_debug                 = $gg_settings['debug_mode']            ?? 'no';

$gg_wc_geo     = get_option( 'woocommerce_default_customer_address' );
$gg_geo_active = in_array( $gg_wc_geo, array( 'geolocation', 'geolocation_ajax' ), true );
?>
<!-- Page Header -->
	<div class="gg-header-card">
		<div class="gg-breadcrumb">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=geoblock-settings' ) ); ?>">
				<?php esc_html_e( 'GeoBlock', 'windcodex-geoblock' ); ?>
			</a>
			<span class="gg-breadcrumb-sep">/</span>
			<span id="gg-breadcrumb-current"><?php esc_html_e( 'General', 'windcodex-geoblock' ); ?></span>
		</div>
		<!-- <div class="gg-page-header">
			<div class="gg-header-text">
				<h1 class="gg-page-title">
					<?php //esc_html_e( 'GeoBlock Country Restrictions', 'windcodex-geoblock' ); ?>
				</h1>
				<p class="gg-page-sub">
					<?php //esc_html_e( 'Control product visibility and purchasability based on your customers\' country.', 'windcodex-geoblock' ); ?>
				</p>
				<div class="gg-header-badges">
					<?php if ( $gg_geo_active ) : ?>
						<span class="gg-badge gg-badge-success">&#10003; <?php //esc_html_e( 'Geolocation Active', 'windcodex-geoblock' ); ?></span>
					<?php else : ?>
						<span class="gg-badge gg-badge-warn">&#9888; <?php //esc_html_e( 'Geolocation Inactive', 'windcodex-geoblock' ); ?></span>
					<?php endif; ?>
					<?php if ( defined( 'WC_VERSION' ) ) : ?>
						<span class="gg-badge gg-badge-info">WooCommerce <?php //echo esc_html( WC_VERSION ); ?></span>
					<?php endif; ?>
					<span class="gg-badge gg-badge-info">v<?php //echo esc_html( GEOBLOCK_VERSION ); ?></span>
				</div>
			</div>
		</div> -->
	</div>
<div class="wrap gg-wrap">

	<!-- Geolocation warning -->
	<?php if ( ! $gg_geo_active ) : ?>
	<div class="gg-notice gg-notice-warn">
		<span class="gg-notice-icon">&#9888;&#65039;</span>
		<div>
			<strong><?php esc_html_e( 'WooCommerce Geolocation is not enabled.', 'windcodex-geoblock' ); ?></strong>
			<ul>
				<li><?php esc_html_e( 'Logged-in customers with a saved shipping or billing address will still be detected correctly.', 'windcodex-geoblock' ); ?></li>
				<li><?php esc_html_e( 'Guest visitors and logged-in customers without a saved address rely on IP-based detection via the MaxMind GeoLite2 database.', 'windcodex-geoblock' ); ?></li>
				<li><?php esc_html_e( 'WooCommerce only downloads the MaxMind database when geolocation is enabled. Without it, guests fall back to the store base country and restrictions will not apply to them.', 'windcodex-geoblock' ); ?></li>
			</ul>
			<p>
			<?php
			/* translators: %s: WooCommerce General Settings URL */
			$gg_fix_url = __( 'Fix this in <a href="%s">WooCommerce &rarr; Settings &rarr; General</a> by setting "Default customer location" to "Geolocate".', 'windcodex-geoblock' );
			echo wp_kses_post( sprintf( $gg_fix_url, esc_url( admin_url( 'admin.php?page=wc-settings' ) ) ) );
			?>
			</p>
		</div>
	</div>
	<?php endif; ?>

	<!-- Tab Navigation -->
	<div class="gg-tabs-nav" role="tablist">
		<button class="gg-tab-btn gg-tab-active" data-tab="general" data-breadcrumb="<?php esc_attr_e( 'General', 'windcodex-geoblock' ); ?>" role="tab" aria-selected="true">
			<?php esc_html_e( 'General', 'windcodex-geoblock' ); ?>
		</button>
		<button class="gg-tab-btn" data-tab="advanced" data-breadcrumb="<?php esc_attr_e( 'Advanced', 'windcodex-geoblock' ); ?>" role="tab" aria-selected="false">
			<?php esc_html_e( 'Advanced', 'windcodex-geoblock' ); ?>
		</button>
	</div>

	<!-- Settings Form -->
	<form id="gg-settings-form" method="post">
		<?php wp_nonce_field( 'geoblock_save_settings', 'gg_nonce_field' ); ?>

		<!-- ---- TAB: General ---- -->
		<div class="gg-tab-panel gg-tab-panel-active" data-panel="general">
			<div class="gg-card" data-section="common-settings">
				<div class="gg-card-header">
					<div>
						<div class="gg-card-header-title"><?php esc_html_e( 'Restriction Settings', 'windcodex-geoblock' ); ?></div>
						<div class="gg-card-header-sub"><?php esc_html_e( 'How restricted products behave across your store.', 'windcodex-geoblock' ); ?></div>
					</div>
					<div class="gg-card-header-actions">
						<button type="button" class="gg-card-accordion-toggle" data-card-accordion-toggle aria-expanded="true" aria-controls="gg-common-settings-content">
							<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'Toggle Restriction Settings section', 'windcodex-geoblock' ); ?></span>
						</button>
					</div>
				</div>

				<div id="gg-common-settings-content" class="gg-card-accordion-content">
				<!-- Restriction Mode -->
				<div class="gg-form-row">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Restriction Mode', 'windcodex-geoblock' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Choose how restricted products behave on your store', 'windcodex-geoblock' ); ?></div>
					</div>
					<div class="gg-row-body">
						<div class="gg-radio-group">

							<!-- Mode: Hide completely -->
							<label class="gg-radio-card<?php echo ( 'hide' === $gg_mode ) ? ' gg-selected' : ''; ?>">
								<input type="radio" name="geoblock_settings[restriction_mode]" value="hide" class="gg-radio-input" <?php checked( $gg_mode, 'hide' ); ?>>
								<span class="gg-radio-dot-wrap"><span class="gg-radio-dot"></span></span>
								<span class="gg-radio-content">
									<span class="gg-radio-title"><?php esc_html_e( 'Hide completely', 'windcodex-geoblock' ); ?></span>
									<span class="gg-radio-desc"><?php esc_html_e( 'Remove from shop, search, and category pages entirely. Direct product URL returns 404.', 'windcodex-geoblock' ); ?></span>
								</span>
							</label>

							<!-- Redirect sub-option ─only visible when Hide is selected -->
							<div class="gg-sub-option<?php echo ( 'hide' !== $gg_mode ) ? ' gg-hidden' : ''; ?>" id="gg-sub-redirect">
								<div class="gg-sub-option-inner">
									<label class="gg-sub-toggle-label">
										<span class="gg-sub-toggle-wrap">
											<input type="checkbox"
												name="geoblock_settings[redirect_enabled]"
												id="gg-redirect-toggle"
												value="yes"
												class="gg-toggle-checkbox screen-reader-text"
												<?php checked( ! empty( $gg_redirect ), true ); ?>>
											<span class="gg-toggle-track gg-toggle-sm">
												<span class="gg-toggle-thumb"></span>
											</span>
										</span>
										<span class="gg-sub-toggle-text">
											<span class="gg-sub-toggle-title"><?php esc_html_e( 'Redirect restricted visitors instead of showing 404', 'windcodex-geoblock' ); ?></span>
											<span class="gg-sub-toggle-desc"><?php esc_html_e( 'Send restricted visitors to a custom URL when they access a product page directly.', 'windcodex-geoblock' ); ?></span>
										</span>
									</label>
									<div class="gg-sub-url-field<?php echo empty( $gg_redirect ) ? ' gg-hidden' : ''; ?>" id="gg-sub-url-field">
										<input type="url"
											id="geoblock_redirect_url"
											name="geoblock_settings[redirect_url]"
											value="<?php echo esc_attr( $gg_redirect ); ?>"
											class="gg-input gg-input-sm"
											placeholder="https://example.com/not-available">
									</div>
								</div>
							</div>

							<!-- Mode: Hide from catalog, allow direct URL -->
							<label class="gg-radio-card<?php echo ( 'catalog_only' === $gg_mode ) ? ' gg-selected' : ''; ?>">
								<input type="radio" name="geoblock_settings[restriction_mode]" value="catalog_only" class="gg-radio-input" <?php checked( $gg_mode, 'catalog_only' ); ?>>
								<span class="gg-radio-dot-wrap"><span class="gg-radio-dot"></span></span>
								<span class="gg-radio-content">
									<span class="gg-radio-title"><?php esc_html_e( 'Hide from catalog, allow direct URL', 'windcodex-geoblock' ); ?></span>
									<span class="gg-radio-desc"><?php esc_html_e( 'Hidden from shop and search pages, but the product URL still loads. Useful for wholesale or private distribution.', 'windcodex-geoblock' ); ?></span>
								</span>
							</label>

							<!-- Catalog purchasable sub-option -->
							<div class="gg-sub-option<?php echo ( 'catalog_only' !== $gg_mode ) ? ' gg-hidden' : ''; ?>" id="gg-sub-catalog">
								<div class="gg-sub-option-inner">
									<label class="gg-sub-toggle-label">
										<span class="gg-sub-toggle-wrap">
											<input type="checkbox"
												name="geoblock_settings[catalog_purchasable]"
												id="gg-catalog-purchasable-toggle"
												value="yes"
												class="gg-toggle-checkbox screen-reader-text"
												<?php checked( $gg_catalog_purchasable, 'yes' ); ?>>
											<span class="gg-toggle-track gg-toggle-sm">
												<span class="gg-toggle-thumb"></span>
											</span>
										</span>
										<span class="gg-sub-toggle-text">
											<span class="gg-sub-toggle-title"><?php esc_html_e( 'Allow purchase via direct URL', 'windcodex-geoblock' ); ?></span>
											<span class="gg-sub-toggle-desc"><?php esc_html_e( 'When enabled, restricted visitors who reach the product directly can still purchase it. By default, purchase is blocked.', 'windcodex-geoblock' ); ?></span>
										</span>
									</label>
								</div>
							</div>

							<!-- Mode: Show restriction message -->
							<label class="gg-radio-card<?php echo ( 'message' === $gg_mode ) ? ' gg-selected' : ''; ?>">
								<input type="radio" name="geoblock_settings[restriction_mode]" value="message" class="gg-radio-input" <?php checked( $gg_mode, 'message' ); ?>>
								<span class="gg-radio-dot-wrap"><span class="gg-radio-dot"></span></span>
								<span class="gg-radio-content">
									<span class="gg-radio-title"><?php esc_html_e( 'Show restriction message', 'windcodex-geoblock' ); ?></span>
									<span class="gg-radio-desc"><?php esc_html_e( 'Visible in shop and search. On the product page, price and Add to Cart are replaced with your custom message.', 'windcodex-geoblock' ); ?></span>
								</span>
							</label>

						</div>
					</div>
				</div>

				<!-- Restriction Message -->
				<div class="gg-form-row gg-row-message<?php echo in_array( $gg_mode, array( 'hide', 'catalog_only' ), true ) ? ' gg-hidden' : ''; ?>" id="gg-row-message">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Restriction Message', 'windcodex-geoblock' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Shown to customers in restricted countries', 'windcodex-geoblock' ); ?></div>
					</div>
					<div class="gg-row-body">
						<textarea id="geoblock_custom_message"
							name="geoblock_settings[custom_message]"
							rows="3"
							class="gg-textarea"
						><?php echo esc_textarea( $gg_message ); ?></textarea>
						<p class="gg-field-hint">
							<?php esc_html_e( 'Shown on the product page when restricted. HTML is allowed.', 'windcodex-geoblock' ); ?>
						</p>
					</div>
				</div>

				<!-- Message Position -->
				<div class="gg-form-row gg-form-row-last gg-row-message-pos<?php echo in_array( $gg_mode, array( 'hide', 'catalog_only' ), true ) ? ' gg-hidden' : ''; ?>" id="gg-row-message-pos">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Message Position', 'windcodex-geoblock' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Where to display the restriction message on the product page', 'windcodex-geoblock' ); ?></div>
					</div>
					<div class="gg-row-body">
						<div class="gg-position-cards" id="gg-position-cards">
							<?php
							// WooCommerce single product summary hook priorities (for reference):
								// priority 5 = product title
								// priority 10 = star rating
								// priority 20 = price  <- hidden in message mode
								// priority 25 = short description
								// priority 30 = add to cart  <- hidden in message mode
								// priority 40 = product meta (SKU, category)
								// Our message replaces price+ATC, so positions 3 & 4 are
								// described relative to what the visitor actually sees.
								$gg_positions = array(
									'before_title' => array(
										'label' => __( 'Before Title', 'windcodex-geoblock' ),
										'desc'  => __( 'Above the product name', 'windcodex-geoblock' ),
										'icon'  => 'top',
									),
									'after_title'  => array(
										'label' => __( 'After Title', 'windcodex-geoblock' ),
										'desc'  => __( 'Below title and star rating', 'windcodex-geoblock' ),
										'icon'  => 'middle-top',
									),
									'after_price'  => array(
										'label' => __( 'After Description', 'windcodex-geoblock' ),
										'desc'  => __( 'Below the short description', 'windcodex-geoblock' ),
										'icon'  => 'middle',
									),
									'after_cart'   => array(
										'label' => __( 'Before Product Meta', 'windcodex-geoblock' ),
										'desc'  => __( 'Before SKU and category info', 'windcodex-geoblock' ),
										'icon'  => 'bottom',
									),
								);
							foreach ( $gg_positions as $gg_pos_val => $gg_pos ) :
								$gg_pos_sel = ( $gg_message_position === $gg_pos_val );
							?>
							<label class="gg-position-card<?php echo $gg_pos_sel ? ' gg-pos-selected' : ''; ?>">
								<input type="radio"
									name="geoblock_settings[message_position]"
									value="<?php echo esc_attr( $gg_pos_val ); ?>"
									class="screen-reader-text gg-position-radio"
									<?php checked( $gg_message_position, $gg_pos_val ); ?>>
								<span class="gg-pos-preview">
									<span class="gg-pos-line gg-pos-line-title <?php echo in_array( $gg_pos['icon'], array( 'top' ), true ) ? 'gg-pos-line-active' : ''; ?>"></span>
									<span class="gg-pos-line gg-pos-line-sub <?php echo in_array( $gg_pos['icon'], array( 'middle-top' ), true ) ? 'gg-pos-line-active' : ''; ?>"></span>
									<span class="gg-pos-line gg-pos-line-price <?php echo in_array( $gg_pos['icon'], array( 'middle' ), true ) ? 'gg-pos-line-active' : ''; ?>"></span>
									<span class="gg-pos-line gg-pos-line-cart <?php echo in_array( $gg_pos['icon'], array( 'bottom' ), true ) ? 'gg-pos-line-active' : ''; ?>"></span>
								</span>
								<span class="gg-pos-label"><?php echo esc_html( $gg_pos['label'] ); ?></span>
								<span class="gg-pos-desc"><?php echo esc_html( $gg_pos['desc'] ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				</div>

			</div><!-- .gg-card -->
		</div><!-- [General] -->

		<!-- ---- TAB: Advanced ---- -->
		<div class="gg-tab-panel" data-panel="advanced">
			<div class="gg-card">
				<div class="gg-card-header">
					<div>
						<div class="gg-card-header-title"><?php esc_html_e( 'Advanced Settings', 'windcodex-geoblock' ); ?></div>
						<div class="gg-card-header-sub"><?php esc_html_e( 'Developer and debugging options.', 'windcodex-geoblock' ); ?></div>
					</div>
				</div>

				<!-- Force Geolocation -->
				<div class="gg-form-row">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Force Geolocation', 'windcodex-geoblock' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Always use IP detection, ignore shipping address', 'windcodex-geoblock' ); ?></div>
					</div>
					<div class="gg-row-body">
						<div class="gg-toggle-row">
							<label class="gg-toggle-wrap">
								<input type="checkbox"
									name="geoblock_settings[force_geolocation]"
									id="gg-force-geo-toggle"
									value="yes"
									class="gg-toggle-checkbox screen-reader-text"
									<?php checked( $gg_force_geo, 'yes' ); ?>>
								<span class="gg-toggle-track gg-toggle-sm">
									<span class="gg-toggle-thumb"></span>
								</span>
							</label>
							<div class="gg-toggle-label">
								<span class="gg-toggle-main"><?php esc_html_e( 'Always use IP-based geolocation', 'windcodex-geoblock' ); ?></span>
								<span class="gg-toggle-sub"><?php esc_html_e( 'By default GeoBlock uses a logged-in customer saved shipping address for maximum accuracy. Enable this to always use IP detection instead ─useful when shipping addresses may not reflect the customer physical location.', 'windcodex-geoblock' ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<!-- Debug Mode -->
				<div class="gg-form-row gg-form-row-last">
					<div class="gg-row-label">
						<div class="gg-row-title"><?php esc_html_e( 'Debug Mode', 'windcodex-geoblock' ); ?></div>
						<div class="gg-row-hint"><?php esc_html_e( 'Admin-only toolbar in site footer', 'windcodex-geoblock' ); ?></div>
					</div>
					<div class="gg-row-body">
						<div class="gg-toggle-row">
							<label class="gg-toggle-wrap">
								<input type="checkbox"
									name="geoblock_settings[debug_mode]"
									id="gg-debug-toggle"
									value="yes"
									class="gg-toggle-checkbox screen-reader-text"
									<?php checked( $gg_debug, 'yes' ); ?>>
								<span class="gg-toggle-track gg-toggle-sm">
									<span class="gg-toggle-thumb"></span>
								</span>
							</label>
							<div class="gg-toggle-label">
								<span class="gg-toggle-main"><?php esc_html_e( 'Enable debug toolbar', 'windcodex-geoblock' ); ?></span>
								<span class="gg-toggle-sub"><?php esc_html_e( 'Shows a bar at the bottom of every frontend page with the detected country and IP address. Visible only to shop managers and administrators.', 'windcodex-geoblock' ); ?></span>
							</div>
						</div>
					</div>
				</div>

			</div><!-- .gg-card -->
		</div><!-- [Advanced] -->

		<!-- Footer Action Bar -->
		<div class="gg-footer-bar" id="gg-footer-bar" data-tab-visible="general,advanced">
			<div class="gg-footer-left">
				<button type="button" id="gg-save-btn" class="gg-btn-primary">
					<span class="gg-btn-label"><?php esc_html_e( 'Save Settings', 'windcodex-geoblock' ); ?></span>
				</button>
				<button type="button" id="gg-reset-btn" class="gg-btn-reset">
					<span class="gg-btn-label"><?php esc_html_e( 'Reset to Defaults', 'windcodex-geoblock' ); ?></span>
				</button>
			</div>
			<div class="gg-toast" id="gg-toast" role="status" aria-live="polite"></div>
		</div>

	</form>

</div><!-- .gg-wrap -->

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
