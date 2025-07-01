<?php
/*
Plugin Name: WooCommerce Gabriel-Sabadin Gateway
Description: Custom payment gateways for Gabriel-Sabadin (Credit Card, PIX, Recurrence)
Version: 1.0.0
Author: Gabriel Sabadin
Text Domain: Gabriel-Sabadin
*/

if (!defined('ABSPATH')) {
    error_log('WC Gabriel-Sabadin: ABSPATH not defined, exiting');
    exit;
}

add_action('plugins_loaded', 'wc_zoop_payment_init');
function wc_zoop_payment_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        error_log('WC Gabriel-Sabadin: WooCommerce not detected');
        return;
    }

    error_log('WC Gabriel-Sabadin: Initializing plugin');

    // Include gateway classes
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-zoop-credit-card.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-zoop-pix.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-zoop-recurrence.php';

    add_filter('woocommerce_payment_gateways', 'wc_zoop_add_gateways');
    function wc_zoop_add_gateways($gateways) {
        error_log('WC Gabriel-Sabadin: Adding gateways');
        $gateways[] = 'WC_Gateway_Zoop_Credit_Card';
        $gateways[] = 'WC_Gateway_Zoop_PIX';
        $gateways[] = 'WC_Gateway_Zoop_Recurrence';
        error_log('WC Gabriel-Sabadin: Gateways added: ' . print_r($gateways, true));
        return $gateways;
    }

    // Add custom settings tab for Gabriel-Sabadin
    add_filter('woocommerce_settings_tabs_array', 'wc_zoop_add_settings_tab', 50);
    function wc_zoop_add_settings_tab($tabs) {
        $tabs['Gabriel-Sabadin_settings'] = __('Gabriel-Sabadin Settings', 'wc-zoop-payments');
        error_log('WC Gabriel-Sabadin: Added Gabriel-Sabadin Settings tab');
        return $tabs;
    }

    // Render settings page
    add_action('woocommerce_settings_Gabriel-Sabadin_settings', 'wc_zoop_render_settings_page');
    function wc_zoop_render_settings_page() {
        error_log('WC Gabriel-Sabadin: Starting to render Gabriel-Sabadin Settings page');
        if (!current_user_can('manage_options')) {
            error_log('WC Gabriel-Sabadin: User lacks manage_options capability');
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-zoop-payments'));
        }
        ?>
        <div class="wrap">
            <h2><?php _e('Gabriel-Sabadin Global Settings', 'wc-zoop-payments'); ?></h2>
            <?php settings_errors('wc_zoop_settings_group'); ?>
            <form method="post" action="options.php" id="wc_zoop_settings_form">
                <?php
                settings_fields('wc_zoop_settings_group');
                do_settings_sections('wc_zoop_settings');
                submit_button(__('Salvar Mudanças', 'wc-zoop-payments'));
                error_log('WC Gabriel-Sabadin: Rendered settings form');
                ?>
            </form>
        </div>
        <?php
        error_log('WC Gabriel-Sabadin: Gabriel-Sabadin Settings page fully rendered');
    }

    // Register settings
    add_action('admin_init', 'wc_zoop_register_settings');
    function wc_zoop_register_settings() {
        error_log('WC Gabriel-Sabadin: Registering settings');
        add_settings_section(
            'wc_zoop_global_settings',
            __('Configuração global', 'wc-zoop-payments'),
            function() {
                echo '<p>' . __('Configure as definições globais para o Gabriel-Sabadin Payment Gateway.', 'wc-zoop-payments') . '</p>';
                error_log('WC Gabriel-Sabadin: Settings section callback executed');
            },
            'wc_zoop_settings'
        );

        add_settings_field(
            'wc_zoop_seller_id',
            __('Seller ID', 'wc-zoop-payments'),
            'wc_zoop_seller_id_callback',
            'wc_zoop_settings',
            'wc_zoop_global_settings',
            ['label_for' => 'wc_zoop_seller_id']
        );

        register_setting('wc_zoop_settings_group', 'wc_zoop_seller_id', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        error_log('WC Gabriel-Sabadin: Seller ID setting registered');
    }

    function wc_zoop_seller_id_callback() {
        $seller_id = get_option('wc_zoop_seller_id', '');
        error_log('WC Gabriel-Sabadin: Rendering seller_id field, current value: ' . $seller_id);
        ?>
        <input type="text" id="wc_zoop_seller_id" name="wc_zoop_seller_id" value="<?php echo esc_attr($seller_id); ?>" class="regular-text" />
        <p class="description"><?php _e('Insira o ID do vendedor fornecido pela Gabriel-Sabadin para solicitações de API.', 'wc-zoop-payments'); ?></p>
        <?php
        error_log('WC Gabriel-Sabadin: Seller ID field rendered');
    }

    // Add settings link on plugins page
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_zoop_add_settings_link');
    function wc_zoop_add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=Gabriel-Sabadin_settings') . '">' . __('Settings', 'wc-zoop-payments') . '</a>';
        array_unshift($links, $settings_link);
        error_log('WC Gabriel-Sabadin: Settings link added to plugins page');
        return $links;
    }

    // Log when settings are saved
    add_action('update_option_wc_zoop_seller_id', function($old_value, $new_value) {
        error_log('WC Gabriel-Sabadin: Seller ID updated from "' . $old_value . '" to "' . $new_value . '"');
        add_settings_error(
            'wc_zoop_settings_group',
            'settings_updated',
            __('Settings saved successfully.', 'wc-zoop-payments'),
            'updated'
        );
    }, 10, 2);

    // Debug form submission
    add_action('admin_init', function() {
        if (isset($_POST['option_page']) && $_POST['option_page'] === 'wc_zoop_settings_group') {
            error_log('WC Gabriel-Sabadin: Form submitted with POST data: ' . print_r($_POST, true));
            if (!isset($_POST['_wpnonce'])) {
                error_log('WC Gabriel-Sabadin: Nonce not provided in form submission');
                add_settings_error(
                    'wc_zoop_settings_group',
                    'nonce_missing',
                    __('Nonce not provided. Please try again.', 'wc-zoop-payments'),
                    'error'
                );
            } elseif (!wp_verify_nonce($_POST['_wpnonce'], 'wc_zoop_settings_group-options')) {
                error_log('WC Gabriel-Sabadin: Nonce verification failed');
                add_settings_error(
                    'wc_zoop_settings_group',
                    'nonce_failed',
                    __('Nonce verification failed. Please try again.', 'wc-zoop-payments'),
                    'error'
                );
            } else {
                error_log('WC Gabriel-Sabadin: Nonce verified successfully');
                // Ensure the option is updated
                if (isset($_POST['wc_zoop_seller_id'])) {
                    $new_value = sanitize_text_field($_POST['wc_zoop_seller_id']);
                    update_option('wc_zoop_seller_id', $new_value);
                    error_log('WC Gabriel-Sabadin: Manually triggered update for wc_zoop_seller_id to ' . $new_value);
                }
            }
        }
    });

    // Disable WooCommerce AJAX and ensure form visibility
    add_action('admin_head', function() {
        if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'Gabriel-Sabadin_settings') {
            ?>
            <style>
                /* Hide WooCommerce default form and buttons */
                .woocommerce-settings__content > form:not(#wc_zoop_settings_form), .woocommerce-save-button {
                    display: none !important;
                }
                /* Ensure our form is visible */
                #wc_zoop_settings_form {
                    display: block !important;
                    margin-top: 20px;
                }
                /* Basic styling for consistency */
                #wc_zoop_settings_form .form-table {
                    margin-bottom: 20px;
                }
            </style>
            <script>
                jQuery(document).ready(function($) {
                    // Prevent WooCommerce AJAX from interfering
                    $(document).off('submit', 'form:not(#wc_zoop_settings_form)');
                    console.log('WC Gabriel-Sabadin: Disabled WooCommerce default form AJAX for Gabriel-Sabadin tab');
                    // Log form submission and ensure POST method
                    $('#wc_zoop_settings_form').on('submit', function(e) {
                        console.log('WC Gabriel-Sabadin: Submitting Gabriel-Sabadin Settings form');
                        $(this).attr('method', 'post');
                        $(this).attr('action', '<?php echo admin_url('options.php'); ?>');
                    });
                });
            </script>
            <?php
            error_log('WC Gabriel-Sabadin: Hid default WooCommerce settings form and disabled AJAX for Gabriel-Sabadin tab');
        }
    });
}
?>