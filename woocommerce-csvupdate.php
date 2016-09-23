<?php
/*
	Plugin Name:            WooCommerce CSV Update
	Plugin URI:             https://github.com/Naahuel/WooCommerce-Batch-Image-Import
	Description:            Update products in the store from an CSV file

	Author:					Nahuel José
	Author URI:			http://www.nahueljose.com.ar/

	Version:		 0.0.1
	Text Domain: woocommerce-csvupdate
	Domain Path: /languages

	License: GPLv2 or later
*/
require_once 'admin/admin.php';
load_plugin_textdomain('woocommerce-csvupdate', false, dirname(plugin_basename(__FILE__)) . '/languages/');

function wcsvu_change_price_by_type( $product_id, $the_price, $price_type ) {
    update_post_meta( $product_id, '_' . $price_type, $the_price );
}

function wcsvu_change_price_all_types( $product_id, $the_price ) {
    wcsvu_change_price_by_type( $product_id, $the_price, 'price' );
    wcsvu_change_price_by_type( $product_id, $the_price, 'sale_price' );
    wcsvu_change_price_by_type( $product_id, $the_price, 'regular_price' );
}

/*
 * `wcsvu_change_product_price` is main function you should call to change product's price
 */
function wcsvu_change_product_price( $product_id, $the_price ) {
    wcsvu_change_price_all_types( $product_id, $the_price );
    $product = wc_get_product( $product_id ); // Handling variable products
    if ( $product->is_type( 'variable' ) ) {
        $variations = $product->get_available_variations();
        foreach ( $variations as $variation ) {
            wcsvu_change_price_all_types( $variation['variation_id'], $the_price );
        }
    }
}
