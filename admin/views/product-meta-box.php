<?php
/**
 * Product meta box view.
 *
 * @var WP_Post $post
 * @var array   $rules  array( 'mode' => '...', 'countries' => [...] )
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$mode          = $rules['mode']      ?? 'exclude';
$countries     = $rules['countries'] ?? array();
$wc            = function_exists( 'WC' ) ? WC() : null;
$all_countries = ( $wc && isset( $wc->countries ) && $wc->countries ) ? $wc->countries->get_countries() : array();
$has_rules     = ! empty( $countries );

$format_country_label = static function( $code, $name ) {
	$label = $name ?: $code;
	$code_tag = '(' . $code . ')';
	if ( false === stripos( $label, $code_tag ) ) {
		$label .= ' ' . $code_tag;
	}
	return $label;
};

$country_names = array_map(
	fn( $c ) => $format_country_label( $c, $all_countries[ $c ] ?? $c ),
	$countries
);
$names_str = implode( ', ', $country_names );
?>
<div class="gg-meta-box">

	<?php wp_nonce_field( 'geoblock_save_product_' . $post->ID, 'geoblock_product_nonce' ); ?>

	<!-- Intro -->
	<div class="gg-meta-intro">
		<span class="gg-meta-intro-icon">&#8505;&#65039;</span>
		<?php esc_html_e( 'Define which countries can or cannot purchase this product. Leave empty for no restrictions.', 'windcodex-geoblock' ); ?>
	</div>

	<div class="gg-meta-body">

		<!-- -- Rule Type -- -->
		<div class="gg-meta-section-label">
			<?php esc_html_e( 'Rule Type', 'windcodex-geoblock' ); ?>
		</div>
		<div class="gg-rule-pills">

			<label class="gg-rule-pill gg-rule-exclude<?php echo 'exclude' === $mode ? ' gg-pill-active' : ''; ?>">
				<input type="radio" name="geoblock_mode" value="exclude"
					class="screen-reader-text gg-rule-radio" <?php checked( $mode, 'exclude' ); ?>>
				<span class="gg-pill-icon">&#128683;</span>
				<span class="gg-pill-content">
					<span class="gg-pill-title"><?php esc_html_e( 'Exclude', 'windcodex-geoblock' ); ?></span>
					<span class="gg-pill-desc"><?php esc_html_e( 'Restrict these countries', 'windcodex-geoblock' ); ?></span>
				</span>
			</label>

			<label class="gg-rule-pill gg-rule-include<?php echo 'include' === $mode ? ' gg-pill-active' : ''; ?>">
				<input type="radio" name="geoblock_mode" value="include"
					class="screen-reader-text gg-rule-radio" <?php checked( $mode, 'include' ); ?>>
				<span class="gg-pill-icon">&#9989;</span>
				<span class="gg-pill-content">
					<span class="gg-pill-title"><?php esc_html_e( 'Include', 'windcodex-geoblock' ); ?></span>
					<span class="gg-pill-desc"><?php esc_html_e( 'Allow ONLY these countries', 'windcodex-geoblock' ); ?></span>
				</span>
			</label>

		</div>

		<!-- -- Countries -- -->
		<div class="gg-meta-section-label" style="margin-top:18px;">
			<?php esc_html_e( 'Countries', 'windcodex-geoblock' ); ?>
		</div>
		<select
			id="geoblock_countries_select"
			name="geoblock_countries[]"
			multiple="multiple"
			class="gg-country-select"
			style="width:100%"
		>
			<?php foreach ( $all_countries as $code => $name ) : ?>
			<option value="<?php echo esc_attr( $code ); ?>"
				<?php selected( in_array( $code, $countries, true ) ); ?>>
				<?php echo esc_html( $format_country_label( $code, $name ) ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<p class="gg-field-hint" style="margin-top:8px;">
			<?php esc_html_e( 'Search by name or 2-letter code. Leave empty to remove all restrictions from this product.', 'windcodex-geoblock' ); ?>
		</p>

		<!-- -- Rule Summary -- -->
		<?php if ( $has_rules ) : ?>
		<div class="gg-rule-summary <?php echo 'include' === $mode ? 'gg-summary-include' : 'gg-summary-exclude'; ?>" id="gg-rule-summary">
			<span class="gg-summary-icon"><?php echo 'include' === $mode ? '&#9989;' : '&#128683;'; ?></span>
			<span class="gg-summary-text">
				<?php if ( 'include' === $mode ) : ?>
					<?php printf(
						/* translators: %s: country names */
						esc_html__( 'Only customers from %s can purchase this product.', 'windcodex-geoblock' ),
						'<strong>' . esc_html( $names_str ) . '</strong>'
					); ?>
				<?php else : ?>
					<?php printf(
						/* translators: %s: country names */
						esc_html__( 'Customers from %s cannot purchase this product.', 'windcodex-geoblock' ),
						'<strong>' . esc_html( $names_str ) . '</strong>'
					); ?>
				<?php endif; ?>
			</span>
		</div>
		<?php else : ?>
		<div class="gg-rule-summary gg-summary-none" id="gg-rule-summary">
			<span class="gg-summary-icon">&#9989;</span>
			<span class="gg-summary-text">
				<?php esc_html_e( 'No restrictions set ─ this product is available to customers in all countries.', 'windcodex-geoblock' ); ?>
			</span>
		</div>
		<?php endif; ?>

	</div><!-- .gg-meta-body -->
</div><!-- .gg-meta-box -->

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
