<?php
/**
 * Regions - named country groups (EU, EEA, Nordics ...) used by the product
 * country selector presets and by the content shortcodes.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Regions {

	/**
	 * Returns all region groups, keyed by upper-case token.
	 *
	 * @return array<string, array{label: string, countries: string[]}>
	 */
	public static function get_groups(): array {
		$eu = array( 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' );

		$groups = array(
			'EU'            => array(
				'label'     => __( 'European Union', 'windcodex-geoblock' ),
				'countries' => $eu,
			),
			'EEA'           => array(
				'label'     => __( 'EEA', 'windcodex-geoblock' ),
				'countries' => array_merge( $eu, array( 'IS', 'LI', 'NO' ) ),
			),
			'NORTH_AMERICA' => array(
				'label'     => __( 'North America', 'windcodex-geoblock' ),
				'countries' => array( 'US', 'CA', 'MX' ),
			),
			'NORDICS'       => array(
				'label'     => __( 'Nordics', 'windcodex-geoblock' ),
				'countries' => array( 'DK', 'FI', 'IS', 'NO', 'SE' ),
			),
			'GCC'           => array(
				'label'     => __( 'GCC', 'windcodex-geoblock' ),
				'countries' => array( 'BH', 'KW', 'OM', 'QA', 'SA', 'AE' ),
			),
			'ASEAN'         => array(
				'label'     => __( 'ASEAN', 'windcodex-geoblock' ),
				'countries' => array( 'BN', 'KH', 'ID', 'LA', 'MY', 'MM', 'PH', 'SG', 'TH', 'VN' ),
			),
			'ANZ'           => array(
				'label'     => __( 'Australia & NZ', 'windcodex-geoblock' ),
				'countries' => array( 'AU', 'NZ' ),
			),
		);

		/**
		 * Filter the region groups available in GeoBlock.
		 *
		 * @param array $groups Region groups keyed by upper-case token.
		 */
		$groups = (array) apply_filters( 'geoblock_region_groups', $groups );

		return $groups;
	}

	/**
	 * Expands a list of country codes and/or region tokens into unique,
	 * upper-case 2-letter country codes.
	 *
	 * @param  string|string[] $tokens Comma-separated string or array, e.g. "EU, US".
	 * @return string[]
	 */
	public static function expand( $tokens ): array {
		if ( is_string( $tokens ) ) {
			$tokens = explode( ',', $tokens );
		}

		$groups = self::get_groups();
		$out    = array();

		foreach ( (array) $tokens as $token ) {
			$token = strtoupper( trim( (string) $token ) );
			if ( '' === $token ) {
				continue;
			}
			if ( isset( $groups[ $token ] ) ) {
				$out = array_merge( $out, $groups[ $token ]['countries'] );
			} elseif ( preg_match( '/^[A-Z]{2}$/', $token ) ) {
				$out[] = $token;
			}
		}

		return array_values( array_unique( $out ) );
	}
}
