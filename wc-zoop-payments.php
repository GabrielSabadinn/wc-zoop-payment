<?php
/*
Plugin Name: WooCommerce Zoop Payment Gateway
Description: Custom payment gateways for Zoop (Credit Card, PIX, Recurrence)
Version: 1.0.0
Author: Gabriel Sabadin
Text Domain: wc-zoop-payments
*/

if (!defined('ABSPATH')) {
    error_log('WC Zoop Payment: ABSPATH not defined, exiting');
    exit;
}

add_action('plugins_loaded', 'wc_zoop_payment_init');
function wc_zoop_payment_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        error_log('WC Zoop Payment: WooCommerce not detected');
        return;
    }

    error_log('WC Zoop Payment: Initializing plugin');

    // Include gateway classes
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-zoop-credit-card.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-zoop-pix.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-zoop-recurrence.php';

    add_filter('woocommerce_payment_gateways', 'wc_zoop_add_gateways');
    function wc_zoop_add_gateways($gateways) {
        error_log('WC Zoop Payment: Adding gateways');
        $gateways[] = 'WC_Gateway_Zoop_Credit_Card';
        $gateways[] = 'WC_Gateway_Zoop_PIX';
        $gateways[] = 'WC_Gateway_Zoop_Recurrence';
        error_log('WC Zoop Payment: Gateways added: ' . print_r($gateways, true));
        return $gateways;
    }
}
?>