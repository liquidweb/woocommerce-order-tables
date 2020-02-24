<?php
/**
 * WooCommerce order refund data store.
 *
 * @package WooCommerce_Custom_Orders_Table
 * @author  Liquid Web
 */

use LiquidWeb\WooCommerceCustomOrdersTable\Concerns\UsesCustomTable;
use LiquidWeb\WooCommerceCustomOrdersTable\Contracts\CustomTableDataStore;

/**
 * Extend the WC_Order_Refund_Data_Store_CPT class, overloading methods that require database access in
 * order to use the new table.
 *
 * This operates in a way similar to WC_Order_Data_Store_Custom_Table, but is for *refunds*.
 */
class WC_Order_Refund_Data_Store_Custom_Table extends WC_Order_Refund_Data_Store_CPT implements CustomTableDataStore {
	use UsesCustomTable;

	/**
	 * Retrieve the name of the custom table for this data store.
	 *
	 * @global $wpdb
	 *
	 * @return string The custom table used by this data store.
	 */
	public static function get_custom_table_name() {
		global $wpdb;

		/**
		 * Filter the WooCommerce orders table name.
		 *
		 * @param string $table The WooCommerce orders table name.
		 */
		return apply_filters( 'wc_custom_refunds_table_name', "{$wpdb->prefix}woocommerce_refunds" );
	}

	/**
	 * Retrieve the column name that serves as the primary key in the custom table.
	 *
	 * @return string The primary key column name.
	 */
	public static function get_custom_table_primary_key() {
		return 'refund_id';
	}

	/**
	 * Retrieve a mapping of database columns to default WooCommerce post-meta keys.
	 *
	 * @return array
	 */
	public static function map_columns_to_post_meta_keys() {
		return [
			'discount_total'     => '_cart_discount',
			'discount_tax'       => '_cart_discount_tax',
			'shipping_total'     => '_order_shipping',
			'shipping_tax'       => '_order_shipping_tax',
			'cart_tax'           => '_order_tax',
			'total'              => '_order_total',

			'version'            => '_order_version',
			'currency'           => '_order_currency',
			'prices_include_tax' => '_prices_include_tax',

			'amount'             => '_refund_amount',
			'reason'             => '_refund_reason',
			'refunded_by'        => '_refunded_by',
			'refunded_payment'   => '_refunded_payment',
		];
	}

	/**
	 * Delete a refund from the database.
	 *
	 * @param WC_Order $refund The refund object, passed by reference.
	 * @param array    $args  Additional arguments to pass to the delete method.
	 */
	public function delete( &$refund, $args = array() ) {
		add_action( 'woocommerce_delete_order_refund', [ $this, 'delete_row' ] );

		parent::delete( $refund, $args );
	}

	/**
	 * Helper method that updates all the post meta for a refund based on it's settings in the
	 * WC_Order_Refund class.
	 *
	 * @global $wpdb
	 *
	 * @param WC_Order_Refund $refund The refund to be updated.
	 */
	protected function update_post_meta( &$refund ) {
		global $wpdb;

		$table       = self::get_custom_table_name();
		$refund_data = array(
			'refund_id'          => $refund->get_id(),
			'discount_total'     => $refund->get_discount_total( 'edit' ),
			'discount_tax'       => $refund->get_discount_tax( 'edit' ),
			'shipping_total'     => $refund->get_shipping_total( 'edit' ),
			'shipping_tax'       => $refund->get_shipping_tax( 'edit' ),
			'cart_tax'           => $refund->get_cart_tax( 'edit' ),
			'total'              => $refund->get_total( 'edit' ),
			'version'            => $refund->get_version( 'edit' ),
			'currency'           => $refund->get_currency( 'edit' ),
			'prices_include_tax' => wc_bool_to_string( $refund->get_prices_include_tax( 'edit' ) ),
			'amount'             => $refund->get_amount( 'edit' ),
			'reason'             => $refund->get_reason( 'edit' ),
			'refunded_by'        => $refund->get_refunded_by( 'edit' ),
		);

		// Insert or update the database record.
		if ( ! $this->row_exists( $refund_data['refund_id'] ) ) {
			$inserted = $wpdb->insert( $table, $refund_data ); // WPCS: DB call OK.

			if ( 1 !== $inserted ) {
				return;
			}
		} else {
			$refund_data = array_intersect_key( $refund_data, $refund->get_changes() );

			// There's nothing to update.
			if ( empty( $refund_data ) ) {
				return;
			}

			$wpdb->update(
				$table,
				$refund_data,
				array( 'refund_id' => (int) $refund->get_id() )
			);
		}

		do_action( 'woocommerce_order_refund_object_updated_props', $refund, $refund_data );
	}
}
