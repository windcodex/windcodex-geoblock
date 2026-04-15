<?php
/**
 * Product helper — thin wrapper used by the Admin class
 * to retrieve and display product-level restriction data.
 *
 * @package GeoBlock_Country_Restrictions
 */

defined( 'ABSPATH' ) || exit;

class GeoBlock_Product {

	/**
	 * Returns the restriction rules for a product.
	 * Delegates to GeoBlock_Restrictions but is kept here
	 * so the Admin class has a single dependency.
	 *
	 * @param  int $product_id
	 * @return array
	 */
	public function get_rules( int $product_id ): array {
		// Reuse the authoritative getter from the restrictions engine.
		$restrictions = new GeoBlock_Restrictions( new GeoBlock_Geolocation() );
		return $restrictions->get_product_rules( $product_id );
	}
}
