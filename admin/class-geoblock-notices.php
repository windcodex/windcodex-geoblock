<?php
/**
 * Admin notices — Pro upsell banner and review request.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Notices {

	const REVIEW_DISMISSED_OPTION = 'geoblock_review_dismissed';
	const REVIEW_REMIND_TRANSIENT = 'geoblock_review_remind_later';
	const REVIEW_DAYS             = 7;
	const REVIEW_REMIND_DAYS      = 14;

	// ── Helpers ────────────────────────────────────────────────────────────

	private function on_settings_page(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && 'woocommerce_page_geoblock-settings' === $screen->id;
	}

	// ── Render ─────────────────────────────────────────────────────────────

	public function render(): void {
		if ( ! $this->on_settings_page() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$this->render_review_notice();
	}

	public function render_pro_notice(): void {
		?>
		<div class="gg-pro-notice">
			<p class="gg-pro-notice-text">
				🚀 <strong><?php esc_html_e( 'GeoBlock Pro', 'windcodex-geoblock' ); ?></strong>
				<?php esc_html_e( '— Bulk category rules, payment gateway restrictions by country, variation-level rules, and analytics dashboard.', 'windcodex-geoblock' ); ?>
			</p>
			<a href="https://windcodex.com/product/woocommerce-country-restriction-plugin/" target="_blank" rel="noopener" class="gg-pro-notice-cta">
				<?php esc_html_e( 'Explore GeoBlock Pro →', 'windcodex-geoblock' ); ?>
			</a>
		</div>
		<?php
	}

	public function render_review_notice(): void {
		if ( get_option( self::REVIEW_DISMISSED_OPTION, '' ) ) {
			return;
		}

		if ( get_transient( self::REVIEW_REMIND_TRANSIENT ) ) {
			return;
		}

		$activated = (int) get_option( 'geoblock_activated_time', 0 );

		if ( ! $activated ) {
			// Seed the time now for existing installs that pre-date this notice.
			update_option( 'geoblock_activated_time', time() );
			return;
		}

		if ( ( time() - $activated ) < ( self::REVIEW_DAYS * DAY_IN_SECONDS ) ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible gg-review-notice" id="gg-review-notice">
			<p class="gg-review-notice-title">
				<?php esc_html_e( '⭐ Enjoying GeoBlock?', 'windcodex-geoblock' ); ?>
			</p>
			<p class="gg-review-notice-body">
				<?php esc_html_e( "You've been using GeoBlock for 7 days — we hope it's saving you from failed deliveries and unwanted orders.", 'windcodex-geoblock' ); ?>
				<br>
				<?php esc_html_e( "If it's been helpful, a quick review on WordPress.org takes less than 2 minutes and helps thousands of other store owners find the plugin.", 'windcodex-geoblock' ); ?>
			</p>
			<div class="gg-review-notice-actions">
				<a href="https://wordpress.org/support/plugin/windcodex-geoblock/reviews/#new-post"
				   target="_blank" rel="noopener"
				   class="gg-review-btn gg-review-btn-primary"
				   data-gg-review-action="reviewed">
					<?php esc_html_e( '⭐ Leave a Review', 'windcodex-geoblock' ); ?>
				</a>
				<button type="button" class="gg-review-btn gg-review-btn-secondary" data-gg-review-action="reviewed">
					<?php esc_html_e( 'I already did ✓', 'windcodex-geoblock' ); ?>
				</button>
				<button type="button" class="gg-review-btn gg-review-btn-link" data-gg-review-action="later">
					<?php esc_html_e( 'Maybe Later', 'windcodex-geoblock' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	// ── AJAX: dismiss review notice ────────────────────────────────────────

	public function ajax_dismiss(): void {
		check_ajax_referer( 'geoblock_dismiss_review', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'windcodex-geoblock' ) ), 403 );
		}

		$action = sanitize_key( $_POST['dismiss_action'] ?? 'later' );

		if ( 'later' === $action ) {
			// Re-show after REVIEW_REMIND_DAYS days.
			set_transient( self::REVIEW_REMIND_TRANSIENT, 1, self::REVIEW_REMIND_DAYS * DAY_IN_SECONDS );
		} else {
			// Permanently dismissed.
			update_option( self::REVIEW_DISMISSED_OPTION, $action );
		}

		wp_send_json_success();
	}

	// ── Enqueue: add dismiss nonce to existing admin script ────────────────

	public function localize_nonce( string $hook ): void {
		if ( 'woocommerce_page_geoblock-settings' !== $hook ) {
			return;
		}
		wp_localize_script( 'geoblock-admin', 'geoblock_notices', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'geoblock_dismiss_review' ),
		) );
	}
}
