<?php
/**
 * Register a custom menu page.
 */
function woocommerce_csvupdate_menu() {
    add_menu_page(
        __('CSV Update', 'woocommerce-csvupdate'),
        __('CSV Update', 'woocommerce-csvupdate'),
        'manage_options',
        'woocommerce-csvupdate/woocommerce-csvupdate-do.php'
    );
}

add_action( 'admin_menu', 'woocommerce_csvupdate_menu' );
