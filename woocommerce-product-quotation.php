<?php

/*
Plugin Name: Woocommerce Product Quotation
Plugin URI: http://whisperand.co/
Description: Request for quotation for all products which is included in the cart.
Version: 1.0.0
Author: Manny
Author URI: http://whisperand.co/
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( is_admin() ) {
	require_once 'admin-quotation-settings.php';
}


function quotation_button_css() {
	wp_enqueue_style( 'quotation-button-css', plugin_dir_url( __FILE__ ) . 'css/quotation-button-front-end.css' );
}
add_action( 'wp_enqueue_scripts', 'quotation_button_css' ); 


$is_button_enable = get_option( 'product_quotation_button' );
if ( isset ( $is_button_enable ) && $is_button_enable == 'yes' ) {
	require_once 'class-quotation-email.php';
}