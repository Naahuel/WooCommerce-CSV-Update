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
