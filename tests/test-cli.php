<?php
/**
 * Tests for the WP-CLI commands.
 *
 * @package Woocommerce_Order_Tables
 * @author  Liquid Web
 */

class CLITest extends TestCase {

	/**
	 * Holds a fresh instance of the WP-CLI command class.
	 *
	 * @var WC_Custom_Order_Table_CLI
	 */
	protected $cli;

	/**
	 * @before
	 */
	public function init() {
		$this->cli = new WC_Custom_Order_Table_CLI();
	}

	public function test_count() {
		$this->toggle_use_custom_table( false );
		$this->generate_orders( 3 );
		$this->toggle_use_custom_table( true );

		$this->assertEquals( 3, $this->cli->count() );
	}

	public function test_migrate() {
		$this->markTestIncomplete();

		$this->toggle_use_custom_table( false );
		$order_ids = $this->generate_orders( 5 );
		$this->toggle_use_custom_table( true );

		$this->assertEquals(
			0,
			$this->count_orders_in_table_with_ids( $order_ids ),
			'Before migration, these orders should not exist in the orders table.'
		);

		// @todo The migration

		$this->assertEquals(
			5,
			$this->count_orders_in_table_with_ids( $order_ids ),
			'Expected to see 5 orders in the custom table.'
		);
	}

	public function test_backfill() {
		$this->markTestIncomplete();
	}

	/**
	 * Generate a $number of orders and return the order IDs in an array.
	 *
	 * @param int $number The number of orders to generate.
	 *
	 * @return array An array of the generated order IDs.
	 */
	protected function generate_orders( $number = 5 ) {
		$orders = array();

		for ( $i = 0; $i < $number; $i++ ) {
			$orders[] = WC_Helper_Order::create_order()->get_id();
		}

		return $orders;
	}
}