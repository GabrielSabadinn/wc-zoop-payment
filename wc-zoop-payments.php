<?php
/*
Plugin Name: WooCommerce Zoop Payment Gateway
Description: Custom payment gateways for Zoop (Credit Card, PIX, Recurrence)
Version: 1.0.0
Author: Gabriel Sabadin
Text Domain: wc-zoop-payments
*/

defined('ABSPATH') || exit;

add_action('plugins_loaded', 'wc_zoop_payment_init', 11);

function wc_zoop_payment_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        error_log('WC Zoop: WooCommerce não carregado');
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__('O plugin WooCommerce Zoop Payment Gateway requer o WooCommerce para funcionar!', 'wc-zoop-payments') . '</p></div>';
        });
        return;
    }

    error_log('WC Zoop: Iniciando carregamento dos gateways');
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-zoop-recurrence.php';

    add_filter('woocommerce_payment_gateways', function($gateways) {
        $gateways[] = 'WC_Gateway_Zoop_Recurrence';
        error_log('WC Zoop: Gateways registrados: ' . print_r($gateways, true));
        return $gateways;
    });

    // Debug checkout process
    add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
        error_log('WC Zoop: Após woocommerce_checkout_order_processed para pedido #' . $order_id);
        error_log('WC Zoop: Método de pagamento: ' . $order->get_payment_method());
        error_log('WC Zoop: Dados POST no checkout: ' . print_r($posted_data, true));
    }, 10, 3);

    add_action('woocommerce_checkout_process_payment', function($order, $payment_result) {
        error_log('WC Zoop: Início do processamento de pagamento para pedido #' . $order->get_id());
        error_log('WC Zoop: Resultado do pagamento: ' . print_r($payment_result, true));
    }, 10, 2);
}
?>